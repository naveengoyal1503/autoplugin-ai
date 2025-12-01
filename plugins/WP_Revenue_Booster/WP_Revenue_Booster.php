<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize your WordPress site's revenue with smart affiliate, ad, and sponsored content rotation.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('revenue_booster', array($this, 'shortcode'));
    }

    public function init() {
        // Register custom post type for campaigns
        register_post_type('revenue_campaign', array(
            'labels' => array('name' => 'Revenue Campaigns'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor')
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('revenue-booster-js', plugin_dir_url(__FILE__) . 'revenue-booster.js', array('jquery'), '1.0', true);
        wp_localize_script('revenue-booster-js', 'revenue_booster_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    public function admin_menu() {
        add_menu_page('Revenue Booster', 'Revenue Booster', 'manage_options', 'revenue-booster', array($this, 'admin_page'));
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>WP Revenue Booster</h1>';
        echo '<p>Manage your revenue campaigns and optimize affiliate links, ads, and sponsored content.</p>';
        echo '<p><a href="' . admin_url('post-new.php?post_type=revenue_campaign') . '">Create New Campaign</a></p>';
        echo '</div>';
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'campaign' => '',
            'type' => 'affiliate' // affiliate, ad, sponsored
        ), $atts);

        $campaign = get_page_by_title($atts['campaign'], OBJECT, 'revenue_campaign');
        if (!$campaign) return '';

        $content = get_post_meta($campaign->ID, '_revenue_content', true);
        if (!$content) return '';

        // Simple rotation logic
        $variants = explode('||', $content);
        $selected = $variants[array_rand($variants)];

        return '<div class="revenue-booster-' . esc_attr($atts['type']) . '">' . do_shortcode($selected) . '</div>';
    }
}

new WP_Revenue_Booster();

// AJAX handler for tracking clicks
add_action('wp_ajax_track_revenue_click', 'track_revenue_click');
add_action('wp_ajax_nopriv_track_revenue_click', 'track_revenue_click');
function track_revenue_click() {
    $campaign_id = intval($_POST['campaign_id']);
    $variant = sanitize_text_field($_POST['variant']);
    $count_key = '_click_count_' . $variant;
    $count = get_post_meta($campaign_id, $count_key, true);
    update_post_meta($campaign_id, $count_key, ($count ? $count + 1 : 1));
    wp_die();
}

// JavaScript for tracking
function revenue_booster_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.revenue-booster-affiliate a, .revenue-booster-ad a, .revenue-booster-sponsored a').on('click', function() {
            var campaignId = $(this).data('campaign-id');
            var variant = $(this).data('variant');
            $.post(revenue_booster_ajax.ajax_url, {
                action: 'track_revenue_click',
                campaign_id: campaignId,
                variant: variant
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'revenue_booster_js');
