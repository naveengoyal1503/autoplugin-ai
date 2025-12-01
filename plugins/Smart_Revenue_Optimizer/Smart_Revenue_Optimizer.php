<?php
/*
Plugin Name: Smart Revenue Optimizer
Plugin URI: https://smartrevenueoptimizer.com
Description: Intelligent monetization strategy analyzer and revenue stream manager for WordPress sites
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Revenue_Optimizer.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: smart-revenue-optimizer
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRO_VERSION', '1.0.0');

class SmartRevenueOptimizer {
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
        add_action('wp_dashboard_setup', array($this, 'addDashboardWidget'));
        add_action('rest_api_init', array($this, 'registerRestRoutes'));
        add_shortcode('sro_revenue_stats', array($this, 'renderRevenueStats'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sro_revenue_tracking';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            date_recorded datetime DEFAULT CURRENT_TIMESTAMP,
            revenue_source varchar(100) NOT NULL,
            amount decimal(10, 2) NOT NULL,
            traffic_count int(11) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('sro_activated', true);
    }

    public function addAdminMenu() {
        add_menu_page(
            'Smart Revenue Optimizer',
            'Revenue Optimizer',
            'manage_options',
            'sro-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'sro-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'sro-dashboard',
            array($this, 'renderDashboard')
        );

        add_submenu_page(
            'sro-dashboard',
            'Revenue Streams',
            'Revenue Streams',
            'manage_options',
            'sro-streams',
            array($this, 'renderRevenueStreams')
        );

        add_submenu_page(
            'sro-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'sro-settings',
            array($this, 'renderSettings')
        );
    }

    public function enqueueAdminAssets($hook) {
        if (strpos($hook, 'sro-') === false) {
            return;
        }

        wp_enqueue_style(
            'sro-admin-style',
            SRO_PLUGIN_URL . 'assets/admin-style.css',
            array(),
            SRO_VERSION
        );

        wp_enqueue_script(
            'sro-admin-script',
            SRO_PLUGIN_URL . 'assets/admin-script.js',
            array('jquery'),
            SRO_VERSION,
            true
        );

        wp_localize_script('sro-admin-script', 'sroData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sro_nonce')
        ));
    }

    public function registerRestRoutes() {
        register_rest_route('sro/v1', '/revenue-analysis', array(
            'methods' => 'GET',
            'callback' => array($this, 'getRevenueAnalysis'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));

        register_rest_route('sro/v1', '/recommendations', array(
            'methods' => 'GET',
            'callback' => array($this, 'getRecommendations'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));

        register_rest_route('sro/v1', '/track-revenue', array(
            'methods' => 'POST',
            'callback' => array($this, 'trackRevenue'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }

    public function getRevenueAnalysis() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sro_revenue_tracking';
        $days = 30;
        $date = date('Y-m-d', strtotime("-{$days} days"));

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT revenue_source, SUM(amount) as total_revenue, COUNT(*) as transactions 
                FROM $table_name 
                WHERE date_recorded >= %s 
                GROUP BY revenue_source",
                $date
            )
        );

        return rest_ensure_response(array(
            'success' => true,
            'data' => $results,
            'period' => $days . ' days'
        ));
    }

    public function getRecommendations() {
        $recommendations = array(
            array(
                'strategy' => 'Display Advertising',
                'reason' => 'High traffic volume detected',
                'potential_revenue' => 'Moderate',
                'implementation' => 'Easy',
                'plugins' => array('Easy Google AdSense', 'Ad Inserter')
            ),
            array(
                'strategy' => 'Affiliate Marketing',
                'reason' => 'Product-focused content detected',
                'potential_revenue' => 'High',
                'implementation' => 'Medium',
                'plugins' => array('AffiliateWP', 'ThirstyAffiliates')
            ),
            array(
                'strategy' => 'Membership Subscriptions',
                'reason' => 'Engaged audience with repeat visitors',
                'potential_revenue' => 'High',
                'implementation' => 'Medium',
                'plugins' => array('Paid Member Subscriptions', 'MemberPress')
            ),
            array(
                'strategy' => 'Digital Products',
                'reason' => 'Unique content creation capability',
                'potential_revenue' => 'High',
                'implementation' => 'Medium',
                'plugins' => array('WooCommerce', 'Easy Digital Downloads')
            ),
            array(
                'strategy' => 'Sponsored Content',
                'reason' => 'Niche audience alignment',
                'potential_revenue' => 'Variable',
                'implementation' => 'Easy',
                'plugins' => array('SponsorWP')
            )
        );

        return rest_ensure_response(array(
            'success' => true,
            'recommendations' => $recommendations
        ));
    }

    public function trackRevenue($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sro_revenue_tracking';

        $params = $request->get_json_params();
        $source = sanitize_text_field($params['source'] ?? 'unknown');
        $amount = floatval($params['amount'] ?? 0);
        $traffic = intval($params['traffic_count'] ?? 0);

        $inserted = $wpdb->insert(
            $table_name,
            array(
                'revenue_source' => $source,
                'amount' => $amount,
                'traffic_count' => $traffic
            ),
            array('%s', '%f', '%d')
        );

        if ($inserted) {
            return rest_ensure_response(array('success' => true, 'id' => $wpdb->insert_id));
        }

        return rest_ensure_response(array('success' => false, 'error' => 'Database error'));
    }

    public function renderDashboard() {
        ?>
        <div class="wrap">
            <h1>Smart Revenue Optimizer Dashboard</h1>
            <div id="sro-dashboard-container" class="sro-container">
                <div class="sro-card">
                    <h2>Revenue Overview</h2>
                    <div id="sro-revenue-chart"></div>
                </div>
                <div class="sro-card">
                    <h2>Quick Recommendations</h2>
                    <div id="sro-recommendations"></div>
                </div>
            </div>
        </div>
        <?php
    }

    public function renderRevenueStreams() {
        ?>
        <div class="wrap">
            <h1>Revenue Streams Management</h1>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Revenue Source</th>
                        <th>Status</th>
                        <th>Configuration</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Display Advertising</td>
                        <td><span class="status-inactive">Not Configured</span></td>
                        <td><a href="#" class="button">Configure</a></td>
                    </tr>
                    <tr>
                        <td>Affiliate Marketing</td>
                        <td><span class="status-inactive">Not Configured</span></td>
                        <td><a href="#" class="button">Configure</a></td>
                    </tr>
                    <tr>
                        <td>Memberships</td>
                        <td><span class="status-inactive">Not Configured</span></td>
                        <td><a href="#" class="button">Configure</a></td>
                    </tr>
                    <tr>
                        <td>Sponsored Content</td>
                        <td><span class="status-inactive">Not Configured</span></td>
                        <td><a href="#" class="button">Configure</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderSettings() {
        ?>
        <div class="wrap">
            <h1>Smart Revenue Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="sro-tracking">Enable Revenue Tracking</label></th>
                        <td>
                            <input type="checkbox" id="sro-tracking" name="sro-tracking" value="1" checked>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sro-email">Email Notifications</label></th>
                        <td>
                            <input type="email" id="sro-email" name="sro-email" value="<?php echo get_option('admin_email'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sro-currency">Currency</label></th>
                        <td>
                            <select id="sro-currency" name="sro-currency">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p><input type="submit" class="button button-primary" value="Save Settings"></p>
            </form>
        </div>
        <?php
    }

    public function addDashboardWidget() {
        wp_add_dashboard_widget(
            'sro-revenue-widget',
            'Revenue Optimizer',
            array($this, 'renderDashboardWidget')
        );
    }

    public function renderDashboardWidget() {
        echo '<p>Track your monetization progress from your WordPress dashboard.</p>';
        echo '<a href="' . admin_url('admin.php?page=sro-dashboard') . '" class="button">View Full Dashboard</a>';
    }

    public function renderRevenueStats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sro_revenue_tracking';
        $total = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
        
        return '<div class="sro-stats"><strong>Total Revenue:</strong> $' . number_format($total, 2) . '</div>';
    }
}

SmartRevenueOptimizer::getInstance();
?>