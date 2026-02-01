/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Automatically cloaks and tracks affiliate links in posts, boosts conversions with smart redirects, and generates performance reports.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCloaker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sac_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sac_track_click', array($this, 'track_click'));
        add_filter('the_content', array($this, 'cloak_links'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_shortcode('sac_stats', array($this, 'stats_shortcode'));
    }

    public function activate() {
        add_option('sac_api_key', wp_generate_uuid4());
        add_option('sac_links', array());
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-tracker', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function cloak_links($content) {
        if (is_admin()) return $content;
        $links = get_option('sac_links', array());
        foreach ($links as $id => $link) {
            $pattern = '/href=["\']' . preg_quote($link['original'], '/') . '["\']/i';
            $replacement = 'href="' . home_url('/go/' . $id . '/') . '" data-sac-id="' . $id . '"';
            $content = preg_replace($pattern, $replacement, $content);
        }
        return $content;
    }

    public function track_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        $id = intval($_POST['id']);
        $links = get_option('sac_links', array());
        if (isset($links[$id])) {
            $links[$id]['clicks'] = isset($links[$id]['clicks']) ? $links[$id]['clicks'] + 1 : 1;
            update_option('sac_links', $links);
            wp_redirect($links[$id]['original'], 301);
            exit;
        }
    }

    public function admin_menu() {
        add_options_page('Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'sac-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sac_settings', 'sac_links');
        register_setting('sac_settings', 'sac_api_key');
    }

    public function settings_page() {
        if (isset($_POST['sac_add_link'])) {
            $links = get_option('sac_links', array());
            $id = count($links);
            $links[$id] = array(
                'name' => sanitize_text_field($_POST['sac_name']),
                'original' => esc_url_raw($_POST['sac_original']),
                'clicks' => 0
            );
            update_option('sac_links', $links);
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Name</th>
                        <td><input type="text" name="sac_name" required /></td>
                    </tr>
                    <tr>
                        <th>Original URL</th>
                        <td><input type="url" name="sac_original" style="width: 400px;" required /></td>
                    </tr>
                </table>
                <?php submit_button('Add Link', 'secondary', 'sac_add_link'); ?>
            </form>
            <h2>Links & Stats</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Name</th><th>Cloaked URL</th><th>Clicks</th></tr></thead>
                <tbody>
        <?php
        $links = get_option('sac_links', array());
        foreach ($links as $id => $link) {
            echo '<tr><td>' . esc_html($link['name']) . '</td><td>' . home_url('/go/' . $id . '/') . '</td><td>' . intval($link['clicks']) . '</td></tr>';
        }
        ?>
                </tbody>
            </table>
            <p><strong>Pro Upgrade:</strong> Unlimited links, A/B testing, detailed analytics. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function stats_shortcode($atts) {
        if (!current_user_can('manage_options')) return '';
        $links = get_option('sac_links', array());
        ob_start();
        echo '<ul>';
        foreach ($links as $id => $link) {
            echo '<li>' . esc_html($link['name']) . ': ' . intval($link['clicks']) . ' clicks</li>';
        }
        echo '</ul>';
        return ob_get_clean();
    }
}

// Handle pretty permalinks for /go/ID/
add_rewrite_rule('^go/([0-9]+)/?', 'index.php?sac_go=$matches[1]', 'top');
add_filter('query_vars', function($vars) {
    $vars[] = 'sac_go';
    return $vars;
});
add_action('template_redirect', function() {
    $go = get_query_var('sac_go');
    if ($go) {
        $links = get_option('sac_links', array());
        if (isset($links[$go])) {
            wp_redirect($links[$go]['original'], 301);
            exit;
        }
    }
});

// Embed JS inline for single file
add_action('wp_footer', function() {
    if (is_admin()) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('a[data-sac-id]').on('click', function(e) {
            var id = $(this).data('sac-id');
            $.post(sac_ajax.ajaxurl, {
                action: 'sac_track_click',
                id: id,
                nonce: sac_ajax.nonce
            });
        });
    });
    </script>
    <?php
});

SmartAffiliateCloaker::get_instance();

// Flush rewrite rules on activation
global $sac_flush_rewrites;
if (isset($_GET['activated']) && is_admin()) {
    flush_rewrite_rules();
}
?>