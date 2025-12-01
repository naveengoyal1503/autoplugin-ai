<?php
/*
Plugin Name: Affiliate Deal Booster
Description: Automatically create and display affiliate discount coupons to boost affiliate sales.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {

    private $coupon_post_type = 'adb_coupon';

    public function __construct() {
        add_action('init', [$this, 'register_coupon_post_type']);
        add_shortcode('adb_coupons', [$this, 'display_coupons']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('save_post_' . 'adb_coupon', [$this, 'validate_coupon_meta'], 10, 3);
    }

    public function register_coupon_post_type() {
        $labels = [
            'name' => 'ADB Coupons',
            'singular_name' => 'ADB Coupon',
            'add_new_item' => 'Add New Coupon',
            'edit_item' => 'Edit Coupon',
            'new_item' => 'New Coupon',
            'view_item' => 'View Coupon',
            'search_items' => 'Search Coupons',
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor'],
            'menu_position' => 25,
            'menu_icon' => 'dashicons-tickets-alt',
            'capability_type' => 'post',
            'capabilities' => ['edit_posts', 'edit_others_posts', 'publish_posts', 'read_post'],
            'map_meta_cap' => true,
        ];

        register_post_type($this->coupon_post_type, $args);
    }

    public function add_admin_menu() {
        add_submenu_page('edit.php?post_type=' . $this->coupon_post_type, 'Settings', 'Settings', 'manage_options', 'adb_settings', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('adb_settings_group', 'adb_affiliate_id');
        register_setting('adb_settings_group', 'adb_default_prefix');
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;

        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('adb_settings_group'); ?>
                <?php do_settings_sections('adb_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate ID (Your Affiliate Tracker)</th>
                        <td><input type="text" name="adb_affiliate_id" value="<?php echo esc_attr(get_option('adb_affiliate_id', '')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Default Coupon Prefix</th>
                        <td><input type="text" name="adb_default_prefix" value="<?php echo esc_attr(get_option('adb_default_prefix', 'SAVE')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>How to use:</strong></p>
            <ol>
                <li>Create new coupons under ‘ADB Coupons’ in admin.</li>
                <li>Set coupon title as deal name (e.g., 20% OFF SOFTWARE).</li>
                <li>In content, put affiliate URL with [affiliate_url] shortcode placeholder, or direct URLs.</li>
                <li>Use shortcode <code>[adb_coupons]</code> to display active coupons on any post/page.</li>
            </ol>
        </div>
        <?php
    }

    public function validate_coupon_meta($post_id, $post, $update) {
        // Can add future validations or auto modifications if needed
    }

    public function display_coupons($atts) {
        $args = [
            'post_type' => $this->coupon_post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ];
        $coupons = get_posts($args);
        if (!$coupons) return '<p>No coupons available currently.</p>';

        $affiliate_id = get_option('adb_affiliate_id', '');
        $default_prefix = get_option('adb_default_prefix', 'SAVE');
        $output = '<div class="adb-coupons">';

        foreach ($coupons as $coupon) {
            $content = apply_filters('the_content', $coupon->post_content);
            // Replace [affiliate_url] placeholder with affiliate ID if present
            $aff_url = '';
            // Extract any URLs from content
            if (preg_match('/\[affiliate_url\]/', $content)) {
                // Create a dummy affiliate URL for demo
                $aff_url = esc_url('https://example.com/?aff=' . urlencode($affiliate_id));
                $content = str_replace('[affiliate_url]', $aff_url, $content);
            }
            $output .= '<div class="adb-coupon-item" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;">
                <h3>' . esc_html($default_prefix . ' - ' . $coupon->post_title) . '</h3>
                <div>' . $content . '</div>
                <p><a href="' . esc_url($aff_url) . '" target="_blank" rel="nofollow noopener" style="background:#0073aa;color:#fff;padding:6px 12px;text-decoration:none;border-radius:3px;">Grab Deal</a></p>
                </div>';
        }
        $output .= '</div>';

        return $output;
    }
}

new AffiliateDealBooster();
