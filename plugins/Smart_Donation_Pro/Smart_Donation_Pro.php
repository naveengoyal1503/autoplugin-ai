/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Create customizable donation buttons with PayPal integration, progress bars, and analytics.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
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
            'goal' => '1000',
            'title' => 'Support Us',
            'button_text' => 'Donate Now',
            'currency' => 'USD'
        ), $atts);

        $current = get_option('sdp_total_donations', 0);
        $progress = min(100, ($current / $atts['goal']) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p class="sdp-goal">$<?php echo number_format($current, 2); ?> / $<?php echo $atts['goal']; ?> raised</p>
            <form class="sdp-form" method="post" action="https://www.paypal.com/cgi-bin/webscr">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo get_option('sdp_paypal_email'); ?>">
                <input type="hidden" name="item_name" value="<?php echo esc_attr(get_bloginfo('name') . ' Donation'); ?>">
                <input type="hidden" name="currency_code" value="<?php echo $atts['currency']; ?>">
                <input type="number" name="amount" value="<?php echo $atts['amount']; ?>" min="1" step="0.01" required>
                <input type="hidden" name="return" value="<?php echo home_url(); ?>">
                <button type="submit" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        // Simulate donation tracking (in pro version, integrate webhooks)
        $amount = sanitize_text_field($_POST['amount']);
        $total = get_option('sdp_total_donations', 0) + floatval($amount);
        update_option('sdp_total_donations', $total);
        wp_send_json_success('Thank you for your donation!');
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
    });

    function sdp_settings_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation amount="10" goal="1000" title="Support Us"]</code></p>
            <p><strong>Pro Features:</strong> Stripe integration, analytics dashboard, unlimited goals. <a href="https://example.com/pro">Upgrade Now</a></p>
        </div>
        <?php
    }
}

SmartDonationPro::get_instance();

// Inline styles and scripts
add_action('wp_head', function() {
    echo '<style>
.sdp-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdp-progress { background: #28a745; height: 100%; transition: width 0.3s; }
.sdp-goal { font-weight: bold; color: #28a745; }
.sdp-form input[type="number"] { width: 100px; padding: 8px; margin-right: 10px; }
.sdp-button { background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
.sdp-button:hover { background: #005a87; }
    </style>';
});

// Minimal JS
add_action('wp_footer', function() {
    if (!wp_script_is('sdp-script', 'enqueued')) return;
    ?><script>jQuery(document).ready(function($) { console.log('Smart Donation Pro loaded'); });</script><?php
});