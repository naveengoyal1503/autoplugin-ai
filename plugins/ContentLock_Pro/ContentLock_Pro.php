<?php
/*
Plugin Name: ContentLock Pro
Plugin URI: https://contentlockpro.com
Description: Advanced content gating and membership monetization for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentLock_Pro.php
License: GPL v2 or later
Text Domain: contentlock-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTLOCK_PRO_VERSION', '1.0.0');
define('CONTENTLOCK_PRO_DIR', plugin_dir_path(__FILE__));
define('CONTENTLOCK_PRO_URL', plugin_dir_url(__FILE__));

class ContentLockPro {
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
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'gate_content'));
        add_shortcode('contentlock', array($this, 'contentlock_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentlock_locks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            lock_type varchar(50) NOT NULL,
            lock_value longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function deactivate() {
        // Cleanup if needed
    }
    
    public function init() {
        load_plugin_textdomain('contentlock-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('ContentLock Pro', 'contentlock-pro'),
            __('ContentLock Pro', 'contentlock-pro'),
            'manage_options',
            'contentlock-pro',
            array($this, 'admin_page'),
            'dashicons-lock',
            110
        );
        
        add_submenu_page(
            'contentlock-pro',
            __('Settings', 'contentlock-pro'),
            __('Settings', 'contentlock-pro'),
            'manage_options',
            'contentlock-pro-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('contentlock-pro-settings', 'contentlock_stripe_key');
        register_setting('contentlock-pro-settings', 'contentlock_paypal_id');
        register_setting('contentlock-pro-settings', 'contentlock_default_message');
    }
    
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'contentlock-pro'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('ContentLock Pro Dashboard', 'contentlock-pro')); ?></h1>
            <div class="contentlock-stats">
                <div class="stat-card">
                    <h3><?php esc_html_e('Locked Content', 'contentlock-pro'); ?></h3>
                    <p><?php echo intval($this->count_locked_posts()); ?></p>
                </div>
                <div class="stat-card">
                    <h3><?php esc_html_e('Active Memberships', 'contentlock-pro'); ?></h3>
                    <p><?php echo intval($this->count_active_memberships()); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'contentlock-pro'));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('ContentLock Pro Settings', 'contentlock-pro')); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentlock-pro-settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="contentlock_stripe_key"><?php esc_html_e('Stripe API Key', 'contentlock-pro'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="contentlock_stripe_key" name="contentlock_stripe_key" value="<?php echo esc_attr(get_option('contentlock_stripe_key')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contentlock_paypal_id"><?php esc_html_e('PayPal Business ID', 'contentlock-pro'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="contentlock_paypal_id" name="contentlock_paypal_id" value="<?php echo esc_attr(get_option('contentlock_paypal_id')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="contentlock_default_message"><?php esc_html_e('Default Lock Message', 'contentlock-pro'); ?></label>
                        </th>
                        <td>
                            <textarea id="contentlock_default_message" name="contentlock_default_message" class="large-text" rows="5"><?php echo esc_textarea(get_option('contentlock_default_message', __('This content is locked. Please subscribe to access.', 'contentlock-pro'))); ?></textarea>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function gate_content($content) {
        if (is_singular() && !is_admin()) {
            $post_id = get_the_ID();
            $lock = $this->get_post_lock($post_id);
            
            if ($lock && !$this->user_has_access($lock)) {
                $message = get_option('contentlock_default_message', __('This content is locked. Please subscribe to access.', 'contentlock-pro'));
                return $message . '<div class="contentlock-gate"><a href="#" class="contentlock-unlock-btn">' . esc_html(__('Unlock Content', 'contentlock-pro')) . '</a></div>';
            }
        }
        return $content;
    }
    
    public function contentlock_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'email',
            'price' => '4.99',
            'message' => __('Enter your email to unlock this content', 'contentlock-pro'),
        ), $atts);
        
        ob_start();
        ?>
        <div class="contentlock-form">
            <p><?php echo esc_html($atts['message']); ?></p>
            <form class="contentlock-unlock-form" data-type="<?php echo esc_attr($atts['type']); ?>" data-price="<?php echo esc_attr($atts['price']); ?>">
                <input type="email" name="email" placeholder="<?php esc_attr_e('your@email.com', 'contentlock-pro'); ?>" required />
                <button type="submit" class="contentlock-submit"><?php esc_html_e('Unlock Now', 'contentlock-pro'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style('contentlock-pro-styles', CONTENTLOCK_PRO_URL . 'assets/css/style.css', array(), CONTENTLOCK_PRO_VERSION);
        wp_enqueue_script('contentlock-pro-scripts', CONTENTLOCK_PRO_URL . 'assets/js/script.js', array('jquery'), CONTENTLOCK_PRO_VERSION, true);
        wp_localize_script('contentlock-pro-scripts', 'contentlockPro', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentlock_nonce'),
        ));
    }
    
    private function get_post_lock($post_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}contentlock_locks WHERE post_id = %d LIMIT 1",
            $post_id
        ));
    }
    
    private function user_has_access($lock) {
        if (current_user_can('manage_options')) {
            return true;
        }
        if (get_current_user_id() === intval(get_post_field('post_author', $lock->post_id))) {
            return true;
        }
        return false;
    }
    
    private function count_locked_posts() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}contentlock_locks");
    }
    
    private function count_active_memberships() {
        // Placeholder for membership count logic
        return 0;
    }
}

ContentLockPro::get_instance();
?>
