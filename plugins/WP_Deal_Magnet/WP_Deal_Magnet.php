<?php
/*
Plugin Name: WP Deal Magnet
Plugin URI: https://example.com/wp-deal-magnet
Description: Create dynamic coupon sections and deal lists to convert visitors with exclusive discounts and affiliate offers.
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Deal_Magnet.php
License: GPLv2 or later
Text Domain: wp-deal-magnet
*/

if (!defined('ABSPATH')) { exit; }

class WP_Deal_Magnet {
    public function __construct() {
        add_action('init', array($this, 'register_deal_post_type'));
        add_shortcode('wp_deal_magnet', array($this, 'render_deals_shortcode'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_deal_meta_box'));
        add_action('save_post', array($this, 'save_deal_meta'));
    }

    public function register_deal_post_type() {
        $labels = array(
            'name' => __('Deals', 'wp-deal-magnet'),
            'singular_name' => __('Deal', 'wp-deal-magnet'),
            'add_new' => __('Add New Deal', 'wp-deal-magnet'),
            'add_new_item' => __('Add New Deal', 'wp-deal-magnet'),
            'edit_item' => __('Edit Deal', 'wp-deal-magnet'),
            'new_item' => __('New Deal', 'wp-deal-magnet'),
            'view_item' => __('View Deal', 'wp-deal-magnet'),
            'search_items' => __('Search Deals', 'wp-deal-magnet'),
            'not_found' => __('No deals found', 'wp-deal-magnet'),
            'not_found_in_trash' => __('No deals found in Trash', 'wp-deal-magnet'),
            'menu_name' => __('WP Deal Magnet', 'wp-deal-magnet'),
        );
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'supports' => array('title','editor'),
            'menu_icon' => 'dashicons-tag',
            'has_archive' => false,
        );
        register_post_type('wpdm_deal', $args);
    }

    public function add_deal_meta_box() {
        add_meta_box(
            'wpdm_deal_details',
            __('Deal Details', 'wp-deal-magnet'),
            array($this, 'deal_meta_box_html'),
            'wpdm_deal',
            'normal',
            'default'
        );
    }

    public function deal_meta_box_html($post) {
        wp_nonce_field('wpdm_deal_nonce_action', 'wpdm_deal_nonce');

        $deal_code = get_post_meta($post->ID, '_wpdm_deal_code', true);
        $affiliate_url = get_post_meta($post->ID, '_wpdm_affiliate_url', true);
        $expiry_date = get_post_meta($post->ID, '_wpdm_expiry_date', true);

        echo '<p><label for="wpdm_deal_code">' . __('Coupon Code:', 'wp-deal-magnet') . '</label><br />';
        echo '<input type="text" id="wpdm_deal_code" name="wpdm_deal_code" value="' . esc_attr($deal_code) . '" style="width:100%;" /></p>';

        echo '<p><label for="wpdm_affiliate_url">' . __('Affiliate or Deal URL:', 'wp-deal-magnet') . '</label><br />';
        echo '<input type="url" id="wpdm_affiliate_url" name="wpdm_affiliate_url" value="' . esc_attr($affiliate_url) . '" style="width:100%;" /></p>';

        echo '<p><label for="wpdm_expiry_date">' . __('Expiry Date:', 'wp-deal-magnet') . '</label><br />';
        echo '<input type="date" id="wpdm_expiry_date" name="wpdm_expiry_date" value="' . esc_attr($expiry_date) . '" /></p>';
    }

    public function save_deal_meta($post_id) {
        if (!isset($_POST['wpdm_deal_nonce']) || !wp_verify_nonce($_POST['wpdm_deal_nonce'], 'wpdm_deal_nonce_action')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (isset($_POST['post_type']) && 'wpdm_deal' == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) return;
        }

        if (isset($_POST['wpdm_deal_code'])) {
            update_post_meta($post_id, '_wpdm_deal_code', sanitize_text_field($_POST['wpdm_deal_code']));
        }
        if (isset($_POST['wpdm_affiliate_url'])) {
            update_post_meta($post_id, '_wpdm_affiliate_url', esc_url_raw($_POST['wpdm_affiliate_url']));
        }
        if (isset($_POST['wpdm_expiry_date'])) {
            update_post_meta($post_id, '_wpdm_expiry_date', sanitize_text_field($_POST['wpdm_expiry_date']));
        }
    }

    public function render_deals_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'order' => 'DESC'
        ), $atts, 'wp_deal_magnet');

        $args = array(
            'post_type' => 'wpdm_deal',
            'posts_per_page' => intval($atts['count']),
            'orderby' => 'date',
            'order' => strtoupper($atts['order']),
            'meta_query' => array(
                array(
                    'key' => '_wpdm_expiry_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            ),
        );

        $deals = new WP_Query($args);
        if (!$deals->have_posts()) {
            return '<p>' . __('No current deals available.', 'wp-deal-magnet') . '</p>';
        }

        ob_start();
        echo '<div class="wpdm-deal-list">';
        while ($deals->have_posts()) {
            $deals->the_post();
            $deal_code = get_post_meta(get_the_ID(), '_wpdm_deal_code', true);
            $affiliate_url = get_post_meta(get_the_ID(), '_wpdm_affiliate_url', true);
            $expiry_date = get_post_meta(get_the_ID(), '_wpdm_expiry_date', true);
            echo '<div class="wpdm-deal-item" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;">';
            echo '<h3><a href="' . esc_url($affiliate_url) . '" target="_blank" rel="nofollow noopener">' . esc_html(get_the_title()) . '</a></h3>';
            if ($deal_code) {
                echo '<p><strong>Coupon Code:</strong> <span class="wpdm-coupon-code" style="background:#f8f8f8;padding:3px 6px;border-radius:3px;">' . esc_html($deal_code) . '</span></p>';
            }
            echo '<div class="wpdm-deal-description">' . wp_kses_post(get_the_content()) . '</div>';
            if ($expiry_date) {
                echo '<p><em>' . sprintf(__('Expires on: %s', 'wp-deal-magnet'), esc_html($expiry_date)) . '</em></p>';
            }
            echo '<p><a class="wpdm-claim-deal-btn" href="' . esc_url($affiliate_url) . '" target="_blank" rel="nofollow noopener" style="display:inline-block;padding:8px 15px;background:#0073aa;color:#fff;text-decoration:none;border-radius:4px;">' . __('Claim Deal', 'wp-deal-magnet') . '</a></p>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function add_admin_menu() {
        add_options_page(
            __('WP Deal Magnet Settings', 'wp-deal-magnet'),
            __('WP Deal Magnet', 'wp-deal-magnet'),
            'manage_options',
            'wpdm-settings',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('wpdm-settings-group', 'wpdm_default_deal_count', array('type' => 'integer', 'default' => 5));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('WP Deal Magnet Settings', 'wp-deal-magnet'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('wpdm-settings-group'); ?>
                <?php do_settings_sections('wpdm-settings-group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Default number of deals to show', 'wp-deal-magnet'); ?></th>
                        <td><input type="number" name="wpdm_default_deal_count" value="<?php echo esc_attr(get_option('wpdm_default_deal_count', 5)); ?>" min="1" max="20" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpdm-style', plugins_url('/style.css', __FILE__));
    }
}

new WP_Deal_Magnet();
