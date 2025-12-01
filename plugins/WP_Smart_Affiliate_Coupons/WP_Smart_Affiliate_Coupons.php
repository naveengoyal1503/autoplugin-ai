/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Affiliate_Coupons.php
*/
<?php
/**
 * Plugin Name: WP Smart Affiliate Coupons
 * Description: Create and display affiliate coupons dynamically to increase conversions and earnings.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class WPSmartAffiliateCoupons {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_shortcode('affiliate_coupon', array($this, 'display_coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => 'Affiliate Coupons',
            'singular_name' => 'Affiliate Coupon',
            'add_new' => 'Add New Coupon',
            'add_new_item' => 'Add New Affiliate Coupon',
            'edit_item' => 'Edit Affiliate Coupon',
            'new_item' => 'New Affiliate Coupon',
            'view_item' => 'View Affiliate Coupon',
            'search_items' => 'Search Coupons',
            'not_found' => 'No coupons found',
            'not_found_in_trash' => 'No coupons found in Trash',
            'all_items' => 'All Coupons',
            'menu_name' => 'Affiliate Coupons',
            'name_admin_bar' => 'Affiliate Coupon'
        );
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'supports' => array('title', 'editor'),
            'menu_position' => 25,
            'menu_icon' => 'dashicons-tickets-alt'
        );
        register_post_type('affiliate_coupon', $args);
    }

    public function enqueue_styles() {
        wp_enqueue_style('wpsac-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    // Shortcode to display coupon
    public function display_coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupon_id = intval($atts['id']);
        if (!$coupon_id) return 'Coupon ID required.';

        $post = get_post($coupon_id);
        if (!$post || $post->post_type !== 'affiliate_coupon') return 'Coupon not found.';

        // Meta fields
        $coupon_code = get_post_meta($coupon_id, '_wpsac_code', true);
        $affiliate_url = get_post_meta($coupon_id, '_wpsac_aff_url', true);
        $expiry_date = get_post_meta($coupon_id, '_wpsac_expiry', true);

        // Check expiry
        if ($expiry_date && strtotime($expiry_date) < time()) {
            return '<div class="wpsac-coupon expired">Coupon expired</div>';
        }

        if (empty($coupon_code) || empty($affiliate_url)) return 'Coupon information incomplete.';

        ob_start();
        ?>
        <div class="wpsac-coupon">
            <span class="wpsac-code"><?php echo esc_html($coupon_code); ?></span>
            <a href="<?php echo esc_url($affiliate_url); ?>" target="_blank" rel="nofollow noopener" class="wpsac-button">Use Coupon</a>
        </div>
        <?php
        return ob_get_clean();
    }
}

new WPSmartAffiliateCoupons();

// Add Meta Boxes
function wpsac_add_meta_boxes() {
    add_meta_box('wpsac_details', 'Coupon Details', 'wpsac_coupon_meta_box', 'affiliate_coupon', 'normal', 'high');
}
add_action('add_meta_boxes', 'wpsac_add_meta_boxes');

function wpsac_coupon_meta_box($post) {
    wp_nonce_field('wpsac_save_meta', 'wpsac_nonce');
    $code = get_post_meta($post->ID, '_wpsac_code', true);
    $aff_url = get_post_meta($post->ID, '_wpsac_aff_url', true);
    $expiry = get_post_meta($post->ID, '_wpsac_expiry', true);
    ?>
    <p><label for="wpsac_code">Coupon Code:</label><br>
    <input type="text" name="wpsac_code" id="wpsac_code" value="<?php echo esc_attr($code); ?>" style="width:100%;" required></p>

    <p><label for="wpsac_aff_url">Affiliate URL:</label><br>
    <input type="url" name="wpsac_aff_url" id="wpsac_aff_url" value="<?php echo esc_url($aff_url); ?>" style="width:100%;" required></p>

    <p><label for="wpsac_expiry">Expiry Date (optional):</label><br>
    <input type="date" name="wpsac_expiry" id="wpsac_expiry" value="<?php echo esc_attr($expiry); ?>"></p>
    <?php
}

function wpsac_save_meta($post_id) {
    if (!isset($_POST['wpsac_nonce'])) return;
    if (!wp_verify_nonce($_POST['wpsac_nonce'], 'wpsac_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['wpsac_code'])) {
        update_post_meta($post_id, '_wpsac_code', sanitize_text_field($_POST['wpsac_code']));
    }
    if (isset($_POST['wpsac_aff_url'])) {
        update_post_meta($post_id, '_wpsac_aff_url', esc_url_raw($_POST['wpsac_aff_url']));
    }
    if (isset($_POST['wpsac_expiry'])) {
        update_post_meta($post_id, '_wpsac_expiry', sanitize_text_field($_POST['wpsac_expiry']));
    }
}
add_action('save_post_affiliate_coupon', 'wpsac_save_meta');

// Simple CSS for coupon styling
add_action('wp_head', function() {
    ?>
    <style>
    .wpsac-coupon { display:inline-block; background:#f9f9f9; border:1px solid #ccc; padding:10px 15px; border-radius:5px; font-family: Arial,sans-serif; margin:10px 0; }
    .wpsac-code { font-weight:bold; font-size:1.2em; margin-right:10px; color:#d35400; letter-spacing:1px; }
    .wpsac-button { background:#d35400; color:#fff; padding:5px 12px; text-decoration:none; border-radius:3px; font-weight:bold; }
    .wpsac-button:hover { background:#e67e22; }
    .wpsac-coupon.expired { color: #999; font-style: italic; font-weight: normal; }
    </style>
    <?php
});
