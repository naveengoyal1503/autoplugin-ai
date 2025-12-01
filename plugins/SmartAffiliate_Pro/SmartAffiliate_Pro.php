<?php
/*
Plugin Name: SmartAffiliate Pro
Plugin URI: https://smartaffiliatepr.com
Description: Advanced affiliate link management with performance tracking and ROI analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Pro.php
License: GPL v2 or later
Text Domain: smartaffiliate-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SMARTAFF_VERSION', '1.0.0');
define('SMARTAFF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMARTAFF_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartAffiliatePro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_nopriv_track_affiliate_click', array($this, 'track_affiliate_click'));
        add_action('wp_ajax_track_affiliate_click', array($this, 'track_affiliate_click'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaff_links';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(50) NOT NULL UNIQUE,
            program_name varchar(100),
            affiliate_url longtext NOT NULL,
            custom_name varchar(100),
            commission_rate decimal(5,2),
            clicks bigint(20) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smartaff_clicks (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            link_id varchar(50),
            user_ip varchar(45),
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('smartaff_activated', time());
    }

    public function deactivate() {
        delete_option('smartaff_activated');
    }

    public function add_admin_menu() {
        add_menu_page(
            'SmartAffiliate Pro',
            'SmartAffiliate Pro',
            'manage_options',
            'smartaff-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
        add_submenu_page(
            'smartaff-dashboard',
            'Affiliate Links',
            'Links',
            'manage_options',
            'smartaff-links',
            array($this, 'render_links_page')
        );
        add_submenu_page(
            'smartaff-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'smartaff-analytics',
            array($this, 'render_analytics_page')
        );
        add_submenu_page(
            'smartaff-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'smartaff-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('smartaff_settings', 'smartaff_default_commission');
        register_setting('smartaff_settings', 'smartaff_tracking_enabled');
    }

    public function render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaff_links';
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $table_name");
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Pro Dashboard</h1>
            <div style="background:#f1f1f1;padding:20px;border-radius:5px;margin:20px 0;">
                <h2>Quick Stats</h2>
                <p><strong>Total Affiliate Links:</strong> <?php echo esc_html($total_links ?: 0); ?></p>
                <p><strong>Total Clicks Tracked:</strong> <?php echo esc_html($total_clicks ?: 0); ?></p>
                <p style="color:#666;font-size:12px;">Track affiliate link performance and optimize your monetization strategy.</p>
            </div>
            <h3>Getting Started</h3>
            <ol>
                <li>Go to <strong>Links</strong> tab to add your affiliate URLs</li>
                <li>Use the shortcode <code>[affiliate_link id="link_id"]Link Text[/affiliate_link]</code> in your posts</li>
                <li>Monitor clicks and performance in <strong>Analytics</strong></li>
                <li>Track commission opportunities to maximize earnings</li>
            </ol>
        </div>
        <?php
    }

    public function render_links_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaff_links';

        if (isset($_POST['smartaff_add_link']) && check_admin_referer('smartaff_nonce')) {
            $link_id = sanitize_text_field($_POST['link_id']);
            $program = sanitize_text_field($_POST['program_name']);
            $url = esc_url_raw($_POST['affiliate_url']);
            $commission = floatval($_POST['commission_rate'] ?: 0);

            $wpdb->insert($table_name, array(
                'link_id' => $link_id,
                'program_name' => $program,
                'affiliate_url' => $url,
                'commission_rate' => $commission
            ));
            echo '<div class="notice notice-success"><p>Affiliate link added successfully!</p></div>';
        }

        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_date DESC");
        ?>
        <div class="wrap">
            <h1>Manage Affiliate Links</h1>
            <form method="post" style="background:#f9f9f9;padding:20px;border-radius:5px;margin:20px 0;">
                <?php wp_nonce_field('smartaff_nonce'); ?>
                <table style="width:100%;">
                    <tr>
                        <td><label>Link ID:</label><input type="text" name="link_id" required style="width:100%;" placeholder="e.g., amazon_prime"></td>
                    </tr>
                    <tr>
                        <td><label>Program Name:</label><input type="text" name="program_name" style="width:100%;" placeholder="e.g., Amazon Associates"></td>
                    </tr>
                    <tr>
                        <td><label>Affiliate URL:</label><input type="url" name="affiliate_url" required style="width:100%;" placeholder="https://..."></td>
                    </tr>
                    <tr>
                        <td><label>Commission Rate (%):</label><input type="number" step="0.01" name="commission_rate" style="width:100%;" placeholder="0.00"></td>
                    </tr>
                </table>
                <button type="submit" name="smartaff_add_link" class="button button-primary" style="margin-top:10px;">Add Link</button>
            </form>

            <h2>Your Affiliate Links</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Link ID</th>
                        <th>Program</th>
                        <th>Commission Rate</th>
                        <th>Clicks</th>
                        <th>Added</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                    <tr>
                        <td><code><?php echo esc_html($link->link_id); ?></code></td>
                        <td><?php echo esc_html($link->program_name ?: 'N/A'); ?></td>
                        <td><?php echo esc_html($link->commission_rate . '%'); ?></td>
                        <td><?php echo esc_html($link->clicks); ?></td>
                        <td><?php echo esc_html(date('M d, Y', strtotime($link->created_date))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_analytics_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaff_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY clicks DESC");
        ?>
        <div class="wrap">
            <h1>Affiliate Analytics</h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Link ID</th>
                        <th>Program</th>
                        <th>Total Clicks</th>
                        <th>Commission Rate</th>
                        <th>Estimated Earnings*</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo esc_html($link->link_id); ?></td>
                        <td><?php echo esc_html($link->program_name ?: 'N/A'); ?></td>
                        <td><?php echo esc_html($link->clicks); ?></td>
                        <td><?php echo esc_html($link->commission_rate . '%'); ?></td>
                        <td>~$<?php echo esc_html(number_format($link->clicks * ($link->commission_rate / 100) * 15, 2)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="font-size:12px;color:#666;margin-top:20px;">*Estimates based on assumed $15 average order value. Actual earnings depend on conversion rates and order values.</p>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smartaff_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="smartaff_tracking_enabled">Enable Click Tracking:</label></th>
                        <td><input type="checkbox" name="smartaff_tracking_enabled" id="smartaff_tracking_enabled" value="1" <?php checked(get_option('smartaff_tracking_enabled'), 1); ?>> Track affiliate clicks for analytics</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr style="margin:30px 0;">
            <h2>Premium Features</h2>
            <div style="background:#e7f3ff;padding:15px;border-left:4px solid #0073aa;">
                <p><strong>Upgrade to Premium ($49/year)</strong> for:</p>
                <ul style="margin-left:20px;">
                    <li>Advanced commission forecasting</li>
                    <li>Automated performance reports</li>
                    <li>A/B testing for affiliate links</li>
                    <li>Integration with popular affiliate networks</li>
                    <li>Priority support</li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function affiliate_link_shortcode($atts, $content = '') {
        global $wpdb;
        $atts = shortcode_atts(array('id' => ''), $atts);
        $link_id = sanitize_text_field($atts['id']);

        if (!$link_id) {
            return '<em>Invalid affiliate link</em>';
        }

        $table_name = $wpdb->prefix . 'smartaff_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE link_id = %s", $link_id));

        if (!$link) {
            return '<em>Affiliate link not found</em>';
        }

        $tracking_url = add_query_arg(array(
            'aff_link_id' => $link_id,
            'aff_source' => 'smartaff'
        ), $link->affiliate_url);

        return sprintf(
            '<a href="%s" class="smartaff-link" data-link-id="%s" target="_blank" rel="noopener noreferrer" style="color:#0073aa;text-decoration:none;">%s</a>',
            esc_url($tracking_url),
            esc_attr($link_id),
            esc_html($content ?: 'View Affiliate Link')
        );
    }

    public function track_affiliate_click() {
        global $wpdb;
        $link_id = sanitize_text_field($_POST['link_id'] ?? '');

        if ($link_id) {
            $table_name = $wpdb->prefix . 'smartaff_links';
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET clicks = clicks + 1 WHERE link_id = %s",
                $link_id
            ));
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script('smartaff-tracker', SMARTAFF_PLUGIN_URL . 'js/tracker.js', array('jquery'), SMARTAFF_VERSION, true);
        wp_localize_script('smartaff-tracker', 'smartaffAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('smartaff-admin', SMARTAFF_PLUGIN_URL . 'css/admin.css', array(), SMARTAFF_VERSION);
    }
}

SmartAffiliatePro::get_instance();
?>