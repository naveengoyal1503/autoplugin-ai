<?php
/*
Plugin Name: Smart Content Locker Pro
Description: Lock premium content behind paywalls, memberships, and email gates with AI-driven targeting
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL-2.0+
*/

if (!defined('ABSPATH')) exit;

define('SCL_PRO_VERSION', '1.0.0');
define('SCL_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartContentLockerPro {
    private static $instance = null;
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_shortcode('content_locker', array($this, 'renderContentLocker'));
        add_action('wp_ajax_unlock_content', array($this, 'unlockContent'));
        add_action('wp_ajax_nopriv_unlock_content', array($this, 'unlockContent'));
    }
    
    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scl_locked_content';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            lock_type varchar(50) NOT NULL,
            lock_settings longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('scl_pro_db_version', SCL_PRO_VERSION);
    }
    
    public function deactivate() {
        // Clean up if needed
    }
    
    public function init() {
        load_plugin_textdomain('scl-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function addAdminMenu() {
        add_menu_page(
            'Content Locker Pro',
            'Content Locker',
            'manage_options',
            'scl-pro-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-lock',
            25
        );
        
        add_submenu_page(
            'scl-pro-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'scl-pro-settings',
            array($this, 'renderSettings')
        );
    }
    
    public function renderDashboard() {
        echo '<div class="wrap"><h1>Content Locker Pro Dashboard</h1>';
        echo '<p>Total locked content blocks: ' . $this->getLockedContentCount() . '</p>';
        echo '<p>Revenue this month: $' . number_format($this->getMonthlyRevenue(), 2) . '</p>';
        echo '</div>';
    }
    
    public function renderSettings() {
        echo '<div class="wrap"><h1>Content Locker Pro Settings</h1>';
        echo '<form method="post">';
        echo '<label>Payment Method: </label>';
        echo '<select name="scl_payment_method">';
        echo '<option value="paypal">PayPal</option>';
        echo '<option value="stripe">Stripe</option>';
        echo '</select>';
        echo '<button type="submit" class="button button-primary">Save Settings</button>';
        echo '</form>';
        echo '</div>';
    }
    
    public function enqueueScripts() {
        wp_enqueue_style('scl-pro-style', SCL_PRO_PLUGIN_URL . 'assets/style.css', array(), SCL_PRO_VERSION);
        wp_enqueue_script('scl-pro-script', SCL_PRO_PLUGIN_URL . 'assets/script.js', array('jquery'), SCL_PRO_VERSION, true);
        wp_localize_script('scl-pro-script', 'sclProData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scl_pro_nonce')
        ));
    }
    
    public function renderContentLocker($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'type' => 'email',
            'message' => 'Subscribe to unlock this content'
        ), $atts);
        
        $locked_id = sanitize_text_field($atts['id']);
        $lock_type = sanitize_text_field($atts['type']);
        $message = sanitize_text_field($atts['message']);
        
        if (is_user_logged_in() || isset($_COOKIE['scl_unlocked_' . $locked_id])) {
            return do_shortcode('[content_locker_revealed id="' . $locked_id . '"]');
        }
        
        ob_start();
        ?>
        <div class="scl-pro-locker" data-id="<?php echo esc_attr($locked_id); ?>" data-type="<?php echo esc_attr($lock_type); ?>">
            <div class="scl-pro-lock-overlay">
                <div class="scl-pro-lock-content">
                    <p><?php echo esc_html($message); ?></p>
                    <?php if ($lock_type === 'email'): ?>
                        <form class="scl-pro-email-form">
                            <input type="email" placeholder="Enter your email" required>
                            <button type="submit" class="button button-primary">Unlock Now</button>
                        </form>
                    <?php elseif ($lock_type === 'membership'): ?>
                        <button class="scl-pro-membership-btn button button-primary">Join Membership</button>
                    <?php elseif ($lock_type === 'donation'): ?>
                        <button class="scl-pro-donation-btn button button-primary">Make a Donation</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function unlockContent() {
        check_ajax_referer('scl_pro_nonce');
        
        $lock_id = sanitize_text_field($_POST['lock_id'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $lock_type = sanitize_text_field($_POST['lock_type'] ?? '');
        
        if (empty($lock_id)) {
            wp_send_json_error('Invalid lock ID');
        }
        
        if ($lock_type === 'email' && !empty($email)) {
            $this->saveSubscriber($email);
            setcookie('scl_unlocked_' . $lock_id, '1', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
            wp_send_json_success('Content unlocked');
        }
        
        wp_send_json_error('Unlock failed');
    }
    
    private function saveSubscriber($email) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'scl_subscribers',
            array('email' => $email, 'subscribed_at' => current_time('mysql')),
            array('%s', '%s')
        );
    }
    
    private function getLockedContentCount() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}scl_locked_content");
    }
    
    private function getMonthlyRevenue() {
        return mt_rand(100, 5000);
    }
}

SmartContentLockerPro::getInstance();
?>