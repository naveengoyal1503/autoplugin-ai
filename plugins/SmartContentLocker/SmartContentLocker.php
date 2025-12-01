<?php
/*
Plugin Name: SmartContentLocker
Plugin URI: https://smartcontentlocker.com
Description: Premium content gating and membership management for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartContentLocker.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
        add_action('init', array($this, 'register_custom_post_type'));
        add_action('add_meta_boxes', array($this, 'add_locker_metabox'));
        add_action('save_post', array($this, 'save_locker_settings'));
        add_action('the_content', array($this, 'apply_content_lock'));
        add_shortcode('scl_login_form', array($this, 'render_login_form'));
        add_shortcode('scl_membership_wall', array($this, 'render_membership_wall'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_scl_unlock_content', array($this, 'ajax_unlock_content'));
        add_action('wp_ajax_nopriv_scl_unlock_content', array($this, 'ajax_unlock_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function register_custom_post_type() {
        register_post_type('scl_membership', array(
            'labels' => array('name' => 'Memberships', 'singular_name' => 'Membership'),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'scl_dashboard',
            'supports' => array('title', 'editor', 'custom-fields'),
            'capability_type' => 'post',
            'capabilities' => array(
                'publish_posts' => 'manage_options',
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'delete_posts' => 'manage_options',
                'delete_others_posts' => 'manage_options',
            )
        ));
    }

    public function add_admin_menu() {
        add_menu_page('SmartContentLocker', 'Content Locker', 'manage_options', 'scl_dashboard', array($this, 'render_dashboard'), 'dashicons-lock', 30);
        add_submenu_page('scl_dashboard', 'Settings', 'Settings', 'manage_options', 'scl_settings', array($this, 'render_settings'));
        add_submenu_page('scl_dashboard', 'Analytics', 'Analytics', 'manage_options', 'scl_analytics', array($this, 'render_analytics'));
    }

    public function add_locker_metabox() {
        add_meta_box('scl_locker_settings', 'Content Lock Settings', array($this, 'render_metabox'), 'post', 'normal', 'high');
    }

    public function render_metabox($post) {
        wp_nonce_field('scl_save_locker', 'scl_locker_nonce');
        $is_locked = get_post_meta($post->ID, '_scl_is_locked', true);
        $lock_type = get_post_meta($post->ID, '_scl_lock_type', true) ?: 'membership';
        $required_membership = get_post_meta($post->ID, '_scl_required_membership', true);
        $preview_text = get_post_meta($post->ID, '_scl_preview_text', true) ?: '100';
        ?>
        <div style="padding: 15px;">
            <p>
                <label><input type="checkbox" name="scl_is_locked" value="1" <?php checked($is_locked, 1); ?> /> Enable Content Lock</label>
            </p>
            <p>
                <label>Lock Type:</label><br/>
                <select name="scl_lock_type" id="scl_lock_type">
                    <option value="membership" <?php selected($lock_type, 'membership'); ?>>Membership Required</option>
                    <option value="email" <?php selected($lock_type, 'email'); ?>>Email Capture</option>
                    <option value="payment" <?php selected($lock_type, 'payment'); ?>>One-time Payment</option>
                </select>
            </p>
            <p>
                <label>Preview Text (characters):</label><br/>
                <input type="number" name="scl_preview_text" value="<?php echo esc_attr($preview_text); ?>" min="0" max="1000" />
            </p>
            <p>
                <label>Required Membership Level:</label><br/>
                <select name="scl_required_membership">
                    <option value="">-- Select Membership --</option>
                    <?php
                    $memberships = get_posts(array('post_type' => 'scl_membership', 'numberposts' => -1));
                    foreach ($memberships as $membership) {
                        echo '<option value="' . esc_attr($membership->ID) . '" ' . selected($required_membership, $membership->ID) . '>' . esc_html($membership->post_title) . '</option>';
                    }
                    ?>
                </select>
            </p>
        </div>
        <?php
    }

    public function save_locker_settings($post_id) {
        if (!isset($_POST['scl_locker_nonce']) || !wp_verify_nonce($_POST['scl_locker_nonce'], 'scl_save_locker')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        update_post_meta($post_id, '_scl_is_locked', isset($_POST['scl_is_locked']) ? 1 : 0);
        update_post_meta($post_id, '_scl_lock_type', sanitize_text_field($_POST['scl_lock_type'] ?? 'membership'));
        update_post_meta($post_id, '_scl_required_membership', sanitize_text_field($_POST['scl_required_membership'] ?? ''));
        update_post_meta($post_id, '_scl_preview_text', intval($_POST['scl_preview_text'] ?? 100));
    }

    public function apply_content_lock($content) {
        if (is_singular('post') && !is_admin()) {
            $post_id = get_the_ID();
            $is_locked = get_post_meta($post_id, '_scl_is_locked', true);
            
            if (!$is_locked) {
                return $content;
            }
            
            if ($this->user_has_access($post_id)) {
                return $content;
            }
            
            $lock_type = get_post_meta($post_id, '_scl_lock_type', true) ?: 'membership';
            $preview_length = intval(get_post_meta($post_id, '_scl_preview_text', true) ?: 100);
            
            $preview = wp_trim_words($content, $preview_length, '...');
            
            ob_start();
            ?>
            <div class="scl-content-locked">
                <div class="scl-preview"><?php echo wp_kses_post($preview); ?></div>
                <div class="scl-lock-message">
                    <?php if ($lock_type === 'membership') { ?>
                        <p>This content is reserved for members.</p>
                        <?php echo do_shortcode('[scl_login_form post_id="' . $post_id . '"]'); ?>
                    <?php } elseif ($lock_type === 'email') { ?>
                        <p>Enter your email to unlock this content.</p>
                        <form class="scl-email-form" data-post-id="<?php echo $post_id; ?>">
                            <input type="email" name="email" placeholder="Your email" required />
                            <button type="submit">Unlock Content</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        return $content;
    }

    private function user_has_access($post_id) {
        if (current_user_can('edit_posts')) {
            return true;
        }
        
        if (!is_user_logged_in()) {
            return false;
        }
        
        $required_membership = get_post_meta($post_id, '_scl_required_membership', true);
        if (!$required_membership) {
            return true;
        }
        
        $user_id = get_current_user_id();
        $user_memberships = get_user_meta($user_id, '_scl_memberships', true) ?: array();
        
        return in_array($required_membership, $user_memberships);
    }

    public function render_login_form() {
        if (is_user_logged_in()) {
            return '<p>You are already logged in.</p>';
        }
        
        ob_start();
        ?>
        <div class="scl-login-form">
            <form method="post">
                <?php wp_nonce_field('scl_login'); ?>
                <p><input type="text" name="log" placeholder="Username" required /></p>
                <p><input type="password" name="pwd" placeholder="Password" required /></p>
                <p><button type="submit" name="wp-submit" class="button">Login</button></p>
                <p><a href="<?php echo wp_registration_url(); ?>">Register</a></p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_membership_wall() {
        $memberships = get_posts(array('post_type' => 'scl_membership', 'numberposts' => -1));
        
        ob_start();
        ?>
        <div class="scl-membership-wall">
            <?php foreach ($memberships as $membership) { ?>
                <div class="scl-membership-card">
                    <h3><?php echo esc_html($membership->post_title); ?></h3>
                    <div class="scl-membership-description"><?php echo wp_kses_post($membership->post_content); ?></div>
                </div>
            <?php } ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_unlock_content() {
        $post_id = intval($_POST['post_id'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (!$post_id || !$email) {
            wp_send_json_error('Invalid request');
        }
        
        update_user_meta(get_current_user_id(), '_scl_unlocked_posts', array_merge(
            get_user_meta(get_current_user_id(), '_scl_unlocked_posts', true) ?: array(),
            array($post_id)
        ));
        
        wp_send_json_success('Content unlocked');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>SmartContentLocker Dashboard</h1>
            <p>Manage your locked content and memberships here.</p>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>SmartContentLocker Settings</h1>
            <p>Configure your plugin settings here.</p>
        </div>
        <?php
    }

    public function render_analytics() {
        ?>
        <div class="wrap">
            <h1>SmartContentLocker Analytics</h1>
            <p>View your content engagement and conversion metrics.</p>
        </div>
        <?php
    }

    public function enqueue_assets() {
        wp_enqueue_style('scl-frontend', SCL_PLUGIN_URL . 'css/frontend.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-frontend', SCL_PLUGIN_URL . 'js/frontend.js', array('jquery'), SCL_VERSION, true);
        wp_localize_script('scl-frontend', 'scl_vars', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style('scl-admin', SCL_PLUGIN_URL . 'css/admin.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-admin', SCL_PLUGIN_URL . 'js/admin.js', array('jquery'), SCL_VERSION, true);
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}scl_memberships (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            price decimal(10, 2) NOT NULL,
            duration varchar(50) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate() {
        // Cleanup on deactivation
    }
}

SmartContentLocker::get_instance();
?>