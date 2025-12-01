<?php
/*
Plugin Name: Affiliate SmartLink Manager
Description: Intelligent affiliate link management, cloaking, and rotation for maximizing affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_SmartLink_Manager.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateSmartLinkManager {
    private $version = '1.0';

    public function __construct() {
        add_action('init', array($this, 'register_smartlink_post_type'));
        add_shortcode('smartlink', array($this, 'smartlink_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aslm_track_click', array($this, 'track_click_ajax'));
        add_action('wp_ajax_nopriv_aslm_track_click', array($this, 'track_click_ajax'));
    }

    public function register_smartlink_post_type() {
        $labels = array(
            'name' => 'SmartLinks',
            'singular_name' => 'SmartLink',
            'menu_name' => 'Affiliate SmartLinks',
            'add_new_item' => 'Add New SmartLink',
            'edit_item' => 'Edit SmartLink',
            'new_item' => 'New SmartLink',
            'view_item' => 'View SmartLink',
            'search_items' => 'Search SmartLinks'
        );
        $args = array(
            'public' => false,
            'show_ui' => true,
            'labels' => $labels,
            'supports' => array('title','editor'),
            'menu_icon' => 'dashicons-randomize',
            'capability_type' => 'post',
            'hierarchical' => false
        );
        register_post_type('aslm_smartlink', $args);
    }

    // Shortcode [smartlink id="123"] - outputs cloaked link with rotation
    public function smartlink_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $post_id = intval($atts['id']);
        if (!$post_id) return '';

        $links = get_post_meta($post_id, '_aslm_links', true);
        if (empty($links) || !is_array($links)) return '';

        // Rotate links
        $link_to_use = $this->get_rotated_link($links);

        $slug = sanitize_title_with_dashes(get_the_title($post_id));
        $url = admin_url('admin-ajax.php?action=aslm_redirect&link=' . urlencode($link_to_use) . '&id=' . $post_id);

        // Return cloaked link
        return '<a href="' . esc_url($url) . '" class="aslm-aff-link" target="_blank" rel="nofollow noopener">' . esc_html(get_the_title($post_id)) . '</a>';
    }

    private function get_rotated_link($links) {
        $total = count($links);
        $index = rand(0, $total - 1);
        return $links[$index];
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aslm-script', plugin_dir_url(__FILE__) . 'aslm.js', array('jquery'), $this->version, true);
        wp_localize_script('aslm-script', 'aslm_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function track_click_ajax() {
        // For analytics or conversion tracking (stub)
        wp_send_json_success();
    }
}

new AffiliateSmartLinkManager();

// Redirect handler
add_action('init', function() {
    if (isset($_GET['action']) && $_GET['action']==='aslm_redirect' && isset($_GET['link'])) {
        $link = esc_url_raw($_GET['link']);
        $post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Optionally track click in DB here

        // Redirect with 302
        wp_redirect($link, 302);
        exit;
    }
});