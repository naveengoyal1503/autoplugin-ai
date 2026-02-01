/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Add customizable donation buttons and progress bars to monetize your WordPress site easily.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        if (get_option('sdp_paypal_email')) {
            // PayPal integration ready
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_save'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_donation_goal', intval($_POST['donation_goal']));
            update_option('sdp_button_text', sanitize_text_field($_POST['button_text']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        $goal = get_option('sdp_donation_goal', 1000);
        $button_text = get_option('sdp_button_text', 'Donate Now');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Donation Goal ($)</th>
                        <td><input type="number" name="donation_goal" value="<?php echo esc_attr($goal); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="button_text" value="<?php echo esc_attr($button_text); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="sdp_save" class="button-primary" value="Save Settings" /></p>
            </form>
            <p>Use shortcode: <code>[smart_donation]</code></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => get_option('sdp_donation_goal', 1000),
            'paypal' => get_option('sdp_paypal_email', ''),
            'text' => get_option('sdp_button_text', 'Donate Now')
        ), $atts);

        $current = get_option('sdp_total_donations', 0);
        $progress = ($current / $atts['goal']) * 100;

        ob_start();
        ?>
        <div class="sdp-container">
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo min(100, $progress); ?>%;"></div>
            </div>
            <p><?php echo number_format($current); ?> / <?php echo number_format($atts['goal']); ?> raised</p>
            <form class="sdp-donate-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="YOUR_BUTTON_ID">
                <input type="hidden" name="business" value="<?php echo esc_attr($atts['paypal']); ?>">
                <input type="hidden" name="item_name" value="Donation">
                <input type="hidden" name="currency_code" value="USD">
                <input type="submit" value="<?php echo esc_attr($atts['text']); ?>" class="sdp-button" />
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdp_total_donations', 0);
        update_option('sdp_total_donations', $current + $amount);
        wp_send_json_success('Thank you for your donation!');
    }
}

new SmartDonationPro();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .sdp-container { text-align: center; margin: 20px 0; }
    .sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
    .sdp-progress { background: #28a745; height: 100%; transition: width 0.3s; }
    .sdp-button { background: #007cba; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .sdp-button:hover { background: #005a87; }
    </style>';
});

// Inline JS
add_action('wp_footer', function() {
    ?><script>
    jQuery(document).ready(function($) {
        $('.sdp-donate-form').on('submit', function(e) {
            // Simulate donation tracking
            $.post(sdp_ajax.ajax_url, {
                action: 'sdp_donate',
                nonce: sdp_ajax.nonce,
                amount: 10
            });
        });
    });
    </script><?php
});