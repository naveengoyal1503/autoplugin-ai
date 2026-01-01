/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create and manage exclusive coupon codes and affiliate deals to boost conversions and commissions.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_post_type();
        wp_register_style('acv-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
    }

    public function create_post_type() {
        register_post_type('acv_coupon', array(
            'labels' => array(
                'name' => 'Coupons',
                'singular_name' => 'Coupon'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart'
        ));
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=acv_coupon', 'Coupon Settings', 'Settings', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('acv_affiliate_links', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="affiliate_links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea><br>
                        <small>One link per line: Brand|Affiliate URL|Discount Code</small></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'acv_save'); ?>
            </form>
        </div>
        <?php
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts);
        $coupons = get_posts(array(
            'post_type' => 'acv_coupon',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish'
        ));
        $output = '<div class="acv-vault">';
        $links = explode("\n", get_option('acv_affiliate_links', ''));
        foreach ($coupons as $coupon) {
            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . get_the_title($coupon->ID) . '</h3>';
            $output .= '<div>' . apply_filters('the_content', $coupon->post_content) . '</div>';
            $output .= $this->get_random_affiliate_link($links);
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    private function get_random_affiliate_link($links) {
        $link = trim($links[array_rand($links)] ?? '');
        if (strpos($link, '|') !== false) {
            list($brand, $url, $code) = explode('|', $link, 3);
            return '<p><strong>Exclusive Deal:</strong> ' . esc_html($brand) . ' - Code: <code>' . esc_html($code) . '</code> <a href="' . esc_url($url) . '" target="_blank" rel="nofollow">Shop Now & Save</a></p>';
        }
        return '';
    }

    public function activate() {
        $this->create_post_type();
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Enqueue admin styles
function acv_admin_styles($hook) {
    if (strpos($hook, 'acv_coupon') !== false || $hook == 'acv_coupon_page_acv-settings') {
        wp_enqueue_style('acv-admin-style');
    }
}
add_action('admin_enqueue_scripts', 'acv_admin_styles');

// Basic frontend styles
add_action('wp_head', function() {
    echo '<style>.acv-vault .acv-coupon {border:1px solid #ddd; padding:15px; margin:10px 0; background:#f9f9f9;}.acv-coupon code {background:#fff; padding:2px 4px;}</style>';
});

// Pro upgrade notice
function acv_pro_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, analytics, and custom branding for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_notice');