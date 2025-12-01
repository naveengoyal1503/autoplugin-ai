<?php
/*
Plugin Name: ContentVault Pro
Plugin URI: https://contentvault.example.com
Description: A powerful membership and content restriction plugin for building recurring revenue streams
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
define('CONTENTVAULT_PATH', plugin_dir_path(__FILE__));
define('CONTENTVAULT_URL', plugin_dir_url(__FILE__));

class ContentVaultPro {
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'registerPostTypes'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_filter('the_content', array($this, 'restrictContent'));
        add_shortcode('vault_login', array($this, 'loginFormShortcode'));
        add_shortcode('vault_subscription', array($this, 'subscriptionShortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        $this->createTables();
    }

    public function registerPostTypes() {
        register_post_type('vault_content', array(
            'label' => 'Vault Content',
            'public' => true,
            'show_in_menu' => true,
            'supports' => array('title', 'editor', 'author'),
            'menu_icon' => 'dashicons-lock'
        ));

        register_post_type('vault_membership', array(
            'label' => 'Membership Plans',
            'public' => false,
            'show_in_menu' => true,
            'supports' => array('title'),
        ));
    }

    public function addAdminMenu() {
        add_menu_page(
            'ContentVault Pro',
            'ContentVault Pro',
            'manage_options',
            'contentvault-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-shield',
            25
        );

        add_submenu_page(
            'contentvault-dashboard',
            'Memberships',
            'Memberships',
            'manage_options',
            'contentvault-memberships',
            array($this, 'renderMemberships')
        );

        add_submenu_page(
            'contentvault-dashboard',
            'Subscribers',
            'Subscribers',
            'manage_options',
            'contentvault-subscribers',
            array($this, 'renderSubscribers')
        );

        add_submenu_page(
            'contentvault-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentvault-settings',
            array($this, 'renderSettings')
        );
    }

    public function renderDashboard() {
        global $wpdb;
        $table = $wpdb->prefix . 'vault_subscriptions';
        $total_subscribers = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        ?>
        <div class="wrap">
            <h1>ContentVault Pro Dashboard</h1>
            <div style="margin-top: 20px;">
                <div style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h2>Quick Stats</h2>
                    <p><strong>Total Subscribers:</strong> <?php echo esc_html($total_subscribers); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function renderMemberships() {
        ?>
        <div class="wrap">
            <h1>Membership Plans</h1>
            <p>Manage your membership tiers and pricing here.</p>
        </div>
        <?php
    }

    public function renderSubscribers() {
        global $wpdb;
        $table = $wpdb->prefix . 'vault_subscriptions';
        $subscribers = $wpdb->get_results("SELECT * FROM $table");
        ?>
        <div class="wrap">
            <h1>Subscribers</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Start Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscribers as $sub): ?>
                        <tr>
                            <td><?php echo esc_html($sub->user_email); ?></td>
                            <td><?php echo esc_html($sub->plan_name); ?></td>
                            <td><?php echo esc_html($sub->status); ?></td>
                            <td><?php echo esc_html($sub->start_date); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderSettings() {
        ?>
        <div class="wrap">
            <h1>ContentVault Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentvault_settings'); ?>
                <?php do_settings_sections('contentvault_settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function restrictContent($content) {
        if (!is_singular('vault_content')) {
            return $content;
        }

        if (!is_user_logged_in()) {
            return '<p>This content is restricted. Please <a href="' . wp_login_url() . '">log in</a> to access.</p>';
        }

        global $post, $wpdb;
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'vault_subscriptions';
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND status = %s",
            $user_id,
            'active'
        ));

        if ($subscription) {
            return $content;
        }

        return '<p>You need an active subscription to view this content. <a href="#vault-subscribe">Subscribe now</a></p>';
    }

    public function loginFormShortcode() {
        if (is_user_logged_in()) {
            return 'Welcome, ' . wp_get_current_user()->display_name . '!';
        }
        return wp_login_form(array('echo' => false));
    }

    public function subscriptionShortcode() {
        ob_start();
        ?>
        <div id="vault-subscribe" style="padding: 20px; background: #f5f5f5; border-radius: 5px;">
            <h3>Subscription Plans</h3>
            <p>Choose a plan to get access to premium content.</p>
            <button class="button button-primary">Basic Plan - $9.99/month</button>
            <button class="button button-primary">Premium Plan - $19.99/month</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueueScripts() {
        wp_enqueue_style('contentvault-style', CONTENTVAULT_URL . 'assets/style.css');
    }

    public function enqueueAdminScripts() {
        wp_enqueue_style('contentvault-admin-style', CONTENTVAULT_URL . 'assets/admin-style.css');
    }

    public function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'vault_subscriptions';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                user_email varchar(255) NOT NULL,
                plan_name varchar(100) NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'active',
                start_date datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
}

ContentVaultPro::getInstance();

register_activation_hook(__FILE__, function() {
    ContentVaultPro::getInstance()->createTables();
});
?>
