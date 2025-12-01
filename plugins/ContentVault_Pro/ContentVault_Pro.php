<?php
/*
Plugin Name: ContentVault Pro
Plugin URI: https://contentvault.example.com
Description: Monetize WordPress content with flexible subscription and membership management
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentVault_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: contentvault-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('CONTENTVAULT_VERSION', '1.0.0');
define('CONTENTVAULT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTVAULT_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentVault {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_shortcode('contentvault_protected', array($this, 'protected_content_shortcode'));
        add_action('the_content', array($this, 'filter_protected_content'));
    }

    private function load_dependencies() {
        require_once CONTENTVAULT_PLUGIN_DIR . 'includes/class-database.php';
        require_once CONTENTVAULT_PLUGIN_DIR . 'includes/class-subscription.php';
        require_once CONTENTVAULT_PLUGIN_DIR . 'includes/class-payment.php';
    }

    public function activate() {
        $db = new CV_Database();
        $db->create_tables();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        load_plugin_textdomain('contentvault-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function admin_menu() {
        add_menu_page(
            'ContentVault Pro',
            'ContentVault Pro',
            'manage_options',
            'contentvault-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-lock',
            30
        );

        add_submenu_page(
            'contentvault-dashboard',
            'Manage Tiers',
            'Manage Tiers',
            'manage_options',
            'contentvault-tiers',
            array($this, 'render_tiers_page')
        );

        add_submenu_page(
            'contentvault-dashboard',
            'Subscribers',
            'Subscribers',
            'manage_options',
            'contentvault-subscribers',
            array($this, 'render_subscribers_page')
        );
    }

    public function render_dashboard() {
        $subscription_model = new CV_Subscription();
        $stats = $subscription_model->get_dashboard_stats();
        ?>
        <div class="wrap">
            <h1>ContentVault Pro Dashboard</h1>
            <div class="contentvault-stats">
                <div class="stat-box">
                    <h3>Total Subscribers</h3>
                    <p class="stat-number"><?php echo esc_html($stats['total_subscribers']); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Monthly Revenue</h3>
                    <p class="stat-number">$<?php echo esc_html($stats['monthly_revenue']); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Active Subscriptions</h3>
                    <p class="stat-number"><?php echo esc_html($stats['active_subscriptions']); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Churn Rate</h3>
                    <p class="stat-number"><?php echo esc_html($stats['churn_rate']); ?>%</p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_tiers_page() {
        ?>
        <div class="wrap">
            <h1>Manage Subscription Tiers</h1>
            <p>Manage your subscription tiers and pricing models here.</p>
        </div>
        <?php
    }

    public function render_subscribers_page() {
        $subscription_model = new CV_Subscription();
        $subscribers = $subscription_model->get_all_subscribers();
        ?>
        <div class="wrap">
            <h1>Subscribers</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Tier</th>
                        <th>Join Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscribers as $subscriber): ?>
                        <tr>
                            <td><?php echo esc_html($subscriber['email']); ?></td>
                            <td><?php echo esc_html($subscriber['tier']); ?></td>
                            <td><?php echo esc_html($subscriber['join_date']); ?></td>
                            <td><?php echo esc_html($subscriber['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function protected_content_shortcode($atts) {
        $atts = shortcode_atts(array(
            'tier' => 'basic',
            'message' => 'This content is protected. Please subscribe to view.'
        ), $atts);

        $subscription = new CV_Subscription();
        if ($subscription->user_has_access(get_current_user_id(), $atts['tier'])) {
            return $atts['content'];
        }
        return '<div class="contentvault-locked">' . esc_html($atts['message']) . '</div>';
    }

    public function filter_protected_content($content) {
        if (is_single() && has_post_meta(get_the_ID(), '_contentvault_protected')) {
            $required_tier = get_post_meta(get_the_ID(), '_contentvault_required_tier', true);
            $subscription = new CV_Subscription();
            
            if (!$subscription->user_has_access(get_current_user_id(), $required_tier)) {
                return '<div class="contentvault-locked"><p>This content is protected. Please <a href="#subscribe">subscribe</a> to view.</p></div>';
            }
        }
        return $content;
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('contentvault-frontend', CONTENTVAULT_PLUGIN_URL . 'assets/css/frontend.css');
        wp_enqueue_script('contentvault-frontend', CONTENTVAULT_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CONTENTVAULT_VERSION);
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('contentvault-admin', CONTENTVAULT_PLUGIN_URL . 'assets/css/admin.css');
        wp_enqueue_script('contentvault-admin', CONTENTVAULT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CONTENTVAULT_VERSION);
    }
}

class CV_Database {
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tiers_table = $wpdb->prefix . 'contentvault_tiers';
        $subscribers_table = $wpdb->prefix . 'contentvault_subscribers';
        $transactions_table = $wpdb->prefix . 'contentvault_transactions';

        $sql = "CREATE TABLE IF NOT EXISTS $tiers_table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description LONGTEXT,
            price DECIMAL(10, 2) NOT NULL,
            billing_cycle VARCHAR(50),
            features LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS $subscribers_table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED,
            tier_id BIGINT(20) UNSIGNED NOT NULL,
            email VARCHAR(255) NOT NULL,
            status VARCHAR(50) DEFAULT 'active',
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tier_id) REFERENCES $tiers_table(id)
        ) $charset_collate;

        CREATE TABLE IF NOT EXISTS $transactions_table (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            subscriber_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'USD',
            status VARCHAR(50) DEFAULT 'pending',
            transaction_id VARCHAR(255),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (subscriber_id) REFERENCES $subscribers_table(id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

class CV_Subscription {
    private $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'contentvault_subscribers';
    }

    public function get_dashboard_stats() {
        global $wpdb;
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $this->table WHERE status = 'active'");
        $revenue = $wpdb->get_var("SELECT SUM(amount) FROM " . $wpdb->prefix . "contentvault_transactions WHERE status = 'completed' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
        $subscriptions = $wpdb->get_var("SELECT COUNT(*) FROM $this->table WHERE status = 'active'");
        $churn = $wpdb->get_var("SELECT COUNT(*) FROM $this->table WHERE status = 'cancelled'");
        $churn_rate = ($total > 0) ? round(($churn / $total) * 100, 2) : 0;

        return array(
            'total_subscribers' => intval($total),
            'monthly_revenue' => floatval($revenue) ?? 0,
            'active_subscriptions' => intval($subscriptions),
            'churn_rate' => $churn_rate
        );
    }

    public function get_all_subscribers() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM $this->table ORDER BY created_at DESC",
            ARRAY_A
        );
    }

    public function user_has_access($user_id, $tier) {
        if (current_user_can('manage_options')) {
            return true;
        }
        global $wpdb;
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $this->table WHERE user_id = %d AND tier_id = (SELECT id FROM " . $wpdb->prefix . "contentvault_tiers WHERE name = %s) AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW())",
                $user_id,
                $tier
            )
        );
        return !empty($result);
    }
}

class CV_Payment {
    public function process_payment($amount, $user_id, $tier_id) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'contentvault_transactions';
        
        $wpdb->insert(
            $transactions_table,
            array(
                'subscriber_id' => $user_id,
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'pending'
            ),
            array('%d', '%f', '%s', '%s')
        );
        return $wpdb->insert_id;
    }
}

function cv_get_instance() {
    return ContentVault::getInstance();
}

cv_get_instance();
?>