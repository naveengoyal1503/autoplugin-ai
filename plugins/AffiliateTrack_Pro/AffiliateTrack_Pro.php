<?php
/*
Plugin Name: AffiliateTrack Pro
Plugin URI: https://affiliatetrackpro.com
Description: Advanced affiliate link tracking and performance analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateTrack_Pro.php
License: GPL v2 or later
Text Domain: affiliatetrack-pro
*/

if (!defined('ABSPATH')) {
    exit;
}

define('AFFILIATETRACK_VERSION', '1.0.0');
define('AFFILIATETRACK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AFFILIATETRACK_PLUGIN_URL', plugin_dir_url(__FILE__));

class AffiliateTrackPro {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->initHooks();
        $this->createDatabase();
    }

    private function initHooks() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
        add_shortcode('affiliate_link', array($this, 'affiliateLinkShortcode'));
        add_action('template_redirect', array($this, 'trackAffiliateClick'));
    }

    private function createDatabase() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'affiliatetrack_links';
        $clicks_table = $wpdb->prefix . 'affiliatetrack_clicks';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                link_slug varchar(100) NOT NULL UNIQUE,
                affiliate_url text NOT NULL,
                affiliate_id varchar(100),
                program_name varchar(255),
                commission_rate decimal(5,2),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$clicks_table'") !== $clicks_table) {
            $sql = "CREATE TABLE $clicks_table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                link_id mediumint(9) NOT NULL,
                click_timestamp datetime DEFAULT CURRENT_TIMESTAMP,
                user_ip varchar(45),
                user_agent text,
                referrer_url text,
                conversion_status varchar(20) DEFAULT 'pending',
                commission_earned decimal(10,2),
                PRIMARY KEY (id),
                FOREIGN KEY (link_id) REFERENCES $table_name(id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public function addAdminMenu() {
        add_menu_page(
            'AffiliateTrack Pro',
            'AffiliateTrack Pro',
            'manage_options',
            'affiliatetrack-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'affiliatetrack-dashboard',
            'My Links',
            'My Links',
            'manage_options',
            'affiliatetrack-links',
            array($this, 'renderLinksPage')
        );

        add_submenu_page(
            'affiliatetrack-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'affiliatetrack-analytics',
            array($this, 'renderAnalyticsPage')
        );

        add_submenu_page(
            'affiliatetrack-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'affiliatetrack-settings',
            array($this, 'renderSettingsPage')
        );
    }

    public function enqueueAdminAssets($hook) {
        if (strpos($hook, 'affiliatetrack') === false) {
            return;
        }
        wp_enqueue_style('affiliatetrack-admin', AFFILIATETRACK_PLUGIN_URL . 'css/admin.css', array(), AFFILIATETRACK_VERSION);
        wp_enqueue_script('affiliatetrack-admin', AFFILIATETRACK_PLUGIN_URL . 'js/admin.js', array('jquery'), AFFILIATETRACK_VERSION, true);
    }

    public function renderDashboard() {
        global $wpdb;
        $table_clicks = $wpdb->prefix . 'affiliatetrack_clicks';
        $table_links = $wpdb->prefix . 'affiliatetrack_links';

        $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM $table_clicks");
        $conversions = $wpdb->get_var("SELECT COUNT(*) FROM $table_clicks WHERE conversion_status='completed'");
        $total_earnings = $wpdb->get_var("SELECT COALESCE(SUM(commission_earned), 0) FROM $table_clicks");
        $active_links = $wpdb->get_var("SELECT COUNT(*) FROM $table_links");

        echo '<div class="wrap">';
        echo '<h1>AffiliateTrack Pro Dashboard</h1>';
        echo '<div class="affiliatetrack-dashboard">';
        echo '<div class="stat-box"><h3>Total Clicks</h3><p>' . esc_html($total_clicks) . '</p></div>';
        echo '<div class="stat-box"><h3>Conversions</h3><p>' . esc_html($conversions) . '</p></div>';
        echo '<div class="stat-box"><h3>Total Earnings</h3><p>$' . esc_html(number_format($total_earnings, 2)) . '</p></div>';
        echo '<div class="stat-box"><h3>Active Links</h3><p>' . esc_html($active_links) . '</p></div>';
        echo '</div>';
        echo '</div>';
    }

    public function renderLinksPage() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliatetrack_links';

        if (isset($_POST['action']) && $_POST['action'] === 'add_link' && isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'affiliatetrack_nonce')) {
            $slug = sanitize_text_field($_POST['link_slug']);
            $url = esc_url_raw($_POST['affiliate_url']);
            $program = sanitize_text_field($_POST['program_name']);
            $rate = floatval($_POST['commission_rate']);

            $wpdb->insert(
                $table_name,
                array(
                    'link_slug' => $slug,
                    'affiliate_url' => $url,
                    'program_name' => $program,
                    'commission_rate' => $rate
                ),
                array('%s', '%s', '%s', '%f')
            );
        }

        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        echo '<div class="wrap">';
        echo '<h1>Manage Affiliate Links</h1>';
        echo '<form method="POST" class="affiliatetrack-form">';
        wp_nonce_field('affiliatetrack_nonce', 'nonce');
        echo '<input type="hidden" name="action" value="add_link">';
        echo '<table class="form-table">';
        echo '<tr><th>Link Slug</th><td><input type="text" name="link_slug" required></td></tr>';
        echo '<tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" required></td></tr>';
        echo '<tr><th>Program Name</th><td><input type="text" name="program_name" required></td></tr>';
        echo '<tr><th>Commission Rate (%)</th><td><input type="number" name="commission_rate" step="0.01" required></td></tr>';
        echo '</table>';
        echo '<p><input type="submit" class="button button-primary" value="Add Link"></p>';
        echo '</form>';

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Slug</th><th>Program</th><th>Commission Rate</th><th>Created</th><th>Shortcode</th></tr></thead>';
        echo '<tbody>';
        foreach ($links as $link) {
            echo '<tr>';
            echo '<td>' . esc_html($link->link_slug) . '</td>';
            echo '<td>' . esc_html($link->program_name) . '</td>';
            echo '<td>' . esc_html($link->commission_rate) . '%</td>';
            echo '<td>' . esc_html($link->created_at) . '</td>';
            echo '<td><code>[affiliate_link slug="' . esc_html($link->link_slug) . '"]</code></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public function renderAnalyticsPage() {
        global $wpdb;
        $table_clicks = $wpdb->prefix . 'affiliatetrack_clicks';
        $table_links = $wpdb->prefix . 'affiliatetrack_links';

        $results = $wpdb->get_results(
            "SELECT l.program_name, COUNT(c.id) as click_count, 
             SUM(CASE WHEN c.conversion_status='completed' THEN 1 ELSE 0 END) as conversion_count,
             COALESCE(SUM(c.commission_earned), 0) as total_earned
             FROM $table_links l
             LEFT JOIN $table_clicks c ON l.id = c.link_id
             GROUP BY l.id"
        );

        echo '<div class="wrap">';
        echo '<h1>Affiliate Analytics</h1>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Program</th><th>Clicks</th><th>Conversions</th><th>Conversion Rate</th><th>Earnings</th></tr></thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            $conversion_rate = $row->click_count > 0 ? ($row->conversion_count / $row->click_count * 100) : 0;
            echo '<tr>';
            echo '<td>' . esc_html($row->program_name) . '</td>';
            echo '<td>' . intval($row->click_count) . '</td>';
            echo '<td>' . intval($row->conversion_count) . '</td>';
            echo '<td>' . number_format($conversion_rate, 2) . '%</td>';
            echo '<td>$' . number_format($row->total_earned, 2) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public function renderSettingsPage() {
        echo '<div class="wrap">';
        echo '<h1>AffiliateTrack Pro Settings</h1>';
        echo '<form method="POST" action="options.php">';
        settings_fields('affiliatetrack_options');
        do_settings_sections('affiliatetrack_settings');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function affiliateLinkShortcode($atts) {
        $atts = shortcode_atts(array('slug' => ''), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliatetrack_links';

        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE link_slug = %s",
            $atts['slug']
        ));

        if (!$link) {
            return '[Invalid affiliate link]';
        }

        return '<a href="' . esc_url(add_query_arg('atc', base64_encode($link->id), site_url('/affiliate-redirect/'))) . '" class="affiliatetrack-link">View Offer</a>';
    }

    public function trackAffiliateClick() {
        if (isset($_GET['atc'])) {
            $link_id = intval(base64_decode($_GET['atc']));
            global $wpdb;
            $table_name = $wpdb->prefix . 'affiliatetrack_links';
            $clicks_table = $wpdb->prefix . 'affiliatetrack_clicks';

            $link = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $link_id
            ));

            if ($link) {
                $wpdb->insert(
                    $clicks_table,
                    array(
                        'link_id' => $link_id,
                        'user_ip' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
                        'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
                        'referrer_url' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : ''
                    ),
                    array('%d', '%s', '%s', '%s')
                );

                wp_redirect($link->affiliate_url);
                exit;
            }
        }
    }
}

register_activation_hook(__FILE__, function() {
    AffiliateTrackPro::getInstance();
});

AffiliateTrackPro::getInstance();
?>