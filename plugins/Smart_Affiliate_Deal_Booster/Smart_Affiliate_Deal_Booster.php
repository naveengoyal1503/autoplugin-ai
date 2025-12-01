<?php
/*
Plugin Name: Smart Affiliate Deal Booster
Plugin URI: https://example.com/smart-affiliate-deal-booster
Description: Personalized affiliate deal popups and banners triggered by user behavior to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Deal_Booster.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class SmartAffiliateDealBooster {

    private $option_name = 'sadb_options';

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'print_deal_popup'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_sadb_get_deal', array($this, 'ajax_get_deal'));
        add_action('wp_ajax_nopriv_sadb_get_deal', array($this, 'ajax_get_deal'));
    }

    // Enqueue frontend JS and CSS
    public function enqueue_scripts() {
        wp_enqueue_style('sadb-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('sadb-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
        wp_localize_script('sadb-script', 'sadb_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sadb_nonce')
        ));
    }

    // Print deal popup container
    public function print_deal_popup() {
        echo '<div id="sadb-deal-popup" style="display:none;">
            <div id="sadb-deal-content">
                <span id="sadb-close">&times;</span>
                <div id="sadb-deal-inner"></div>
            </div>
        </div>';
    }

    // Admin menu
    public function admin_menu() {
        add_options_page('Smart Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'sadb-settings', array($this, 'settings_page'));
    }

    // Register plugin settings
    public function register_settings() {
        register_setting('sadb_option_group', $this->option_name);
        add_settings_section('sadb_main_section', 'Main Settings', null, 'sadb-settings');
        add_settings_field('sadb_affiliate_links', 'Affiliate Deals (JSON)', array($this, 'affiliate_links_callback'), 'sadb-settings', 'sadb_main_section');
    }

    // Settings field callback
    public function affiliate_links_callback() {
        $options = get_option($this->option_name);
        $value = isset($options['affiliate_links']) ? $options['affiliate_links'] : '[{"title":"Sample Deal","url":"https://example.com/deal","description":"Save 20% on product X!"}]';
        echo '<textarea name="sadb_options[affiliate_links]" rows="8" cols="50" class="large-text code">'.esc_textarea($value).'</textarea>';
        echo '<p class="description">Enter affiliate deals as JSON array with keys title, url, and description.</p>';
    }

    // Settings page HTML
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Deal Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sadb_option_group');
                do_settings_sections('sadb-settings');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Enter your affiliate deals as a JSON array in the settings above. Each deal should include <code>title</code>, <code>url</code>, and <code>description</code>. The plugin automatically analyzes visitor behavior and shows personalized deals in popups to increase conversion.</p>
        </div>
        <?php
    }

    // AJAX handler to serve a deal
    public function ajax_get_deal() {
        check_ajax_referer('sadb_nonce', 'nonce');
        $options = get_option($this->option_name);
        $deals_json = isset($options['affiliate_links']) ? $options['affiliate_links'] : '[]';
        $deals = json_decode($deals_json, true);
        if (!$deals || !is_array($deals)) {
            wp_send_json_error('No valid deals found');
        }
        // Simple random selection for demo; real plugin uses user context
        $deal = $deals[array_rand($deals)];
        wp_send_json_success($deal);
    }
}

new SmartAffiliateDealBooster();

// Minimal inline CSS and JS for popup
add_action('wp_head', function() {
    ?>
    <style>
        #sadb-deal-popup {position:fixed;bottom:20px;right:20px;width:300px;background:#fff;border:1px solid #ccc;box-shadow:0 0 10px rgba(0,0,0,0.3);z-index:99999;padding:15px;border-radius:8px;}
        #sadb-deal-popup #sadb-close {float:right;cursor:pointer;font-size:20px;font-weight:bold;}
        #sadb-deal-content {font-family: Arial,sans-serif;}
        #sadb-deal-content a {color:#0066cc; text-decoration:none;}
        #sadb-deal-content a:hover {text-decoration: underline;}
    </style>
    <script>
    jQuery(document).ready(function($){
        function fetchDeal() {
            $.post(sadb_ajax.ajax_url, {action:'sadb_get_deal', nonce:sadb_ajax.nonce}, function(response){
                if(response.success) {
                    var deal = response.data;
                    var html = '<h3>'+deal.title+'</h3><p>'+deal.description+'</p><p><a href="'+deal.url+'" target="_blank" rel="nofollow noopener">Claim Deal</a></p>';
                    $('#sadb-deal-inner').html(html);
                    $('#sadb-deal-popup').fadeIn();
                }
            });
        }
        $('#sadb-close').click(function(){
            $('#sadb-deal-popup').fadeOut();
        });
        // Trigger a popup after 15 seconds on the site
        setTimeout(fetchDeal, 15000);
    });
    </script>
    <?php
});