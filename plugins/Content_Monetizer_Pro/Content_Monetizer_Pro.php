<?php
/*
Plugin Name: Content Monetizer Pro
Description: Monetize your WordPress content via sales, subscriptions, and affiliate offers.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Content_Monetizer_Pro.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class ContentMonetizerPro {
    private static $instance = null;

    private function __construct() {
        add_action('init', array($this, 'register_content_sale_post_type'));
        add_shortcode('cmp_sale_button', array($this, 'render_sale_button'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_cmp_process_sale', array($this, 'process_sale'));
        add_action('wp_ajax_nopriv_cmp_process_sale', array($this, 'process_sale'));
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Register a custom post type for sellable content
    public function register_content_sale_post_type() {
        $labels = array(
            'name' => 'Sales',
            'singular_name' => 'Sale Content',
            'add_new' => 'Add New Sale',
            'add_new_item' => 'Add New Sale Content',
            'edit_item' => 'Edit Sale Content',
            'new_item' => 'New Sale Content',
            'view_item' => 'View Sale Content',
            'search_items' => 'Search Sales',
            'not_found' => 'No sales found',
            'not_found_in_trash' => 'No sales found in Trash'
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title','editor'),
            'menu_position' => 20,
            'menu_icon' => 'dashicons-money-alt',
            'rewrite' => false
        );

        register_post_type('cmp_sale', $args);
    }

    // Enqueue scripts for ajax and styles
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('cmp-scripts', plugin_dir_url(__FILE__) . 'cmp-scripts.js', array('jquery'), '1.0', true);
        wp_localize_script('cmp-scripts', 'cmp_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cmp_nonce')
        ));
    }

    // Shortcode to render a sale button for current post
    public function render_sale_button($atts) {
        global $post;
        $price = get_post_meta($post->ID, '_cmp_price', true);
        if(!$price) {
            $price = 'Free';
        }
        ob_start();
        ?>
        <div class="cmp-sale-wrapper">
            <p>Price: <strong><?php echo esc_html($price); ?></strong></p>
            <button class="cmp-buy-btn" data-postid="<?php echo esc_attr($post->ID); ?>">Buy Now</button>
            <div class="cmp-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    // Handle the AJAX request for processing sale (simulation)
    public function process_sale() {
        check_ajax_referer('cmp_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        if (!$post_id || get_post_type($post_id) !== 'cmp_sale') {
            wp_send_json_error('Invalid content.');
        }

        // Simulated payment processing & granting access
        // In real use, integrate with payment gateway here

        // Mark user meta or cookie for access granted
        $user_id = get_current_user_id();
        if ($user_id) {
            $purchased = get_user_meta($user_id, '_cmp_purchases', true);
            if (!is_array($purchased)) $purchased = array();
            if (!in_array($post_id, $purchased)) {
                $purchased[] = $post_id;
                update_user_meta($user_id, '_cmp_purchases', $purchased);
            }
        } else {
            // Use cookie for guest access (expires in 7 days)
            $purchased = isset($_COOKIE['cmp_purchases']) ? json_decode(stripslashes($_COOKIE['cmp_purchases']), true) : array();
            if (!is_array($purchased)) $purchased = array();
            if (!in_array($post_id, $purchased)) {
                $purchased[] = $post_id;
                setcookie('cmp_purchases', json_encode($purchased), time()+7*24*3600, COOKIEPATH, COOKIE_DOMAIN);
            }
        }

        wp_send_json_success('Purchase successful! Access granted.');
    }
}

ContentMonetizerPro::get_instance();
