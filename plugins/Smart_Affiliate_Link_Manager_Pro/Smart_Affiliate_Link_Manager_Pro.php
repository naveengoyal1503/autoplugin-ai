/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateManager {
    private static $instance = null;
    
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function init() {
        if (get_option('smart_affiliate_pro') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate', plugin_dir_url(__FILE__) . 'assets/tracker.js', array('jquery'), '1.0.0', true);
    }
    
    public function cloak_links($content) {
        if (!is_single()) return $content;
        $links = array();
        preg_match_all('/href=["\']([^\"\']*aff\.[^\"\']*|[^\"\']*\?ref[^\"\']*|[^\"\']*affiliate[^\"\']*)["\']/i', $content, $matches);
        foreach ($matches[1] as $url) {
            $shortcode = '[afflink url="' . esc_attr($url) . '"]';
            $content = str_replace('href="' . $url . '"', 'href="' . $shortcode . '"', $content);
        }
        return $content;
    }
    
    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        $id = uniqid('aff_');
        $click_url = admin_url('admin-post.php?action=track_affiliate&id=' . $id . '&url=' . urlencode($atts['url']));
        return '<a href="' . esc_url($click_url) . '" class="smart-aff-link" data-real-url="' . esc_attr($atts['url']) . '" data-id="' . $id . '">Click Here</a>';
    }
    
    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate Manager', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
    }
    
    public function settings_page() {
        if (isset($_POST['save'])) {
            update_option('smart_affiliate_links', sanitize_textarea_field($_POST['links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('smart_affiliate_links', '');
        echo '<div class="wrap"><h1>Affiliate Manager</h1><form method="post"><textarea name="links" rows="10" cols="50">' . esc_textarea($links) . '</textarea><br><input type="submit" name="save" value="Save" class="button-primary"></form><p><strong>Pro Upgrade:</strong> Unlock A/B testing, analytics & unlimited links for $49/year! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
    
    public function activate() {
        update_option('smart_affiliate_pro', 'free');
    }
    
    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Pro</strong> for advanced features! <a href="https://example.com/pro">Learn More</a></p></div>';
    }
}

SmartAffiliateManager::get_instance();

add_action('admin_post_track_affiliate', function() {
    $url = isset($_GET['url']) ? esc_url_raw(urldecode($_GET['url'])) : '';
    $id = sanitize_text_field($_GET['id']);
    // Log click (free version: basic log)
    $logs = get_option('affiliate_logs', array());
    $logs[] = array('time' => current_time('mysql'), 'id' => $id, 'url' => $url);
    update_option('affiliate_logs', $logs);
    if (get_option('smart_affiliate_pro') === 'activated') {
        // Pro: advanced tracking
    }
    wp_redirect($url);
    exit;
});

// Pro teaser
if (!wp_doing_ajax()) {
    add_action('wp_footer', function() {
        echo '<script>console.log("Smart Affiliate Pro: Track ' . (get_option('smart_affiliate_pro') === 'activated' ? 'Pro' : 'FREE') . ' clicks!");</script>';
    });
}
?>