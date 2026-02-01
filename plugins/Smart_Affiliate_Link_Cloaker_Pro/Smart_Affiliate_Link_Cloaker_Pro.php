/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Automatically cloak, track, and optimize affiliate links with powerful analytics.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCloakerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
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
        load_plugin_textdomain('smart-affiliate-cloaker', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        // Free features
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('sac_link', array($this, 'shortcode_handler'));

        // Admin
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('wp_ajax_sac_get_stats', array($this, 'ajax_get_stats'));
        }

        // Pro check (simulate with option)
        $this->is_pro = get_option('sac_pro_active', false);
    }

    public function activate() {
        add_option('sac_db_version', '1.0');
        $this->create_tables();
    }

    public function deactivate() {
        // Cleanup optional
    }

    private function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sac_clicks';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_hash varchar(32) NOT NULL,
            original_url text NOT NULL,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            last_click datetime ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_hash (link_hash)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function cloak_links($content) {
        if (false !== strpos($content, 'rel="nofollow"')) {
            preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*rel=["\']nofollow["\'][^>]*>/i', $content, $matches);
            foreach ($matches[1] as $url) {
                if ($this->is_affiliate_url($url)) {
                    $cloaked = $this->get_cloaked_url($url);
                    $content = str_replace('href="' . $url . '"', 'href="' . $cloaked . '"', $content);
                }
            }
        }
        return $content;
    }

    private function is_affiliate_url($url) {
        $affiliates = array('amazon.com', 'clickbank.net', 'shareasale.com', 'cj.com'); // Free: basic check
        foreach ($affiliates as $domain) {
            if (strpos($url, $domain) !== false) {
                return true;
            }
        }
        return false;
    }

    private function get_cloaked_url($url) {
        $hash = md5($url);
        $cloaked = home_url('/go/' . $hash);
        add_rewrite_rule('go/([^/]+)/?', 'index.php?sac_hash=$matches[1]', 'top');
        flush_rewrite_rules();
        return $cloaked;
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        if (empty($atts['url'])) return '';
        $cloaked = $this->get_cloaked_url($atts['url']);
        return '<a href="' . $cloaked . '" rel="nofollow" target="_blank">' . ($atts['text'] ?? 'Click Here') . '</a>';
    }

    // Query var for redirect
    public function init_query_vars() {
        add_filter('query_vars', function($vars) {
            $vars[] = 'sac_hash';
            return $vars;
        });
        add_action('template_redirect', array($this, 'handle_redirect'));
    }

    public function handle_redirect() {
        if (get_query_var('sac_hash')) {
            global $wpdb;
            $hash = sanitize_text_field(get_query_var('sac_hash'));
            $table = $wpdb->prefix . 'sac_clicks';
            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE link_hash = %s", $hash));

            if (!$link) {
                $original_url = $this->resolve_original_url($hash); // Simulate
                $wpdb->insert($table, array('link_hash' => $hash, 'original_url' => $original_url));
            } else {
                $wpdb->query($wpdb->prepare("UPDATE $table SET clicks = clicks + 1 WHERE link_hash = %s", $hash));
            }

            if ($this->is_pro) {
                // Pro: advanced tracking (UTM, geo, etc.)
                error_log('Pro tracking for: ' . $hash);
            }

            wp_redirect($this->resolve_original_url($hash), 301);
            exit;
        }
    }

    private function resolve_original_url($hash) {
        // Demo: map hash back (in real, store mapping)
        $demo_urls = array(
            md5('https://amazon.com/example?tag=aff123') => 'https://amazon.com/example?tag=aff123',
        );
        return $demo_urls[$hash] ?? 'https://example.com';
    }

    public function admin_menu() {
        add_options_page('SAC Pro', 'SAC Pro', 'manage_options', 'sac-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_pro_key'])) {
            // Simulate license check
            if ($_POST['sac_pro_key'] === 'pro123') {
                update_option('sac_pro_active', true);
                echo '<div class="notice notice-success"><p>Pro activated!</p></div>';
            }
        }
        if ($this->is_pro) {
            echo '<h2>Pro Analytics</h2>';
            echo $this->get_stats_html();
            echo '<p><a href="?page=sac-pro&upgrade=1" class="button button-primary">Upgrade to Advanced Pro ($79/year)</a></p>'; // Upsell
        } else {
            echo '<h2>Smart Affiliate Cloaker</h2><p>Free version active. Enter Pro key:</p>';
            echo '<form method="post"><input type="text" name="sac_pro_key" placeholder="Pro License Key"><input type="submit" class="button-primary" value="Activate Pro"></form>';
            echo '<p>Or <a href="https://example.com/pro">buy Pro now</a>.</p>';
        }
    }

    private function get_stats_html() {
        global $wpdb;
        $table = $wpdb->prefix . 'sac_clicks';
        $stats = $wpdb->get_results("SELECT link_hash, clicks, original_url FROM $table ORDER BY clicks DESC LIMIT 10");
        $html = '<table class="wp-list-table widefat"><thead><tr><th>Link</th><th>Clicks</th></tr></thead><tbody>';
        foreach ($stats as $stat) {
            $html .= '<tr><td>' . esc_html(substr($stat->original_url, 0, 50)) . '...</td><td>' . $stat->clicks . '</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    public function ajax_get_stats() {
        if (!current_user_can('manage_options')) wp_die();
        echo $this->get_stats_html();
        wp_die();
    }
}

SmartAffiliateCloakerPro::get_instance();