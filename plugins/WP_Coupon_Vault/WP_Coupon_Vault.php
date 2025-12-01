/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Plugin URI: https://example.com/wp-coupon-vault
 * Description: Create, manage, and display exclusive coupons and deals for affiliate products.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPCouponVault {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_coupon_meta'));
        add_shortcode('coupon_vault', array($this, 'display_coupons'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function register_post_type() {
        register_post_type('coupon',
            array(
                'labels' => array(
                    'name' => __('Coupons', 'wp-coupon-vault'),
                    'singular_name' => __('Coupon', 'wp-coupon-vault')
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array('title', 'editor'),
                'menu_icon' => 'dashicons-tag'
            )
        );
    }

    public function add_meta_boxes() {
        add_meta_box('coupon_details', 'Coupon Details', array($this, 'coupon_details_meta_box'), 'coupon', 'normal', 'high');
    }

    public function coupon_details_meta_box($post) {
        wp_nonce_field('save_coupon_meta', 'coupon_nonce');
        $code = get_post_meta($post->ID, '_coupon_code', true);
        $url = get_post_meta($post->ID, '_coupon_url', true);
        $expiry = get_post_meta($post->ID, '_coupon_expiry', true);
        $store = get_post_meta($post->ID, '_coupon_store', true);
        ?>
        <p>
            <label for="coupon_code">Coupon Code:</label>
            <input type="text" id="coupon_code" name="coupon_code" value="<?php echo esc_attr($code); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="coupon_url">Affiliate URL:</label>
            <input type="url" id="coupon_url" name="coupon_url" value="<?php echo esc_url($url); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="coupon_expiry">Expiry Date:</label>
            <input type="date" id="coupon_expiry" name="coupon_expiry" value="<?php echo esc_attr($expiry); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="coupon_store">Store Name:</label>
            <input type="text" id="coupon_store" name="coupon_store" value="<?php echo esc_attr($store); ?>" style="width:100%;" />
        </p>
        <?php
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['coupon_nonce']) || !wp_verify_nonce($_POST['coupon_nonce'], 'save_coupon_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['coupon_code'])) {
            update_post_meta($post_id, '_coupon_code', sanitize_text_field($_POST['coupon_code']));
        }
        if (isset($_POST['coupon_url'])) {
            update_post_meta($post_id, '_coupon_url', esc_url_raw($_POST['coupon_url']));
        }
        if (isset($_POST['coupon_expiry'])) {
            update_post_meta($post_id, '_coupon_expiry', sanitize_text_field($_POST['coupon_expiry']));
        }
        if (isset($_POST['coupon_store'])) {
            update_post_meta($post_id, '_coupon_store', sanitize_text_field($_POST['coupon_store']));
        }
    }

    public function display_coupons($atts) {
        $atts = shortcode_atts(array(
            'store' => '',
            'limit' => 10
        ), $atts, 'coupon_vault');

        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => $atts['limit'],
            'meta_query' => array()
        );

        if (!empty($atts['store'])) {
            $args['meta_query'][] = array(
                'key' => '_coupon_store',
                'value' => $atts['store'],
                'compare' => 'LIKE'
            );
        }

        $coupons = new WP_Query($args);
        $output = '<div class="coupon-vault">
            <h3>Exclusive Coupons</h3>
            <div class="coupon-list">
        ';

        if ($coupons->have_posts()) {
            while ($coupons->have_posts()) {
                $coupons->the_post();
                $code = get_post_meta(get_the_ID(), '_coupon_code', true);
                $url = get_post_meta(get_the_ID(), '_coupon_url', true);
                $expiry = get_post_meta(get_the_ID(), '_coupon_expiry', true);
                $store = get_post_meta(get_the_ID(), '_coupon_store', true);
                $output .= '<div class="coupon-item">
                    <h4>' . get_the_title() . '</h4>
                    <p><strong>Store:</strong> ' . esc_html($store) . '</p>
                    <p><strong>Coupon Code:</strong> <span class="coupon-code">' . esc_html($code) . '</span></p>
                    <p><strong>Expires:</strong> ' . esc_html($expiry) . '</p>
                    <a href="' . esc_url($url) . '" target="_blank" class="coupon-link">Get Deal</a>
                </div>';
            }
            wp_reset_postdata();
        } else {
            $output .= '<p>No coupons found.</p>';
        }

        $output .= '</div></div>';
        return $output;
    }

    public function admin_menu() {
        add_options_page('WP Coupon Vault', 'Coupon Vault', 'manage_options', 'wp-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        echo '<div class="wrap">
            <h1>WP Coupon Vault</h1>
            <p>Welcome to WP Coupon Vault. Manage your coupons and deals here.</p>
            <p>Use the shortcode <code>[coupon_vault]</code> to display coupons on any page or post.</p>
        </div>';
    }
}

new WPCouponVault();
