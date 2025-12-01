/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Description: Create, manage, and display exclusive coupons and deals for your audience.
 * Version: 1.0
 * Author: Cozmo Labs
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPCouponVault {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_vault', array($this, 'shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function register_post_type() {
        register_post_type('coupon', array(
            'labels' => array(
                'name' => 'Coupons',
                'singular_name' => 'Coupon'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'custom-fields'),
            'menu_icon' => 'dashicons-tag'
        ));
    }

    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=coupon',
            'Settings',
            'Settings',
            'manage_options',
            'coupon-vault-settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['save_settings'])) {
            update_option('coupon_vault_style', sanitize_text_field($_POST['style']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $style = get_option('coupon_vault_style', 'default');
        ?>
        <div class="wrap">
            <h1>Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="style">Display Style</label></th>
                        <td>
                            <select name="style" id="style">
                                <option value="default" <?php selected($style, 'default'); ?>>Default</option>
                                <option value="compact" <?php selected($style, 'compact'); ?>>Compact</option>
                                <option value="grid" <?php selected($style, 'grid'); ?>>Grid</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php
    }

    public function enqueue_styles() {
        wp_enqueue_style('coupon-vault', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => '',
            'style' => get_option('coupon_vault_style', 'default')
        ), $atts);

        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => $atts['limit'],
            'tax_query' => $atts['category'] ? array(
                array(
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            ) : array()
        );

        $coupons = new WP_Query($args);
        $output = '<div class="coupon-vault coupon-vault-' . esc_attr($atts['style']) . '">';
        while ($coupons->have_posts()) {
            $coupons->the_post();
            $code = get_post_meta(get_the_ID(), 'coupon_code', true);
            $url = get_post_meta(get_the_ID(), 'coupon_url', true);
            $output .= '<div class="coupon-item">
                <h3>' . get_the_title() . '</h3>
                <p>' . get_the_content() . '</p>
                <p><strong>Code:</strong> <span class="coupon-code">' . esc_html($code) . '</span></p>
                <a href="' . esc_url($url) . '" class="coupon-link" target="_blank">Get Deal</a>
            </div>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }
}

new WPCouponVault();
