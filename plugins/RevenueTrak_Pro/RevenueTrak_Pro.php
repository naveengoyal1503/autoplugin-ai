<?php
/*
Plugin Name: RevenueTrak Pro
Plugin URI: https://revenuetrak.pro
Description: Track and optimize multiple WordPress revenue streams with advanced analytics and recommendations
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=RevenueTrak_Pro.php
License: GPL2
Text Domain: revenuetrak-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('REVENUETRAK_VERSION', '1.0.0');
define('REVENUETRAK_PATH', plugin_dir_path(__FILE__));
define('REVENUETRAK_URL', plugin_dir_url(__FILE__));

class RevenueTrakPro {
    private static $instance = null;
    private $options = array();

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->load_hooks();
        $this->create_tables();
    }

    public function load_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_rt_log_revenue', array($this, 'log_revenue'));
        add_action('wp_ajax_rt_get_analytics', array($this, 'get_analytics'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'revenuetrak_events';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(100) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            description text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'RevenueTrak Pro',
            'RevenueTrak Pro',
            'manage_options',
            'revenuetrak-pro',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'revenuetrak-pro',
            'Analytics',
            'Analytics',
            'manage_options',
            'revenuetrak-analytics',
            array($this, 'render_analytics')
        );

        add_submenu_page(
            'revenuetrak-pro',
            'Revenue Sources',
            'Revenue Sources',
            'manage_options',
            'revenuetrak-sources',
            array($this, 'render_sources')
        );

        add_submenu_page(
            'revenuetrak-pro',
            'Settings',
            'Settings',
            'manage_options',
            'revenuetrak-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'revenuetrak') === false) {
            return;
        }

        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
        wp_enqueue_style('revenuetrak-admin', REVENUETRAK_URL . 'assets/admin.css', array(), REVENUETRAK_VERSION);
        wp_enqueue_script('revenuetrak-admin', REVENUETRAK_URL . 'assets/admin.js', array('jquery', 'chart-js'), REVENUETRAK_VERSION, true);

        wp_localize_script('revenuetrak-admin', 'revenuetrakData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('revenuetrak_nonce'),
            'currency' => get_option('revenuetrak_currency', 'USD')
        ));
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'revenuetrak_widget',
            'Revenue Summary',
            array($this, 'render_widget')
        );
    }

    public function render_dashboard() {
        ?>
        <div class="wrap revenuetrak-container">
            <h1>RevenueTrak Pro Dashboard</h1>
            <div class="revenuetrak-grid">
                <div class="revenuetrak-card">
                    <h3>Total Revenue</h3>
                    <p class="revenuetrak-amount" id="total-revenue">$0.00</p>
                </div>
                <div class="revenuetrak-card">
                    <h3>This Month</h3>
                    <p class="revenuetrak-amount" id="monthly-revenue">$0.00</p>
                </div>
                <div class="revenuetrak-card">
                    <h3>Revenue Sources</h3>
                    <p class="revenuetrak-amount" id="source-count">0</p>
                </div>
                <div class="revenuetrak-card">
                    <h3>Average Per Source</h3>
                    <p class="revenuetrak-amount" id="avg-revenue">$0.00</p>
                </div>
            </div>
            <div class="revenuetrak-chart-container">
                <canvas id="revenuetrak-chart"></canvas>
            </div>
        </div>
        <?php
    }

    public function render_analytics() {
        ?>
        <div class="wrap revenuetrak-container">
            <h1>Revenue Analytics</h1>
            <div class="revenuetrak-filters">
                <select id="rt-date-range" class="revenuetrak-filter">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="365">Last Year</option>
                </select>
                <button class="button button-primary" id="rt-filter-btn">Filter</button>
            </div>
            <table class="wp-list-table widefat striped" id="rt-analytics-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody id="rt-table-body">
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_sources() {
        ?>
        <div class="wrap revenuetrak-container">
            <h1>Revenue Sources</h1>
            <div class="revenuetrak-source-form">
                <h3>Add Revenue Source</h3>
                <form id="rt-source-form">
                    <input type="text" id="rt-source-name" placeholder="Source Name" required>
                    <select id="rt-source-type" required>
                        <option value="">Select Type</option>
                        <option value="advertising">Advertising</option>
                        <option value="affiliate">Affiliate Marketing</option>
                        <option value="products">Product Sales</option>
                        <option value="services">Services</option>
                        <option value="memberships">Memberships</option>
                        <option value="donations">Donations</option>
                        <option value="other">Other</option>
                    </select>
                    <input type="number" id="rt-source-amount" placeholder="Amount" step="0.01" required>
                    <textarea id="rt-source-description" placeholder="Description"></textarea>
                    <button type="submit" class="button button-primary">Add Source</button>
                </form>
            </div>
            <div id="rt-sources-list"></div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap revenuetrak-container">
            <h1>RevenueTrak Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('revenuetrak_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="rt-currency">Currency</label></th>
                        <td>
                            <select name="revenuetrak_currency" id="rt-currency">
                                <option value="USD" <?php selected(get_option('revenuetrak_currency', 'USD'), 'USD'); ?>>USD</option>
                                <option value="EUR" <?php selected(get_option('revenuetrak_currency', 'USD'), 'EUR'); ?>>EUR</option>
                                <option value="GBP" <?php selected(get_option('revenuetrak_currency', 'USD'), 'GBP'); ?>>GBP</option>
                                <option value="AUD" <?php selected(get_option('revenuetrak_currency', 'USD'), 'AUD'); ?>>AUD</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rt-email-reports">Email Reports</label></th>
                        <td>
                            <input type="checkbox" name="revenuetrak_email_reports" id="rt-email-reports" value="1" <?php checked(get_option('revenuetrak_email_reports'), 1); ?>> Enable weekly email reports
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revenuetrak_events';
        $total = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
        $monthly = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table_name WHERE MONTH(timestamp) = MONTH(NOW()) AND YEAR(timestamp) = YEAR(NOW())"
        ));
        ?>
        <div class="revenuetrak-widget">
            <p><strong>Total Revenue:</strong> <?php echo wp_kses_post(get_option('revenuetrak_currency', 'USD')); ?> <?php echo esc_html(number_format($total, 2)); ?></p>
            <p><strong>This Month:</strong> <?php echo wp_kses_post(get_option('revenuetrak_currency', 'USD')); ?> <?php echo esc_html(number_format($monthly, 2)); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=revenuetrak-pro')); ?>" class="button">View Dashboard</a>
        </div>
        <?php
    }

    public function log_revenue() {
        check_ajax_referer('revenuetrak_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        global $wpdb;
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $description = sanitize_text_field($_POST['description']);
        $currency = sanitize_text_field($_POST['currency']);

        $wpdb->insert(
            $wpdb->prefix . 'revenuetrak_events',
            array(
                'source' => $source,
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description
            ),
            array('%s', '%f', '%s', '%s')
        );

        wp_send_json_success('Revenue logged successfully');
    }

    public function get_analytics() {
        check_ajax_referer('revenuetrak_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'revenuetrak_events';
        $days = intval($_POST['days']);

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) ORDER BY timestamp DESC",
            $days
        ));

        wp_send_json_success($results);
    }

    public function activate() {
        $this->create_tables();
        register_setting('revenuetrak_settings', 'revenuetrak_currency');
        register_setting('revenuetrak_settings', 'revenuetrak_email_reports');
    }

    public function deactivate() {
        wp_clear_scheduled_hook('revenuetrak_weekly_report');
    }
}

function revenuetrak_init() {
    RevenueTrakPro::get_instance();
}

add_action('plugins_loaded', 'revenuetrak_init');
?>