<?php
/*
Plugin Name: CommissionFlow
Plugin URI: https://commissionflow.io
Description: Affiliate commission management and tracking system with automated payouts and performance analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=CommissionFlow.php
License: GPL v2 or later
Text Domain: commissionflow
*/

if (!defined('ABSPATH')) exit;

define('COMMISSIONFLOW_VERSION', '1.0.0');
define('COMMISSIONFLOW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('COMMISSIONFLOW_PLUGIN_URL', plugin_dir_url(__FILE__));

class CommissionFlow {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->registerHooks();
        $this->loadDependencies();
    }

    private function registerHooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_shortcode('cf_affiliate_link', array($this, 'renderAffiliateLink'));
        add_action('rest_api_init', array($this, 'registerRestRoutes'));
    }

    private function loadDependencies() {
        require_once COMMISSIONFLOW_PLUGIN_DIR . 'includes/database.php';
        require_once COMMISSIONFLOW_PLUGIN_DIR . 'includes/affiliate.php';
        require_once COMMISSIONFLOW_PLUGIN_DIR . 'includes/commission.php';
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cf_affiliates (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            affiliate_code varchar(50) NOT NULL UNIQUE,
            commission_rate float NOT NULL DEFAULT 10,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cf_commissions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            affiliate_id mediumint(9) NOT NULL,
            sale_amount decimal(10,2) NOT NULL,
            commission_amount decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            paid_at datetime,
            PRIMARY KEY (id),
            FOREIGN KEY (affiliate_id) REFERENCES {$wpdb->prefix}cf_affiliates(id)
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cf_clicks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            affiliate_id mediumint(9) NOT NULL,
            click_date datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(50),
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('commissionflow_version', COMMISSIONFLOW_VERSION);
    }

    public function deactivate() {
        // Cleanup on deactivation
    }

    public function addAdminMenu() {
        add_menu_page(
            'CommissionFlow',
            'CommissionFlow',
            'manage_options',
            'commissionflow',
            array($this, 'renderDashboard'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'commissionflow',
            'Affiliates',
            'Affiliates',
            'manage_options',
            'commissionflow-affiliates',
            array($this, 'renderAffiliatesPage')
        );

        add_submenu_page(
            'commissionflow',
            'Commissions',
            'Commissions',
            'manage_options',
            'commissionflow-commissions',
            array($this, 'renderCommissionsPage')
        );

        add_submenu_page(
            'commissionflow',
            'Settings',
            'Settings',
            'manage_options',
            'commissionflow-settings',
            array($this, 'renderSettingsPage')
        );
    }

    public function renderDashboard() {
        global $wpdb;
        $affiliates_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cf_affiliates WHERE status='active'");
        $pending_commission = $wpdb->get_var("SELECT SUM(commission_amount) FROM {$wpdb->prefix}cf_commissions WHERE status='pending'");
        $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cf_clicks");
        ?>
        <div class="wrap">
            <h1>CommissionFlow Dashboard</h1>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px;">
                <div style="background: #f1f1f1; padding: 20px; border-radius: 5px;">
                    <h3>Active Affiliates</h3>
                    <p style="font-size: 24px; margin: 0;"><?php echo esc_html($affiliates_count ?: 0); ?></p>
                </div>
                <div style="background: #f1f1f1; padding: 20px; border-radius: 5px;">
                    <h3>Pending Commission</h3>
                    <p style="font-size: 24px; margin: 0;"><?php echo esc_html('$' . number_format($pending_commission ?: 0, 2)); ?></p>
                </div>
                <div style="background: #f1f1f1; padding: 20px; border-radius: 5px;">
                    <h3>Total Clicks</h3>
                    <p style="font-size: 24px; margin: 0;"><?php echo esc_html($total_clicks ?: 0); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function renderAffiliatesPage() {
        global $wpdb;
        $affiliates = $wpdb->get_results("SELECT a.*, u.display_name FROM {$wpdb->prefix}cf_affiliates a LEFT JOIN {$wpdb->prefix}users u ON a.user_id = u.ID");
        ?>
        <div class="wrap">
            <h1>Manage Affiliates</h1>
            <table class="wp-list-table widefat striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Commission Rate</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($affiliates as $affiliate): ?>
                    <tr>
                        <td><?php echo esc_html($affiliate->display_name); ?></td>
                        <td><code><?php echo esc_html($affiliate->affiliate_code); ?></code></td>
                        <td><?php echo esc_html($affiliate->commission_rate . '%'); ?></td>
                        <td><?php echo esc_html(ucfirst($affiliate->status)); ?></td>
                        <td><?php echo esc_html(date('M d, Y', strtotime($affiliate->created_at))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderCommissionsPage() {
        global $wpdb;
        $commissions = $wpdb->get_results("SELECT c.*, a.affiliate_code, u.display_name FROM {$wpdb->prefix}cf_commissions c LEFT JOIN {$wpdb->prefix}cf_affiliates a ON c.affiliate_id = a.id LEFT JOIN {$wpdb->prefix}users u ON a.user_id = u.ID ORDER BY c.created_at DESC");
        ?>
        <div class="wrap">
            <h1>Commission History</h1>
            <table class="wp-list-table widefat striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Affiliate</th>
                        <th>Sale Amount</th>
                        <th>Commission</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commissions as $commission): ?>
                    <tr>
                        <td><?php echo esc_html($commission->display_name . ' (' . $commission->affiliate_code . ')'); ?></td>
                        <td><?php echo esc_html('$' . number_format($commission->sale_amount, 2)); ?></td>
                        <td><?php echo esc_html('$' . number_format($commission->commission_amount, 2)); ?></td>
                        <td><?php echo esc_html(ucfirst($commission->status)); ?></td>
                        <td><?php echo esc_html(date('M d, Y', strtotime($commission->created_at))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderSettingsPage() {
        ?>
        <div class="wrap">
            <h1>CommissionFlow Settings</h1>
            <p>Premium features available: Unlimited affiliates, Advanced analytics, Automated payouts, Custom commission rules.</p>
            <a href="#" class="button button-primary">Upgrade to Premium</a>
        </div>
        <?php
    }

    public function renderAffiliateLink($atts) {
        $atts = shortcode_atts(array(
            'affiliate_code' => '',
            'text' => 'Click here'
        ), $atts, 'cf_affiliate_link');

        $affiliate_code = sanitize_text_field($atts['affiliate_code']);
        $text = sanitize_text_field($atts['text']);

        if (!$affiliate_code) {
            return '';
        }

        global $wpdb;
        $affiliate = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}cf_affiliates WHERE affiliate_code = %s",
            $affiliate_code
        ));

        if ($affiliate) {
            $wpdb->insert($wpdb->prefix . 'cf_clicks', array(
                'affiliate_id' => $affiliate->id,
                'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'])
            ));
        }

        return '<a href="' . esc_url(add_query_arg('ref', $affiliate_code)) . '">' . esc_html($text) . '</a>';
    }

    public function enqueueScripts() {}

    public function enqueueAdminScripts() {}

    public function registerRestRoutes() {
        register_rest_route('commissionflow/v1', '/affiliates', array(
            'methods' => 'GET',
            'callback' => array($this, 'getAffiliatesRest'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }

    public function getAffiliatesRest() {
        global $wpdb;
        $affiliates = $wpdb->get_results("SELECT id, affiliate_code, commission_rate, status FROM {$wpdb->prefix}cf_affiliates");
        return rest_ensure_response($affiliates);
    }
}

CommissionFlow::getInstance();
?>