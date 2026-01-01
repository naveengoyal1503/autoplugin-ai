/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Link Optimizer Pro
 * Plugin URI: https://example.com/affiliate-link-optimizer
 * Description: Automatically optimizes and tracks affiliate links for maximum conversions. Cloaks links, tracks clicks, and provides analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateLinkOptimizer {
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
        add_action('wp_ajax_alo_track_click', array($this, 'track_click'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('alo_api_key') === false) {
            add_option('alo_api_key', wp_generate_uuid4());
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('alo-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('alo-tracker', 'alo_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('alo_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Link Optimizer', 'ALO Pro', 'manage_options', 'alo-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['alo_save'])) {
            update_option('alo_enable_cloaking', isset($_POST['enable_cloaking']));
            update_option('alo_enable_tracking', isset($_POST['enable_tracking']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $enable_cloaking = get_option('alo_enable_cloaking', true);
        $enable_tracking = get_option('alo_enable_tracking', true);
        ?>
        <div class="wrap">
            <h1>Affiliate Link Optimizer Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Enable Link Cloaking</th>
                        <td><input type="checkbox" name="enable_cloaking" <?php checked($enable_cloaking); ?> /></td>
                    </tr>
                    <tr>
                        <th>Enable Click Tracking</th>
                        <td><input type="checkbox" name="enable_tracking" <?php checked($enable_tracking); ?> /></td>
                    </tr>
                </table>
                <p><strong>Upgrade to Pro</strong> for A/B testing, detailed analytics, and unlimited links! <a href="#" onclick="alert('Pro features coming soon!')">Get Pro ($49/year)</a></p>
                <p>Your API Key: <code><?php echo get_option('alo_api_key'); ?></code></p>
                <?php submit_button('Save Settings', 'primary', 'alo_save'); ?>
            </form>
            <h2>Stats</h2>
            <p>Total Clicks: <?php echo get_option('alo_total_clicks', 0); ?></p>
        </div>
        <?php
    }

    public function cloak_links($content) {
        if (!get_option('alo_enable_cloaking')) return $content;
        $api_key = get_option('alo_api_key');
        $home_url = home_url('/');
        if (preg_match_all('/<a[^>]+href=["\'](https?:\/\/[^"\']+)["\'][^>]*>.*<\/a>/isU', $content, $matches)) {
            foreach ($matches[1] as $url) {
                if (strpos($url, 'amazon.com') !== false || strpos($url, 'clickbank.net') !== false || strpos($url, 'affiliate') !== false) {
                    $cloaked = $home_url . '?alo=' . base64_encode($url) . '&key=' . substr($api_key, 0, 8);
                    $content = str_replace($url, $cloaked, $content);
                }
            }
        }
        return $content;
    }

    public function track_click() {
        check_ajax_referer('alo_nonce', 'nonce');
        if (isset($_GET['alo'])) {
            $url = base64_decode(sanitize_url($_GET['alo']));
            $clicks = get_option('alo_total_clicks', 0) + 1;
            update_option('alo_total_clicks', $clicks);
            wp_redirect($url);
            exit;
        }
        wp_die();
    }

    public function activate() {
        add_option('alo_total_clicks', 0);
    }

    public function deactivate() {}
}

AffiliateLinkOptimizer::get_instance();

add_action('template_redirect', function() {
    if (isset($_GET['alo'])) {
        $optimizer = AffiliateLinkOptimizer::get_instance();
        $optimizer->track_click();
    }
});

// Inline JS for tracker
add_action('wp_footer', function() {
    if (get_option('alo_enable_tracking')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('a[href*="amazon.com"], a[href*="clickbank"], a[href*="affiliate"]').click(function(e) {
                var href = $(this).attr('href');
                $.post(alo_ajax.ajax_url, {
                    action: 'alo_track_click',
                    nonce: alo_ajax.nonce,
                    url: href
                });
            });
        });
        </script>
        <?php
    }
});