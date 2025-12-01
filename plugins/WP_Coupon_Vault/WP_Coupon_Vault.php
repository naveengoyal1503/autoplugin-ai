/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Description: Manage and display exclusive coupons and deals for affiliate products.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register custom post type for coupons
define('WPCV_COUPON_POST_TYPE', 'wpcv_coupon');

class WPCVCouponVault {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_shortcode('wpcv_coupons', array($this, 'display_coupons_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function register_post_type() {
        $args = array(
            'public' => true,
            'label'  => 'Coupons',
            'supports' => array('title', 'editor'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'coupons'),
        );
        register_post_type(WPCV_COUPON_POST_TYPE, $args);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'wpcv_coupon_details',
            'Coupon Details',
            array($this, 'render_meta_box'),
            WPCV_COUPON_POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('wpcv_save_coupon', 'wpcv_coupon_nonce');
        $code = get_post_meta($post->ID, '_wpcv_coupon_code', true);
        $url = get_post_meta($post->ID, '_wpcv_coupon_url', true);
        $expiry = get_post_meta($post->ID, '_wpcv_coupon_expiry', true);
        $store = get_post_meta($post->ID, '_wpcv_coupon_store', true);
        ?>
        <p>
            <label for="wpcv_coupon_code">Coupon Code:</label>
            <input type="text" id="wpcv_coupon_code" name="wpcv_coupon_code" value="<?php echo esc_attr($code); ?>" class="widefat">
        </p>
        <p>
            <label for="wpcv_coupon_url">Affiliate URL:</label>
            <input type="url" id="wpcv_coupon_url" name="wpcv_coupon_url" value="<?php echo esc_url($url); ?>" class="widefat">
        </p>
        <p>
            <label for="wpcv_coupon_expiry">Expiry Date (YYYY-MM-DD):</label>
            <input type="date" id="wpcv_coupon_expiry" name="wpcv_coupon_expiry" value="<?php echo esc_attr($expiry); ?>" class="widefat">
        </p>
        <p>
            <label for="wpcv_coupon_store">Store Name:</label>
            <input type="text" id="wpcv_coupon_store" name="wpcv_coupon_store" value="<?php echo esc_attr($store); ?>" class="widefat">
        </p>
        <?php
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['wpcv_coupon_nonce']) || !wp_verify_nonce($_POST['wpcv_coupon_nonce'], 'wpcv_save_coupon')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array('wpcv_coupon_code', 'wpcv_coupon_url', 'wpcv_coupon_expiry', 'wpcv_coupon_store');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_wpcv_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function display_coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'store' => '',
        ), $atts, 'wpcv_coupons');

        $args = array(
            'post_type' => WPCV_COUPON_POST_TYPE,
            'posts_per_page' => $atts['limit'],
            'meta_query' => array(),
        );

        if (!empty($atts['store'])) {
            $args['meta_query'][] = array(
                'key' => '_wpcv_coupon_store',
                'value' => $atts['store'],
                'compare' => 'LIKE'
            );
        }

        $coupons = new WP_Query($args);
        if (!$coupons->have_posts()) {
            return '<p>No coupons found.</p>';
        }

        $output = '<div class="wpcv-coupons-list">';
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), '_wpcv_coupon_code', true);
            $url = get_post_meta(get_the_ID(), '_wpcv_coupon_url', true);
            $expiry = get_post_meta(get_the_ID(), '_wpcv_coupon_expiry', true);
            $store = get_post_meta(get_the_ID(), '_wpcv_coupon_store', true);
            $output .= '<div class="wpcv-coupon">
                <h4>' . get_the_title() . '</h4>
                <p><strong>Store:</strong> ' . esc_html($store) . '</p>
                <p><strong>Coupon Code:</strong> <span class="wpcv-code">' . esc_html($code) . '</span></p>
                <p><strong>Expires:</strong> ' . esc_html($expiry) . '</p>
                <a href="' . esc_url($url) . '" target="_blank" class="wpcv-claim-btn">Claim Deal</a>
            </div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }

    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=' . WPCV_COUPON_POST_TYPE,
            'Settings',
            'Settings',
            'manage_options',
            'wpcv_settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>WP Coupon Vault Settings</h1><p>Settings page for coupon display and management.</p></div>';
    }
}

new WPCVCouponVault();

// Enqueue frontend styles
function wpcv_enqueue_styles() {
    wp_enqueue_style('wpcv-styles', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'wpcv_enqueue_styles');
?>