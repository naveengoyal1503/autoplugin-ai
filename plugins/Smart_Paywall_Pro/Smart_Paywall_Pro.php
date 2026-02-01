/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Paywall_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Paywall Pro
 * Plugin URI: https://example.com/smart-paywall-pro
 * Description: Automatically add paywalls to content for monetization. Free version limits to 5 posts; pro unlocks unlimited.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-paywall-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartPaywallPro {
    private static $instance = null;
    public $pro = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->pro = get_option('smart_paywall_pro_key') !== false;
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'register_post_meta'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_filter('the_content', array($this, 'paywall_content'));
        add_shortcode('paywall_unlock', array($this, 'paywall_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_spp_process_payment', array($this, 'process_payment'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-paywall-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if ($this->pro && class_exists('WC_Payment_Gateway')) {
            // Pro integrates with WooCommerce/Stripe
        }
    }

    public function activate() {
        if (!$this->pro && get_posts(array('post_type' => 'post', 'meta_key' => 'spp_paywall_price', 'numberposts' => -1)) > 5) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>Smart Paywall Pro free version limited to 5 paywalled posts. Upgrade to pro!</p></div>';
            });
        }
    }

    public function register_post_meta() {
        register_post_meta('post', 'spp_paywall_price', array(
            'type' => 'number',
            'single' => true,
            'show_in_rest' => true,
        ));
        register_post_meta('post', 'spp_paywall_teaser', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
        ));
    }

    public function add_meta_box() {
        add_meta_box('spp-paywall', 'Smart Paywall', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('spp_meta_nonce', 'spp_meta_nonce');
        $price = get_post_meta($post->ID, 'spp_paywall_price', true);
        $teaser = get_post_meta($post->ID, 'spp_paywall_teaser', true);
        echo '<label for="spp_price">Price ($):</label><br>';
        echo '<input type="number" id="spp_price" name="spp_price" value="' . esc_attr($price) . '" step="0.01" />';
        echo '<p><label for="spp_teaser">Teaser Text:</label><br>';
        echo '<input type="text" id="spp_teaser" name="spp_teaser" value="' . esc_attr($teaser) . '" style="width:100%;" placeholder="Read full article for $X" /></p>';
        if (!$this->pro) {
            $count = get_posts(array('post_type' => 'post', 'meta_key' => 'spp_paywall_price', 'numberposts' => -1, 'meta_value' => '1'));
            echo '<p style="color:red;">Free version: ' . count($count) . '/5 paywalls used. <a href="#" onclick="alert(\'Upgrade to pro!\')">Upgrade</a></p>';
        }
    }

    public function save_meta($post_id) {
        if (!isset($_POST['spp_meta_nonce']) || !wp_verify_nonce($_POST['spp_meta_nonce'], 'spp_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $price = isset($_POST['spp_price']) ? floatval($_POST['spp_price']) : '';
        update_post_meta($post_id, 'spp_paywall_price', $price);
        update_post_meta($post_id, 'spp_paywall_teaser', sanitize_text_field($_POST['spp_teaser']));
    }

    public function paywall_content($content) {
        if (is_admin() || is_feed()) return $content;
        global $post;
        $price = get_post_meta($post->ID, 'spp_paywall_price', true);
        if (!$price) return $content;

        $user_id = get_current_user_id();
        $purchases = get_user_meta($user_id, 'spp_purchases', true);
        if (!$purchases || !in_array($post->ID, (array)$purchases)) {
            $teaser = get_post_meta($post->ID, 'spp_paywall_teaser', true) ?: 'Subscribe to unlock full content for $' . $price;
            $button = '<div class="spp-paywall" style="background:#f9f9f9;padding:20px;border:1px solid #ddd;text-align:center;">';
            $button .= '<p>' . esc_html($teaser) . '</p>';
            $button .= '<button class="spp-pay-button" data-post="' . $post->ID . '" data-price="' . $price . '">Pay $' . $price . ' Now</button>';
            $button .= '</div>';
            $button .= '<script>document.addEventListener("DOMContentLoaded",function(){const btn=document.querySelector(".spp-pay-button[data-post=' . $post->ID . ']");btn&&btn.addEventListener("click",function(){fetch("' . admin_url('admin-ajax.php') . '",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"action=spp_process_payment&post_id=' . $post->ID . '&price=' . $price . '&nonce=' . wp_create_nonce('spp_payment') . '"}).then(r=>r.json()).then(d=>d.success?location.reload():alert(d.data))})});</script>';
            return substr($content, 0, 300) . '...' . $button;
        }
        return $content;
    }

    public function paywall_shortcode($atts) {
        $atts = shortcode_atts(array('post_id' => get_the_ID()), $atts);
        ob_start();
        echo do_shortcode('[paywall_unlock]');
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('spp-frontend', plugin_dir_url(__FILE__) . 'spp.js', array('jquery'), '1.0.0', true);
    }

    public function process_payment() {
        check_ajax_referer('spp_payment', 'nonce');
        $post_id = intval($_POST['post_id']);
        $price = floatval($_POST['price']);
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Login required');
        }
        $purchases = get_user_meta($user_id, 'spp_purchases', true) ?: array();
        $purchases[] = $post_id;
        update_user_meta($user_id, 'spp_purchases', array_unique($purchases));
        // In pro: Integrate Stripe/PayPal
        wp_send_json_success('Unlocked!');
    }

    public function admin_menu() {
        add_options_page('Smart Paywall Pro', 'Paywall Pro', 'manage_options', 'spp-pro', array($this, 'pro_page'));
    }

    public function pro_page() {
        if (!$this->pro) {
            echo '<div class="wrap"><h1>Upgrade to Pro</h1><p>Unlock unlimited paywalls, subscriptions, analytics. <a href="https://example.com/pro">Buy Now $29/year</a></p></div>';
        }
    }
}

SmartPaywallPro::get_instance();

// Pro key check
function spp_activate_pro($key) {
    update_option('smart_paywall_pro_key', $key);
}
?>