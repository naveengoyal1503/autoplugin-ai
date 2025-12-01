/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Plugin URI: https://example.com/wp-coupon-vault
 * Description: Manage and display exclusive coupons and deals for affiliate brands.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

define('WP_COUPON_VAULT_VERSION', '1.0');

class WPCouponVault {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_coupon_data'));
        add_shortcode('coupon_vault', array($this, 'display_coupons'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function register_post_type() {
        register_post_type('coupon',
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

    public function add_meta_boxes() {
        add_meta_box('coupon_details', 'Coupon Details', array($this, 'coupon_details_callback'), 'coupon');
    }

    public function coupon_details_callback($post) {
        wp_nonce_field('save_coupon_data', 'coupon_nonce');
        $code = get_post_meta($post->ID, '_coupon_code', true);
        $expiry = get_post_meta($post->ID, '_coupon_expiry', true);
        $url = get_post_meta($post->ID, '_coupon_url', true);
        $brand = get_post_meta($post->ID, '_coupon_brand', true);
        ?>
        <p>
            <label for="coupon_code">Coupon Code:</label>
            <input type="text" id="coupon_code" name="coupon_code" value="<?php echo esc_attr($code); ?>" class="widefat">
        </p>
        <p>
            <label for="coupon_expiry">Expiry Date:</label>
            <input type="date" id="coupon_expiry" name="coupon_expiry" value="<?php echo esc_attr($expiry); ?>" class="widefat">
        </p>
        <p>
            <label for="coupon_url">Affiliate URL:</label>
            <input type="url" id="coupon_url" name="coupon_url" value="<?php echo esc_attr($url); ?>" class="widefat">
        </p>
        <p>
            <label for="coupon_brand">Brand:</label>
            <input type="text" id="coupon_brand" name="coupon_brand" value="<?php echo esc_attr($brand); ?>" class="widefat">
        </p>
        <?php
    }

    public function save_coupon_data($post_id) {
        if (!isset($_POST['coupon_nonce']) || !wp_verify_nonce($_POST['coupon_nonce'], 'save_coupon_data')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array('coupon_code', 'coupon_expiry', 'coupon_url', 'coupon_brand');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_'.$field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function display_coupons($atts) {
        $atts = shortcode_atts(array('brand' => '', 'limit' => 10), $atts);
        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => $atts['limit'],
            'meta_query' => array()
        );
        if (!empty($atts['brand'])) {
            $args['meta_query'][] = array(
                'key' => '_coupon_brand',
                'value' => $atts['brand'],
                'compare' => 'LIKE'
            );
        }
        $coupons = new WP_Query($args);
        $output = '<div class="coupon-vault">';
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), '_coupon_code', true);
            $expiry = get_post_meta(get_the_ID(), '_coupon_expiry', true);
            $url = get_post_meta(get_the_ID(), '_coupon_url', true);
            $brand = get_post_meta(get_the_ID(), '_coupon_brand', true);
            $output .= '<div class="coupon-item">
                <h3>'.get_the_title().'</h3>
                <p><strong>Brand:</strong> '.$brand.'</p>
                <p><strong>Code:</strong> <span class="coupon-code">'.$code.'</span></p>
                <p><strong>Expires:</strong> '.$expiry.'</p>
                <a href="'.$url.'" target="_blank" class="button">Get Deal</a>
            </div>';
        }
        wp_reset_postdata();
        $output .= '</div>';
        return $output;
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=coupon', 'Settings', 'Settings', 'manage_options', 'coupon-vault-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>Coupon Vault Settings</h1><p>Configure your coupon display settings here.</p></div>';
    }
}

new WPCouponVault();
?>