/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Securely cloak affiliate links, track clicks, and optimize conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloaker {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sal_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sal_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sal_pro_version')) {
            // Pro features hook point
        }
        add_filter('widget_text', 'shortcode_unautop');
        add_filter('the_content', 'shortcode_unautop');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-tracker', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Click Here',
            'id' => ''
        ), $atts);

        if (empty($atts['url'])) return '';

        $id = sanitize_text_field($atts['id']);
        $hash = md5($atts['url'] . $id);
        $cloak_url = add_query_arg('sal', $hash, home_url('/'));

        update_option('sal_links_' . $hash, $atts['url'], false);

        return '<a href="' . esc_url($cloak_url) . '" class="sal-link" data-sal="' . esc_attr($hash) . '" rel="nofollow">' . esc_html($atts['text']) . '</a>';
    }

    public function track_click() {
        if (!isset($_POST['sal_hash'])) {
            wp_die('Invalid request');
        }
        $hash = sanitize_text_field($_POST['sal_hash']);
        $url = get_option('sal_links_' . $hash);

        if ($url) {
            // Log click (free version: simple count)
            $count = get_option('sal_clicks_' . $hash, 0) + 1;
            update_option('sal_clicks_' . $hash, $count, false);

            // Pro: advanced analytics
            if (get_option('sal_pro_version')) {
                // Hook for pro tracking
            }

            wp_redirect(esc_url_raw($url), 301);
            exit;
        }
        wp_die('Link not found');
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateCloaker::get_instance();

// Pro upsell notice
function sal_pro_notice() {
    if (!get_option('sal_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Cloaker Pro</strong> for A/B testing, analytics & integrations! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'sal_pro_notice');

// tracker.js content (inline for single file)
function sal_inline_tracker() {
    ?>
    <script>jQuery(document).ready(function($) {
        $('.sal-link').on('click', function(e) {
            e.preventDefault();
            var hash = $(this).data('sal');
            $.post(sal_ajax.ajaxurl, {action: 'sal_track_click', sal_hash: hash}, function() {
                window.location = $(this).attr('href');
            }.bind(this));
        });
    });</script>
    <?php
}
add_action('wp_footer', 'sal_inline_tracker');