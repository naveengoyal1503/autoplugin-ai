<?php
/*
Plugin Name: ContentROI Tracker
Plugin URI: https://contentroi.local
Description: Advanced analytics for affiliate links, sponsored content, and ad revenue tracking
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentROI_Tracker.php
License: GPL v2 or later
Text Domain: contentroi-tracker
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTROI_VERSION', '1.0.0');
define('CONTENTROI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTROI_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentROI_Tracker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_tracker'));
        add_action('wp_ajax_contentroi_track_click', array($this, 'track_link_click'));
        add_action('wp_ajax_nopriv_contentroi_track_click', array($this, 'track_link_click'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_clicks = $wpdb->prefix . 'contentroi_clicks';
        $table_conversions = $wpdb->prefix . 'contentroi_conversions';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_clicks'") != $table_clicks) {
            $sql = "CREATE TABLE $table_clicks (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                link_id varchar(100) NOT NULL,
                link_url text NOT NULL,
                click_time datetime DEFAULT CURRENT_TIMESTAMP,
                user_ip varchar(45),
                user_agent text,
                referrer text,
                PRIMARY KEY (id),
                KEY link_id (link_id),
                KEY click_time (click_time)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_conversions'") != $table_conversions) {
            $sql = "CREATE TABLE $table_conversions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                link_id varchar(100) NOT NULL,
                conversion_amount decimal(10,2),
                conversion_time datetime DEFAULT CURRENT_TIMESTAMP,
                conversion_source varchar(50),
                PRIMARY KEY (id),
                KEY link_id (link_id),
                KEY conversion_time (conversion_time)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        add_option('contentroi_license_type', 'free');
    }

    public function deactivate_plugin() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentROI Tracker',
            'ContentROI',
            'manage_options',
            'contentroi-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'contentroi-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentroi-dashboard',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'contentroi-dashboard',
            'Tracked Links',
            'Tracked Links',
            'manage_options',
            'contentroi-links',
            array($this, 'render_links')
        );

        add_submenu_page(
            'contentroi-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentroi-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'contentroi-') === false) {
            return;
        }
        wp_enqueue_style('contentroi-admin', CONTENTROI_PLUGIN_URL . 'assets/admin.css', array(), CONTENTROI_VERSION);
        wp_enqueue_script('contentroi-chart', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
        wp_enqueue_script('contentroi-admin', CONTENTROI_PLUGIN_URL . 'assets/admin.js', array('wp-api-fetch', 'contentroi-chart'), CONTENTROI_VERSION, true);
    }

    public function enqueue_frontend_tracker() {
        wp_enqueue_script('contentroi-tracker', CONTENTROI_PLUGIN_URL . 'assets/tracker.js', array(), CONTENTROI_VERSION, true);
        wp_localize_script('contentroi-tracker', 'contentroiData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentroi_nonce')
        ));
    }

    public function track_link_click() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contentroi_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        global $wpdb;
        $link_id = sanitize_text_field($_POST['link_id']);
        $link_url = esc_url($_POST['link_url']);
        $user_ip = $this->get_user_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url($_SERVER['HTTP_REFERER']) : '';

        $wpdb->insert(
            $wpdb->prefix . 'contentroi_clicks',
            array(
                'link_id' => $link_id,
                'link_url' => $link_url,
                'user_ip' => $user_ip,
                'user_agent' => $user_agent,
                'referrer' => $referrer
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        wp_send_json_success('Click tracked');
    }

    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }

    public function render_dashboard() {
        global $wpdb;
        $license = get_option('contentroi_license_type', 'free');
        $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}contentroi_clicks");
        $today_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}contentroi_clicks WHERE DATE(click_time) = CURDATE()");
        ?>
        <div class="wrap">
            <h1>ContentROI Dashboard</h1>
            <div class="contentroi-stats">
                <div class="stat-box">
                    <h3>Total Clicks</h3>
                    <p class="stat-number"><?php echo intval($total_clicks); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Today's Clicks</h3>
                    <p class="stat-number"><?php echo intval($today_clicks); ?></p>
                </div>
                <div class="stat-box">
                    <h3>License</h3>
                    <p class="stat-text"><?php echo ucfirst($license); ?></p>
                </div>
            </div>
            <?php if ($license === 'free') { ?>
                <div class="contentroi-upgrade-notice">
                    <p>Upgrade to Premium for advanced analytics, AI recommendations, and more!</p>
                    <button class="button button-primary">Upgrade Now</button>
                </div>
            <?php } ?>
        </div>
        <?php
    }

    public function render_links() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT link_id, link_url, COUNT(*) as click_count FROM {$wpdb->prefix}contentroi_clicks GROUP BY link_id ORDER BY click_count DESC LIMIT 20");
        ?>
        <div class="wrap">
            <h1>Tracked Links Performance</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Link ID</th>
                        <th>URL</th>
                        <th>Total Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link) { ?>
                        <tr>
                            <td><?php echo esc_html($link->link_id); ?></td>
                            <td><?php echo esc_url($link->link_url); ?></td>
                            <td><?php echo intval($link->click_count); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>ContentROI Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentroi_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Tracking Enabled</th>
                        <td>
                            <input type="checkbox" name="contentroi_tracking_enabled" value="1" <?php checked(get_option('contentroi_tracking_enabled'), 1); ?> />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

function contentroi_tracker() {
    return ContentROI_Tracker::get_instance();
}

contentroi_tracker();
?>