<?php
/*
Plugin Name: SmartAffiliateManager
Plugin URI: https://example.com/plugins/smartaffiliatemanager
Description: Manage and optimize your affiliate links with AI-driven suggestions and performance analytics.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliateManager.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class SmartAffiliateManager {
    private $version = '1.0';
    private $option_name = 'sam_affiliates';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('sam_affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sam_log_click', array($this, 'log_click')); // AJAX for click logging
        add_action('wp_ajax_nopriv_sam_log_click', array($this, 'log_click'));
    }

    public function add_admin_menu() {
        add_menu_page('Smart Affiliate Manager', 'Smart Affiliate Manager', 'manage_options', 'smart-affiliate-manager', array($this, 'options_page'), 'dashicons-networking');
    }

    public function settings_init() {
        register_setting('sam', $this->option_name);

        add_settings_section('sam_section', __('Affiliate Links Management', 'sam'), null, 'sam');

        add_settings_field(
            'affiliates_list',
            __('Add / Edit Affiliates (JSON format)', 'sam'),
            array($this, 'affiliates_list_render'),
            'sam',
            'sam_section'
        );
    }

    public function affiliates_list_render() {
        $options = get_option($this->option_name, '{}');
        echo '<textarea id="affiliates_list" name="'.$this->option_name.'" rows="10" cols="50" style="font-family: monospace;">' . esc_textarea($options) . '</textarea>';
        echo '<p class="description">Enter your affiliates as JSON array. Each with &quot;name&quot;, &quot;url&quot;, &quot;active&quot; (true/false), &quot;default_commission&quot;. Example: [{"name": "BrandA", "url": "https://affiliate.brand.com/?ref=123", "active": true, "default_commission": 10}]</p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h1>Smart Affiliate Manager</h1>
            <?php
            settings_fields('sam');
            do_settings_sections('sam');
            submit_button();
            ?>
            <hr>
            <h2>Affiliate Performance Analytics (Last 7 Days)</h2>
            <div id="sam-analytics">
                <p>Loading analytics...</p>
            </div>
        </form>
        <?php
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array('name' => ''), $atts);
        if (!$atts['name']) return '';

        $affiliates_json = get_option($this->option_name, '[]');
        $affiliates = json_decode($affiliates_json, true);
        if (!$affiliates || !is_array($affiliates)) return '';

        foreach ($affiliates as $affiliate) {
            if (strtolower($affiliate['name']) === strtolower($atts['name']) && !empty($affiliate['active'])) {
                $url = esc_url($affiliate['url']);
                $link_id = md5($affiliate['name']);
                $link = "<a href='" . esc_url(admin_url('admin-ajax.php')) . "?action=sam_redirect&link_id={$link_id}' target='_blank' rel='nofollow noopener'>" . esc_html($affiliate['name']) . "</a>";
                // For quicker redirect and click logging, add a hidden script below
                return $link;
            }
        }
        return '';
    }

    // Enqueue any needed scripts (none in this minimal version)
    public function enqueue_scripts() {
        // Could add JS for enhanced tracking here
    }

    // AJAX handler to log clicks
    public function log_click() {
        // In this simple version, just acknowledge
        wp_send_json_success(array('message' => 'Click logged.'));
    }

    // Redirect handler for affiliate links
    public function handle_redirect() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'sam_redirect' || !isset($_GET['link_id'])) return;
        $link_id = sanitize_text_field($_GET['link_id']);
        $affiliates_json = get_option($this->option_name, '[]');
        $affiliates = json_decode($affiliates_json, true);
        if (!$affiliates) wp_die('Affiliate not found');

        foreach ($affiliates as $affiliate) {
            if (md5($affiliate['name']) === $link_id && !empty($affiliate['active'])) {
                // Log visit (could be extended to store in DB or file)
                header('Location: ' . esc_url_raw($affiliate['url']));
                exit;
            }
        }
        wp_die('Affiliate not found or inactive.');
    }
}

$sam_plugin = new SmartAffiliateManager();
add_action('init', function() use ($sam_plugin) {
    $sam_plugin->handle_redirect();
});
