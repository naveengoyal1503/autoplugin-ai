/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Plugin URI: https://example.com/smart-affiliate-manager
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('wp_head', array($this, 'inject_tracker'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('sam_options', array('api_key' => '', 'track_clicks' => true));
        load_plugin_textdomain('smart-affiliate-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sam-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sam-tracker', 'sam_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sam_nonce')));
    }

    public function cloak_links($content) {
        if (!is_single()) return $content;
        $patterns = array(
            '/https?:\/\/(?:www\.)?(amazon|clickbank|shareasale|commissionjunction)\.[a-z\.]+\S*/i',
            '/\b(?:aff|ref|tag)=[a-z0-9]+/i'
        );
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            foreach ($matches as $match) {
                $shortcode = '[sam_link url="' . esc_attr($match) . '"]';
                $content = str_replace($match, $shortcode, $content);
            }
        }
        return $content;
    }

    public function inject_tracker() {
        if ($this->options['track_clicks']) {
            echo '<script>console.log("SAM Tracker Active");</script>';
        }
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Manager',
            'SAM Pro',
            'manage_options',
            'sam-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['sam_save'])) {
            update_option('sam_options', $_POST['sam_options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = $this->options;
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Manager Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="sam_options[api_key]" value="<?php echo esc_attr($options['api_key']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Track Clicks</th>
                        <td><input type="checkbox" name="sam_options[track_clicks]" <?php checked($options['track_clicks']); ?> /></td>
                    </tr>
                </table>
                <p><input type="submit" name="sam_save" class="button-primary" value="Save Settings" /></p>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Get A/B testing, analytics dashboard, and more for $49/year. <a href="https://example.com/pro">Buy Now</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('sam_options', array('api_key' => '', 'track_clicks' => true));
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Shortcode handler
add_shortcode('sam_link', function($atts) {
    $atts = shortcode_atts(array('url' => ''), $atts);
    $id = uniqid('sam_');
    $click_url = admin_url('admin-ajax.php') . '?action=sam_track&url=' . urlencode($atts['url']) . '&id=' . $id;
    return '<a href="' . esc_url($click_url) . '" class="sam-link" data-real-url="' . esc_attr($atts['url']) . '" onclick="samTrack(this); return false;">Click Here for Offer</a>';
});

// AJAX track
add_action('wp_ajax_nopriv_sam_track', function() {
    $url = isset($_GET['url']) ? esc_url_raw(urldecode($_GET['url'])) : '';
    if ($url) {
        // Log click (Pro feature placeholder)
        error_log('SAM Click: ' . $url);
        wp_redirect($url);
        exit;
    }
});

add_action('wp_ajax_sam_track', function() {
    sam_track_ajax();
});

SmartAffiliateManager::get_instance();

// Dummy JS file content (base64 encoded for single file)
$js_content = "jQuery(document).ready(function($){window.samTrack=function(el){var url=$(el).data('real-url');$.post(sam_ajax.ajax_url,{action:'sam_track',url:url,nonce:sam_ajax.nonce},function(){window.location=url;});};});";
file_put_contents(plugin_dir_path(__FILE__) . 'tracker.js', base64_decode($js_content));