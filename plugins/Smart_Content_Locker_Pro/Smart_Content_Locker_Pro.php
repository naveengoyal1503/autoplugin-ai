<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.com
Description: Advanced content gating and membership plugin for monetizing WordPress content
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL2
Text Domain: smart-content-locker
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('SCLP_VERSION', '1.0.0');
define('SCLP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCLP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCLP_PLUGIN_FILE', __FILE__);

class SmartContentLockerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->define_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function define_constants() {
        if (!defined('SCLP_LITE')) {
            define('SCLP_LITE', true);
        }
    }

    private function load_dependencies() {
        require_once SCLP_PLUGIN_DIR . 'includes/class-database.php';
        require_once SCLP_PLUGIN_DIR . 'includes/class-settings.php';
        require_once SCLP_PLUGIN_DIR . 'includes/class-locker.php';
    }

    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('init', array($this, 'register_shortcodes'));
        add_filter('the_content', array($this, 'apply_content_lock'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        register_activation_hook(SCLP_PLUGIN_FILE, array($this, 'activate'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('smart-content-locker', false, dirname(plugin_basename(SCLP_PLUGIN_FILE)) . '/languages');
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Content Locker', 'smart-content-locker'),
            __('Content Locker', 'smart-content-locker'),
            'manage_options',
            'sclp-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-lock',
            30
        );

        add_submenu_page(
            'sclp-dashboard',
            __('Locks', 'smart-content-locker'),
            __('Locks', 'smart-content-locker'),
            'manage_options',
            'sclp-locks',
            array($this, 'render_locks_page')
        );

        add_submenu_page(
            'sclp-dashboard',
            __('Settings', 'smart-content-locker'),
            __('Settings', 'smart-content-locker'),
            'manage_options',
            'sclp-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_shortcodes() {
        add_shortcode('sclp_lock', array($this, 'shortcode_lock'));
        add_shortcode('sclp_locked_content', array($this, 'shortcode_locked_content'));
    }

    public function shortcode_lock($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'type' => 'paywall',
            'message' => __('This content is locked', 'smart-content-locker')
        ), $atts);

        ob_start();
        ?>
        <div class="sclp-lock" data-lock-id="<?php echo esc_attr($atts['id']); ?>" data-lock-type="<?php echo esc_attr($atts['type']); ?>">
            <div class="sclp-lock-message"><?php echo wp_kses_post($atts['message']); ?></div>
            <button class="sclp-unlock-btn" id="unlock-<?php echo esc_attr($atts['id']); ?>"><?php _e('Unlock Content', 'smart-content-locker'); ?></button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_locked_content($atts, $content = '') {
        $atts = shortcode_atts(array(
            'lock_id' => ''
        ), $atts);

        ob_start();
        ?>
        <div class="sclp-locked-content" data-lock-id="<?php echo esc_attr($atts['lock_id']); ?>" style="display:none;">
            <?php echo wp_kses_post($content); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function apply_content_lock($content) {
        if (is_admin() || !is_singular()) {
            return $content;
        }

        global $post;
        $lock_settings = get_post_meta($post->ID, '_sclp_lock_settings', true);

        if (!$lock_settings || !isset($lock_settings['enabled']) || !$lock_settings['enabled']) {
            return $content;
        }

        $lock_type = isset($lock_settings['type']) ? $lock_settings['type'] : 'paywall';
        $lock_message = isset($lock_settings['message']) ? $lock_settings['message'] : __('Premium content', 'smart-content-locker');

        return sprintf(
            '<div class="sclp-content-lock" data-lock-type="%s"><div class="sclp-lock-overlay"><p>%s</p><button class="sclp-unlock-btn">%s</button></div></div>%s',
            esc_attr($lock_type),
            wp_kses_post($lock_message),
            esc_html__('Unlock Content', 'smart-content-locker'),
            $content
        );
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('sclp-frontend', SCLP_PLUGIN_URL . 'assets/css/frontend.css', array(), SCLP_VERSION);
        wp_enqueue_script('sclp-frontend', SCLP_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SCLP_VERSION, true);
        wp_localize_script('sclp-frontend', 'SCLP', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sclp_nonce')
        ));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('sclp-admin', SCLP_PLUGIN_URL . 'assets/css/admin.css', array(), SCLP_VERSION);
        wp_enqueue_script('sclp-admin', SCLP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SCLP_VERSION, true);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e('Content Locker Dashboard', 'smart-content-locker'); ?></h1>
            <div class="sclp-dashboard">
                <div class="sclp-card">
                    <h2><?php _e('Welcome to Smart Content Locker Pro', 'smart-content-locker'); ?></h2>
                    <p><?php _e('Start monetizing your content with advanced content locking features.', 'smart-content-locker'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_locks_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Manage Content Locks', 'smart-content-locker'); ?></h1>
            <p><?php _e('Configure which posts and pages should be locked.', 'smart-content-locker'); ?></p>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Content Locker Settings', 'smart-content-locker'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('sclp_settings_group'); ?>
                <?php do_settings_sections('sclp_settings_group'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sclp_locks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            lock_type varchar(50) NOT NULL,
            lock_data longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

function sclp_init() {
    return SmartContentLockerPro::get_instance();
}

sclp_init();
?>