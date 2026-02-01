/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost your WordPress site revenue with customizable donation buttons, progress bars, and payment integration.
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
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'button_text' => 'Donate Now',
            'goal' => '1000',
            'currency' => '$',
            'paypal_email' => get_option('sdp_paypal_email'),
        ), $atts);

        $current = get_option('sdp_total_donations', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p class="sdp-goal"><?php echo $atts['currency']; ?> <?php echo number_format($current, 0); ?> / <?php echo $atts['currency']; ?> <?php echo number_format($atts['goal'], 0); ?> raised</p>
            <form class="sdp-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo esc_attr($atts['paypal_email']); ?>">
                <input type="hidden" name="item_name" value="Donation to <?php echo get_bloginfo('name'); ?>">
                <input type="hidden" name="currency_code" value="USD">
                <input type="number" name="amount" class="sdp-amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01" required>
                <input type="hidden" name="return" value="<?php echo home_url(); ?>">
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        // Simulate donation logging (in pro version, integrate Stripe/PayPal IPN)
        $amount = floatval($_POST['amount']);
        $current = floatval(get_option('sdp_total_donations', 0));
        update_option('sdp_total_donations', $current + $amount);
        wp_send_json_success('Thank you for your donation!');
    }
}

// Settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', function() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation amount="10" goal="1000" button_text="Support Us"]</code></p>
        </div>
        <?php
    });
});

new SmartDonationPro();

// Inline styles and scripts for single file
add_action('wp_head', function() {
    echo '<style>
        .sdp-container { max-width: 400px; margin: 20px 0; text-align: center; }
        .sdp-progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
        .sdp-progress { background: #4CAF50; height: 100%; transition: width 0.3s; }
        .sdp-goal { margin: 10px 0; font-weight: bold; }
        .sdp-form input[type="number"] { width: 100px; padding: 10px; margin: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .sdp-button { background: #0073aa; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .sdp-button:hover { background: #005a87; }
    </style>';
});

add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Optional AJAX for custom processing
    });
    </script>
    <?php
});