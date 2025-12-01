/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentRevenue_Pro.php
*/
<?php
/**
 * ContentRevenue Pro - Monetization Management Plugin
 * Version: 1.0.0
 * Author: ContentRevenue
 */

if (!defined('ABSPATH')) exit;

define('CRP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CRP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CRP_VERSION', '1.0.0');

class ContentRevenuePro {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('the_content', [$this, 'inject_affiliate_links']);
        add_shortcode('crp_affiliate_link', [$this, 'affiliate_link_shortcode']);
        add_shortcode('crp_sponsored_block', [$this, 'sponsored_block_shortcode']);
        add_action('wp_ajax_crp_track_click', [$this, 'track_affiliate_click']);
        add_action('wp_ajax_nopriv_crp_track_click', [$this, 'track_affiliate_click']);
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}crp_affiliate_links (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            display_text varchar(255) NOT NULL,
            target_niche varchar(100),
            clicks int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            revenue decimal(10, 2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}crp_sponsored_posts (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            brand_name varchar(255) NOT NULL,
            payment_amount decimal(10, 2) NOT NULL,
            disclosure_text text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}crp_click_tracking (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            affiliate_id mediumint(9) NOT NULL,
            ip_address varchar(100),
            user_agent text,
            referrer text,
            click_time datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
        
        add_option('crp_plugin_version', CRP_VERSION);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function init() {
        wp_register_script('crp-admin', CRP_PLUGIN_URL . 'assets/admin.js', ['jquery'], CRP_VERSION);
        wp_register_script('crp-frontend', CRP_PLUGIN_URL . 'assets/frontend.js', ['jquery'], CRP_VERSION);
        wp_register_style('crp-admin', CRP_PLUGIN_URL . 'assets/admin.css', [], CRP_VERSION);
    }

    public function admin_menu() {
        add_menu_page('ContentRevenue Pro', 'ContentRevenue', 'manage_options', 'crp_dashboard', [$this, 'dashboard_page'], 'dashicons-money-alt', 30);
        add_submenu_page('crp_dashboard', 'Affiliate Links', 'Affiliate Links', 'manage_options', 'crp_affiliates', [$this, 'affiliates_page']);
        add_submenu_page('crp_dashboard', 'Sponsored Content', 'Sponsored Content', 'manage_options', 'crp_sponsored', [$this, 'sponsored_page']);
        add_submenu_page('crp_dashboard', 'Analytics', 'Analytics', 'manage_options', 'crp_analytics', [$this, 'analytics_page']);
        add_submenu_page('crp_dashboard', 'Settings', 'Settings', 'manage_options', 'crp_settings', [$this, 'settings_page']);
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'crp_') === false) return;
        wp_enqueue_script('crp-admin');
        wp_enqueue_style('crp-admin');
    }

    public function dashboard_page() {
        global $wpdb;
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM {$wpdb->prefix}crp_affiliate_links");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM {$wpdb->prefix}crp_affiliate_links");
        $active_links = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}crp_affiliate_links");
        
        echo '<div class="wrap"><h1>ContentRevenue Pro Dashboard</h1>';
        echo '<div class="crp-dashboard-widgets">';
        echo '<div class="crp-widget"><h3>Total Clicks</h3><p class="crp-stat">' . intval($total_clicks) . '</p></div>';
        echo '<div class="crp-widget"><h3>Total Revenue</h3><p class="crp-stat">$' . number_format(floatval($total_revenue), 2) . '</p></div>';
        echo '<div class="crp-widget"><h3>Active Links</h3><p class="crp-stat">' . intval($active_links) . '</p></div>';
        echo '</div></div>';
    }

    public function affiliates_page() {
        global $wpdb;
        
        if (isset($_POST['crp_add_affiliate'])) {
            check_admin_referer('crp_affiliate_nonce');
            $wpdb->insert($wpdb->prefix . 'crp_affiliate_links', [
                'user_id' => get_current_user_id(),
                'affiliate_url' => sanitize_url($_POST['affiliate_url']),
                'display_text' => sanitize_text_field($_POST['display_text']),
                'target_niche' => sanitize_text_field($_POST['target_niche'])
            ]);
        }
        
        $links = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}crp_affiliate_links WHERE user_id = " . get_current_user_id());
        
        echo '<div class="wrap"><h1>Affiliate Links</h1>';
        echo '<form method="POST"><div class="crp-form-group">';
        wp_nonce_field('crp_affiliate_nonce');
        echo '<label>Affiliate URL: <input type="url" name="affiliate_url" required></label>';
        echo '<label>Display Text: <input type="text" name="display_text" required></label>';
        echo '<label>Niche: <input type="text" name="target_niche"></label>';
        echo '<button type="submit" name="crp_add_affiliate" class="button button-primary">Add Link</button>';
        echo '</div></form>';
        
        echo '<table class="wp-list-table widefat"><thead><tr><th>URL</th><th>Text</th><th>Clicks</th><th>Revenue</th></tr></thead><tbody>';
        foreach ($links as $link) {
            echo '<tr><td>' . esc_url($link->affiliate_url) . '</td><td>' . esc_html($link->display_text) . '</td><td>' . $link->clicks . '</td><td>$' . number_format($link->revenue, 2) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function sponsored_page() {
        global $wpdb;
        
        if (isset($_POST['crp_add_sponsored'])) {
            check_admin_referer('crp_sponsored_nonce');
            $wpdb->insert($wpdb->prefix . 'crp_sponsored_posts', [
                'post_id' => intval($_POST['post_id']),
                'brand_name' => sanitize_text_field($_POST['brand_name']),
                'payment_amount' => floatval($_POST['payment_amount']),
                'disclosure_text' => sanitize_textarea_field($_POST['disclosure_text'])
            ]);
        }
        
        $sponsored = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}crp_sponsored_posts");
        
        echo '<div class="wrap"><h1>Sponsored Content</h1>';
        echo '<form method="POST"><div class="crp-form-group">';
        wp_nonce_field('crp_sponsored_nonce');
        echo '<label>Post: <select name="post_id"><option>Select Post</option>';
        $posts = get_posts(['numberposts' => -1]);
        foreach ($posts as $post) {
            echo '<option value="' . $post->ID . '">' . esc_html($post->post_title) . '</option>';
        }
        echo '</select></label>';
        echo '<label>Brand Name: <input type="text" name="brand_name" required></label>';
        echo '<label>Payment: <input type="number" name="payment_amount" step="0.01" required></label>';
        echo '<label>Disclosure: <textarea name="disclosure_text"></textarea></label>';
        echo '<button type="submit" name="crp_add_sponsored" class="button button-primary">Add Sponsored Post</button>';
        echo '</div></form>';
        
        echo '<table class="wp-list-table widefat"><thead><tr><th>Brand</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
        foreach ($sponsored as $item) {
            echo '<tr><td>' . esc_html($item->brand_name) . '</td><td>$' . number_format($item->payment_amount, 2) . '</td><td>' . esc_html($item->status) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function analytics_page() {
        global $wpdb;
        $clicks = $wpdb->get_results("SELECT DATE(click_time) as date, COUNT(*) as count FROM {$wpdb->prefix}crp_click_tracking GROUP BY DATE(click_time) ORDER BY date DESC LIMIT 30");
        
        echo '<div class="wrap"><h1>Analytics</h1>';
        echo '<h3>Recent Clicks (Last 30 Days)</h3>';
        echo '<table class="wp-list-table widefat"><thead><tr><th>Date</th><th>Clicks</th></tr></thead><tbody>';
        foreach ($clicks as $click) {
            echo '<tr><td>' . esc_html($click->date) . '</td><td>' . $click->count . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function settings_page() {
        echo '<div class="wrap"><h1>Settings</h1>';
        echo '<form method="POST" action="options.php">';
        settings_fields('crp_settings_group');
        do_settings_sections('crp_settings_group');
        submit_button();
        echo '</form></div>';
    }

    public function inject_affiliate_links($content) {
        if (is_admin()) return $content;
        return $content;
    }

    public function affiliate_link_shortcode($atts) {
        global $wpdb;
        $atts = shortcode_atts(['id' => 0], $atts, 'crp_affiliate_link');
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}crp_affiliate_links WHERE id = %d", $atts['id']));
        
        if (!$link) return '';
        
        return '<a href="javascript:void(0)" class="crp-affiliate-link" data-id="' . $link->id . '" onclick="crpTrackClick(this)">' . esc_html($link->display_text) . '</a>';
    }

    public function sponsored_block_shortcode($atts) {
        global $wpdb;
        $post_id = get_the_ID();
        $sponsored = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}crp_sponsored_posts WHERE post_id = %d AND status = 'active'", $post_id));
        
        if (!$sponsored) return '';
        
        return '<div class="crp-sponsored-block"><p class="crp-disclosure">' . esc_html($sponsored->disclosure_text) . '</p><p class="crp-brand">Sponsored by ' . esc_html($sponsored->brand_name) . '</p></div>';
    }

    public function track_affiliate_click() {
        global $wpdb;
        $affiliate_id = intval($_POST['affiliate_id']);
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}crp_affiliate_links WHERE id = %d", $affiliate_id));
        
        if ($link) {
            $wpdb->insert($wpdb->prefix . 'crp_click_tracking', [
                'affiliate_id' => $affiliate_id,
                'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
                'referrer' => sanitize_url($_POST['referrer'] ?? '')
            ]);
            $wpdb->update($wpdb->prefix . 'crp_affiliate_links', ['clicks' => $link->clicks + 1], ['id' => $affiliate_id]);
            wp_redirect($link->affiliate_url);
            exit;
        }
        wp_die('Invalid link');
    }
}

ContentRevenuePro::get_instance();

// Activation hook for plugin initialization
if (!function_exists('crp_init_plugin')) {
    function crp_init_plugin() {
        do_action('crp_loaded');
    }
    add_action('plugins_loaded', 'crp_init_plugin');
}
?>