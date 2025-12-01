/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Revenue_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: Revenue Optimizer Pro
 * Plugin URI: https://example.com/revenue-optimizer-pro
 * Description: All-in-one monetization management dashboard for WordPress sites
 * Version: 1.0.0
 * Author: Revenue Optimizer
 * License: GPL v2 or later
 * Text Domain: revenue-optimizer-pro
 */

if (!defined('ABSPATH')) exit;

define('ROP_VERSION', '1.0.0');
define('ROP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ROP_PLUGIN_URL', plugin_dir_url(__FILE__));

class RevenueOptimizerPro {
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendAssets'));
        add_action('init', array($this, 'registerCustomPostTypes'));
        add_shortcode('revenue_tracker', array($this, 'renderRevenueTracker'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rop_revenue_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            revenue_type varchar(50) NOT NULL,
            amount decimal(10, 2) NOT NULL,
            source varchar(255),
            date_logged datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('rop_plugin_activated', true);
    }

    public function deactivate() {
        delete_option('rop_plugin_activated');
    }

    public function registerCustomPostTypes() {
        register_post_type('rop_monetization', array(
            'labels' => array(
                'name' => __('Monetization Methods', 'revenue-optimizer-pro'),
                'singular_name' => __('Monetization Method', 'revenue-optimizer-pro')
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'custom-fields')
        ));
    }

    public function addAdminMenu() {
        add_menu_page(
            __('Revenue Optimizer', 'revenue-optimizer-pro'),
            __('Revenue Optimizer', 'revenue-optimizer-pro'),
            'manage_options',
            'revenue-optimizer-pro',
            array($this, 'renderDashboard'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'revenue-optimizer-pro',
            __('Dashboard', 'revenue-optimizer-pro'),
            __('Dashboard', 'revenue-optimizer-pro'),
            'manage_options',
            'revenue-optimizer-pro',
            array($this, 'renderDashboard')
        );

        add_submenu_page(
            'revenue-optimizer-pro',
            __('Monetization Methods', 'revenue-optimizer-pro'),
            __('Monetization Methods', 'revenue-optimizer-pro'),
            'manage_options',
            'rop-methods',
            array($this, 'renderMethods')
        );

        add_submenu_page(
            'revenue-optimizer-pro',
            __('Revenue Logs', 'revenue-optimizer-pro'),
            __('Revenue Logs', 'revenue-optimizer-pro'),
            'manage_options',
            'rop-logs',
            array($this, 'renderLogs')
        );

        add_submenu_page(
            'revenue-optimizer-pro',
            __('Settings', 'revenue-optimizer-pro'),
            __('Settings', 'revenue-optimizer-pro'),
            'manage_options',
            'rop-settings',
            array($this, 'renderSettings')
        );
    }

    public function renderDashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rop_revenue_logs';
        
        $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
        $monthly_revenue = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM $table_name WHERE DATE_FORMAT(date_logged, '%Y-%m') = %s",
                date('Y-m')
            )
        );
        $revenue_sources = $wpdb->get_results("SELECT revenue_type, SUM(amount) as total FROM $table_name GROUP BY revenue_type");
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Revenue Dashboard', 'revenue-optimizer-pro')); ?></h1>
            <div class="rop-dashboard-grid">
                <div class="rop-card">
                    <h3><?php echo esc_html(__('Total Revenue', 'revenue-optimizer-pro')); ?></h3>
                    <p class="rop-big-number">$<?php echo number_format($total_revenue ?: 0, 2); ?></p>
                </div>
                <div class="rop-card">
                    <h3><?php echo esc_html(__('This Month', 'revenue-optimizer-pro')); ?></h3>
                    <p class="rop-big-number">$<?php echo number_format($monthly_revenue ?: 0, 2); ?></p>
                </div>
            </div>
            <div class="rop-card">
                <h3><?php echo esc_html(__('Revenue by Source', 'revenue-optimizer-pro')); ?></h3>
                <table class="wp-list-table fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html(__('Source', 'revenue-optimizer-pro')); ?></th>
                            <th><?php echo esc_html(__('Amount', 'revenue-optimizer-pro')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revenue_sources as $source) : ?>
                            <tr>
                                <td><?php echo esc_html($source->revenue_type); ?></td>
                                <td>$<?php echo number_format($source->total, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function renderMethods() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Monetization Methods', 'revenue-optimizer-pro')); ?></h1>
            <p><?php echo esc_html(__('Configure and manage your revenue streams', 'revenue-optimizer-pro')); ?></p>
            <table class="wp-list-table fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html(__('Method', 'revenue-optimizer-pro')); ?></th>
                        <th><?php echo esc_html(__('Status', 'revenue-optimizer-pro')); ?></th>
                        <th><?php echo esc_html(__('Action', 'revenue-optimizer-pro')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo esc_html(__('Display Ads', 'revenue-optimizer-pro')); ?></td>
                        <td><?php echo esc_html(__('Available', 'revenue-optimizer-pro')); ?></td>
                        <td><a href="#" class="button"><?php echo esc_html(__('Configure', 'revenue-optimizer-pro')); ?></a></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html(__('Affiliate Marketing', 'revenue-optimizer-pro')); ?></td>
                        <td><?php echo esc_html(__('Available', 'revenue-optimizer-pro')); ?></td>
                        <td><a href="#" class="button"><?php echo esc_html(__('Configure', 'revenue-optimizer-pro')); ?></a></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html(__('Memberships', 'revenue-optimizer-pro')); ?></td>
                        <td><?php echo esc_html(__('Available', 'revenue-optimizer-pro')); ?></td>
                        <td><a href="#" class="button"><?php echo esc_html(__('Configure', 'revenue-optimizer-pro')); ?></a></td>
                    </tr>
                    <tr>
                        <td><?php echo esc_html(__('Sponsored Content', 'revenue-optimizer-pro')); ?></td>
                        <td><?php echo esc_html(__('Available', 'revenue-optimizer-pro')); ?></td>
                        <td><a href="#" class="button"><?php echo esc_html(__('Configure', 'revenue-optimizer-pro')); ?></a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderLogs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rop_revenue_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date_logged DESC LIMIT 50");
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Revenue Logs', 'revenue-optimizer-pro')); ?></h1>
            <table class="wp-list-table fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html(__('Type', 'revenue-optimizer-pro')); ?></th>
                        <th><?php echo esc_html(__('Amount', 'revenue-optimizer-pro')); ?></th>
                        <th><?php echo esc_html(__('Source', 'revenue-optimizer-pro')); ?></th>
                        <th><?php echo esc_html(__('Date', 'revenue-optimizer-pro')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log->revenue_type); ?></td>
                            <td>$<?php echo number_format($log->amount, 2); ?></td>
                            <td><?php echo esc_html($log->source ?: __('N/A', 'revenue-optimizer-pro')); ?></td>
                            <td><?php echo esc_html(date('M d, Y H:i', strtotime($log->date_logged))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderSettings() {
        if ($_POST && check_admin_referer('rop_settings_nonce')) {
            update_option('rop_currency', sanitize_text_field($_POST['rop_currency'] ?? 'USD'));
            echo '<div class="notice notice-success"><p>' . esc_html(__('Settings saved successfully!', 'revenue-optimizer-pro')) . '</p></div>';
        }

        $currency = get_option('rop_currency', 'USD');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Revenue Optimizer Settings', 'revenue-optimizer-pro')); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('rop_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="rop_currency"><?php echo esc_html(__('Currency', 'revenue-optimizer-pro')); ?></label></th>
                        <td>
                            <select id="rop_currency" name="rop_currency">
                                <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                                <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                                <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueueAdminAssets() {
        wp_enqueue_style('rop-admin', ROP_PLUGIN_URL . 'admin.css', array(), ROP_VERSION);
    }

    public function enqueueFrontendAssets() {
        wp_enqueue_style('rop-frontend', ROP_PLUGIN_URL . 'frontend.css', array(), ROP_VERSION);
    }

    public function renderRevenueTracker() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rop_revenue_logs';
        $total = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
        return '<div class="rop-revenue-badge">Total Revenue: $' . number_format($total ?: 0, 2) . '</div>';
    }
}

RevenueOptimizerPro::getInstance();
?>