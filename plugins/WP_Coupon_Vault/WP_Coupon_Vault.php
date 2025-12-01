/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Description: Manage and display exclusive coupon codes for affiliate products.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPCouponVault {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_shortcode('coupon_vault', array($this, 'shortcode_handler'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function register_post_type() {
        register_post_type('wp_coupon',
            array(
                'labels' => array(
                    'name' => __('Coupons', 'textdomain'),
                    'singular_name' => __('Coupon', 'textdomain')
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array('title', 'editor'),
                'menu_icon' => 'dashicons-tag'
            )
        );
    }

    public function add_meta_box() {
        add_meta_box(
            'coupon_details',
            __('Coupon Details', 'textdomain'),
            array($this, 'render_meta_box'),
            'wp_coupon',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('save_coupon_details', 'coupon_nonce');
        $code = get_post_meta($post->ID, '_coupon_code', true);
        $url = get_post_meta($post->ID, '_coupon_url', true);
        $expiry = get_post_meta($post->ID, '_coupon_expiry', true);
        ?>
        <p>
            <label for="coupon_code">Coupon Code:</label>
            <input type="text" id="coupon_code" name="coupon_code" value="<?php echo esc_attr($code); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="coupon_url">Affiliate URL:</label>
            <input type="url" id="coupon_url" name="coupon_url" value="<?php echo esc_url($url); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="coupon_expiry">Expiry Date (YYYY-MM-DD):</label>
            <input type="date" id="coupon_expiry" name="coupon_expiry" value="<?php echo esc_attr($expiry); ?>" />
        </p>
        <?php
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['coupon_nonce']) || !wp_verify_nonce($_POST['coupon_nonce'], 'save_coupon_details')) {
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
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5
        ), $atts, 'coupon_vault');

        $args = array(
            'post_type' => 'wp_coupon',
            'posts_per_page' => $atts['limit'],
            'meta_query' => array(
                array(
                    'key' => '_coupon_expiry',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        );

        $coupons = new WP_Query($args);
        $output = '<div class="wp-coupon-vault">
            <h3>Exclusive Coupons</h3>
            <ul class="coupon-list">';

        if ($coupons->have_posts()) {
            while ($coupons->have_posts()) {
                $coupons->the_post();
                $code = get_post_meta(get_the_ID(), '_coupon_code', true);
                $url = get_post_meta(get_the_ID(), '_coupon_url', true);
                $output .= '<li><strong>' . get_the_title() . '</strong>: <code>' . $code . '</code> <a href="' . $url . '" target="_blank">Use Now</a></li>';
            }
            wp_reset_postdata();
        } else {
            $output .= '<li>No active coupons found.</li>';
        }

        $output .= '</ul></div>';
        return $output;
    }

    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=wp_coupon',
            'Settings',
            'Settings',
            'manage_options',
            'coupon-vault-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>Coupon Vault Settings</h1><p>Manage your coupon display settings here.</p></div>';
    }
}

new WPCouponVault();
