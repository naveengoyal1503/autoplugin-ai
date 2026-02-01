/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Plugin URI: https://example.com/smart-affiliate-link-manager
 * Description: Automatically cloak, track, and optimize affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-link-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateLinkManager {
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
        load_plugin_textdomain('smart-affiliate-link-manager', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        // Free features
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('affiliate_link', array($this, 'shortcode_link'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_salmp_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_salmp_track_click', array($this, 'track_click'));

        // Admin
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }

        // Premium nag
        add_action('admin_notices', array($this, 'premium_nag'));
    }

    public function activate() {
        update_option('salmp_db_version', '1.0.0');
        $this->create_tables();
    }

    public function deactivate() {
        // Cleanup optional
    }

    private function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'salmp_clicks';

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(255) NOT NULL,
            url text NOT NULL,
            ip varchar(45) NOT NULL,
            user_agent text NOT NULL,
            referer text,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function cloak_links($content) {
        if (is_feed() || is_preview()) return $content;

        $pattern = '/https?:\/\/[^\s<>"\[]+?(?:\?[a-zA-Z0-9_=&;]+)?/i';
        $content = preg_replace_callback($pattern, array($this, 'replace_link'), $content);
        return $content;
    }

    private function replace_link($matches) {
        $url = $matches;
        $shortcode = '[affiliate_link url="' . esc_attr($url) . '"]';
        return $shortcode;
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('url' => ''), $atts);
        if (empty($atts['url'])) return '';

        $link_id = md5($atts['url']);
        $track_url = add_query_arg('salmp', $link_id, home_url('/'));

        return '<a href="' . esc_url($track_url) . '" target="_blank" rel="nofollow noopener" class="salmp-link">' . esc_html($atts['url']) . '</a>';
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salmp-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('salmp-tracker', 'salmp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'salmp_nonce')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $link_id = sanitize_text_field($_POST['link_id']);
        $url = esc_url_raw(get_option('salmp_link_' . $link_id, $_POST['url']));

        $wpdb->insert(
            $wpdb->prefix . 'salmp_clicks',
            array(
                'link_id' => $link_id,
                'url' => $url,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'referer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''
            )
        );

        wp_redirect($url, 302);
        exit;
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Link Manager',
            'Affiliate Links',
            'manage_options',
            'salmp',
            array($this, 'admin_page')
        );
    }

    public function admin_scripts($hook) {
        if ('settings_page_salmp' !== $hook) return;
        wp_enqueue_script('salmp-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
    }

    public function admin_page() {
        if (isset($_POST['salmp_save'])) {
            update_option('salmp_settings', $_POST['salmp_settings']);
        }
        $settings = get_option('salmp_settings', array());
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function premium_nag() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Link Manager:</strong> Unlock <a href="https://example.com/premium" target="_blank">Premium features</a> like A/B testing and detailed analytics for higher conversions!</p></div>';
    }
}

SmartAffiliateLinkManager::get_instance();

// Dummy files content (base64 encoded for single file)
$tracker_js = '<script>document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".salmp-link").forEach(function(e){e.addEventListener("click",function(n){n.preventDefault();var r=e.getAttribute("href"),i=r.split("salmp=")[1];jQuery.post(salmp_ajax.ajaxurl,{action:"salmp_track_click",link_id:i,nonce:"' . wp_create_nonce('salmp_nonce') . '",url:r},function(){window.location.href=r})})});</script>';
file_put_contents(plugin_dir_path(__FILE__) . 'tracker.js', $tracker_js);

$admin_php = '<?php if(!defined("ABSPATH")) exit; ?><div class="wrap"><h1>Affiliate Link Dashboard</h1><form method="post"><table class="form-table"><tr><th>Auto-cloak</th><td><input type="checkbox" name="salmp_settings[cloak]" ' . (isset($settings['cloak']) ? 'checked' : '') . ' /></td></tr></table><input type="submit" name="salmp_save" value="Save" class="button-primary" /></form><h2>Clicks (Free Version - Limited)</h2><table class="wp-list-table widefat"><thead><tr><th>Link</th><th>Clicks</th></tr></thead><tbody><?php global $wpdb; $clicks = $wpdb->get_results("SELECT link_id, COUNT(*) as count FROM " . $wpdb->prefix . "salmp_clicks GROUP BY link_id"); foreach($clicks as $c): ?><tr><td><?php echo esc_html($c->link_id); ?></td><td><?php echo $c->count; ?></td></tr><?php endforeach; ?></tbody></table><p><em>Upgrade to Pro for full analytics, A/B testing, and WooCommerce integration!</em></p></div>';
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', $admin_php);