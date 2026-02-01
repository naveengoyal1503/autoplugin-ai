/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Easily add donation buttons, progress bars, and payment forms to monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_shortcode('smart_donation_progress', array($this, 'progress_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('smart_donations_paypal_email')) {
            add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Donations Pro', 'manage_options', 'smart-donations-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_donations_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('smart_donations_goal_amount', floatval($_POST['goal_amount']));
            update_option('smart_donations_current_amount', floatval($_POST['current_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('smart_donations_paypal_email', '');
        $goal = get_option('smart_donations_goal_amount', 1000);
        $current = get_option('smart_donations_current_amount', 0);
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($goal); ?>" step="0.01" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="current_amount" value="<?php echo esc_attr($current); ?>" step="0.01" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcodes: <code>[smart_donation]</code> or <code>[smart_donation_progress]</code></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => '10', 'label' => 'Donate Now'), $atts);
        $paypal_email = get_option('smart_donations_paypal_email');
        if (!$paypal_email) return '<p>Please configure PayPal email in settings.</p>';

        ob_start();
        ?>
        <div class="smart-donation" style="text-align:center; margin:20px 0;">
            <h3><?php echo esc_html($atts['label']); ?></h3>
            <form action="https://www.paypal.com/donate" method="post" target="_top">
                <input type="hidden" name="hosted_button_id" value="YOUR_BUTTON_ID" />
                <input type="hidden" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" />
                <input type="hidden" name="currency_code" value="USD" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
                <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" />
            </form>
            <p>Support us with any amount!</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.smart-donation form').on('submit', function() {
                $(this).find('input[name="amount"]').remove();
                var amount = prompt('Enter donation amount ($):', '<?php echo esc_js($atts['amount']); ?>');
                if (amount && !isNaN(amount) && amount > 0) {
                    $('<input>').attr({type: 'hidden', name: 'amount', value: amount}).appendTo(this);
                } else {
                    return false;
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function progress_shortcode($atts) {
        $atts = shortcode_atts(array('goal' => ''), $atts);
        $goal = $atts['goal'] ?: get_option('smart_donations_goal_amount', 1000);
        $current = get_option('smart_donations_current_amount', 0);
        $percent = min(100, ($current / $goal) * 100);

        ob_start();
        ?>
        <div class="smart-progress" style="margin:20px 0;">
            <p><strong>$<?php echo number_format($current, 2); ?></strong> raised of $<strong><?php echo number_format($goal, 2); ?></strong> goal (<strong><?php echo round($percent); ?>%</strong>)</p>
            <div style="background:#f0f0f0; height:30px; border-radius:15px; overflow:hidden;">
                <div style="background:#4CAF50; height:100%; width:<?php echo $percent; ?>%; transition:width 0.5s;"></div>
            </div>
            <?php echo $this->donation_shortcode(array('amount' => '5', 'label' => 'Contribute Now')); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function add_defer_attribute($tag, $handle) {
        return str_replace(' src', ' defer src', $tag);
    }

    public function activate() {
        add_option('smart_donations_current_amount', 0);
    }
}

new SmartDonationsPro();

// Premium teaser
add_action('admin_notices', function() {
    if (!get_option('smart_donations_paypal_email') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Smart Donations Pro:</strong> Configure your PayPal email in <a href="' . admin_url('options-general.php?page=smart-donations-pro') . '">Settings &gt; Donations Pro</a>. <em>Upgrade to Pro for recurring donations, Stripe, and analytics!</em></p></div>';
    }
});