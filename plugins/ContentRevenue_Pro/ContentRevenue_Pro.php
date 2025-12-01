/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentRevenue_Pro.php
*/
<?php
/**
 * Plugin Name: ContentRevenue Pro
 * Description: Comprehensive monetization and affiliate tracking for WordPress blogs
 * Version: 1.0.0
 * Author: ContentRevenue Team
 * Text Domain: content-revenue-pro
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CRP_VERSION', '1.0.0');
define('CRP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRP_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentRevenuePro {
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('crp_affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_shortcode('crp_sponsored_content', array($this, 'sponsored_content_shortcode'));
        add_action('wp_ajax_crp_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_crp_track_click', array($this, 'track_click'));
        add_filter('the_content', array($this, 'inject_analytics_beacon'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $affiliate_links_table = "{$wpdb->prefix}crp_affiliate_links";
        $if_not_exists = "CREATE TABLE IF NOT EXISTS {$affiliate_links_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            affiliate_url longtext NOT NULL,
            link_name varchar(255) NOT NULL,
            program varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            clicks mediumint(9) DEFAULT 0,
            conversions mediumint(9) DEFAULT 0,
            commission_earned decimal(10,2) DEFAULT 0.00,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($if_not_exists);

        $sponsored_content_table = "{$wpdb->prefix}crp_sponsored_content";
        $sponsored_sql = "CREATE TABLE IF NOT EXISTS {$sponsored_content_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            brand_name varchar(255) NOT NULL,
            payment_amount decimal(10,2) NOT NULL,
            payment_date datetime,
            disclosure_included boolean DEFAULT true,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset_collate};";
        dbDelta($sponsored_sql);

        update_option('crp_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentRevenue Pro',
            'ContentRevenue',
            'manage_options',
            'crp-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            76
        );
        add_submenu_page(
            'crp-dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'crp-affiliate-links',
            array($this, 'render_affiliate_links')
        );
        add_submenu_page(
            'crp-dashboard',
            'Sponsored Content',
            'Sponsored Content',
            'manage_options',
            'crp-sponsored-content',
            array($this, 'render_sponsored_content')
        );
        add_submenu_page(
            'crp-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'crp-settings',
            array($this, 'render_settings')
        );
    }

    public function render_dashboard() {
        global $wpdb;
        $affiliate_table = "{$wpdb->prefix}crp_affiliate_links";
        $sponsored_table = "{$wpdb->prefix}crp_sponsored_content";
        
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM {$affiliate_table}");
        $total_conversions = $wpdb->get_var("SELECT SUM(conversions) FROM {$affiliate_table}");
        $total_commission = $wpdb->get_var("SELECT SUM(commission_earned) FROM {$affiliate_table}");
        $sponsored_revenue = $wpdb->get_var("SELECT SUM(payment_amount) FROM {$sponsored_table}");
        
        echo '<div class="wrap">';
        echo '<h1>ContentRevenue Pro Dashboard</h1>';
        echo '<div class="crp-stats-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin:20px 0;">';
        echo '<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;">';
        echo '<h3>Total Affiliate Clicks</h3>';
        echo '<p style="font-size:24px;font-weight:bold;">' . ($total_clicks ?? 0) . '</p>';
        echo '</div>';
        echo '<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;">';
        echo '<h3>Commission Earned</h3>';
        echo '<p style="font-size:24px;font-weight:bold;color:green;">$' . number_format($total_commission ?? 0, 2) . '</p>';
        echo '</div>';
        echo '<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;">';
        echo '<h3>Conversions</h3>';
        echo '<p style="font-size:24px;font-weight:bold;">' . ($total_conversions ?? 0) . '</p>';
        echo '</div>';
        echo '<div style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:5px;">';
        echo '<h3>Sponsored Revenue</h3>';
        echo '<p style="font-size:24px;font-weight:bold;color:green;">$' . number_format($sponsored_revenue ?? 0, 2) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function render_affiliate_links() {
        global $wpdb;
        $affiliate_table = "{$wpdb->prefix}crp_affiliate_links";
        
        if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_link') {
            check_admin_referer('crp_add_affiliate');
            $wpdb->insert($affiliate_table, array(
                'post_id' => intval($_POST['post_id']),
                'affiliate_url' => sanitize_url($_POST['affiliate_url']),
                'link_name' => sanitize_text_field($_POST['link_name']),
                'program' => sanitize_text_field($_POST['program'])
            ));
            echo '<div class="notice notice-success"><p>Affiliate link added successfully!</p></div>';
        }

        $links = $wpdb->get_results("SELECT * FROM {$affiliate_table} ORDER BY created_at DESC LIMIT 50");
        
        echo '<div class="wrap">';
        echo '<h1>Manage Affiliate Links</h1>';
        echo '<form method="post" style="background:#fff;padding:20px;margin:20px 0;border:1px solid #ddd;border-radius:5px;">';
        wp_nonce_field('crp_add_affiliate');
        echo '<table style="width:100%;">';
        echo '<tr><td><label>Post ID:</label><input type="number" name="post_id" required></td>';
        echo '<td><label>Affiliate URL:</label><input type="url" name="affiliate_url" required style="width:100%;"></td></tr>';
        echo '<tr><td><label>Link Name:</label><input type="text" name="link_name" required></td>';
        echo '<td><label>Program:</label><input type="text" name="program" placeholder="e.g., Amazon Associates"></td></tr>';
        echo '</table>';
        echo '<input type="hidden" name="action" value="add_link">';
        echo '<button type="submit" class="button button-primary" style="margin-top:10px;">Add Affiliate Link</button>';
        echo '</form>';
        
        echo '<h2>Recent Links</h2>';
        echo '<table class="wp-list-table widefat" style="margin-top:20px;">';
        echo '<thead><tr><th>Link Name</th><th>Program</th><th>Clicks</th><th>Conversions</th><th>Commission</th></tr></thead>';
        echo '<tbody>';
        foreach ($links as $link) {
            echo '<tr>';
            echo '<td>' . esc_html($link->link_name) . '</td>';
            echo '<td>' . esc_html($link->program) . '</td>';
            echo '<td>' . $link->clicks . '</td>';
            echo '<td>' . $link->conversions . '</td>';
            echo '<td>$' . number_format($link->commission_earned, 2) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function render_sponsored_content() {
        global $wpdb;
        $sponsored_table = "{$wpdb->prefix}crp_sponsored_content";
        
        if ($_POST && isset($_POST['action']) && $_POST['action'] === 'add_sponsored') {
            check_admin_referer('crp_add_sponsored');
            $wpdb->insert($sponsored_table, array(
                'post_id' => intval($_POST['post_id']),
                'brand_name' => sanitize_text_field($_POST['brand_name']),
                'payment_amount' => floatval($_POST['payment_amount']),
                'payment_date' => sanitize_text_field($_POST['payment_date']),
                'disclosure_included' => isset($_POST['disclosure']) ? 1 : 0
            ));
            echo '<div class="notice notice-success"><p>Sponsored content recorded successfully!</p></div>';
        }

        $sponsored = $wpdb->get_results("SELECT * FROM {$sponsored_table} ORDER BY created_at DESC LIMIT 50");
        
        echo '<div class="wrap">';
        echo '<h1>Manage Sponsored Content</h1>';
        echo '<form method="post" style="background:#fff;padding:20px;margin:20px 0;border:1px solid #ddd;border-radius:5px;">';
        wp_nonce_field('crp_add_sponsored');
        echo '<table style="width:100%;">';
        echo '<tr><td><label>Post ID:</label><input type="number" name="post_id" required></td>';
        echo '<td><label>Brand Name:</label><input type="text" name="brand_name" required></td></tr>';
        echo '<tr><td><label>Payment Amount:</label><input type="number" step="0.01" name="payment_amount" required></td>';
        echo '<td><label>Payment Date:</label><input type="date" name="payment_date"></td></tr>';
        echo '<tr><td colspan="2"><label><input type="checkbox" name="disclosure" checked> Include FTC Disclosure</label></td></tr>';
        echo '</table>';
        echo '<input type="hidden" name="action" value="add_sponsored">';
        echo '<button type="submit" class="button button-primary" style="margin-top:10px;">Record Sponsored Content</button>';
        echo '</form>';
        
        echo '<h2>Sponsored Content History</h2>';
        echo '<table class="wp-list-table widefat" style="margin-top:20px;">';
        echo '<thead><tr><th>Brand</th><th>Payment</th><th>Date</th><th>Disclosure</th></tr></thead>';
        echo '<tbody>';
        foreach ($sponsored as $item) {
            echo '<tr>';
            echo '<td>' . esc_html($item->brand_name) . '</td>';
            echo '<td>$' . number_format($item->payment_amount, 2) . '</td>';
            echo '<td>' . esc_html($item->payment_date) . '</td>';
            echo '<td>' . ($item->disclosure_included ? 'Yes' : 'No') . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function render_settings() {
        echo '<div class="wrap">';
        echo '<h1>ContentRevenue Pro Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('crp_settings');
        do_settings_sections('crp_settings');
        echo '<p>Configure your monetization preferences and tracking settings here.</p>';
        echo '<p><strong>Premium Features:</strong> Upgrade to unlock advanced analytics, automated reporting, and multi-channel tracking.</p>';
        echo '</form>';
        echo '</div>';
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'text' => 'Click here'
        ), $atts);

        if (!$atts['id']) {
            return '';
        }

        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}crp_affiliate_links WHERE id = %d", $atts['id']));

        if (!$link) {
            return '';
        }

        return sprintf(
            '<a href="%s" class="crp-affiliate-link" data-link-id="%d" onclick="ContentRevenuePro.trackClick(event, %d);">%s</a>',
            esc_url($link->affiliate_url),
            $link->id,
            $link->id,
            esc_html($atts['text'])
        );
    }

    public function sponsored_content_shortcode($atts) {
        $atts = shortcode_atts(array(
            'brand' => '',
            'content' => ''
        ), $atts);

        if (!$atts['brand']) {
            return '';
        }

        return sprintf(
            '<div class="crp-sponsored-content" style="background:#f9f9f9;border-left:4px solid #0073aa;padding:15px;margin:15px 0;">' .
            '<p style="margin:0;font-size:12px;color:#666;"><strong>Sponsored Content</strong></p>' .
            '<p style="margin:10px 0 0 0;">%s</p>' .
            '<p style="margin:10px 0 0 0;font-size:11px;color:#999;"><em>This post is sponsored by %s</em></p>' .
            '</div>',
            wp_kses_post($atts['content']),
            esc_html($atts['brand'])
        );
    }

    public function track_click() {
        if (!isset($_POST['link_id'])) {
            wp_send_json_error();
        }

        global $wpdb;
        $link_id = intval($_POST['link_id']);
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}crp_affiliate_links SET clicks = clicks + 1 WHERE id = %d",
            $link_id
        ));

        wp_send_json_success();
    }

    public function inject_analytics_beacon($content) {
        if (is_singular() && !is_admin()) {
            $beacon = '<script>'
                . 'var ContentRevenuePro = ContentRevenuePro || {};'
                . 'ContentRevenuePro.trackClick = function(e, linkId) {'
                . 'fetch("' . admin_url('admin-ajax.php') . '", {'
                . 'method: "POST",'
                . 'headers: {"Content-Type": "application/x-www-form-urlencoded"},'
                . 'body: "action=crp_track_click&link_id=" + linkId'
                . '});'
                . '};'
                . '</script>';
            $content .= $beacon;
        }
        return $content;
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'crp-frontend',
            CRP_PLUGIN_URL . 'assets/frontend.css',
            array(),
            CRP_VERSION
        );
    }

    public function enqueue_admin_assets() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'crp-') === 0) {
            wp_enqueue_style(
                'crp-admin',
                CRP_PLUGIN_URL . 'assets/admin.css',
                array(),
                CRP_VERSION
            );
        }
    }
}

ContentRevenuePro::get_instance();
?>