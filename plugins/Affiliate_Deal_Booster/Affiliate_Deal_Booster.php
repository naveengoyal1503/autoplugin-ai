<?php
/*
Plugin Name: Affiliate Deal Booster
Plugin URI: https://example.com/affiliate-deal-booster
Description: Automatically finds, verifies, and highlights affiliate coupons and deals in your posts to boost affiliate sales.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
License: GPL2
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AffiliateDealBooster {
    public function __construct() {
        add_filter('the_content', [$this, 'insert_affiliate_deals']);
        add_action('admin_menu', [$this, 'add_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    // Insert deals block at the end of post content
    public function insert_affiliate_deals($content) {
        if (is_single()) {
            $deals_html = $this->get_affiliate_deals_html(get_the_ID());
            $content .= $deals_html;
        }
        return $content;
    }

    // Stub function for fetching deals - In free version, static demo data
    private function get_affiliate_deals_html($post_id) {
        $deals = get_post_meta($post_id, '_adb_deals', true);
        if (!$deals || empty($deals)) {
            // default demo deals
            $deals = [
                [
                    'title' => 'Save 20% on Product A',
                    'url' => 'https://affiliate.example.com/product-a?ref=123',
                    'expires' => '2025-12-31'
                ],
                [
                    'title' => 'Get $10 off your order',
                    'url' => 'https://affiliate.example.com/offer?ref=123',
                    'expires' => '2025-11-30'
                ]
            ];
        }

        $html = '<div class="adb-deals" style="border:1px solid #ccc;padding:15px;margin-top:30px;background:#f9f9f9;">';
        $html .= '<h3>Exclusive Affiliate Deals:</h3><ul style="list-style-type:disc;margin-left:20px;">';
        foreach ($deals as $deal) {
            if (strtotime($deal['expires']) >= time()) {
                $html .= '<li><a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener">' . esc_html($deal['title']) . '</a> (Expires: ' . esc_html($deal['expires']) . ')</li>';
            }
        }
        $html .= '</ul></div>';
        return $html;
    }

    // Add settings page
    public function add_admin_page() {
        add_options_page(
            'Affiliate Deal Booster Settings',
            'Affiliate Deal Booster',
            'manage_options',
            'affiliate-deal-booster',
            [$this, 'settings_page_html']
        );
    }

    // Register settings
    public function register_settings() {
        register_setting('adb_settings_group', 'adb_deals_json', [
            'sanitize_callback' => [$this, 'sanitize_deals_json']
        ]);
        add_settings_section('adb_main_section', 'Affiliate Deals Settings', null, 'affiliate-deal-booster');
        add_settings_field('adb_deals_field', 'Affiliate Deals (JSON)', [$this, 'deals_field_html'], 'affiliate-deal-booster', 'adb_main_section');
    }

    // Sanitize JSON input for deals
    public function sanitize_deals_json($input) {
        $decoded = json_decode($input, true);
        if (!is_array($decoded)) {
            add_settings_error('adb_deals_json', 'invalid_json', 'Invalid JSON format for affiliate deals.');
            return get_option('adb_deals_json');
        }
        update_post_meta_for_all_posts('_adb_deals', $decoded);
        return $input;
    }

    // Display the textarea field in admin
    public function deals_field_html() {
        $val = esc_textarea(get_option('adb_deals_json', ''));
        echo '<textarea name="adb_deals_json" rows="10" cols="50" placeholder="[{\"title\":\"Deal title\", \"url\":\"https://example.com\", \"expires\":\"YYYY-MM-DD\"}]">' . $val . '</textarea>';
        echo '<p class="description">Enter deals JSON array. Example: [
{"title":"Get 30% off","url":"https://affiliate.link","expires":"2025-12-31"} ]</p>';
    }

    // Render admin settings page
    public function settings_page_html() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('adb_settings_group');
                do_settings_sections('affiliate-deal-booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

// Helper to update deals meta for all posts
function update_post_meta_for_all_posts($meta_key, $deals_array) {
    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => -1
    ];
    $posts = get_posts($args);
    foreach ($posts as $post) {
        update_post_meta($post->ID, $meta_key, $deals_array);
    }
}

new AffiliateDealBooster();