<?php
/*
Plugin Name: Smart Membership Upsell Pro
Plugin URI: https://smartmembershipupsell.com
Description: Intelligent membership plugin with behavior-based upselling and conversion optimization
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Membership_Upsell_Pro.php
License: GPL v2 or later
Text Domain: smart-membership-upsell
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SMU_VERSION', '1.0.0');
define('SMU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SMU_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartMembershipUpsell {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        $this->load_dependencies();
        $this->register_hooks();
    }
    
    private function load_dependencies() {
        require_once SMU_PLUGIN_DIR . 'includes/class-database.php';
        require_once SMU_PLUGIN_DIR . 'includes/class-membership.php';
        require_once SMU_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once SMU_PLUGIN_DIR . 'includes/class-admin.php';
    }
    
    private function register_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_shortcode('membership_upsell_widget', array($this, 'render_upsell_widget'));
        add_action('wp_footer', array($this, 'track_user_engagement'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Smart Membership Upsell',
            'SMU Pro',
            'manage_options',
            'smu-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-users'
        );
        
        add_submenu_page(
            'smu-dashboard',
            'Members',
            'Members',
            'manage_options',
            'smu-members',
            array($this, 'render_members_page')
        );
        
        add_submenu_page(
            'smu-dashboard',
            'Membership Plans',
            'Plans',
            'manage_options',
            'smu-plans',
            array($this, 'render_plans_page')
        );
        
        add_submenu_page(
            'smu-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'smu-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'smu-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'smu-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style('smu-frontend', SMU_PLUGIN_URL . 'assets/css/frontend.css', array(), SMU_VERSION);
        wp_enqueue_script('smu-frontend', SMU_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SMU_VERSION, true);
        
        wp_localize_script('smu-frontend', 'smuData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smu_nonce')
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'smu-') === false) {
            return;
        }
        
        wp_enqueue_style('smu-admin', SMU_PLUGIN_URL . 'assets/css/admin.css', array(), SMU_VERSION);
        wp_enqueue_script('smu-admin', SMU_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SMU_VERSION, true);
        wp_enqueue_script('smu-chart', 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js', array(), '3.9.1');
    }
    
    public function render_dashboard() {
        echo '<div class="wrap"><h1>Smart Membership Dashboard</h1>';
        $analytics = new SMU_Analytics();
        $stats = $analytics->get_dashboard_stats();
        echo '<div class="smu-dashboard-grid">';
        echo '<div class="smu-stat-card"><h3>Total Members: ' . esc_html($stats['total_members']) . '</h3></div>';
        echo '<div class="smu-stat-card"><h3>Active Subscriptions: ' . esc_html($stats['active_subscriptions']) . '</h3></div>';
        echo '<div class="smu-stat-card"><h3>Monthly Revenue: $' . esc_html(number_format($stats['monthly_revenue'], 2)) . '</h3></div>';
        echo '<div class="smu-stat-card"><h3>Conversion Rate: ' . esc_html($stats['conversion_rate']) . '%</h3></div>';
        echo '</div></div>';
    }
    
    public function render_members_page() {
        echo '<div class="wrap"><h1>Members</h1>';
        $membership = new SMU_Membership();
        $members = $membership->get_all_members();
        echo '<table class="wp-list-table widefat fixed"><thead><tr><th>Name</th><th>Email</th><th>Plan</th><th>Status</th><th>Joined</th></tr></thead><tbody>';
        foreach ($members as $member) {
            echo '<tr><td>' . esc_html($member['name']) . '</td><td>' . esc_html($member['email']) . '</td><td>' . esc_html($member['plan']) . '</td><td>' . esc_html($member['status']) . '</td><td>' . esc_html($member['joined']) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
    
    public function render_plans_page() {
        echo '<div class="wrap"><h1>Membership Plans</h1>';
        echo '<button class="button button-primary" id="smu-add-plan">Add New Plan</button>';
        echo '<div id="smu-plans-list"></div>';
        echo '</div>';
    }
    
    public function render_analytics_page() {
        echo '<div class="wrap"><h1>Analytics</h1>';
        echo '<canvas id="smu-conversion-chart"></canvas>';
        echo '<canvas id="smu-revenue-chart"></canvas>';
        echo '</div>';
    }
    
    public function render_settings_page() {
        echo '<div class="wrap"><h1>Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('smu_settings');
        do_settings_sections('smu_settings');
        submit_button();
        echo '</form></div>';
    }
    
    public function render_upsell_widget($atts) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '';
        }
        
        $membership = new SMU_Membership();
        $current_plan = $membership->get_user_plan($user_id);
        $recommended_plans = $membership->get_upsell_recommendations($user_id);
        
        ob_start();
        echo '<div class="smu-upsell-widget"><h3>Upgrade Your Experience</h3>';
        foreach ($recommended_plans as $plan) {
            echo '<div class="smu-plan-card"><h4>' . esc_html($plan['name']) . '</h4>';
            echo '<p>' . esc_html($plan['description']) . '</p>';
            echo '<p class="smu-price">$' . esc_html(number_format($plan['price'], 2)) . '/month</p>';
            echo '<button class="button button-primary smu-upgrade" data-plan-id="' . intval($plan['id']) . '">Upgrade Now</button>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
    
    public function track_user_engagement() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $analytics = new SMU_Analytics();
        $analytics->log_engagement(get_current_user_id(), [
            'page' => get_the_ID(),
            'time_spent' => 0,
            'timestamp' => current_time('mysql')
        ]);
    }
    
    public function activate() {
        $database = new SMU_Database();
        $database->create_tables();
        update_option('smu_activated', current_time('mysql'));
    }
    
    public function deactivate() {
        delete_option('smu_activated');
    }
}

class SMU_Database {
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smu_members (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            plan_id BIGINT UNSIGNED,
            status VARCHAR(20) DEFAULT 'active',
            joined DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smu_plans (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT,
            price DECIMAL(10,2) NOT NULL,
            interval VARCHAR(20) DEFAULT 'month',
            features LONGTEXT,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smu_analytics (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            event_type VARCHAR(50),
            event_data LONGTEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}

class SMU_Membership {
    public function get_all_members() {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT u.ID, u.user_login as name, u.user_email as email, m.status, m.joined, p.name as plan 
            FROM {$wpdb->users} u 
            LEFT JOIN {$wpdb->prefix}smu_members m ON u.ID = m.user_id 
            LEFT JOIN {$wpdb->prefix}smu_plans p ON m.plan_id = p.id",
            ARRAY_A
        );
        return $results ?: array();
    }
    
    public function get_user_plan($user_id) {
        global $wpdb;
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT p.* FROM {$wpdb->prefix}smu_plans p 
                JOIN {$wpdb->prefix}smu_members m ON m.plan_id = p.id 
                WHERE m.user_id = %d",
                $user_id
            ),
            ARRAY_A
        );
        return $result ?: array();
    }
    
    public function get_upsell_recommendations($user_id) {
        global $wpdb;
        $current_plan = $this->get_user_plan($user_id);
        $current_plan_id = isset($current_plan['id']) ? $current_plan['id'] : 0;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}smu_plans WHERE id != %d ORDER BY price ASC LIMIT 3",
                $current_plan_id
            ),
            ARRAY_A
        );
        return $results ?: array();
    }
}

class SMU_Analytics {
    public function get_dashboard_stats() {
        global $wpdb;
        
        $total_members = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}smu_members");
        $active_subscriptions = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}smu_members WHERE status = 'active'");
        
        $monthly_revenue = $wpdb->get_var(
            "SELECT COALESCE(SUM(p.price), 0) FROM {$wpdb->prefix}smu_plans p 
            JOIN {$wpdb->prefix}smu_members m ON m.plan_id = p.id 
            WHERE m.status = 'active'"
        );
        
        $conversion_rate = $total_members > 0 ? round(($active_subscriptions / $total_members) * 100, 2) : 0;
        
        return array(
            'total_members' => $total_members,
            'active_subscriptions' => $active_subscriptions,
            'monthly_revenue' => (float) $monthly_revenue,
            'conversion_rate' => $conversion_rate
        );
    }
    
    public function log_engagement($user_id, $data) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'smu_analytics',
            array(
                'user_id' => $user_id,
                'event_type' => 'engagement',
                'event_data' => json_encode($data),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
}

SmartMembershipUpsell::get_instance();
?>