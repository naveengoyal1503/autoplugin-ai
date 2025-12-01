<?php
/*
Plugin Name: SmartCommissionTracker
Plugin URI: https://smartcommissiontracker.com
Description: Automate affiliate commission tracking, payout management, and performance analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartCommissionTracker.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: smartcommissiontracker
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('SCT_VERSION', '1.0.0');
define('SCT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCT_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartCommissionTracker {
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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('affiliate_dashboard', array($this, 'affiliate_dashboard_shortcode'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $affiliates_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sct_affiliates (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            commission_rate float NOT NULL DEFAULT 5,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        $commissions_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sct_commissions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            affiliate_id mediumint(9) NOT NULL,
            sale_amount decimal(10,2) NOT NULL,
            commission_amount decimal(10,2) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            transaction_id varchar(100) UNIQUE,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            paid_at datetime,
            PRIMARY KEY  (id),
            KEY affiliate_id (affiliate_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($affiliates_table);
        dbDelta($commissions_table);
        
        add_option('sct_settings', array(
            'commission_rate' => 5,
            'payout_threshold' => 50,
            'is_premium' => false
        ));
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('sct_weekly_payout');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Smart Commission Tracker',
            'Commission Tracker',
            'manage_options',
            'sct-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-money-alt',
            80
        );
        
        add_submenu_page(
            'sct-dashboard',
            'Affiliates',
            'Affiliates',
            'manage_options',
            'sct-affiliates',
            array($this, 'render_affiliates')
        );
        
        add_submenu_page(
            'sct-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'sct-settings',
            array($this, 'render_settings')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'sct-') === false) return;
        
        wp_enqueue_style('sct-admin', SCT_PLUGIN_URL . 'assets/admin-style.css', array(), SCT_VERSION);
        wp_enqueue_script('sct-admin', SCT_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), SCT_VERSION, true);
        wp_localize_script('sct-admin', 'sctData', array(
            'nonce' => wp_create_nonce('sct_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style('sct-frontend', SCT_PLUGIN_URL . 'assets/frontend-style.css', array(), SCT_VERSION);
        wp_enqueue_script('sct-frontend', SCT_PLUGIN_URL . 'assets/frontend-script.js', array('jquery'), SCT_VERSION, true);
    }
    
    public function render_dashboard() {
        global $wpdb;
        $settings = get_option('sct_settings');
        
        $total_affiliates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sct_affiliates");
        $pending_commissions = $wpdb->get_var("SELECT SUM(commission_amount) FROM {$wpdb->prefix}sct_commissions WHERE status='pending'");
        $total_paid = $wpdb->get_var("SELECT SUM(commission_amount) FROM {$wpdb->prefix}sct_commissions WHERE status='paid'");
        
        echo '<div class="wrap">';
        echo '<h1>Commission Tracker Dashboard</h1>';
        echo '<div class="sct-dashboard-grid">';
        echo '<div class="sct-card"><h3>Total Affiliates</h3><p class="sct-metric">' . intval($total_affiliates) . '</p></div>';
        echo '<div class="sct-card"><h3>Pending Payouts</h3><p class="sct-metric">$' . number_format(floatval($pending_commissions), 2) . '</p></div>';
        echo '<div class="sct-card"><h3>Total Paid</h3><p class="sct-metric">$' . number_format(floatval($total_paid), 2) . '</p></div>';
        echo '</div>';
        echo '</div>';
    }
    
    public function render_affiliates() {
        global $wpdb;
        $affiliates = $wpdb->get_results("SELECT a.*, u.user_email, u.user_login FROM {$wpdb->prefix}sct_affiliates a JOIN {$wpdb->prefix}users u ON a.user_id = u.ID");
        
        echo '<div class="wrap">';
        echo '<h1>Manage Affiliates</h1>';
        echo '<table class="wp-list-table widefat striped">';
        echo '<thead><tr><th>User</th><th>Email</th><th>Commission Rate</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($affiliates as $affiliate) {
            echo '<tr>';
            echo '<td>' . esc_html($affiliate->user_login) . '</td>';
            echo '<td>' . esc_html($affiliate->user_email) . '</td>';
            echo '<td>' . floatval($affiliate->commission_rate) . '%</td>';
            echo '<td>' . esc_html($affiliate->status) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    public function render_settings() {
        $settings = get_option('sct_settings');
        
        if (isset($_POST['sct_save_settings'])) {
            check_admin_referer('sct_settings_nonce');
            
            $settings['commission_rate'] = floatval($_POST['commission_rate']);
            $settings['payout_threshold'] = floatval($_POST['payout_threshold']);
            
            update_option('sct_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings updated!</p></div>';
        }
        
        echo '<div class="wrap">';
        echo '<h1>Commission Tracker Settings</h1>';
        echo '<form method="post">';
        wp_nonce_field('sct_settings_nonce');
        echo '<table class="form-table">';
        echo '<tr><th>Default Commission Rate (%)</th><td><input type="number" step="0.1" name="commission_rate" value="' . floatval($settings['commission_rate']) . '"></td></tr>';
        echo '<tr><th>Payout Threshold ($)</th><td><input type="number" step="0.01" name="payout_threshold" value="' . floatval($settings['payout_threshold']) . '"></td></tr>';
        echo '</table>';
        echo '<button type="submit" name="sct_save_settings" class="button button-primary">Save Settings</button>';
        echo '</form>';
        echo '</div>';
    }
    
    public function affiliate_dashboard_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your affiliate dashboard.</p>';
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sct_affiliates WHERE user_id = %d", $user_id));
        
        if (!$affiliate) {
            return '<p>You are not registered as an affiliate.</p>';
        }
        
        $commissions = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sct_commissions WHERE affiliate_id = %d", $affiliate->id));
        
        $pending = array_sum(array_filter(array_map(function($c) { return $c->status === 'pending' ? $c->commission_amount : 0; }, $commissions)));
        $paid = array_sum(array_filter(array_map(function($c) { return $c->status === 'paid' ? $c->commission_amount : 0; }, $commissions)));
        
        $output = '<div class="sct-affiliate-dashboard">';
        $output .= '<h2>Your Affiliate Dashboard</h2>';
        $output .= '<div class="sct-stats"><p>Pending: <strong>$' . number_format($pending, 2) . '</strong></p>';
        $output .= '<p>Paid: <strong>$' . number_format($paid, 2) . '</strong></p></div>';
        $output .= '</div>';
        
        return $output;
    }
    
    public function register_rest_routes() {
        register_rest_route('sct/v1', '/commission', array(
            'methods' => 'POST',
            'callback' => array($this, 'log_commission'),
            'permission_callback' => array($this, 'verify_request')
        ));
    }
    
    public function log_commission($request) {
        global $wpdb;
        
        $params = $request->get_json_params();
        $affiliate_id = intval($params['affiliate_id']);
        $sale_amount = floatval($params['sale_amount']);
        $transaction_id = sanitize_text_field($params['transaction_id']);
        
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sct_affiliates WHERE id = %d", $affiliate_id));
        
        if (!$affiliate) {
            return new WP_Error('invalid_affiliate', 'Affiliate not found', array('status' => 404));
        }
        
        $commission_amount = $sale_amount * ($affiliate->commission_rate / 100);
        
        $wpdb->insert(
            "{$wpdb->prefix}sct_commissions",
            array(
                'affiliate_id' => $affiliate_id,
                'sale_amount' => $sale_amount,
                'commission_amount' => $commission_amount,
                'transaction_id' => $transaction_id,
                'status' => 'pending'
            )
        );
        
        return new WP_REST_Response(array('success' => true, 'commission_id' => $wpdb->insert_id), 201);
    }
    
    public function verify_request() {
        return current_user_can('manage_options') || isset($_SERVER['HTTP_X_API_KEY']);
    }
}

SmartCommissionTracker::get_instance();
?>