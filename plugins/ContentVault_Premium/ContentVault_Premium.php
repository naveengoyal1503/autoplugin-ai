<?php
/*
Plugin Name: ContentVault Premium
Plugin URI: https://contentvault-premium.com
Description: Create tiered membership plans and gate premium content with recurring payments
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentVault_Premium.php
License: GPL v2 or later
Text Domain: contentvault-premium
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTVAULT_VERSION', '1.0.0');
define('CONTENTVAULT_PATH', plugin_dir_path(__FILE__));
define('CONTENTVAULT_URL', plugin_dir_url(__FILE__));

class ContentVaultPremium {
    private static $instance = null;

    public static function get_instance() {
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('the_content', array($this, 'gate_content'));
    }

    private function load_dependencies() {
        // Load dependencies here
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentvault_plans (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            plan_name varchar(100) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL,
            billing_cycle varchar(50),
            features text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentvault_subscriptions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_id mediumint(9) NOT NULL,
            status varchar(50),
            started_date datetime,
            renewal_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('contentvault_premium_version', CONTENTVAULT_VERSION);
    }

    public function deactivate() {
        // Cleanup on deactivation
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentVault Premium',
            'ContentVault',
            'manage_options',
            'contentvault-premium',
            array($this, 'render_admin_page'),
            'dashicons-lock',
            80
        );

        add_submenu_page(
            'contentvault-premium',
            'Membership Plans',
            'Plans',
            'manage_options',
            'contentvault-plans',
            array($this, 'render_plans_page')
        );

        add_submenu_page(
            'contentvault-premium',
            'Subscriptions',
            'Subscriptions',
            'manage_options',
            'contentvault-subscriptions',
            array($this, 'render_subscriptions_page')
        );

        add_submenu_page(
            'contentvault-premium',
            'Settings',
            'Settings',
            'manage_options',
            'contentvault-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('contentvault_settings', 'contentvault_payment_gateway');
        register_setting('contentvault_settings', 'contentvault_stripe_key');
        register_setting('contentvault_settings', 'contentvault_paypal_email');
    }

    public function render_admin_page() {
        echo '<div class="wrap"><h1>ContentVault Premium Dashboard</h1>';
        echo '<p>Manage your membership plans and subscriptions.</p>';
        echo '</div>';
    }

    public function render_plans_page() {
        global $wpdb;
        echo '<div class="wrap"><h1>Membership Plans</h1>';
        echo '<form method="post" action="admin.php?page=contentvault-plans&action=add">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Plan Name</th><th>Price</th><th>Billing Cycle</th><th>Actions</th></tr></thead>';
        echo '<tbody>';

        $plans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}contentvault_plans");
        foreach ($plans as $plan) {
            echo '<tr>';
            echo '<td>' . esc_html($plan->plan_name) . '</td>';
            echo '<td>$' . esc_html($plan->price) . '</td>';
            echo '<td>' . esc_html($plan->billing_cycle) . '</td>';
            echo '<td><a href="#" class="button">Edit</a> <a href="#" class="button button-danger">Delete</a></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</form>';
        echo '</div>';
    }

    public function render_subscriptions_page() {
        global $wpdb;
        echo '<div class="wrap"><h1>Subscriptions</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>User</th><th>Plan</th><th>Status</th><th>Renewal Date</th></tr></thead>';
        echo '<tbody>';

        $subs = $wpdb->get_results(
            "SELECT s.*, p.plan_name, u.user_login FROM {$wpdb->prefix}contentvault_subscriptions s 
            JOIN {$wpdb->prefix}contentvault_plans p ON s.plan_id = p.id 
            JOIN {$wpdb->prefix}users u ON s.user_id = u.ID"
        );
        foreach ($subs as $sub) {
            echo '<tr>';
            echo '<td>' . esc_html($sub->user_login) . '</td>';
            echo '<td>' . esc_html($sub->plan_name) . '</td>';
            echo '<td><span class="badge">' . esc_html($sub->status) . '</span></td>';
            echo '<td>' . esc_html($sub->renewal_date) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function render_settings_page() {
        echo '<div class="wrap"><h1>ContentVault Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('contentvault_settings');
        echo '<table class="form-table">';
        echo '<tr><th scope="row"><label for="payment_gateway">Payment Gateway</label></th>';
        echo '<td><select name="contentvault_payment_gateway" id="payment_gateway">';
        echo '<option value="stripe">Stripe</option>';
        echo '<option value="paypal">PayPal</option>';
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="stripe_key">Stripe API Key</label></th>';
        echo '<td><input type="text" name="contentvault_stripe_key" id="stripe_key" value="' . esc_attr(get_option('contentvault_stripe_key')) . '" class="regular-text" /></td></tr>';
        echo '<tr><th scope="row"><label for="paypal_email">PayPal Email</label></th>';
        echo '<td><input type="email" name="contentvault_paypal_email" id="paypal_email" value="' . esc_attr(get_option('contentvault_paypal_email')) . '" class="regular-text" /></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function gate_content($content) {
        if (is_singular('post') && !current_user_can('manage_options')) {
            $post_id = get_the_ID();
            $premium = get_post_meta($post_id, '_is_premium_content', true);

            if ($premium && !$this->user_has_access()) {
                return '<div class="contentvault-locked"><p>This content is exclusive to premium members. <a href="#">Subscribe Now</a></p></div>';
            }
        }
        return $content;
    }

    private function user_has_access() {
        if (!is_user_logged_in()) {
            return false;
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $sub = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}contentvault_subscriptions WHERE user_id = %d AND status = %s",
                $user_id,
                'active'
            )
        );

        return !empty($sub);
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('contentvault-frontend', CONTENTVAULT_URL . 'assets/frontend.css');
        wp_enqueue_script('contentvault-frontend', CONTENTVAULT_URL . 'assets/frontend.js', array('jquery'), CONTENTVAULT_VERSION, true);
    }

    public function enqueue_admin_scripts($hook_suffix) {
        if (strpos($hook_suffix, 'contentvault') !== false) {
            wp_enqueue_style('contentvault-admin', CONTENTVAULT_URL . 'assets/admin.css');
            wp_enqueue_script('contentvault-admin', CONTENTVAULT_URL . 'assets/admin.js', array('jquery'), CONTENTVAULT_VERSION, true);
        }
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    ContentVaultPremium::get_instance();
});

// Shortcode for membership button
add_shortcode('contentvault_subscribe', function() {
    if (is_user_logged_in()) {
        return '<p>You are already a member!</p>';
    }
    return '<a href="#" class="button button-primary">Subscribe Now</a>';
});
