<?php

/*
Plugin Name: ContentVault Pro
Plugin URI: https://contentvault.pro
Description: Create tiered membership plans and monetize exclusive content with recurring payments
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentVault_Pro.php
License: GPL v2 or later
Text Domain: contentvault-pro
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTVAULT_VERSION', '1.0.0');
define('CONTENTVAULT_DIR', plugin_dir_path(__FILE__));
define('CONTENTVAULT_URL', plugin_dir_url(__FILE__));

class ContentVaultPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init();
    }

    private function init() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('contentvault_login', array($this, 'login_form_shortcode'));
        add_shortcode('contentvault_register', array($this, 'register_form_shortcode'));
        add_shortcode('contentvault_protected', array($this, 'protected_content_shortcode'));
        add_filter('the_content', array($this, 'protect_post_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        $this->create_database_tables();
    }

    public function load_textdomain() {
        load_plugin_textdomain('contentvault-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_menu_page(
            __('ContentVault Pro', 'contentvault-pro'),
            __('ContentVault', 'contentvault-pro'),
            'manage_options',
            'contentvault-settings',
            array($this, 'render_settings_page'),
            'dashicons-lock'
        );
        add_submenu_page(
            'contentvault-settings',
            __('Membership Plans', 'contentvault-pro'),
            __('Plans', 'contentvault-pro'),
            'manage_options',
            'contentvault-plans',
            array($this, 'render_plans_page')
        );
        add_submenu_page(
            'contentvault-settings',
            __('Members', 'contentvault-pro'),
            __('Members', 'contentvault-pro'),
            'manage_options',
            'contentvault-members',
            array($this, 'render_members_page')
        );
        add_submenu_page(
            'contentvault-settings',
            __('Revenue Analytics', 'contentvault-pro'),
            __('Analytics', 'contentvault-pro'),
            'manage_options',
            'contentvault-analytics',
            array($this, 'render_analytics_page')
        );
    }

    public function register_settings() {
        register_setting('contentvault_settings', 'contentvault_stripe_key');
        register_setting('contentvault_settings', 'contentvault_stripe_secret');
        register_setting('contentvault_settings', 'contentvault_payment_currency');
        register_setting('contentvault_settings', 'contentvault_site_name');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('contentvault_settings');
                do_settings_sections('contentvault_settings');
                ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="cv_site_name"><?php _e('Site Name', 'contentvault-pro'); ?></label></th>
                            <td><input type="text" name="contentvault_site_name" id="cv_site_name" value="<?php echo esc_attr(get_option('contentvault_site_name')); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cv_stripe_key"><?php _e('Stripe Public Key', 'contentvault-pro'); ?></label></th>
                            <td><input type="text" name="contentvault_stripe_key" id="cv_stripe_key" value="<?php echo esc_attr(get_option('contentvault_stripe_key')); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cv_stripe_secret"><?php _e('Stripe Secret Key', 'contentvault-pro'); ?></label></th>
                            <td><input type="password" name="contentvault_stripe_secret" id="cv_stripe_secret" value="<?php echo esc_attr(get_option('contentvault_stripe_secret')); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cv_currency"><?php _e('Currency', 'contentvault-pro'); ?></label></th>
                            <td>
                                <select name="contentvault_payment_currency" id="cv_currency">
                                    <option value="USD" <?php selected(get_option('contentvault_payment_currency'), 'USD'); ?>>USD</option>
                                    <option value="EUR" <?php selected(get_option('contentvault_payment_currency'), 'EUR'); ?>>EUR</option>
                                    <option value="GBP" <?php selected(get_option('contentvault_payment_currency'), 'GBP'); ?>>GBP</option>
                                    <option value="AUD" <?php selected(get_option('contentvault_payment_currency'), 'AUD'); ?>>AUD</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_plans_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <button class="button button-primary" id="cv-add-plan-btn"><?php _e('Add New Plan', 'contentvault-pro'); ?></button>
            <table class="wp-list-table widefat striped" id="cv-plans-table">
                <thead>
                    <tr>
                        <th><?php _e('Plan Name', 'contentvault-pro'); ?></th>
                        <th><?php _e('Price', 'contentvault-pro'); ?></th>
                        <th><?php _e('Billing Cycle', 'contentvault-pro'); ?></th>
                        <th><?php _e('Members', 'contentvault-pro'); ?></th>
                        <th><?php _e('Actions', 'contentvault-pro'); ?></th>
                    </tr>
                </thead>
                <tbody id="cv-plans-list">
                    <?php
                    global $wpdb;
                    $plans = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cv_plans");
                    foreach ($plans as $plan) {
                        ?>
                        <tr data-plan-id="<?php echo esc_attr($plan->id); ?>">
                            <td><?php echo esc_html($plan->name); ?></td>
                            <td><?php echo esc_html(get_option('contentvault_payment_currency', 'USD') . ' ' . $plan->price); ?></td>
                            <td><?php echo esc_html($plan->billing_cycle); ?></td>
                            <td><?php echo esc_html($plan->member_count); ?></td>
                            <td>
                                <button class="button cv-edit-plan" data-plan-id="<?php echo esc_attr($plan->id); ?>"><?php _e('Edit', 'contentvault-pro'); ?></button>
                                <button class="button button-link-delete cv-delete-plan" data-plan-id="<?php echo esc_attr($plan->id); ?>"><?php _e('Delete', 'contentvault-pro'); ?></button>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_members_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Member', 'contentvault-pro'); ?></th>
                        <th><?php _e('Email', 'contentvault-pro'); ?></th>
                        <th><?php _e('Plan', 'contentvault-pro'); ?></th>
                        <th><?php _e('Join Date', 'contentvault-pro'); ?></th>
                        <th><?php _e('Status', 'contentvault-pro'); ?></th>
                        <th><?php _e('Actions', 'contentvault-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    global $wpdb;
                    $members = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cv_members ORDER BY created_at DESC LIMIT 50");
                    foreach ($members as $member) {
                        $plan = $wpdb->get_row("SELECT name FROM {$wpdb->prefix}cv_plans WHERE id = {$member->plan_id}");
                        ?>
                        <tr>
                            <td><?php echo esc_html($member->user_name); ?></td>
                            <td><?php echo esc_html($member->email); ?></td>
                            <td><?php echo $plan ? esc_html($plan->name) : __('N/A', 'contentvault-pro'); ?></td>
                            <td><?php echo esc_html(date('M d, Y', strtotime($member->created_at))); ?></td>
                            <td><span class="cv-status-<?php echo esc_attr($member->status); ?>"><?php echo esc_html(ucfirst($member->status)); ?></span></td>
                            <td>
                                <button class="button cv-view-member" data-member-id="<?php echo esc_attr($member->id); ?>"><?php _e('View', 'contentvault-pro'); ?></button>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="cv-analytics-dashboard">
                <div class="cv-stat-card">
                    <h3><?php _e('Total Revenue', 'contentvault-pro'); ?></h3>
                    <p class="cv-stat-value"><?php echo esc_html(get_option('contentvault_payment_currency', 'USD') . ' ' . $this->get_total_revenue()); ?></p>
                </div>
                <div class="cv-stat-card">
                    <h3><?php _e('Active Members', 'contentvault-pro'); ?></h3>
                    <p class="cv-stat-value"><?php echo esc_html($this->get_active_member_count()); ?></p>
                </div>
                <div class="cv-stat-card">
                    <h3><?php _e('Total Plans', 'contentvault-pro'); ?></h3>
                    <p class="cv-stat-value"><?php echo esc_html($this->get_total_plans()); ?></p>
                </div>
                <div class="cv-stat-card">
                    <h3><?php _e('Avg. Member Lifetime Value', 'contentvault-pro'); ?></h3>
                    <p class="cv-stat-value"><?php echo esc_html(get_option('contentvault_payment_currency', 'USD') . ' ' . $this->get_avg_ltv()); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function login_form_shortcode() {
        ob_start();
        ?>
        <div class="cv-login-form">
            <h2><?php _e('Member Login', 'contentvault-pro'); ?></h2>
            <form method="post" class="cv-form">
                <div class="cv-form-group">
                    <label for="cv_login_email"><?php _e('Email', 'contentvault-pro'); ?></label>
                    <input type="email" id="cv_login_email" name="email" required>
                </div>
                <div class="cv-form-group">
                    <label for="cv_login_password"><?php _e('Password', 'contentvault-pro'); ?></label>
                    <input type="password" id="cv_login_password" name="password" required>
                </div>
                <button type="submit" class="cv-btn"><?php _e('Login', 'contentvault-pro'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function register_form_shortcode() {
        ob_start();
        ?>
        <div class="cv-register-form">
            <h2><?php _e('Register for Premium Access', 'contentvault-pro'); ?></h2>
            <form method="post" class="cv-form">
                <div class="cv-form-group">
                    <label for="cv_reg_name"><?php _e('Full Name', 'contentvault-pro'); ?></label>
                    <input type="text" id="cv_reg_name" name="name" required>
                </div>
                <div class="cv-form-group">
                    <label for="cv_reg_email"><?php _e('Email', 'contentvault-pro'); ?></label>
                    <input type="email" id="cv_reg_email" name="email" required>
                </div>
                <div class="cv-form-group">
                    <label for="cv_reg_password"><?php _e('Password', 'contentvault-pro'); ?></label>
                    <input type="password" id="cv_reg_password" name="password" required>
                </div>
                <div class="cv-form-group">
                    <label for="cv_reg_plan"><?php _e('Select Plan', 'contentvault-pro'); ?></label>
                    <select id="cv_reg_plan" name="plan_id" required>
                        <option value=""><?php _e('Choose a plan...', 'contentvault-pro'); ?></option>
                        <?php
                        global $wpdb;
                        $plans = $wpdb->get_results("SELECT id, name, price, billing_cycle FROM {$wpdb->prefix}cv_plans WHERE status = 'active'");
                        foreach ($plans as $plan) {
                            echo '<option value="' . esc_attr($plan->id) . '">' . esc_html($plan->name . ' - ' . get_option('contentvault_payment_currency', 'USD') . $plan->price . '/' . $plan->billing_cycle) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="cv-btn"><?php _e('Register & Subscribe', 'contentvault-pro'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function protected_content_shortcode($atts) {
        $atts = shortcode_atts(array('plan_id' => 0), $atts);
        ob_start();
        ?>
        <div class="cv-protected-content">
            <p><?php _e('This content is available only to premium members.', 'contentvault-pro'); ?></p>
            <p><a href="#register" class="cv-btn"><?php _e('Join Now', 'contentvault-pro'); ?></a></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function protect_post_content($content) {
        if (is_single() && get_post_meta(get_the_ID(), '_cv_protected', true)) {
            $required_plan = get_post_meta(get_the_ID(), '_cv_required_plan', true);
            if (!$this->user_has_access($required_plan)) {
                return '<div class="cv-content-locked"><p>' . __('This content is restricted to premium members.', 'contentvault-pro') . '</p><a href="#register" class="cv-btn">' . __('Subscribe Now', 'contentvault-pro') . '</a></div>';
            }
        }
        return $content;
    }

    private function user_has_access($plan_id) {
        if (!is_user_logged_in()) return false;
        global $wpdb;
        $user_id = get_current_user_id();
        $member = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cv_members WHERE user_id = %d AND status = 'active'", $user_id));
        if (!$member) return false;
        return (int)$member->plan_id === (int)$plan_id || (int)$plan_id === 0;
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('contentvault-frontend', CONTENTVAULT_URL . 'css/frontend.css', array(), CONTENTVAULT_VERSION);
        wp_enqueue_script('contentvault-frontend', CONTENTVAULT_URL . 'js/frontend.js', array('jquery'), CONTENTVAULT_VERSION, true);
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('contentvault-admin', CONTENTVAULT_URL . 'css/admin.css', array(), CONTENTVAULT_VERSION);
        wp_enqueue_script('contentvault-admin', CONTENTVAULT_URL . 'js/admin.js', array('jquery'), CONTENTVAULT_VERSION, true);
    }

    private function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $plans_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cv_plans (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description longtext,
            price decimal(10, 2) NOT NULL,
            billing_cycle varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'active',
            member_count int DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        dbDelta($plans_table);

        $members_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cv_members (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            user_name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            plan_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'active',
            subscription_date datetime,
            next_billing_date datetime,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (plan_id) REFERENCES {$wpdb->prefix}cv_plans(id)
        ) $charset_collate;";
        dbDelta($members_table);

        $transactions_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cv_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            member_id bigint(20) NOT NULL,
            amount decimal(10, 2) NOT NULL,
            currency varchar(10),
            status varchar(20) DEFAULT 'completed',
            transaction_date timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (member_id) REFERENCES {$wpdb->prefix}cv_members(id)
        ) $charset_collate;";
        dbDelta($transactions_table);
    }

    private function get_total_revenue() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}cv_transactions WHERE status = 'completed'");
        return $result ? round($result, 2) : 0;
    }

    private function get_active_member_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cv_members WHERE status = 'active'");
    }

    private function get_total_plans() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cv_plans");
    }

    private function get_avg_ltv() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT AVG(total_spent) FROM (SELECT member_id, SUM(amount) as total_spent FROM {$wpdb->prefix}cv_transactions WHERE status = 'completed' GROUP BY member_id) as member_totals");
        return $result ? round($result, 2) : 0;
    }
}

ContentVaultPro::get_instance();

register_activation_hook(__FILE__, function() {
    ContentVaultPro::get_instance();
});

register_deactivation_hook(__FILE__, function() {
    // Handle deactivation if needed
});

?>