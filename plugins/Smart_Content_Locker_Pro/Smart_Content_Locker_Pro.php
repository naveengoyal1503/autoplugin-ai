<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.local
Description: Gate premium content behind email opt-ins, social shares, or micropayments
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL v2 or later
Text Domain: smart-content-locker
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SCL_VERSION', '1.0.0');
define('SCL_DIR', plugin_dir_path(__FILE__));
define('SCL_URL', plugin_dir_url(__FILE__));

class SmartContentLocker {
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->initHooks();
        $this->createTables();
    }

    private function initHooks() {
        add_action('admin_menu', [$this, 'registerAdminMenu']);
        add_action('init', [$this, 'registerShortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('wp_ajax_scl_verify_email', [$this, 'verifyEmail']);
        add_action('wp_ajax_nopriv_scl_verify_email', [$this, 'verifyEmail']);
        add_action('wp_ajax_scl_verify_share', [$this, 'verifyShare']);
        add_action('wp_ajax_nopriv_scl_verify_share', [$this, 'verifyShare']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function activate() {
        $this->createTables();
        add_option('scl_activated', 1);
    }

    private function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'scl_locks';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                post_id mediumint(9) NOT NULL,
                lock_type varchar(20) NOT NULL,
                unlock_condition longtext NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        $table_name = $wpdb->prefix . 'scl_unlocks';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                post_id mediumint(9) NOT NULL,
                lock_id mediumint(9) NOT NULL,
                user_identifier varchar(255) NOT NULL,
                unlock_method varchar(20) NOT NULL,
                unlocked_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public function registerAdminMenu() {
        add_menu_page(
            'Smart Content Locker',
            'Content Locker',
            'manage_options',
            'scl-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-lock',
            25
        );
    }

    public function renderDashboard() {
        ?>
        <div class="wrap">
            <h1>Smart Content Locker Pro</h1>
            <div class="scl-dashboard">
                <p>Welcome to Smart Content Locker! Use the [scl_lock] shortcode to gate your content.</p>
                <h2>Quick Stats</h2>
                <p><?php echo $this->getUnlockStats(); ?></p>
            </div>
        </div>
        <?php
    }

    private function getUnlockStats() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}scl_unlocks");
        return "Total content unlocks: " . intval($count);
    }

    public function registerShortcode() {
        add_shortcode('scl_lock', [$this, 'renderLockedContent']);
    }

    public function renderLockedContent($atts, $content = '') {
        $atts = shortcode_atts([
            'type' => 'email',
            'message' => 'Unlock premium content',
            'id' => uniqid('scl_')
        ], $atts, 'scl_lock');

        $post_id = get_the_ID();
        $lock_id = $atts['id'];
        $lock_type = sanitize_text_field($atts['type']);
        $user_identifier = $this->getUserIdentifier();

        if ($this->isContentUnlocked($post_id, $lock_id, $user_identifier)) {
            return do_shortcode($content);
        }

        ob_start();
        ?>
        <div class="scl-locked-content" data-lock-id="<?php echo esc_attr($lock_id); ?>" data-post-id="<?php echo esc_attr($post_id); ?>" data-type="<?php echo esc_attr($lock_type); ?>">
            <div class="scl-lock-overlay">
                <div class="scl-lock-message">
                    <h3><?php echo esc_html($atts['message']); ?></h3>
                    <?php if ($lock_type === 'email'): ?>
                        <form class="scl-email-form" data-lock-id="<?php echo esc_attr($lock_id); ?>">
                            <input type="email" placeholder="Enter your email" required>
                            <button type="submit">Unlock</button>
                        </form>
                    <?php elseif ($lock_type === 'share'): ?>
                        <p>Share this post to unlock premium content</p>
                        <button class="scl-share-btn" data-lock-id="<?php echo esc_attr($lock_id); ?>">Share to Unlock</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function getUserIdentifier() {
        if (is_user_logged_in()) {
            return 'user_' . get_current_user_id();
        }
        if (!isset($_COOKIE['scl_identifier'])) {
            $_COOKIE['scl_identifier'] = 'visitor_' . bin2hex(random_bytes(16));
            setcookie('scl_identifier', $_COOKIE['scl_identifier'], strtotime('+30 days'));
        }
        return $_COOKIE['scl_identifier'];
    }

    private function isContentUnlocked($post_id, $lock_id, $user_identifier) {
        global $wpdb;
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}scl_unlocks WHERE post_id = %d AND lock_id = %s AND user_identifier = %s",
                $post_id,
                $lock_id,
                $user_identifier
            )
        );
        return !empty($result);
    }

    public function verifyEmail() {
        check_ajax_referer('scl_nonce', 'nonce');
        $email = sanitize_email($_POST['email']);
        $lock_id = sanitize_text_field($_POST['lock_id']);
        $post_id = intval($_POST['post_id']);
        $user_identifier = $this->getUserIdentifier();

        if (empty($email) || !is_email($email)) {
            wp_send_json_error('Invalid email address');
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'scl_unlocks',
            [
                'post_id' => $post_id,
                'lock_id' => $lock_id,
                'user_identifier' => $user_identifier,
                'unlock_method' => 'email'
            ],
            ['%d', '%s', '%s', '%s']
        );

        do_action('scl_email_collected', $email, $post_id);
        wp_send_json_success('Content unlocked!');
    }

    public function verifyShare() {
        check_ajax_referer('scl_nonce', 'nonce');
        $lock_id = sanitize_text_field($_POST['lock_id']);
        $post_id = intval($_POST['post_id']);
        $user_identifier = $this->getUserIdentifier();

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'scl_unlocks',
            [
                'post_id' => $post_id,
                'lock_id' => $lock_id,
                'user_identifier' => $user_identifier,
                'unlock_method' => 'share'
            ],
            ['%d', '%s', '%s', '%s']
        );

        wp_send_json_success('Content unlocked!');
    }

    public function enqueueScripts() {
        wp_enqueue_script('scl-frontend', SCL_URL . 'assets/frontend.js', ['jquery'], SCL_VERSION, true);
        wp_localize_script('scl-frontend', 'sclData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scl_nonce')
        ]);
        wp_enqueue_style('scl-frontend', SCL_URL . 'assets/frontend.css', [], SCL_VERSION);
    }

    public function enqueueAdminScripts() {
        wp_enqueue_script('scl-admin', SCL_URL . 'assets/admin.js', ['jquery'], SCL_VERSION, true);
        wp_enqueue_style('scl-admin', SCL_URL . 'assets/admin.css', [], SCL_VERSION);
    }
}

SmartContentLocker::getInstance();
?>