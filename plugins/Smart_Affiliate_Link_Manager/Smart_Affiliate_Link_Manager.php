/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate-link-manager
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with analytics. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SALM_VERSION', '1.0.0');
define('SALM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SALM_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Free features
$free_features = true;

// Check for premium (simulate with option; in real, use license check)
$premium_active = get_option('salm_premium_active', false);

class SmartAffiliateLinkManager {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Rewrite rule for tracking
        add_rewrite_rule('^track/([^/]+)/([0-9]+)/?$', 'index.php?salmlink=$matches[1]&clickid=$matches[2]', 'top');
        flush_rewrite_rules();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salmlm-script', SALM_PLUGIN_URL . 'assets/script.js', array('jquery'), SALM_VERSION, true);
        wp_enqueue_style('salmlm-style', SALM_PLUGIN_URL . 'assets/style.css', array(), SALM_VERSION);
    }

    public function cloak_links($content) {
        if (is_admin() || !$free_features) return $content;

        // Simple regex to find affiliate links (e.g., containing 'aff', 'ref', or specific domains)
        $pattern = '/<a[^>]+href=["\']([^"\']*(?:aff|ref|amazon|clickbank)[^"\']*["\'][^>]*>(.*?)</a>/i';
        $content = preg_replace_callback($pattern, array($this, 'replace_link'), $content);
        return $content;
    }

    private function replace_link($matches) {
        $url = $matches[1];
        $text = $matches[2];
        $id = uniqid('salmlink_');
        update_option('salmlink_' . $id, $url, false);

        return '<a href="' . home_url('/track/' . $id . '/1/') . '" data-salm-id="' . $id . '" class="salm-link">' . $text . '</a>';
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Link Manager', 'Affiliate Manager', 'manage_options', 'salm', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('salm_options', 'salm_options');
        register_setting('salm_options', 'salm_premium_active');
    }

    public function settings_page() {
        if (isset($_POST['salm_upgrade'])) {
            // Simulate premium activation (in real: API call)
            update_option('salm_premium_active', true);
            echo '<div class="notice notice-success"><p>Premium activated! (Demo)</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <form method="post" action="options.php">
                <?php settings_fields('salm_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Free Features</th>
                        <td>Link cloaking and basic tracking active.</td>
                    </tr>
                    <?php if (!$premium_active): ?>
                    <tr>
                        <th>Upgrade to Premium</th>
                        <td>
                            <p>Unlock A/B testing, auto-optimization, detailed analytics for $9/month.</p>
                            <form method="post">
                                <input type="submit" name="salm_upgrade" class="button-primary" value="Activate Premium (Demo)">
                            </form>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <th>Premium Features</th>
                        <td>Active! Enjoy advanced tools.</td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Analytics</h2>
            <div id="salm-analytics">Loading...</div>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Track clicks
add_action('init', function() {
    global $wp_query;
    if (get_query_var('salmlink')) {
        $id = get_query_var('salmlink');
        $clickid = get_query_var('clickid');
        $url = get_option('salmlink_' . $id);
        if ($url) {
            // Log click (simple option increment)
            $stats = get_option('salm_stats', array());
            $stats[$id] = isset($stats[$id]) ? $stats[$id] + 1 : 1;
            update_option('salm_stats', $stats);
            wp_redirect($url, 301);
            exit;
        }
    }
});

// AJAX for analytics
add_action('wp_ajax_salm_get_stats', function() {
    $stats = get_option('salm_stats', array());
    wp_send_json($stats);
});

new SmartAffiliateLinkManager();

// Create assets dir placeholder (in real plugin, include files)
if (!file_exists(SALM_PLUGIN_PATH . 'assets')) {
    mkdir(SALM_PLUGIN_PATH . 'assets', 0755, true);
}

// Sample JS (inline for single file)
add_action('wp_footer', function() {
    if (is_admin()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.salm-link').click(function() {
            var id = $(this).data('salm-id');
            // Track click
        });
        if (typeof salmAdmin !== 'undefined') {
            $.get(ajaxurl, {action: 'salm_get_stats'}, function(data) {
                $('#salm-analytics').html(JSON.stringify(data, null, 2));
            });
        }
    });
    </script>
    <style>
    .salm-link { text-decoration: none; }
    .salm-link:hover { text-decoration: underline; }
    </style>
    <?php
});

// Premium feature stub
if ($premium_active) {
    // Add A/B testing logic here in full version
}