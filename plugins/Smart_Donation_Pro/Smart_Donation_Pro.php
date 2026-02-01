/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons and progress bars to monetize your WordPress site with PayPal.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email') === false) {
            update_option('sdp_paypal_email', get_option('admin_email'));
        }
        if (get_option('sdp_donation_amount') === false) {
            update_option('sdp_donation_amount', '5,10,20,50');
        }
        if (get_option('sdp_button_text') === false) {
            update_option('sdp_button_text', 'Donate Now');
        }
        if (get_option('sdp_goal_amount') === false) {
            update_option('sdp_goal_amount', '1000');
        }
        if (get_option('sdp_current_amount') === false) {
            update_option('sdp_current_amount', '0');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro Settings', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_save'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_donation_amount', sanitize_text_field($_POST['donation_amount']));
            update_option('sdp_button_text', sanitize_text_field($_POST['button_text']));
            update_option('sdp_goal_amount', floatval($_POST['goal_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email');
        $amounts = get_option('sdp_donation_amount');
        $button_text = get_option('sdp_button_text');
        $goal = get_option('sdp_goal_amount');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Donation Amounts (comma-separated)</th>
                        <td><input type="text" name="donation_amount" value="<?php echo esc_attr($amounts); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="button_text" value="<?php echo esc_attr($button_text); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($goal); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'sdp_save'); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation]</code></p>
            <p><strong>Upgrade to Pro</strong> for recurring donations, analytics, and more! <a href="#">Learn More</a></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_goal' => 'true'
        ), $atts);

        $amounts = explode(',', get_option('sdp_donation_amount'));
        $amount_options = '';
        foreach ($amounts as $amt) {
            $amount_options .= '<option value="' . floatval(trim($amt)) . '">' . trim($amt) . '</option>';
        }

        $goal = get_option('sdp_goal_amount');
        $current = get_option('sdp_current_amount');
        $progress = ($current / $goal) * 100;

        ob_start();
        ?>
        <div id="sdp-container" style="max-width: 400px; margin: 20px 0;">
            <div id="sdp-progress" style="<?php echo $atts['show_goal'] === 'true' ? '' : 'display:none;'; ?>">
                <div style="background: #f0f0f0; border-radius: 10px; padding: 3px;">
                    <div style="background: #4CAF50; height: 20px; border-radius: 7px; width: <?php echo min($progress, 100); ?>%; transition: width 0.3s;"></div>
                </div>
                <p style="text-align: center; margin: 10px 0 0 0;">$<?php echo number_format($current, 0); ?> / $<?php echo number_format($goal, 0); ?> raised</p>
            </div>
            <select id="sdp-amount" style="width: 100%; padding: 10px; margin: 10px 0;">
                <?php echo $amount_options; ?>
                <option value="custom">Custom Amount</option>
            </select>
            <input type="number" id="sdp-custom-amount" placeholder="Enter amount" style="display: none; width: 100%; padding: 10px; margin: 10px 0;" />
            <button id="sdp-donate-btn" class="button button-primary" style="width: 100%; padding: 12px; font-size: 16px; background: #0073aa; color: white; border: none; border-radius: 5px; cursor: pointer;"><?php echo esc_html(get_option('sdp_button_text')); ?></button>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#sdp-amount').change(function() {
                if ($(this).val() === 'custom') {
                    $('#sdp-custom-amount').show();
                } else {
                    $('#sdp-custom-amount').hide();
                }
            });
            $('#sdp-donate-btn').click(function() {
                var amount = $('#sdp-amount').val() !== 'custom' ? $('#sdp-amount').val() : $('#sdp-custom-amount').val();
                if (amount > 0) {
                    window.open('https://www.paypal.com/donate/?business=<?php echo urlencode(get_option('sdp_paypal_email')); ?>&amount=' + amount + '&item_name=Donation', '_blank');
                    $.post(sdp_ajax.ajax_url, {
                        action: 'sdp_donate',
                        amount: amount,
                        nonce: sdp_ajax.nonce
                    });
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdp_current_amount');
        update_option('sdp_current_amount', $current + $amount);
        wp_die('success');
    }
}

new SmartDonationPro();

// Inline styles and scripts for self-contained plugin
function sdp_add_inline_styles() {
    ?>
    <style>
    #sdp-container { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    #sdp-donate-btn:hover { background: #005a87 !important; }
    </style>
    <?php
}
add_action('wp_head', 'sdp_add_inline_styles');

// Note: For production, enqueue external JS/CSS files. This is single-file version with inline for simplicity.
// Pro version would include analytics, recurring PayPal, custom themes, etc.