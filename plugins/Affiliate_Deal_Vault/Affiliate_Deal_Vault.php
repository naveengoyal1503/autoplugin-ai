<?php
/*
Plugin Name: Affiliate Deal Vault
Plugin URI: https://example.com/affiliate-deal-vault
Description: Curate and monetize affiliate coupons and deals effectively.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Vault.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateDealVault {
    private $version = '1.0';
    private $plugin_slug = 'affiliate-deal-vault';

    public function __construct() {
        add_action('init', array($this, 'register_deal_post_type'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_deal_meta_boxes'));
        add_action('save_post', array($this, 'save_deal_meta')); 
        add_shortcode('affiliate_deals', array($this, 'deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function register_deal_post_type() {
        $labels = array(
            'name' => __('Deals','affiliate-deal-vault'),
            'singular_name' => __('Deal','affiliate-deal-vault'),
            'add_new' => __('Add New Deal','affiliate-deal-vault'),
            'add_new_item' => __('Add New Deal','affiliate-deal-vault'),
            'edit_item' => __('Edit Deal','affiliate-deal-vault'),
            'new_item' => __('New Deal','affiliate-deal-vault'),
            'view_item' => __('View Deal','affiliate-deal-vault'),
            'search_items' => __('Search Deals','affiliate-deal-vault'),
            'not_found' => __('No Deals found','affiliate-deal-vault'),
            'not_found_in_trash' => __('No Deals found in Trash','affiliate-deal-vault'),
            'menu_name' => __('Affiliate Deals','affiliate-deal-vault'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'show_ui' => true,
            'supports' => array('title','editor'),
            'menu_icon' => 'dashicons-tag',
            'rewrite' => array('slug' => 'affiliate-deals'),
        );

        register_post_type('affiliate_deal', $args);
    }

    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=affiliate_deal',
            'Settings',
            'Settings',
            'manage_options',
            'affiliate_deal_settings',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized','affiliate-deal-vault'));
        }
        if (isset($_POST['save_deal_vault_settings'])) {
            check_admin_referer('affiliate_deal_vault_settings_nonce');
            $affiliate_id = sanitize_text_field($_POST['affiliate_id']);
            update_option('ad_vault_affiliate_id', $affiliate_id);
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        $affiliate_id = get_option('ad_vault_affiliate_id', '');
        echo '<div class="wrap"><h1>Affiliate Deal Vault Settings</h1><form method="post">';
        wp_nonce_field('affiliate_deal_vault_settings_nonce');
        echo '<table class="form-table"><tr><th><label for="affiliate_id">Default Affiliate ID / Tracking Code</label></th><td><input type="text" name="affiliate_id" id="affiliate_id" class="regular-text" value="' . esc_attr($affiliate_id) . '" /></td></tr></table>';
        echo '<p class="submit"><input type="submit" name="save_deal_vault_settings" class="button-primary" value="Save Settings" /></p></form></div>';
    }

    public function add_deal_meta_boxes() {
        add_meta_box('deal_details', 'Deal Details', array($this, 'deal_meta_box_callback'), 'affiliate_deal', 'normal', 'high');
    }

    public function deal_meta_box_callback($post) {
        wp_nonce_field('save_deal_meta', 'deal_meta_nonce');

        $coupon_code = get_post_meta($post->ID, '_deal_coupon_code', true);
        $affiliate_link = get_post_meta($post->ID, '_deal_affiliate_link', true);
        $expiration_date = get_post_meta($post->ID, '_deal_expiration_date', true);

        echo '<p><label for="deal_coupon_code">Coupon Code (optional): </label><input type="text" id="deal_coupon_code" name="deal_coupon_code" value="' . esc_attr($coupon_code) . '" style="width:100%;" /></p>';
        echo '<p><label for="deal_affiliate_link">Affiliate Link (required): </label><input type="url" id="deal_affiliate_link" name="deal_affiliate_link" value="' . esc_attr($affiliate_link) . '" style="width:100%;" required /></p>';
        echo '<p><label for="deal_expiration_date">Expiration Date (optional): </label><input type="date" id="deal_expiration_date" name="deal_expiration_date" value="' . esc_attr($expiration_date) . '" /></p>';

    }

    public function save_deal_meta($post_id) {
        if (!isset($_POST['deal_meta_nonce']) || !wp_verify_nonce($_POST['deal_meta_nonce'], 'save_deal_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['deal_coupon_code'])) {
            update_post_meta($post_id, '_deal_coupon_code', sanitize_text_field($_POST['deal_coupon_code']));
        }
        if (isset($_POST['deal_affiliate_link'])) {
            update_post_meta($post_id, '_deal_affiliate_link', esc_url_raw($_POST['deal_affiliate_link']));
        }
        if (isset($_POST['deal_expiration_date'])) {
            update_post_meta($post_id, '_deal_expiration_date', sanitize_text_field($_POST['deal_expiration_date']));
        }
    }

    public function deals_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 10,
            'show_expired' => 'no'
        ), $atts, 'affiliate_deals');

        $meta_query = array();
        if ($atts['show_expired'] === 'no') {
            $today = date('Y-m-d');
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_deal_expiration_date',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_deal_expiration_date',
                    'compare' => 'NOT EXISTS'
                )
            );
        }

        $query = new WP_Query(array(
            'post_type' => 'affiliate_deal',
            'posts_per_page' => intval($atts['count']),
            'meta_query' => $meta_query,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        if (!$query->have_posts()) {
            return '<p>No deals found.</p>';
        }

        ob_start();
        echo '<div class="ad-vault-deals">';
        while ($query->have_posts()) {
            $query->the_post();
            $coupon_code = get_post_meta(get_the_ID(), '_deal_coupon_code', true);
            $affiliate_link = get_post_meta(get_the_ID(), '_deal_affiliate_link', true);
            $expiration_date = get_post_meta(get_the_ID(), '_deal_expiration_date', true);

            $title = get_the_title();
            $content = get_the_content();

            $button_text = 'Get Deal';
            if ($coupon_code) {
                $button_text = 'Use Coupon';
            }

            echo '<div class="ad-vault-deal" style="border:1px solid #ccc; padding:15px; margin-bottom:15px;">';
            echo '<h3>' . esc_html($title) . '</h3>';
            if ($content) {
                echo '<div>' . wp_kses_post(wpautop($content)) . '</div>';
            }
            if ($coupon_code) {
                echo '<p><strong>Coupon Code: </strong><code>' . esc_html($coupon_code) . '</code></p>';
            }
            if ($expiration_date) {
                echo '<p><small>Expires on: ' . esc_html($expiration_date) . '</small></p>';
            }

            // Append default affiliate ID if set and link does not have tracking
            $default_affiliate_id = get_option('ad_vault_affiliate_id');
            $final_link = $affiliate_link;
            if ($default_affiliate_id && strpos($affiliate_link, $default_affiliate_id) === false) {
                // Basic append parameter for affiliate tracking
                $delimiter = (strpos($affiliate_link, '?') === false) ? '?' : '&';
                $final_link = $affiliate_link . $delimiter . 'aff_id=' . urlencode($default_affiliate_id);
            }

            echo '<p><a href="' . esc_url($final_link) . '" target="_blank" rel="nofollow noopener" style="background:#0073aa;color:#fff;padding:8px 12px;text-decoration:none;border-radius:3px;" >' . esc_html($button_text) . '</a></p>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();

        return ob_get_clean();
    }

    public function enqueue_styles() {
        wp_register_style('affiliate-deal-vault-style', plugins_url('style.css', __FILE__));
        wp_enqueue_style('affiliate-deal-vault-style');
    }
}

new AffiliateDealVault();
