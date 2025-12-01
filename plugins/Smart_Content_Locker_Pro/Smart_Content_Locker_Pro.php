<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.com
Description: Lock premium content behind paywalls and create membership tiers to monetize your WordPress site
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL2
Text Domain: smart-content-locker
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SCL_VERSION', '1.0.0');
define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartContentLocker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        add_filter('the_content', array($this, 'lock_content'), 20);
        add_shortcode('scl_login_form', array($this, 'render_login_form'));
        add_action('wp_ajax_scl_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_scl_login', array($this, 'handle_login'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    public function init_plugin() {
        load_plugin_textdomain('smart-content-locker', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_database_tables();
    }

    public function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_members = $wpdb->prefix . 'scl_members';
        $table_tiers = $wpdb->prefix . 'scl_tiers';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_members'") !== $table_members) {
            $sql = "CREATE TABLE $table_members (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                tier_id mediumint(9) NOT NULL,
                status varchar(20) DEFAULT 'active',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                expires_at datetime,
                PRIMARY KEY (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_tiers'") !== $table_tiers) {
            $sql = "CREATE TABLE $table_tiers (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                price decimal(10, 2) NOT NULL,
                billing_cycle varchar(20) DEFAULT 'monthly',
                description text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Content Locker', 'smart-content-locker'),
            __('Content Locker', 'smart-content-locker'),
            'manage_options',
            'scl_dashboard',
            array($this, 'render_dashboard'),
            'dashicons-lock'
        );

        add_submenu_page(
            'scl_dashboard',
            __('Membership Tiers', 'smart-content-locker'),
            __('Membership Tiers', 'smart-content-locker'),
            'manage_options',
            'scl_tiers',
            array($this, 'render_tiers')
        );

        add_submenu_page(
            'scl_dashboard',
            __('Settings', 'smart-content-locker'),
            __('Settings', 'smart-content-locker'),
            'manage_options',
            'scl_settings',
            array($this, 'render_settings')
        );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'scl_lock_content',
            __('Lock This Content', 'smart-content-locker'),
            array($this, 'render_lock_meta_box'),
            array('post', 'page'),
            'normal',
            'high'
        );
    }

    public function render_lock_meta_box($post) {
        wp_nonce_field('scl_lock_nonce', 'scl_lock_nonce');
        $is_locked = get_post_meta($post->ID, '_scl_is_locked', true);
        $required_tier = get_post_meta($post->ID, '_scl_required_tier', true);
        $lock_type = get_post_meta($post->ID, '_scl_lock_type', true) ?: 'partial';

        echo '<div style="margin: 10px 0;">';
        echo '<label><input type="checkbox" name="scl_is_locked" value="1" ' . checked($is_locked, 1, false) . ' /> ' . __('Lock this content', 'smart-content-locker') . '</label>';
        echo '</div>';

        echo '<div style="margin: 10px 0;">';
        echo '<label>' . __('Lock Type:', 'smart-content-locker') . '</label><br/>';
        echo '<select name="scl_lock_type">';
        echo '<option value="partial" ' . selected($lock_type, 'partial', false) . '>' . __('Partial (Show excerpt)', 'smart-content-locker') . '</option>';
        echo '<option value="full" ' . selected($lock_type, 'full', false) . '>' . __('Full Lock', 'smart-content-locker') . '</option>';
        echo '</select>';
        echo '</div>';

        echo '<div style="margin: 10px 0;">';
        echo '<label>' . __('Required Tier:', 'smart-content-locker') . '</label><br/>';
        echo '<select name="scl_required_tier">';
        echo '<option value="0">' . __('Any tier', 'smart-content-locker') . '</option>';
        $tiers = $this->get_tiers();
        foreach ($tiers as $tier) {
            echo '<option value="' . $tier->id . '" ' . selected($required_tier, $tier->id, false) . '>' . $tier->name . '</option>';
        }
        echo '</select>';
        echo '</div>';
    }

    public function save_post_meta($post_id) {
        if (!isset($_POST['scl_lock_nonce']) || !wp_verify_nonce($_POST['scl_lock_nonce'], 'scl_lock_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, '_scl_is_locked', isset($_POST['scl_is_locked']) ? 1 : 0);
        update_post_meta($post_id, '_scl_lock_type', sanitize_text_field($_POST['scl_lock_type'] ?? 'partial'));
        update_post_meta($post_id, '_scl_required_tier', intval($_POST['scl_required_tier'] ?? 0));
    }

    public function lock_content($content) {
        if (!is_singular(array('post', 'page'))) {
            return $content;
        }

        $is_locked = get_post_meta(get_the_ID(), '_scl_is_locked', true);
        if (!$is_locked) {
            return $content;
        }

        if (is_user_logged_in()) {
            return $content;
        }

        $lock_type = get_post_meta(get_the_ID(), '_scl_lock_type', true) ?: 'partial';

        if ($lock_type === 'partial') {
            $excerpt = wp_trim_excerpt();
            return $excerpt . '<div style="text-align: center; padding: 20px; background: #f5f5f5; margin: 20px 0; border-radius: 5px;">' . do_shortcode('[scl_login_form]') . '</div>';
        } else {
            return '<div style="text-align: center; padding: 40px; background: #f5f5f5; border-radius: 5px;">' .
                   '<h3>' . __('Premium Content', 'smart-content-locker') . '</h3>' .
                   '<p>' . __('This content is reserved for members.', 'smart-content-locker') . '</p>' .
                   do_shortcode('[scl_login_form]') .
                   '</div>';
        }
    }

    public function render_login_form() {
        if (is_user_logged_in()) {
            return __('You are already logged in.', 'smart-content-locker');
        }

        ob_start();
        ?>
        <div class="scl-login-form">
            <form id="scl-login" method="post">
                <input type="text" name="username" placeholder="<?php _e('Username', 'smart-content-locker'); ?>" required />
                <input type="password" name="password" placeholder="<?php _e('Password', 'smart-content-locker'); ?>" required />
                <button type="submit" class="scl-btn"><?php _e('Login', 'smart-content-locker'); ?></button>
            </form>
            <p><a href="<?php echo wp_registration_url(); ?>"><?php _e('Not a member? Sign up', 'smart-content-locker'); ?></a></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_login() {
        check_ajax_referer('scl_login_nonce', 'nonce');

        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = sanitize_text_field($_POST['password'] ?? '');

        $user = wp_authenticate($username, $password);
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => __('Invalid credentials', 'smart-content-locker')));
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        wp_send_json_success(array('message' => __('Login successful', 'smart-content-locker')));
    }

    public function render_dashboard() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Content Locker Dashboard', 'smart-content-locker') . '</h1>';
        echo '<p>' . __('Welcome to Smart Content Locker Pro', 'smart-content-locker') . '</p>';
        echo '</div>';
    }

    public function render_tiers() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Membership Tiers', 'smart-content-locker') . '</h1>';
        $tiers = $this->get_tiers();
        echo '<table class="wp-list-table widefat">';
        echo '<thead><tr><th>' . __('Name', 'smart-content-locker') . '</th><th>' . __('Price', 'smart-content-locker') . '</th><th>' . __('Cycle', 'smart-content-locker') . '</th></tr></thead>';
        foreach ($tiers as $tier) {
            echo '<tr><td>' . $tier->name . '</td><td>$' . $tier->price . '</td><td>' . $tier->billing_cycle . '</td></tr>';
        }
        echo '</table>';
        echo '</div>';
    }

    public function render_settings() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Settings', 'smart-content-locker') . '</h1>';
        echo '<p>' . __('Configure your Content Locker settings here.', 'smart-content-locker') . '</p>';
        echo '</div>';
    }

    public function get_tiers() {
        global $wpdb;
        $table = $wpdb->prefix . 'scl_tiers';
        return $wpdb->get_results("SELECT * FROM $table");
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('scl-admin', SCL_PLUGIN_URL . 'assets/admin.css');
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.css');
        wp_enqueue_script('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.js', array('jquery'), SCL_VERSION);
        wp_localize_script('scl-frontend', 'scl_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scl_login_nonce')
        ));
    }
}

SmartContentLocker::get_instance();

register_activation_hook(__FILE__, function() {
    SmartContentLocker::get_instance()->create_database_tables();
});

register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
});
?>