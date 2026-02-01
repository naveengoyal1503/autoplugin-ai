/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Paywall.php
*/
<?php
/**
 * Plugin Name: Smart Content Paywall
 * Plugin URI: https://example.com/smart-content-paywall
 * Description: Automatically paywalls your content after a free preview, unlocking full access via one-time or recurring payments with Stripe integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-content-paywall
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartContentPaywall {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_scp_paywall_unlock', array($this, 'handle_unlock'));
        add_action('wp_ajax_nopriv_scp_paywall_unlock', array($this, 'handle_unlock'));
        add_shortcode('scp_paywall', array($this, 'paywall_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('scp_stripe_key') && get_option('scp_stripe_secret')) {
            // Stripe is configured
        }
        load_plugin_textdomain('smart-content-paywall', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scp-frontend', plugin_dir_url(__FILE__) . 'scp-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('scp-frontend', 'scp_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scp_nonce'),
            'stripe_key' => get_option('scp_stripe_key'),
            'paywall_price' => get_option('scp_price', 4.99),
        ));
        wp_enqueue_style('scp-styles', plugin_dir_url(__FILE__) . 'scp-styles.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Content Paywall Settings',
            'Content Paywall',
            'manage_options',
            'scp-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['scp_save'])) {
            update_option('scp_stripe_key', sanitize_text_field($_POST['stripe_key']));
            update_option('scp_stripe_secret', sanitize_text_field($_POST['stripe_secret']));
            update_option('scp_price', floatval($_POST['price']));
            update_option('scp_preview_words', intval($_POST['preview_words']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Content Paywall Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Stripe Publishable Key</th>
                        <td><input type="text" name="stripe_key" value="<?php echo esc_attr(get_option('scp_stripe_key')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Stripe Secret Key</th>
                        <td><input type="password" name="stripe_secret" value="<?php echo esc_attr(get_option('scp_stripe_secret')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Paywall Price ($)</th>
                        <td><input type="number" step="0.01" name="price" value="<?php echo esc_attr(get_option('scp_price', 4.99)); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Free Preview Words</th>
                        <td><input type="number" name="preview_words" value="<?php echo esc_attr(get_option('scp_preview_words', 100)); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button('scp_save'); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock subscriptions, analytics, and more for $49/year.</p>
        </div>
        <?php
    }

    public function paywall_shortcode($atts) {
        $atts = shortcode_atts(array('id' => null), $atts);
        ob_start();
        if ($atts['id']) {
            echo do_shortcode('[scp_content id="' . esc_attr($atts['id']) . '"]');
        } else {
            echo do_shortcode('[scp_content]');
        }
        return ob_get_clean();
    }

    public function the_content_filter($content) {
        if (!is_single() || is_admin()) return $content;

        global $post;
        $paywall_enabled = get_post_meta($post->ID, '_scp_paywall', true);
        if (!$paywall_enabled) return $content;

        $preview_words = get_option('scp_preview_words', 100);
        $words = explode(' ', strip_tags($content));
        if (count($words) <= $preview_words) return $content;

        $preview = implode(' ', array_slice($words, 0, $preview_words));
        $full_content = $content;

        $user_has_access = false;
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $purchases = get_user_meta($user_id, 'scp_purchases', true);
            if ($purchases && in_array($post->ID, $purchases)) {
                $user_has_access = true;
            }
        }

        if ($user_has_access) {
            return $full_content;
        }

        ob_start();
        ?>
        <div class="scp-preview"><?php echo $preview; ?>...</div>
        <div id="scp-paywall" class="scp-paywall" style="background:#f9f9f9;padding:20px;border:1px solid #ddd;">
            <h3>Unlock Full Article</h3>
            <p>Pay only $<span id="scp-price"><?php echo get_option('scp_price', 4.99); ?></span> once to read this article.</p>
            <div id="scp-stripe-button"></div>
            <p id="scp-message"></p>
        </div>
        <script src="https://js.stripe.com/v3/"></script>
        <?php
        return ob_get_clean();
    }

    public function handle_unlock() {
        check_ajax_referer('scp_nonce', 'nonce');

        if (!isset($_POST['payment_intent_id'])) {
            wp_die('Invalid request');
        }

        $stripe_secret = get_option('scp_stripe_secret');
        if (!$stripe_secret) {
            wp_send_json_error('Stripe not configured');
        }

        require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php'; // Assume Stripe PHP SDK is included or use curl

        \Stripe\Stripe::setApiKey($stripe_secret);

        try {
            $intent = \Stripe\PaymentIntent::retrieve($_POST['payment_intent_id']);
            if ($intent->status == 'succeeded') {
                if (!is_user_logged_in()) {
                    wp_send_json_error('Login required');
                }
                $user_id = get_current_user_id();
                $post_id = intval($_POST['post_id']);
                $purchases = get_user_meta($user_id, 'scp_purchases', true);
                if (!is_array($purchases)) $purchases = array();
                $purchases[] = $post_id;
                update_user_meta($user_id, 'scp_purchases', $purchases);
                wp_send_json_success('Unlocked!');
            } else {
                wp_send_json_error('Payment failed');
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    public function activate() {
        update_option('scp_preview_words', 100);
        update_option('scp_price', 4.99);
    }
}

// Auto filter content
global $scp_filter_added;
if (!$scp_filter_added) {
    add_filter('the_content', array(SmartContentPaywall::get_instance(), 'the_content_filter'));
    $scp_filter_added = true;
}

SmartContentPaywall::get_instance();

// Note: For full Stripe integration, download Stripe PHP SDK and place in /vendor. Free version uses simple one-time payments. Pro adds subscriptions.