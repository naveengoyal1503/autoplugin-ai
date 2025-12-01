<?php
/*
Plugin Name: Affiliate Deal Booster
Plugin URI: https://example.com/affiliatedealbooster
Description: Fetches and displays affiliate discount deals and tracks clicks to boost affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
License: GPL2
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {

    private $option_name = 'adb_affiliate_links';

    public function __construct() {
        add_action('admin_menu', array($this, 'adb_admin_menu'));
        add_action('admin_init', array($this, 'adb_settings_init'));
        add_shortcode('affiliate_deals', array($this, 'render_affiliate_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_adb_track_click', array($this, 'track_click')); 
        add_action('wp_ajax_nopriv_adb_track_click', array($this, 'track_click'));
    }

    public function adb_admin_menu() {
        add_options_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'affiliate-deal-booster', array($this, 'settings_page'));
    }

    public function adb_settings_init() {
        register_setting('adb_settings', 'adb_options');

        add_settings_section(
            'adb_section_deals',
            __('Affiliate Deals Settings', 'adb'),
            null,
            'adb_settings'
        );

        add_settings_field(
            'adb_field_affiliates',
            __('Affiliate Deals (JSON)', 'adb'),
            array($this, 'field_affiliates_cb'),
            'adb_settings',
            'adb_section_deals'
        );
    }

    public function field_affiliates_cb() {
        $options = get_option('adb_options');
        $value = isset($options['affiliate_deals_json']) ? esc_textarea($options['affiliate_deals_json']) : '';
        echo '<textarea rows="10" cols="50" name="adb_options[affiliate_deals_json]" placeholder="[{\"title\":\"Deal 1\",\"url\":\"https://affiliate1.com/product?ref=123\",\"description\":\"10% off on product\"}]">' . $value . '</textarea><p class="description">Enter affiliate deals in JSON format with title, url, and description.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h2>Affiliate Deal Booster Settings</h2>
            <form action="options.php" method="post">
                <?php
                settings_fields('adb_settings');
                do_settings_sections('adb_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_affiliate_deals($atts) {
        $options = get_option('adb_options');
        if (empty($options['affiliate_deals_json'])) return '<p>No deals available at the moment.</p>';

        $deals = json_decode($options['affiliate_deals_json'], true);
        if (!is_array($deals) || empty($deals)) return '<p>No valid deals found.</p>';

        ob_start();
        echo '<ul class="adb-deals-list">';
        foreach ($deals as $index => $deal) {
            $title = isset($deal['title']) ? esc_html($deal['title']) : 'Affiliate Deal';
            $url = isset($deal['url']) ? esc_url($deal['url']) : '#';
            $desc = isset($deal['description']) ? esc_html($deal['description']) : '';
            $link_id = 'adb_link_' . $index;
            echo "<li><a href=\"$url\" target=\"_blank\" rel=\"nofollow noopener noreferrer\" class=\"adb-affiliate-link\" data-linkid=\"$link_id\">$title</a> - <span>$desc</span></li>";
        }
        echo '</ul>';
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('adb-script', plugin_dir_url(__FILE__) . 'adb-script.js', array('jquery'), '1.0', true);

        // Localize ajax URL for tracking clicks
        wp_localize_script('adb-script', 'adb_ajax_obj', array('ajaxurl' => admin_url('admin-ajax.php')));

        wp_add_inline_script('adb-script', "
            jQuery(document).on('click', '.adb-affiliate-link', function(e) {
                var linkId = jQuery(this).data('linkid');
                jQuery.post(adb_ajax_obj.ajaxurl, {action: 'adb_track_click', link_id: linkId});
            });
        ");
    }

    public function track_click() {
        $link_id = isset($_POST['link_id']) ? sanitize_text_field($_POST['link_id']) : '';
        if (!$link_id) wp_send_json_error('Invalid link ID');

        // Store or update click count in options
        $click_data = get_option('adb_click_data', array());
        if (!isset($click_data[$link_id])) {
            $click_data[$link_id] = 0;
        }
        $click_data[$link_id]++;
        update_option('adb_click_data', $click_data);

        wp_send_json_success('Click recorded');
    }

}

new AffiliateDealBooster();

?>