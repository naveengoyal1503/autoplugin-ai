/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Tracker_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Link Tracker Pro
 * Plugin URI: https://example.com/affiliate-tracker
 * Description: Track affiliate links, monitor clicks, and analyze performance to boost earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-link-tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateLinkTrackerPro {
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
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        } else {
            add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
            add_filter('wp_nav_menu_link_att', array($this, 'track_menu_links'), 10, 4);
            add_filter('the_content', array($this, 'track_content_links'));
        }
        add_action('wp_ajax_alt_log_click', array($this, 'log_click'));
        add_action('wp_ajax_nopriv_alt_log_click', array($this, 'log_click'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alt_clicks';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_url text NOT NULL,
            ip varchar(45) NOT NULL,
            user_agent text NOT NULL,
            referer text NOT NULL,
            click_time datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('alt_pro_license', '');
        update_option('alt_pro_active', false);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Tracker',
            'Affiliate Tracker',
            'manage_options',
            'affiliate-tracker',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
    }

    public function admin_scripts($hook) {
        if ('toplevel_page_affiliate-tracker' !== $hook) {
            return;
        }
        wp_enqueue_script('alt-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('alt-admin', 'alt_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('alt_nonce')));
        wp_enqueue_style('alt-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function frontend_scripts() {
        wp_enqueue_script('alt-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('alt-tracker', 'alt_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function track_menu_links($atts, $item, $args, $depth) {
        if (isset($atts['href']) && $this->is_affiliate_link($atts['href'])) {
            $atts['href'] = $this->wrap_affiliate_link($atts['href']);
        }
        return $atts;
    }

    public function track_content_links($content) {
        preg_match_all('/<a[^>]+href=["\'`]([^"\'<>]+)["\''][^>]*>(.*?)<\/a>/i', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $url = $match[1];
            if ($this->is_affiliate_link($url)) {
                $tracked_url = $this->wrap_affiliate_link($url);
                $content = str_replace($match, str_replace($url, $tracked_url, $match), $content);
            }
        }
        return $content;
    }

    private function is_affiliate_link($url) {
        // Detect common affiliate patterns: ref=, aff=, tag=, etc.
        return preg_match('/(\/ref=|\?aff=|\?tag=|\?affid=|tracking=)/i', $url);
    }

    private function wrap_affiliate_link($url) {
        $id = uniqid('alt_');
        return "javascript:void(0)" . ";" . " data-alt-url='" . esc_url($url) . "' onclick='altTrackClick(this.dataset.altUrl);'";
    }

    public function log_click() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'alt_nonce')) {
            wp_die('Security check failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'alt_clicks';
        $url = sanitize_url($_POST['url'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        $wpdb->insert(
            $table_name,
            array(
                'link_url' => $url,
                'ip' => $ip,
                'user_agent' => $user_agent,
                'referer' => $referer
            )
        );

        $redirect_url = $url;
        wp_redirect($redirect_url);
        exit;
    }

    public function admin_page() {
        $is_pro = $this->is_pro_active();
        $stats = $this->get_stats();
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    private function is_pro_active() {
        return get_option('alt_pro_active', false);
    }

    private function get_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alt_clicks';
        return $wpdb->get_results("SELECT link_url, COUNT(*) as clicks, AVG(DATEDIFF(NOW(), click_time)) as avg_age FROM $table_name GROUP BY link_url ORDER BY clicks DESC LIMIT 10");
    }
}

// Admin page template (embedded)
function alt_admin_template() {?><div class="wrap">
<h1>Affiliate Link Tracker <?php if (!AffiliateLinkTrackerPro::get_instance()->is_pro_active()) echo '<span style="color:gold;">[PRO]</span>'; ?></h1>
<?php if (!AffiliateLinkTrackerPro::get_instance()->is_pro_active()): ?>
<div id="alt-pro-upsell">
    <h2>Unlock Pro Features</h2>
    <p>Advanced analytics, unlimited links, conversion tracking. <button id="alt-activate-pro" class="button button-primary">Activate Pro ($9/mo)</button></p>
</div>
<?php endif; ?>
<div id="alt-stats">
    <h2>Top Links</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>Link</th><th>Clicks</th></tr></thead>
        <tbody><?php foreach (AffiliateLinkTrackerPro::get_instance()->get_stats() as $stat): ?><tr><td><?php echo esc_html($stat->link_url); ?></td><td><?php echo $stat->clicks; ?></td></tr><?php endforeach; ?></tbody>
    </table>
</div>
</div><?php }

AffiliateLinkTrackerPro::get_instance();

// Frontend JS
add_action('wp_footer', function() { ?>
<script>
function altTrackClick(url) {
    fetch(alt_ajax.ajaxurl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=alt_log_click&url=' + encodeURIComponent(url) + '&nonce=<?php echo wp_create_nonce('alt_nonce'); ?>'
    }).then(() => {
        window.location.href = url;
    });
}
</script>
<?php });

// Pro activation (simplified Stripe integration placeholder)
add_action('wp_ajax_alt_activate_pro', function() {
    // In real plugin, integrate Stripe checkout
    update_option('alt_pro_active', true);
    wp_send_json_success('Pro activated!');
});