/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Collect donations easily with customizable buttons, progress bars, PayPal integration, and analytics.
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
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('sdp_donate', array($this, 'donate_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_goal') === false) {
            update_option('sdp_goal', 1000);
        }
        if (get_option('sdp_current') === false) {
            update_option('sdp_current', 0);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'button_text' => 'Donate Now',
            'show_progress' => 'true'
        ), $atts);

        $goal = get_option('sdp_goal', 1000);
        $current = get_option('sdp_current', 0);
        $progress = ($current / $goal) * 100;

        ob_start();
        ?>
        <div class="sdp-container">
            <?php if ($atts['show_progress'] === 'true') : ?>
            <div class="sdp-progress">
                <div class="sdp-progress-bar" style="width: <?php echo esc_attr($progress); ?>%;"></div>
            </div>
            <p class="sdp-progress-text">$<?php echo esc_html($current); ?> / $<?php echo esc_html($goal); ?> raised</p>
            <?php endif; ?>
            <form class="sdp-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo esc_attr(get_option('sdp_paypal_email', '')); ?>">
                <input type="hidden" name="item_name" value="Donation to <?php echo esc_attr(get_bloginfo('name')); ?>">
                <input type="hidden" name="currency_code" value="USD">
                <input type="number" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01" required>
                <input type="hidden" name="return" value="<?php echo esc_url(home_url()); ?>">
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdp_save'])) {
            update_option('sdp_goal', sanitize_text_field($_POST['sdp_goal']));
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $goal = get_option('sdp_goal', 1000);
        $paypal = get_option('sdp_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Donation Goal ($)</th>
                        <td><input type="number" name="sdp_goal" value="<?php echo esc_attr($goal); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal); ?>" /></td>
                    </tr>
                </table>
                <p>Use shortcode: <code>[sdp_donate amount="20" button_text="Support Us" show_progress="true"]</code></p>
                <?php submit_button('Save Settings', 'primary', 'sdp_save'); ?>
            </form>
            <h2>Analytics</h2>
            <p>Current: $<?php echo esc_html(get_option('sdp_current', 0)); ?> / $<?php echo esc_html($goal); ?></p>
        </div>
        <?php
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdp_current', 0);
        update_option('sdp_current', $current + $amount);
        wp_send_json_success('Thank you for your donation!');
    }

    public function activate() {
        $this->init();
    }
}

new SmartDonationPro();

// Inline styles and scripts to keep single file
function sdp_add_inline_styles() {
    echo '<style>
        .sdp-container { max-width: 400px; margin: 20px 0; }
        .sdp-progress { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; }
        .sdp-progress-bar { height: 100%; background: #28a745; transition: width 0.3s; }
        .sdp-progress-text { margin: 10px 0; font-weight: bold; }
        .sdp-form input[type="number"] { width: 100px; padding: 8px; margin-right: 10px; }
        .sdp-button { background: #007cba; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; }
        .sdp-button:hover { background: #005a87; }
    </style>';
}
add_action('wp_head', 'sdp_add_inline_styles');

// Inline script
function sdp_add_inline_script() {
    ?><script>jQuery(document).ready(function($) { /* Basic functionality for progress updates if needed */ });</script><?php
}
add_action('wp_footer', 'sdp_add_inline_script');