/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with easy donation buttons, tiers, and analytics.
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
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
        add_shortcode('sdp_donate', array($this, 'donate_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5',
            'button_text' => 'Donate Now',
            'tiers' => '5,10,25,50,100'
        ), $atts);

        $tiers = explode(',', $atts['tiers']);
        $paypal_email = get_option('sdp_paypal_email', '');

        if (empty($paypal_email)) {
            return '<p>Please set up PayPal email in plugin settings.</p>';
        }

        ob_start();
        ?>
        <div class="sdp-container">
            <h3>Support This Site</h3>
            <div class="sdp-tiers">
                <?php foreach ($tiers as $tier): $tier = trim($tier); ?>
                    <button class="sdp-tier-btn" data-amount="<?php echo esc_attr($tier); ?>"><?php echo esc_html($tier); ?>$</button>
                <?php endforeach; ?>
            </div>
            <div class="sdp-amount-input">
                <input type="number" id="sdp-amount" placeholder="Custom amount" min="1" step="1">
            </div>
            <button id="sdp-donate-btn" class="sdp-donate-btn" data-email="<?php echo esc_attr($paypal_email); ?>"><?php echo esc_html($atts['button_text']); ?></button>
            <div id="sdp-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $email = sanitize_email($_POST['email']);

        if ($amount < 1) {
            wp_send_json_error('Minimum donation is $1');
        }

        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=' . urlencode($email) . '&lc=US&item_name=Donation to ' . get_bloginfo('name') . '&amount=' . $amount . '&currency_code=USD&no_note=0&cn=&no_shipping=1&rm=POST&return=' . urlencode(home_url('/thank-you/'));

        // Log donation
        $log = get_option('sdp_logs', array());
        $log[] = array('amount' => $amount, 'time' => current_time('mysql'));
        update_option('sdp_logs', array_slice($log, -50)); // Keep last 50

        wp_send_json_success(array('url' => $paypal_url));
    }

    public function activate() {
        add_option('sdp_paypal_email', '');
        add_option('sdp_logs', array());
    }
}

new SmartDonationPro();

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
    });
}

function sdp_settings_page() {
    if (isset($_POST['sdp_paypal_email'])) {
        update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $email = get_option('sdp_paypal_email', '');
    $logs = get_option('sdp_logs', array());
    ?>
    <div class="wrap">
        <h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($email); ?>" class="regular-text"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Recent Donations</h2>
        <ul>
            <?php foreach (array_reverse($logs) as $log): ?>
                <li><?php echo esc_html($log['amount']); ?>$ - <?php echo esc_html($log['time']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

// Inline JS and CSS for simplicity (self-contained)
function sdp_inline_assets() {
    if (has_shortcode(get_post()->post_content ?? '', 'sdp_donate')) {
        ?>
        <script>jQuery(document).ready(function($) {
            $('.sdp-tier-btn').click(function() {
                $('#sdp-amount').val($(this).data('amount'));
            });
            $('#sdp-donate-btn').click(function(e) {
                e.preventDefault();
                var amount = parseFloat($('#sdp-amount').val()) || 5;
                var email = $(this).data('email');
                $.post(sdp_ajax.ajax_url, {
                    action: 'sdp_process_donation',
                    amount: amount,
                    email: email,
                    nonce: sdp_ajax.nonce
                }, function(res) {
                    if (res.success) {
                        window.location = res.data.url;
                    } else {
                        $('#sdp-message').html('<p style="color:red;">' + res.data + '</p>');
                    }
                });
            });
        });</script>
        <style>
            .sdp-container { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; max-width: 300px; margin: 20px auto; }
            .sdp-tiers { margin: 10px 0; }
            .sdp-tier-btn { margin: 5px; padding: 10px 15px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
            .sdp-tier-btn:hover { background: #005a87; }
            .sdp-amount-input input { padding: 10px; width: 80%; margin: 10px 0; }
            .sdp-donate-btn { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; width: 100%; }
            .sdp-donate-btn:hover { background: #218838; }
        </style>
        <?php
    }
}
add_action('wp_footer', 'sdp_inline_assets');