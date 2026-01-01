/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates, manages, and displays personalized affiliate coupon codes and deals to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Create custom post type for coupons
        register_post_type('affiliate_coupon', array(
            'labels' => array(
                'name' => 'Affiliate Coupons',
                'singular_name' => 'Affiliate Coupon'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=affiliate_coupon', 'Coupon Settings', 'Settings', 'manage_options', 'affiliate-coupon-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_affiliate_ids', sanitize_text_field($_POST['affiliate_ids']));
            update_option('acv_promo_base', sanitize_text_field($_POST['promo_base']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliate_ids = get_option('acv_affiliate_ids', '');
        $promo_base = get_option('acv_promo_base', 'SAVE10');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Program IDs (comma-separated)</th>
                        <td><input type="text" name="affiliate_ids" value="<?php echo esc_attr($affiliate_ids); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Promo Code Base</th>
                        <td><input type="text" name="promo_base" value="<?php echo esc_attr($promo_base); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'category' => ''
        ), $atts);

        $args = array(
            'post_type' => 'affiliate_coupon',
            'posts_per_page' => $atts['count'],
            'post_status' => 'publish'
        );
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'coupon_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }

        $coupons = get_posts($args);
        if (empty($coupons)) {
            return '<p>No coupons available.</p>';
        }

        $output = '<div class="affiliate-coupon-vault">';
        foreach ($coupons as $coupon) {
            $affiliate_link = get_post_meta($coupon->ID, 'affiliate_link', true);
            $discount = get_post_meta($coupon->ID, 'discount', true);
            $promo_code = get_post_meta($coupon->ID, 'promo_code', true);
            if (empty($promo_code)) {
                $promo_code = $this->generate_promo_code($coupon->post_title);
                update_post_meta($coupon->ID, 'promo_code', $promo_code);
            }
            $output .= '<div class="coupon-item">';
            $output .= '<h3>' . esc_html($coupon->post_title) . '</h3>';
            $output .= '<p>Discount: ' . esc_html($discount) . '%</p>';
            $output .= '<p>Code: <strong>' . esc_html($promo_code) . '</strong></p>';
            $output .= '<a href="' . esc_url($affiliate_link) . '" target="_blank" class="coupon-btn" data-coupon="' . esc_attr($promo_code) . '">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    private function generate_promo_code($title) {
        $base = get_option('acv_promo_base', 'SAVE10');
        $random = wp_generate_password(4, false);
        return strtoupper($base . $random);
    }

    public function activate() {
        $this->init();
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Add meta boxes
function acv_add_meta_boxes() {
    add_meta_box('acv_coupon_details', 'Coupon Details', 'acv_coupon_meta_box_callback', 'affiliate_coupon');
}
add_action('add_meta_boxes', 'acv_add_meta_boxes');

function acv_coupon_meta_box_callback($post) {
    wp_nonce_field('acv_meta_nonce', 'acv_nonce');
    $link = get_post_meta($post->ID, 'affiliate_link', true);
    $discount = get_post_meta($post->ID, 'discount', true);
    echo '<p><label>Affiliate Link: <input type="url" name="affiliate_link" value="' . esc_attr($link) . '" style="width:100%;"></label></p>';
    echo '<p><label>Discount %: <input type="number" name="discount" value="' . esc_attr($discount) . '" style="width:100%;"></label></p>';
}

function acv_save_meta($post_id) {
    if (!isset($_POST['acv_nonce']) || !wp_verify_nonce($_POST['acv_nonce'], 'acv_meta_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    update_post_meta($post_id, 'affiliate_link', sanitize_url($_POST['affiliate_link']));
    update_post_meta($post_id, 'discount', sanitize_text_field($_POST['discount']));
}
add_action('save_post', 'acv_save_meta');

// CSS
$css = ".affiliate-coupon-vault { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; } .coupon-item { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; } .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; } .coupon-btn:hover { background: #005a87; }";
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $css);

// JS
$js = "jQuery(document).ready(function($) { $('.coupon-btn').click(function(e) { var code = $(this).data('coupon'); navigator.clipboard.writeText(code).then(function() { alert('Coupon code copied: ' + code); }); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'script.js', $js);