/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Content Locker Pro
 * Description: Lock and monetize your content with email signups, social shares, and payments
 * Version: 1.0.0
 * Author: Content Locker Team
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SMART_LOCKER_VERSION', '1.0.0');
define('SMART_LOCKER_PATH', plugin_dir_path(__FILE__));
define('SMART_LOCKER_URL', plugin_dir_url(__FILE__));

class SmartContentLocker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        add_action('init', array($this, 'register_post_type'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('content_locker', array($this, 'render_locker'));
        add_action('wp_ajax_unlock_content', array($this, 'handle_unlock'));
        add_action('wp_ajax_nopriv_unlock_content', array($this, 'handle_unlock'));
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain('smart-content-locker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function register_post_type() {
        register_post_type('locker_campaign', array(
            'labels' => array('name' => __('Locker Campaigns', 'smart-content-locker')),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor'),
            'capability_type' => 'post'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smart-locker-style', SMART_LOCKER_URL . 'css/locker.css', array(), SMART_LOCKER_VERSION);
        wp_enqueue_script('smart-locker-script', SMART_LOCKER_URL . 'js/locker.js', array('jquery'), SMART_LOCKER_VERSION, true);
        wp_localize_script('smart-locker-script', 'smartLocker', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_locker_nonce')
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Content Locker', 'smart-content-locker'),
            __('Content Locker', 'smart-content-locker'),
            'manage_options',
            'smart-content-locker',
            array($this, 'render_dashboard'),
            'dashicons-lock',
            25
        );

        add_submenu_page(
            'smart-content-locker',
            __('Settings', 'smart-content-locker'),
            __('Settings', 'smart-content-locker'),
            'manage_options',
            'smart-locker-settings',
            array($this, 'render_settings')
        );
    }

    public function register_settings() {
        register_setting('smart-locker-settings', 'smart_locker_options');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php _e('Content Locker Dashboard', 'smart-content-locker'); ?></h1>
            <div class="smart-locker-stats">
                <div class="stat-box">
                    <h3><?php _e('Total Unlocks', 'smart-content-locker'); ?></h3>
                    <p><?php echo $this->get_total_unlocks(); ?></p>
                </div>
                <div class="stat-box">
                    <h3><?php _e('Email Captures', 'smart-content-locker'); ?></h3>
                    <p><?php echo $this->get_email_captures(); ?></p>
                </div>
                <div class="stat-box">
                    <h3><?php _e('Revenue Generated', 'smart-content-locker'); ?></h3>
                    <p>$<?php echo number_format($this->get_revenue(), 2); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1><?php _e('Content Locker Settings', 'smart-content-locker'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart-locker-settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="stripe_key"><?php _e('Stripe API Key', 'smart-content-locker'); ?></label></th>
                        <td>
                            <input type="text" id="stripe_key" name="smart_locker_options[stripe_key]" value="<?php echo esc_attr(get_option('smart_locker_options')['stripe_key'] ?? ''); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mailchimp_key"><?php _e('Mailchimp API Key', 'smart-content-locker'); ?></label></th>
                        <td>
                            <input type="text" id="mailchimp_key" name="smart_locker_options[mailchimp_key]" value="<?php echo esc_attr(get_option('smart_locker_options')['mailchimp_key'] ?? ''); ?>" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_locker($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'message' => __('Unlock this content', 'smart-content-locker'),
            'unlock_type' => 'email'
        ), $atts);

        $locker_id = intval($atts['id']);
        $unlock_type = sanitize_text_field($atts['unlock_type']);
        $message = wp_kses_post($atts['message']);

        ob_start();
        ?>
        <div class="smart-locker-wrapper" data-locker-id="<?php echo $locker_id; ?>">
            <div class="locker-overlay">
                <div class="locker-content">
                    <h3><?php echo $message; ?></h3>
                    <?php if ($unlock_type === 'email'): ?>
                        <form class="locker-form" method="post">
                            <input type="email" name="email" placeholder="<?php _e('Enter your email', 'smart-content-locker'); ?>" required />
                            <button type="submit" class="btn-unlock"><?php _e('Unlock Content', 'smart-content-locker'); ?></button>
                        </form>
                    <?php elseif ($unlock_type === 'social'): ?>
                        <div class="social-unlock">
                            <button class="btn-social facebook"><?php _e('Share on Facebook', 'smart-content-locker'); ?></button>
                            <button class="btn-social twitter"><?php _e('Share on Twitter', 'smart-content-locker'); ?></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="locker-content" style="display:none;">
                <?php echo wp_kses_post(get_post_meta($locker_id, '_locker_content', true)); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_unlock() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smart_locker_nonce')) {
            wp_send_json_error(__('Security check failed', 'smart-content-locker'));
        }

        $locker_id = intval($_POST['locker_id'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');

        if (!$email || !$locker_id) {
            wp_send_json_error(__('Invalid data', 'smart-content-locker'));
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'locker_unlocks',
            array(
                'locker_id' => $locker_id,
                'email' => $email,
                'timestamp' => current_time('mysql')
            )
        );

        do_action('smart_locker_email_captured', $email, $locker_id);

        wp_send_json_success(__('Content unlocked!', 'smart-content-locker'));
    }

    private function get_total_unlocks() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}locker_unlocks");
    }

    private function get_email_captures() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(DISTINCT email) FROM {$wpdb->prefix}locker_unlocks");
    }

    private function get_revenue() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}locker_transactions WHERE status = 'completed'");
        return floatval($result);
    }
}

function smart_content_locker() {
    return SmartContentLocker::get_instance();
}

small_content_locker();

register_activation_hook(__FILE__, function() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}locker_unlocks (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        locker_id mediumint(9) NOT NULL,
        email varchar(255) NOT NULL,
        timestamp datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}locker_transactions (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        locker_id mediumint(9) NOT NULL,
        amount decimal(10,2) NOT NULL,
        status varchar(50) NOT NULL,
        timestamp datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    dbDelta($sql2);
});

register_deactivation_hook(__FILE__, function() {
    // Cleanup
});
?>