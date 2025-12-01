<?php
/*
Plugin Name: SmartAffiliate Optimizer
Plugin URI: https://smartaffiliate-optimizer.local
Description: Automatically track, optimize, and monetize affiliate links with intelligent recommendations
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Optimizer.php
License: GPL v2 or later
Text Domain: smartaffiliate-optimizer
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SAO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SAO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SAO_VERSION', '1.0.0');

class SmartAffiliateOptimizer {
    private static $instance = null;
    private $db_version = '1.0';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $affiliate_links_table = $wpdb->prefix . 'sao_affiliate_links';
        $link_stats_table = $wpdb->prefix . 'sao_link_stats';

        $sql_links = "CREATE TABLE IF NOT EXISTS $affiliate_links_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            original_url longtext NOT NULL,
            affiliate_url longtext NOT NULL,
            anchor_text varchar(255) NOT NULL,
            network varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        $sql_stats = "CREATE TABLE IF NOT EXISTS $link_stats_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            link_id bigint(20) NOT NULL,
            clicks bigint(20) DEFAULT 0,
            conversions bigint(20) DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0.00,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_links);
        dbDelta($sql_stats);

        add_option('sao_db_version', $this->db_version);
        add_option('sao_tracking_enabled', true);
        add_option('sao_premium_active', false);
    }

    public function deactivate() {
        wp_clear_scheduled_hook('sao_daily_stats_sync');
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_footer', array($this, 'enqueue_tracking_script'));
        add_action('wp_ajax_sao_save_affiliate_links', array($this, 'save_affiliate_links'));
        add_action('wp_ajax_sao_get_link_stats', array($this, 'get_link_stats'));
        add_action('wp_ajax_sao_get_optimization_suggestions', array($this, 'get_optimization_suggestions'));
        add_filter('the_content', array($this, 'process_affiliate_links'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'SmartAffiliate Optimizer',
            'SmartAffiliate',
            'manage_options',
            'sao-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'sao-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'sao-dashboard',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'sao-dashboard',
            'Manage Links',
            'Manage Links',
            'manage_options',
            'sao-links',
            array($this, 'render_links_page')
        );

        add_submenu_page(
            'sao-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'sao-settings',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'sao-') === false) {
            return;
        }

        wp_enqueue_style('sao-admin-css', SAO_PLUGIN_URL . 'assets/admin-style.css', array(), SAO_VERSION);
        wp_enqueue_script('sao-admin-js', SAO_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), SAO_VERSION, true);

        wp_localize_script('sao-admin-js', 'saoAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sao_admin_nonce')
        ));
    }

    public function render_dashboard() {
        global $wpdb;
        $links_table = $wpdb->prefix . 'sao_affiliate_links';
        $stats_table = $wpdb->prefix . 'sao_link_stats';

        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $links_table");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $stats_table");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $stats_table");
        $total_conversions = $wpdb->get_var("SELECT SUM(conversions) FROM $stats_table");

        echo '<div class="wrap">';
        echo '<h1>SmartAffiliate Optimizer Dashboard</h1>';
        echo '<div class="sao-dashboard-grid">';
        echo '<div class="sao-card"><h3>Total Links</h3><p class="sao-stat">' . intval($total_links) . '</p></div>';
        echo '<div class="sao-card"><h3>Total Clicks</h3><p class="sao-stat">' . intval($total_clicks) . '</p></div>';
        echo '<div class="sao-card"><h3>Total Revenue</h3><p class="sao-stat">$' . number_format(floatval($total_revenue), 2) . '</p></div>';
        echo '<div class="sao-card"><h3>Total Conversions</h3><p class="sao-stat">' . intval($total_conversions) . '</p></div>';
        echo '</div>';

        echo '<div id="sao-optimization-suggestions" class="sao-suggestions">';
        echo '<h2>Optimization Suggestions</h2>';
        echo '<p>Loading suggestions...</p>';
        echo '</div>';
        echo '</div>';
    }

    public function render_links_page() {
        global $wpdb;
        $links_table = $wpdb->prefix . 'sao_affiliate_links';
        $stats_table = $wpdb->prefix . 'sao_link_stats';

        $links = $wpdb->get_results(
            "SELECT l.*, s.clicks, s.conversions, s.revenue FROM $links_table l 
            LEFT JOIN $stats_table s ON l.id = s.link_id ORDER BY l.created_at DESC"
        );

        echo '<div class="wrap">';
        echo '<h1>Manage Affiliate Links</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>Post</th><th>Anchor Text</th><th>Network</th><th>Clicks</th><th>Conversions</th><th>Revenue</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($links as $link) {
            $post = get_post($link->post_id);
            echo '<tr>';
            echo '<td>' . esc_html($post ? $post->post_title : 'Unknown') . '</td>';
            echo '<td>' . esc_html($link->anchor_text) . '</td>';
            echo '<td>' . esc_html($link->network) . '</td>';
            echo '<td>' . intval($link->clicks) . '</td>';
            echo '<td>' . intval($link->conversions) . '</td>';
            echo '<td>$' . number_format(floatval($link->revenue), 2) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>SmartAffiliate Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('sao-settings-group');
        do_settings_sections('sao-settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function process_affiliate_links($content) {
        if (is_admin() || !get_option('sao_tracking_enabled')) {
            return $content;
        }

        global $wpdb;
        $post_id = get_the_ID();
        $links_table = $wpdb->prefix . 'sao_affiliate_links';

        $affiliate_links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $links_table WHERE post_id = %d",
            $post_id
        ));

        foreach ($affiliate_links as $link) {
            $replacement = '<a href="' . esc_url($link->affiliate_url) . '" data-sao-link-id="' . intval($link->id) . '" class="sao-tracked-link" onclick="saoTrackClick(' . intval($link->id) . ')">' . esc_html($link->anchor_text) . '</a>';
            $content = str_replace($link->anchor_text, $replacement, $content);
        }

        return $content;
    }

    public function enqueue_tracking_script() {
        if (is_admin()) {
            return;
        }
        ?>
        <script>
        function saoTrackClick(linkId) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url("admin-ajax.php"); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=sao_track_click&link_id=' + linkId + '&nonce=<?php echo wp_create_nonce("sao_tracking_nonce"); ?>');
        }
        </script>
        <?php
    }

    public function save_affiliate_links() {
        check_ajax_referer('sao_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $links_table = $wpdb->prefix . 'sao_affiliate_links';

        $post_id = intval($_POST['post_id']);
        $links = isset($_POST['links']) ? (array) $_POST['links'] : array();

        foreach ($links as $link) {
            $wpdb->insert($links_table, array(
                'post_id' => $post_id,
                'original_url' => sanitize_url($link['original_url']),
                'affiliate_url' => sanitize_url($link['affiliate_url']),
                'anchor_text' => sanitize_text_field($link['anchor_text']),
                'network' => sanitize_text_field($link['network'])
            ));
        }

        wp_send_json_success('Links saved successfully');
    }

    public function get_link_stats() {
        check_ajax_referer('sao_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $stats_table = $wpdb->prefix . 'sao_link_stats';

        $stats = $wpdb->get_results("SELECT * FROM $stats_table ORDER BY revenue DESC LIMIT 10");
        wp_send_json_success($stats);
    }

    public function get_optimization_suggestions() {
        check_ajax_referer('sao_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $links_table = $wpdb->prefix . 'sao_affiliate_links';
        $stats_table = $wpdb->prefix . 'sao_link_stats';

        $low_performing = $wpdb->get_results(
            "SELECT l.*, s.clicks, s.revenue FROM $links_table l 
            LEFT JOIN $stats_table s ON l.id = s.link_id 
            WHERE s.clicks < 5 OR s.revenue < 10 LIMIT 5"
        );

        $suggestions = array();
        foreach ($low_performing as $link) {
            $suggestions[] = array(
                'type' => 'optimization',
                'message' => 'Consider optimizing anchor text for: ' . $link->anchor_text . ' (Clicks: ' . $link->clicks . ')'
            );
        }

        wp_send_json_success($suggestions);
    }
}

SmartAffiliateOptimizer::get_instance();

add_action('wp_ajax_sao_track_click', function() {
    check_ajax_referer('sao_tracking_nonce', 'nonce');

    global $wpdb;
    $link_id = intval($_POST['link_id']);
    $stats_table = $wpdb->prefix . 'sao_link_stats';

    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $stats_table WHERE link_id = %d",
        $link_id
    ));

    if ($existing) {
        $wpdb->update($stats_table, array(
            'clicks' => $existing->clicks + 1
        ), array('link_id' => $link_id));
    } else {
        $wpdb->insert($stats_table, array(
            'link_id' => $link_id,
            'clicks' => 1
        ));
    }

    wp_send_json_success('Click tracked');
});
?>