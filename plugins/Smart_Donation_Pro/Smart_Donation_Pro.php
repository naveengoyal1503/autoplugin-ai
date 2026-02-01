/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site monetization with easy PayPal donation buttons, goal trackers, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
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
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'goal' => '1000',
            'title' => 'Support Us!',
            'button_text' => 'Donate Now',
            'currency' => 'USD'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email', '');
        if (empty($paypal_email)) {
            return '<p>Please set up PayPal email in plugin settings.</p>';
        }

        $current = get_option('sdp_total_donations', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p><strong><?php echo $currency_symbol = $atts['currency'] === 'USD' ? '$' : '€'; echo number_format($current, 2); ?></strong> / <?php echo $currency_symbol . $atts['goal']; ?> raised</p>
            <form class="sdp-donate-form" method="post" action="https://www.paypal.com/donate">
                <input type="hidden" name="hosted_button_id" value="<?php echo get_option('sdp_button_id', 'YOUR_BUTTON_ID'); ?>">
                <input type="hidden" name="amount" value="<?php echo $atts['amount']; ?>">
                <input type="hidden" name="currency_code" value="<?php echo $atts['currency']; ?>">
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $current = floatval(get_option('sdp_total_donations', 0));
        $donation = floatval($_POST['amount'] ?? 0);
        update_option('sdp_total_donations', $current + $donation);
        wp_send_json_success('Thank you for your donation!');
    }

    public function activate() {
        add_option('sdp_total_donations', 0);
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_admin_page');
    });

    function sdp_admin_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            update_option('sdp_button_id', sanitize_text_field($_POST['sdp_button_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>PayPal Button ID</th>
                        <td><input type="text" name="sdp_button_id" value="<?php echo esc_attr(get_option('sdp_button_id')); ?>" class="regular-text"><br><small>Create at <a href="https://www.paypal.com/buttons" target="_blank">PayPal Buttons</a></small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Usage: <code>[smart_donation amount="20" goal="5000" title="Help Our Project"]</code></p>
        </div>
        <?php
    }
}

SmartDonationPro::get_instance();

// Inline styles and scripts for single file
add_action('wp_head', function() {
    echo '<style>
    .sdp-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
    .sdp-progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdp-progress { height: 100%; background: #007cba; transition: width 0.3s; }
    .sdp-button { background: #007cba; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
    .sdp-button:hover { background: #005a87; }
    </style>';
});

add_action('wp_footer', function() {
    echo '<script>
    jQuery(document).ready(function($) {
        $(".sdp-donate-form").on("submit", function(e) {
            // Optional: Track donation attempt
            $.post(sdp_ajax.ajaxurl, {
                action: "sdp_donate",
                nonce: sdp_ajax.nonce,
                amount: $("input[name=\'amount\']").val()
            });
        });
    });
    </script>';
});