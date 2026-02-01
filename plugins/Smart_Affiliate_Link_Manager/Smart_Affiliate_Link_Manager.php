/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateManager {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('wp_loaded', array($this, 'rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_redirect'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate', false, dirname(plugin_basename(__FILE__)) . '/languages');
        flush_rewrite_rules();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate Manager',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_affiliate_links', sanitize_textarea_field($_POST['links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('smart_affiliate_links', '');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links (Keyword|Affiliate URL)</th>
                        <td><textarea name="links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea><br>
                        <small>One per line: keyword|https://affiliate-url.com</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use <code>[smartlink keyword="yourkeyword"]</code> or auto-replace keywords in posts.</p>
            <p><strong>Pro Features:</strong> Analytics, A/B Testing - <a href="https://example.com/pro">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function rewrite_rules() {
        add_rewrite_rule('^go/([^/]+)/?', 'index.php?smart_affiliate=1&link=$matches[1]', 'top');
    }

    public function handle_redirect() {
        if (get_query_var('smart_affiliate')) {
            $link_key = get_query_var('link');
            $links = explode("\n", get_option('smart_affiliate_links', ''));
            foreach ($links as $line) {
                $parts = explode('|', trim($line));
                if (isset($parts[1]) && $parts === $link_key) {
                    wp_redirect(esc_url_raw($parts[1]), 301);
                    exit;
                }
            }
            wp_die('Link not found.');
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateManager::get_instance();

// Shortcode
function smart_affiliate_shortcode($atts) {
    $atts = shortcode_atts(array('keyword' => ''), $atts);
    return '<a href="' . home_url('/go/' . esc_attr($atts['keyword']) . '/') . '" class="smart-affiliate-link">' . esc_html($atts['keyword']) . '</a>';
}
add_shortcode('smartlink', 'smart_affiliate_shortcode');

// Auto-link keywords in content
function smart_affiliate_content($content) {
    if (is_single()) {
        $links = explode("\n", get_option('smart_affiliate_links', ''));
        foreach ($links as $line) {
            $parts = explode('|', trim($line));
            if (isset($parts[1])) {
                $keyword = preg_quote($parts, '/');
                $content = preg_replace("/\b({$keyword})\b/i", '<a href="' . home_url('/go/' . esc_attr($parts) . '/') . '" class="smart-affiliate-link">$1</a>', $content, -1, $count);
            }
        }
    }
    return $content;
}
add_filter('the_content', 'smart_affiliate_content');

// Basic tracking
add_action('wp_ajax_track_click', 'track_affiliate_click');
add_action('wp_ajax_nopriv_track_click', 'track_affiliate_click');
function track_affiliate_click() {
    if (isset($_POST['link_key'])) {
        $clicks = get_option('smart_affiliate_clicks', array());
        $link_key = sanitize_text_field($_POST['link_key']);
        $clicks[$link_key] = isset($clicks[$link_key]) ? $clicks[$link_key] + 1 : 1;
        update_option('smart_affiliate_clicks', $clicks);
    }
    wp_die();
}

// Frontend JS placeholder
/*
assets/frontend.js content:
jQuery(document).ready(function($) {
    $('.smart-affiliate-link').on('click', function(e) {
        $.post(ajaxurl, {action: 'track_click', link_key: $(this).data('key')});
    });
});
*/

// CSS placeholder
/*
assets/frontend.css content:
.smart-affiliate-link {
    color: #0073aa;
    text-decoration: none;
}
.smart-affiliate-link:hover {
    text-decoration: underline;
}
*/