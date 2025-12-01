<?php
/*
Plugin Name: WP Coupon Vault
Description: Create, manage, and display exclusive coupons and deals for your audience.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/

class WPCouponVault {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_box'));
        add_action('save_post', array($this, 'save_coupon_meta'));
        add_shortcode('coupon_vault', array($this, 'display_coupons'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function register_post_type() {
        register_post_type('wpcv_coupon',
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

    public function add_coupon_meta_box() {
        add_meta_box(
            'wpcv_coupon_meta',
            'Coupon Details',
            array($this, 'render_coupon_meta_box'),
            'wpcv_coupon',
            'normal',
            'high'
        );
    }

    public function render_coupon_meta_box($post) {
        wp_nonce_field('wpcv_save_coupon_meta', 'wpcv_coupon_nonce');
        $code = get_post_meta($post->ID, '_wpcv_code', true);
        $expiry = get_post_meta($post->ID, '_wpcv_expiry', true);
        $url = get_post_meta($post->ID, '_wpcv_url', true);
        $brand = get_post_meta($post->ID, '_wpcv_brand', true);
        ?>
        <p>
            <label for="wpcv_code">Coupon Code:</label>
            <input type="text" id="wpcv_code" name="wpcv_code" value="<?php echo esc_attr($code); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="wpcv_expiry">Expiry Date (YYYY-MM-DD):</label>
            <input type="text" id="wpcv_expiry" name="wpcv_expiry" value="<?php echo esc_attr($expiry); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="wpcv_url">Affiliate URL:</label>
            <input type="url" id="wpcv_url" name="wpcv_url" value="<?php echo esc_attr($url); ?>" style="width:100%;" />
        </p>
        <p>
            <label for="wpcv_brand">Brand:</label>
            <input type="text" id="wpcv_brand" name="wpcv_brand" value="<?php echo esc_attr($brand); ?>" style="width:100%;" />
        </p>
        <?php
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['wpcv_coupon_nonce']) || !wp_verify_nonce($_POST['wpcv_coupon_nonce'], 'wpcv_save_coupon_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['wpcv_code'])) {
            update_post_meta($post_id, '_wpcv_code', sanitize_text_field($_POST['wpcv_code']));
        }
        if (isset($_POST['wpcv_expiry'])) {
            update_post_meta($post_id, '_wpcv_expiry', sanitize_text_field($_POST['wpcv_expiry']));
        }
        if (isset($_POST['wpcv_url'])) {
            update_post_meta($post_id, '_wpcv_url', esc_url_raw($_POST['wpcv_url']));
        }
        if (isset($_POST['wpcv_brand'])) {
            update_post_meta($post_id, '_wpcv_brand', sanitize_text_field($_POST['wpcv_brand']));
        }
    }

    public function display_coupons($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'limit' => 10
        ), $atts);

        $args = array(
            'post_type' => 'wpcv_coupon',
            'posts_per_page' => $atts['limit'],
            'meta_query' => array()
        );

        if (!empty($atts['brand'])) {
            $args['meta_query'][] = array(
                'key' => '_wpcv_brand',
                'value' => $atts['brand'],
                'compare' => 'LIKE'
            );
        }

        $coupons = new WP_Query($args);
        $output = '<div class="wpcv-coupon-list">';
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), '_wpcv_code', true);
            $expiry = get_post_meta(get_the_ID(), '_wpcv_expiry', true);
            $url = get_post_meta(get_the_ID(), '_wpcv_url', true);
            $brand = get_post_meta(get_the_ID(), '_wpcv_brand', true);
            $output .= '<div class="wpcv-coupon">
                <h3>' . get_the_title() . '</h3>
                <p><strong>Brand:</strong> ' . esc_html($brand) . '</p>
                <p><strong>Code:</strong> <span class="wpcv-code">' . esc_html($code) . '</span></p>
                <p><strong>Expires:</strong> ' . esc_html($expiry) . '</p>
                <p><a href="' . esc_url($url) . '" target="_blank" class="wpcv-claim-btn">Claim Deal</a></p>
            </div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }

    public function admin_menu() {
        add_options_page(
            'WP Coupon Vault Settings',
            'Coupon Vault',
            'manage_options',
            'wpcv-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>WP Coupon Vault Settings</h1><p>Settings page for premium features will be available in the Pro version.</p></div>';
    }
}

new WPCouponVault();
?>