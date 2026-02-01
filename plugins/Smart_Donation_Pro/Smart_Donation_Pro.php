/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Add customizable donation buttons with PayPal integration, goal tracking, and progress bars.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('sdp_donate', array($this, 'donation_shortcode'));
        add_shortcode('sdp_goal', array($this, 'goal_shortcode'));
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

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'currency' => 'USD'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email', '');
        if (!$paypal_email) {
            return '<p>Please set up PayPal email in plugin settings.</p>';
        }

        $paypal_url = 'https://www.paypal.com/donate?hosted_button_id=' . $this->get_paypal_button_id() . '&amount=' . $atts['amount'] . '&currency_code=' . $atts['currency'];

        ob_start();
        ?>
        <div class="sdp-donate-button" style="text-align: center; margin: 20px 0;">
            <a href="<?php echo esc_url($paypal_url); ?}" target="_blank" class="sdp-button" style="background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px; display: inline-block;">
                <?php echo esc_html($atts['label']); ?> $<?php echo esc_attr($atts['amount']); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function goal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'current' => '250',
            'label' => 'Donation Goal'
        ), $atts);

        $percentage = ($atts['current'] / $atts['goal']) * 100;

        ob_start();
        ?>
        <div class="sdp-goal-tracker" style="margin: 20px 0;">
            <h3><?php echo esc_html($atts['label']); ?></h3>
            <div style="background: #f0f0f0; border-radius: 10px; height: 20px;">
                <div style="background: #007cba; height: 20px; border-radius: 10px; width: <?php echo $percentage; ?>%; transition: width 0.5s;"></div>
            </div>
            <p style="text-align: center; margin-top: 5px;">$<?php echo esc_html($atts['current']); ?> / $<?php echo esc_html($atts['goal']); ?> (<?php echo round($percentage); ?>%)</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        // Simulate donation tracking (in pro version, integrate with PayPal IPN)
        $current = get_option('sdp_total_donations', 0) + 10;
        update_option('sdp_total_donations', $current);
        wp_send_json_success('Thank you for your donation!');
    }

    private function get_paypal_button_id() {
        return get_option('sdp_paypal_button_id', 'YOUR_BUTTON_ID'); // User sets this
    }

    public function activate() {
        add_option('sdp_total_donations', 0);
    }
}

// Admin settings page
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
    });
}

function sdp_settings_page() {
    if (isset($_POST['sdp_paypal_email'])) {
        update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
        update_option('sdp_paypal_button_id', sanitize_text_field($_POST['sdp_paypal_button_id']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $email = get_option('sdp_paypal_email', '');
    $button_id = get_option('sdp_paypal_button_id', '');
    ?>
    <div class="wrap">
        <h1>Smart Donation Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr($email); ?>" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th>PayPal Button ID</th>
                    <td><input type="text" name="sdp_paypal_button_id" value="<?php echo esc_attr($button_id); ?>" class="regular-text" placeholder="Create at paypal.com/buttons" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>Shortcodes:</strong></p>
        <ul>
            <li>[sdp_donate amount="10" label="Buy Me Coffee"]</li>
            <li>[sdp_goal goal="1000" current="250" label="Fundraiser Goal"]</li>
        </ul>
    </div>
    <?php
}

// Inline CSS and JS for self-contained
add_action('wp_head', function() {
    echo '<style>
        .sdp-donate-button:hover .sdp-button { background: #005a87; }
        .sdp-goal-tracker { max-width: 400px; }
    </style>';
    echo '<script>
        jQuery(document).ready(function($) {
            $(".sdp-button").click(function(e) {
                // Optional: Track click
                $.post(sdp_ajax.ajax_url, {action: "sdp_donate", nonce: sdp_ajax.nonce});
            });
        });
    </script>';
});

SmartDonationPro::get_instance();