/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Boost your WordPress site revenue with easy-to-use donation buttons, progress bars, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationsPro {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('sdp_donate', array($this, 'donate_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-frontend', plugin_dir_url(__FILE__) . 'sdp-frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-frontend', plugin_dir_url(__FILE__) . 'sdp-frontend.css', array(), '1.0.0');
        wp_localize_script('sdp-frontend', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Donations', 'manage_options', 'smart-donations-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdp_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode <code>[sdp_donate goal="500" received="120"]</code></p>
        </div>
        <?php
    }

    public function donate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '100',
            'received' => '0',
            'button_text' => 'Donate Now',
            'currency' => '$'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email');
        if (!$paypal_email) {
            return '<p>Please configure PayPal email in settings.</p>';
        }

        $progress = ($atts['received'] / $atts['goal']) * 100;
        ob_start();
        ?>
        <div class="sdp-container">
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo min(100, $progress); ?>%;"></div>
            </div>
            <p class="sdp-goal"><?php echo $atts['currency']; ?><?php echo number_format($atts['received']); ?> / <?php echo $atts['currency']; ?><?php echo number_format($atts['goal']); ?></p>
            <form action="https://www.paypal.com/donate" method="post" target="_top" class="sdp-form">
                <input type="hidden" name="hosted_button_id" value="YOUR_BUTTON_ID">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
                <input type="hidden" name="item_name" value="Donation to <?php echo get_bloginfo('name'); ?>">
                <input type="hidden" name="currency_code" value="USD">
                <input type="submit" value="<?php echo esc_attr($atts['button_text']); ?>" class="sdp-button">
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        // Log donation (simulate)
        $received = get_option('sdp_total_received', 0) + floatval($_POST['amount']);
        update_option('sdp_total_received', $received);
        wp_send_json_success('Thank you for your donation!');
    }

    public function activate() {
        add_option('sdp_total_received', 0);
    }
}

SmartDonationsPro::get_instance();

// Inline styles and scripts for self-contained

function sdp_add_inline_styles() {
    echo '<style>
.sdp-container { max-width: 400px; margin: 20px auto; text-align: center; }
.sdp-progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
.sdp-progress { height: 100%; background: #28a745; transition: width 0.3s; }
.sdp-goal { font-weight: bold; margin-bottom: 15px; }
.sdp-button { background: #007cba; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-size: 16px; }
.sdp-button:hover { background: #005a87; }
.sdp-form { display: inline-block; }
    </style>';
}
add_action('wp_head', 'sdp_add_inline_styles');

function sdp_add_inline_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sdp-button').on('click', function() {
            // Optional: Track click
            $.post(sdp_ajax.ajax_url, {
                action: 'sdp_donate',
                nonce: sdp_ajax.nonce,
                amount: 10
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sdp_add_inline_scripts');