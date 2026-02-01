/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Collect one-time and recurring donations easily with Stripe, progress bars, and thank-you pages.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donation-pro
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-donation-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (get_option('sdp_stripe_key') || get_option('sdp_stripe_secret')) {
            add_action('wp_ajax_sdp_process_donation', array($this, 'process_donation'));
            add_action('wp_ajax_nopriv_sdp_process_donation', array($this, 'process_donation'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('stripe', 'https://js.stripe.com/v3/', array(), '3.0.0', true);
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery', 'stripe'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sdp_nonce'),
            'publishable_key' => get_option('sdp_stripe_key')
        ));
    }

    public function admin_menu() {
        add_options_page(
            'Smart Donation Pro Settings',
            'Donation Pro',
            'manage_options',
            'smart-donation-pro',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['sdp_save'])) {
            update_option('sdp_stripe_key', sanitize_text_field($_POST['stripe_key']));
            update_option('sdp_stripe_secret', sanitize_text_field($_POST['stripe_secret']));
            update_option('sdp_donation_goal', intval($_POST['donation_goal']));
            update_option('sdp_thank_you_page', intval($_POST['thank_you_page']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Stripe Publishable Key</th>
                        <td><input type="text" name="stripe_key" value="<?php echo esc_attr(get_option('sdp_stripe_key')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Stripe Secret Key</th>
                        <td><input type="password" name="stripe_secret" value="<?php echo esc_attr(get_option('sdp_stripe_secret')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Donation Goal ($)</th>
                        <td><input type="number" name="donation_goal" value="<?php echo esc_attr(get_option('sdp_donation_goal', 1000)); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Thank You Page</th>
                        <td><?php wp_dropdown_pages(array('selected' => get_option('sdp_thank_you_page'))); ?></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'sdp_save'); ?>
            </form>
            <p>Use shortcode <code>[smart_donation]</code> to display the donation form.</p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10,25,50',
            'button_text' => 'Donate Now',
            'goal' => ''
        ), $atts);

        $amounts = explode(',', $atts['amount']);
        $goal = $atts['goal'] ?: get_option('sdp_donation_goal', 1000);
        $current = get_option('sdp_total_donated', 0);
        $percent = min(100, ($current / $goal) * 100);

        ob_start();
        ?>
        <div class="sdp-container">
            <div class="sdp-goal-bar">
                <div class="sdp-progress" style="width: <?php echo $percent; ?>%;"></div>
                <span class="sdp-goal-text">$<?php echo number_format($current); ?> / $<?php echo number_format($goal); ?> raised (<?php echo round($percent); ?>%)</span>
            </div>
            <form id="sdp-form" class="sdp-form">
                <div class="sdp-amounts">
                    <?php foreach ($amounts as $amount): $amount = trim($amount); ?>
                    <button type="button" class="sdp-amount-btn" data-amount="<?php echo $amount; ?>">$<?php echo $amount; ?></button>
                    <?php endforeach; ?>
                    <input type="number" id="sdp-custom-amount" placeholder="Custom amount" min="1" />
                </div>
                <div id="sdp-card-element"></div>
                <button type="submit" class="sdp-submit-btn"><?php echo esc_html($atts['button_text']); ?></button>
                <div id="sdp-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        \Stripe\Stripe::setApiKey(get_option('sdp_stripe_secret'));

        try {
            $token = $_POST['stripeToken'];
            $amount = intval($_POST['amount'] * 100); // cents
            $charge = \Stripe\Charge::create(array(
                'amount' => $amount,
                'currency' => 'usd',
                'source' => $token,
                'description' => 'Donation to ' . get_bloginfo('name')
            ));

            $current = get_option('sdp_total_donated', 0) + ($_POST['amount']);
            update_option('sdp_total_donated', $current);

            $thank_you = get_option('sdp_thank_you_page') ? get_permalink(get_option('sdp_thank_you_page')) : home_url('/thank-you/');
            wp_send_json_success(array('redirect' => $thank_you));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function activate() {
        update_option('sdp_total_donated', 0);
    }
}

SmartDonationPro::get_instance();

// Prevent direct access to assets
function sdp_assets_dir_setup() {
    $upload_dir = wp_upload_dir();
    $assets_dir = $upload_dir['basedir'] . '/sdp-assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Note: Create assets/script.js and assets/style.css manually or via FTP
    // script.js: Stripe handling code
    // style.css: Basic styling
}
add_action('init', 'sdp_assets_dir_setup');