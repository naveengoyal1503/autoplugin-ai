/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Manage and display affiliate coupons with tracking, shortcodes, and widgets for maximum conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_cpt();
    }

    public function create_cpt() {
        register_post_type('acv_coupon', array(
            'labels' => array('name' => 'Coupons', 'singular_name' => 'Coupon'),
            'public' => true,
            'show_ui' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart',
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=acv_coupon', 'Coupon Settings', 'Settings', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Default Affiliate Disclaimer</th>
                        <td><textarea name="acv_options[disclaimer]" rows="4" cols="50"><?php echo esc_attr(get_option('acv_options')['disclaimer'] ?? ''); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('category' => '', 'limit' => 10), $atts);
        $args = array(
            'post_type' => 'acv_coupon',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish'
        );
        if ($atts['category']) {
            $args['tax_query'] = array(array('taxonomy' => 'category', 'field' => 'slug', 'terms' => $atts['category']));
        }
        $coupons = get_posts($args);
        $output = '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $aff_link = get_post_meta($coupon->ID, 'affiliate_link', true);
            $code = get_post_meta($coupon->ID, 'coupon_code', true);
            $discount = get_post_meta($coupon->ID, 'discount', true);
            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . get_the_title($coupon->ID) . '</h3>';
            $output .= '<p>' . get_the_excerpt($coupon->ID) . '</p>';
            if ($code) $output .= '<strong>Code: ' . $code . '</strong><br>';
            if ($discount) $output .= '<span class="acv-discount">' . $discount . ' OFF</span><br>';
            if ($aff_link) $output .= '<a href="' . esc_url($aff_link) . '" target="_blank" class="acv-btn" rel="nofollow">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        $this->create_cpt();
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Add meta boxes
function acv_add_meta_boxes() {
    add_meta_box('acv_coupon_details', 'Coupon Details', 'acv_coupon_meta_box_callback', 'acv_coupon');
}
add_action('add_meta_boxes', 'acv_add_meta_boxes');

function acv_coupon_meta_box_callback($post) {
    wp_nonce_field('acv_meta_nonce', 'acv_nonce');
    $link = get_post_meta($post->ID, 'affiliate_link', true);
    $code = get_post_meta($post->ID, 'coupon_code', true);
    $discount = get_post_meta($post->ID, 'discount', true);
    echo '<p><label>Affiliate Link: <input type="url" name="affiliate_link" value="' . esc_attr($link) . '" style="width:100%;"></label></p>';
    echo '<p><label>Coupon Code: <input type="text" name="coupon_code" value="' . esc_attr($code) . '" style="width:100%;"></label></p>';
    echo '<p><label>Discount: <input type="text" name="discount" value="' . esc_attr($discount) . '" style="width:100%;"></label></p>';
}

function acv_save_meta($post_id) {
    if (!isset($_POST['acv_nonce']) || !wp_verify_nonce($_POST['acv_nonce'], 'acv_meta_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    update_post_meta($post_id, 'affiliate_link', sanitize_url($_POST['affiliate_link'] ?? ''));
    update_post_meta($post_id, 'coupon_code', sanitize_text_field($_POST['coupon_code'] ?? ''));
    update_post_meta($post_id, 'discount', sanitize_text_field($_POST['discount'] ?? ''));
}
add_action('save_post', 'acv_save_meta');

// Widget
class ACV_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('acv_widget', 'Affiliate Coupon Vault');
    }
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo do_shortcode('[acv_coupons limit="' . ($instance['limit'] ?? 5) . '"]');
        echo $args['after_widget'];
    }
    public function form($instance) {
        $limit = $instance['limit'] ?? 5;
        echo '<p><label>Number of Coupons: <input type="number" name="' . $this->get_field_name('limit') . '" value="' . $limit . '"></label></p>';
    }
    public function update($new, $old) {
        return $new;
    }
}
add_action('widgets_init', function() { register_widget('ACV_Widget'); });

// Basic CSS
$css = ".acv-coupons { display: grid; gap: 20px; } .acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; } .acv-discount { background: #ff4500; color: white; padding: 5px 10px; border-radius: 4px; font-weight: bold; } .acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; } .acv-btn:hover { background: #005a87; }";
wp_add_inline_style('acv-style', $css);