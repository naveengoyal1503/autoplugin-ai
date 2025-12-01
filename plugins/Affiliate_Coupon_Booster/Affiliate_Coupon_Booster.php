<?php
/*
Plugin Name: Affiliate Coupon Booster
Plugin URI: https://example.com/affiliate-coupon-booster
Description: Creates a user-friendly coupon section with affiliate link cloaking and tracking to increase affiliate sales.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
License: GPL2
Text Domain: affiliate-coupon-booster
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponBooster {
    const VERSION = '1.0';
    private static $instance = null;

    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_shortcode('acb_coupons', array($this, 'render_coupons_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));

        add_filter('the_content', array($this, 'auto_cloak_affiliate_links'));
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_post_type() {
        $labels = array(
            'name' => __('Coupons', 'affiliate-coupon-booster'),
            'singular_name' => __('Coupon', 'affiliate-coupon-booster'),
            'add_new' => __('Add New Coupon', 'affiliate-coupon-booster'),
            'add_new_item' => __('Add New Coupon', 'affiliate-coupon-booster'),
            'edit_item' => __('Edit Coupon', 'affiliate-coupon-booster'),
            'new_item' => __('New Coupon', 'affiliate-coupon-booster'),
            'all_items' => __('All Coupons', 'affiliate-coupon-booster'),
            'view_item' => __('View Coupon', 'affiliate-coupon-booster'),
            'search_items' => __('Search Coupons', 'affiliate-coupon-booster'),
            'not_found' => __('No Coupons Found', 'affiliate-coupon-booster'),
            'not_found_in_trash' => __('No Coupons Found In Trash', 'affiliate-coupon-booster'),
            'menu_name' => __('Coupons', 'affiliate-coupon-booster')
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title','editor'),
            'menu_icon' => 'dashicons-tickets-alt',
        );

        register_post_type('acb_coupon', $args);
    }

    public function admin_menu() {
        add_menu_page(
            __('Affiliate Coupon Booster', 'affiliate-coupon-booster'),
            __('Coupon Booster', 'affiliate-coupon-booster'),
            'manage_options',
            'affiliate-coupon-booster',
            array($this, 'admin_page'),
            'dashicons-tickets-alt'
        );

        add_submenu_page(
            'affiliate-coupon-booster',
            __('Coupons', 'affiliate-coupon-booster'),
            __('Coupons', 'affiliate-coupon-booster'),
            'manage_options',
            'edit.php?post_type=acb_coupon'
        );

        add_submenu_page(
            'affiliate-coupon-booster',
            __('Settings', 'affiliate-coupon-booster'),
            __('Settings', 'affiliate-coupon-booster'),
            'manage_options',
            'acb_settings',
            array($this, 'settings_page')
        );
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>' . esc_html__('Affiliate Coupon Booster', 'affiliate-coupon-booster') . '</h1>';
        echo '<p>' . esc_html__('Manage and display coupons with affiliate link cloaking and tracking.', 'affiliate-coupon-booster') . '</p>';
        echo '</div>';
    }

    public function settings_init() {
        register_setting('acb_settings_group', 'acb_settings');

        add_settings_section(
            'acb_main_section',
            __('General Settings', 'affiliate-coupon-booster'),
            null,
            'acb_settings'
        );

        add_settings_field(
            'acb_prefix',
            __('Affiliate Link Prefix (Cloaking slug)', 'affiliate-coupon-booster'),
            array($this, 'field_prefix_render'),
            'acb_settings',
            'acb_main_section'
        );
    }

    public function field_prefix_render() {
        $options = get_option('acb_settings');
        $prefix = isset($options['prefix']) ? esc_attr($options['prefix']) : 'deal';
        echo '<input type="text" name="acb_settings[prefix]" value="' . $prefix . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('This slug is used in cloaked affiliate URLs: yoursite.com/{prefix}/couponid', 'affiliate-coupon-booster') . '</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Affiliate Coupon Booster Settings', 'affiliate-coupon-booster'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('acb_settings_group');
                do_settings_sections('acb_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_coupons_shortcode($atts) {
        $args = array('post_type' => 'acb_coupon', 'posts_per_page' => -1, 'post_status' => 'publish');
        $coupons = get_posts($args);
        if (!$coupons) return '<p>' . esc_html__('No coupons available.', 'affiliate-coupon-booster') . '</p>';

        $options = get_option('acb_settings');
        $prefix = isset($options['prefix']) ? sanitize_title($options['prefix']) : 'deal';

        $output = '<div class="acb-coupon-list">';
        foreach ($coupons as $coupon) {
            $meta = get_post_meta($coupon->ID, 'acb_coupon_meta', true);
            $code = isset($meta['code']) ? esc_html($meta['code']) : '';
            $desc = wp_trim_words($coupon->post_content, 20, '...');
            $aff_link = isset($meta['affiliate_link']) ? esc_url($meta['affiliate_link']) : '';
            if ($aff_link) {
                $cloaked_url = home_url("/{$prefix}/" . $coupon->ID);
                $output .= '<div class="acb-coupon-item" style="border:1px solid #ccc;padding:10px;margin:10px 0;">';
                $output .= '<h3><a href="' . esc_url($cloaked_url) . '" target="_blank" rel="nofollow noopener noreferrer">' . esc_html($coupon->post_title) . '</a></h3>';
                if ($code) {
                    $output .= '<p><strong>Coupon Code:</strong> <code>' . $code . '</code></p>';
                }
                $output .= '<p>' . esc_html($desc) . '</p>';
                $output .= '<p><a href="' . esc_url($cloaked_url) . '" target="_blank" rel="nofollow noopener noreferrer" style="background:#0073aa;color:#fff;padding:6px 12px;text-decoration:none;">Use Coupon</a></p>';
                $output .= '</div>';
            }
        }
        $output .= '</div>';
        return $output;
    }

    public function auto_cloak_affiliate_links($content) {
        $options = get_option('acb_settings');
        $prefix = isset($options['prefix']) ? sanitize_title($options['prefix']) : 'deal';

        // Detect if current URL is a cloaked coupon URL
        if (is_admin()) return $content;

        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        $parts = explode('/', $request_uri);
        if (count($parts) == 2 && $parts === $prefix && is_numeric($parts[1])) {
            $coupon_id = intval($parts[1]);
            $coupon_post = get_post($coupon_id);
            if ($coupon_post && $coupon_post->post_type === 'acb_coupon') {
                $meta = get_post_meta($coupon_id, 'acb_coupon_meta', true);
                if (isset($meta['affiliate_link'])) {
                    wp_redirect($meta['affiliate_link'], 301);
                    exit();
                }
            }
        }

        return $content;
    }
}

if (is_admin()) {
    add_action('add_meta_boxes', function() {
        add_meta_box('acb_coupon_meta', __('Coupon Details', 'affiliate-coupon-booster'), function($post) {
            wp_nonce_field('acb_save_coupon_meta', 'acb_coupon_meta_nonce');
            $meta = get_post_meta($post->ID, 'acb_coupon_meta', true);
            $affiliate_link = isset($meta['affiliate_link']) ? esc_url($meta['affiliate_link']) : '';
            $code = isset($meta['code']) ? esc_html($meta['code']) : '';
            ?>
            <p>
                <label for="acb_affiliate_link"><?php _e('Affiliate Link URL', 'affiliate-coupon-booster'); ?>:</label><br />
                <input type="url" id="acb_affiliate_link" name="acb_coupon_meta[affiliate_link]" value="<?php echo $affiliate_link; ?>" style="width:100%;" required />
            </p>
            <p>
                <label for="acb_coupon_code"><?php _e('Coupon Code (optional)', 'affiliate-coupon-booster'); ?>:</label><br />
                <input type="text" id="acb_coupon_code" name="acb_coupon_meta[code]" value="<?php echo $code; ?>" style="width:100%;" />
            </p>
            <?php
        }, 'acb_coupon', 'normal', 'high');
    });

    add_action('save_post', function($post_id) {
        if (!isset($_POST['acb_coupon_meta_nonce'])) return;
        if (!wp_verify_nonce($_POST['acb_coupon_meta_nonce'], 'acb_save_coupon_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (isset($_POST['post_type']) && 'acb_coupon' === $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) return;
            if (isset($_POST['acb_coupon_meta']) && is_array($_POST['acb_coupon_meta'])) {
                $meta = array();
                $meta['affiliate_link'] = sanitize_text_field($_POST['acb_coupon_meta']['affiliate_link']);
                $meta['code'] = sanitize_text_field($_POST['acb_coupon_meta']['code']);
                update_post_meta($post_id, 'acb_coupon_meta', $meta);
            }
        }
    });
}

AffiliateCouponBooster::get_instance();
