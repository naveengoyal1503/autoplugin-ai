/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons and forms to monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

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
            // Plugin active
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_save'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sdp_button_text', sanitize_text_field($_POST['button_text']));
            update_option('sdp_goal_amount', floatval($_POST['goal_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        $button_text = get_option('sdp_button_text', 'Donate Now');
        $goal_amount = get_option('sdp_goal_amount', 1000);
        $current_amount = get_option('sdp_current_amount', 0);
        include plugin_dir_path(__FILE__) . 'settings.php';
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'currency' => '$',
            'button_text' => get_option('sdp_button_text', 'Donate Now'),
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) return '<p>Please configure PayPal email in settings.</p>';

        $goal_amount = get_option('sdp_goal_amount', 1000);
        $current_amount = get_option('sdp_current_amount', 0);
        $progress = min(100, ($current_amount / $goal_amount) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p class="sdp-goal"><?php echo $atts['currency']; ?><?php echo number_format($current_amount, 2); ?> / <?php echo $atts['currency']; ?><?php echo number_format($goal_amount, 2); ?> raised</p>
            <form class="sdp-donate-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="item_name" value="Donation to <?php echo get_bloginfo('name'); ?>">
                <input type="hidden" name="currency_code" value="USD">
                <input type="number" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01" required>
                <input type="hidden" name="return" value="<?php echo esc_url(home_url()); ?>">
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdp_current_amount', 0);
        update_option('sdp_current_amount', $current + $amount);
        wp_send_json_success('Thank you for your donation!');
    }
}

new SmartDonationPro();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .sdp-container { max-width: 400px; margin: 20px 0; }
    .sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
    .sdp-progress { background: #28a745; height: 100%; transition: width 0.3s; }
    .sdp-goal { text-align: center; margin-bottom: 15px; }
    .sdp-donate-form { text-align: center; }
    .sdp-donate-form input[type="number"] { padding: 10px; width: 100px; margin-right: 10px; }
    .sdp-button { background: #007cba; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; }
    .sdp-button:hover { background: #005a87; }
    </style>';
});

// Minimal JS for future AJAX donations
add_action('wp_footer', function() {
    ?><script>jQuery(document).ready(function($) { /* Future enhancements */ });</script><?php
});