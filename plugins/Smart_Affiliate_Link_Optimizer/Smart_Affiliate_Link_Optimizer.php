/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Optimizer
 * Plugin URI: https://example.com/smart-affiliate-optimizer
 * Description: Automatically cloaks, tracks, and optimizes affiliate links to boost commissions. Free version with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('sa_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_options_page('Smart Affiliate Optimizer', 'SA Optimizer', 'manage_options', 'sa-optimizer', array($this, 'settings_page'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sa-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sa-tracker', 'sa_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sa_nonce')));
    }

    public function cloak_links($content) {
        if (!is_single()) return $content;
        $pattern = '/https?:\/\/[^\s]+\?(?:[^\s&]*=[^\s&]*&)*aff=([^\s&]+)/i';
        $content = preg_replace_callback($pattern, array($this, 'cloak_callback'), $content);
        return $content;
    }

    private function cloak_callback($matches) {
        $aff_id = sanitize_text_field($matches[1]);
        $cloaked = home_url('/go/' . base64_encode($aff_id));
        return '<a href="' . esc_url($cloaked) . '" class="sa-link" data-aff="' . esc_attr($aff_id) . '" onclick="saTrack(this)">Click here for offer</a>';
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('url' => '', 'text' => 'Click Here'), $atts);
        $cloaked = home_url('/go/' . base64_encode($atts['url']));
        return '<a href="' . esc_url($cloaked) . '" class="sa-link" data-aff="' . esc_attr($atts['url']) . '" onclick="saTrack(this)">' . esc_html($atts['text']) . '</a>';
    }

    public function settings_init() {
        register_setting('sa_options', 'sa_settings');
        add_settings_section('sa_section', 'Settings', null, 'sa-optimizer');
        add_settings_field('sa_api_key', 'Analytics API Key (Premium)', array($this, 'api_key_field'), 'sa-optimizer', 'sa_section');
    }

    public function api_key_field() {
        $options = get_option('sa_settings');
        echo '<input type="text" name="sa_settings[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" />';
        echo '<p class="description">Enter premium API key for advanced tracking.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Optimizer</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sa_options');
                do_settings_sections('sa-optimizer');
                submit_button();
                ?>
            </form>
            <p><strong>Upgrade to Premium</strong> for A/B testing, geo-targeting, and detailed reports. <a href="https://example.com/premium" target="_blank">Get it now</a></p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
        add_rewrite_rule('^go/([^/]*)/?', 'index.php?sa_go=$matches[1]', 'top');
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

add_action('init', array('SmartAffiliateOptimizer', 'get_instance'));

// Rewrite endpoint
add_filter('query_vars', function($vars) {
    $vars[] = 'sa_go';
    return $vars;
});

add_action('template_redirect', function() {
    if (get_query_var('sa_go')) {
        $aff = base64_decode(get_query_var('sa_go'));
        wp_redirect($aff, 301);
        exit;
    }
});

// AJAX tracking
add_action('wp_ajax_sa_track', 'sa_track_ajax');
add_action('wp_ajax_nopriv_sa_track', 'sa_track_ajax');

function sa_track_ajax() {
    check_ajax_referer('sa_nonce', 'nonce');
    $aff = sanitize_text_field($_POST['aff']);
    error_log('SA Track: ' . $aff . ' from ' . $_SERVER['REMOTE_ADDR']);
    wp_die();
}

// Inline JS for tracking
add_action('wp_footer', function() {
    if (is_single()) {
        echo '<script>function saTrack(el) { fetch(sa_ajax.ajaxurl, {method: "POST", body: new FormData().append("action", "sa_track").append("aff", el.dataset.aff).append("nonce", sa_ajax.nonce)}); }</script>';
    }
});

// Premium teaser
add_action('admin_notices', function() {
    if (!get_option('sa_premium')) {
        echo '<div class="notice notice-info"><p>Unlock premium features in <strong>Smart Affiliate Optimizer</strong>! <a href="' . admin_url('options-general.php?page=sa-optimizer') . '">Upgrade now</a></p></div>';
    }
});