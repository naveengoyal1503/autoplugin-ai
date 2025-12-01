<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.com
Description: Gate premium content behind email signups, social shares, or payments
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL v2 or later
Text Domain: smart-content-locker
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCL_VERSION', '1.0.0');

class SmartContentLockerPro {
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_shortcode('scl_locker', array($this, 'render_locker'));
        add_action('wp_ajax_scl_unlock_content', array($this, 'handle_unlock'));
        add_action('wp_ajax_nopriv_scl_unlock_content', array($this, 'handle_unlock'));
        add_action('wp_ajax_scl_save_locker', array($this, 'save_locker'));
        add_action('wp_ajax_scl_get_lockers', array($this, 'get_lockers'));
    }

    public function register_post_type() {
        register_post_type('scl_locker', array(
            'labels' => array(
                'name' => 'Content Lockers',
                'singular_name' => 'Content Locker',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=scl_locker',
            'supports' => array('title'),
            'capability_type' => 'post',
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Smart Content Locker Pro',
            'Content Lockers',
            'manage_options',
            'scl-dashboard',
            array($this, 'render_admin_dashboard'),
            'dashicons-lock',
            25
        );
    }

    public function render_admin_dashboard() {
        ?>
        <div class="wrap">
            <h1>Smart Content Locker Pro</h1>
            <div id="scl-admin-app"></div>
        </div>
        <?php
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.js', array('jquery'), SCL_VERSION, true);
        wp_localize_script('scl-frontend', 'sclData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scl_nonce'),
        ));
    }

    public function enqueue_admin_assets() {
        if (isset($_GET['page']) && $_GET['page'] === 'scl-dashboard') {
            wp_enqueue_style('scl-admin', SCL_PLUGIN_URL . 'assets/admin.css', array(), SCL_VERSION);
            wp_enqueue_script('scl-admin', SCL_PLUGIN_URL . 'assets/admin.js', array('jquery'), SCL_VERSION, true);
            wp_localize_script('scl-admin', 'sclAdmin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('scl_admin_nonce'),
            ));
        }
    }

    public function render_locker($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'message' => 'Enter your email to unlock this content',
            'button_text' => 'Unlock Now',
            'unlock_type' => 'email',
        ), $atts);

        $locker_id = intval($atts['id']);
        if (!$locker_id) {
            return '<p>Invalid locker ID</p>';
        }

        $user_id = get_current_user_id();
        $unlocked_lockers = get_user_meta($user_id, 'scl_unlocked_lockers', true);
        if (!is_array($unlocked_lockers)) {
            $unlocked_lockers = array();
        }

        $is_unlocked = in_array($locker_id, $unlocked_lockers);

        ob_start();
        ?>
        <div class="scl-locker" data-locker-id="<?php echo $locker_id; ?>" data-unlock-type="<?php echo esc_attr($atts['unlock_type']); ?>">
            <?php if ($is_unlocked): ?>
                <div class="scl-content-unlocked">
                    <?php echo do_shortcode(get_post_meta($locker_id, 'scl_content', true)); ?>
                </div>
            <?php else: ?>
                <div class="scl-locker-overlay">
                    <div class="scl-locker-box">
                        <h3><?php echo esc_html($atts['message']); ?></h3>
                        <form class="scl-unlock-form">
                            <?php if ($atts['unlock_type'] === 'email'): ?>
                                <input type="email" name="email" placeholder="your@email.com" required>
                            <?php elseif ($atts['unlock_type'] === 'share'): ?>
                                <p>Share this on social media to unlock</p>
                                <div class="scl-share-buttons">
                                    <button type="button" class="scl-share-btn" data-network="twitter">Share on Twitter</button>
                                    <button type="button" class="scl-share-btn" data-network="facebook">Share on Facebook</button>
                                </div>
                            <?php endif; ?>
                            <button type="submit" class="scl-unlock-btn"><?php echo esc_html($atts['button_text']); ?></button>
                            <input type="hidden" name="locker_id" value="<?php echo $locker_id; ?>">
                            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('scl_unlock_' . $locker_id); ?>">
                        </form>
                    </div>
                </div>
                <div class="scl-content-preview" style="filter: blur(5px); pointer-events: none;">
                    <?php echo do_shortcode(get_post_meta($locker_id, 'scl_content', true)); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_unlock() {
        check_ajax_referer('scl_nonce', 'nonce');

        $locker_id = intval($_POST['locker_id']);
        $unlock_type = sanitize_text_field($_POST['unlock_type'] ?? 'email');
        $user_id = get_current_user_id();

        if ($unlock_type === 'email') {
            $email = sanitize_email($_POST['email'] ?? '');
            if (!is_email($email)) {
                wp_send_json_error('Invalid email address');
            }
            update_user_meta($user_id ?: 0, 'scl_email', $email);
        }

        $unlocked_lockers = get_user_meta($user_id, 'scl_unlocked_lockers', true);
        if (!is_array($unlocked_lockers)) {
            $unlocked_lockers = array();
        }

        $unlocked_lockers[] = $locker_id;
        update_user_meta($user_id ?: md5($_SERVER['REMOTE_ADDR']), 'scl_unlocked_lockers', array_unique($unlocked_lockers));

        wp_send_json_success(array('message' => 'Content unlocked successfully'));
    }

    public function save_locker() {
        check_ajax_referer('scl_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $locker_id = intval($_POST['locker_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? 'New Locker');
        $content = wp_kses_post($_POST['content'] ?? '');
        $unlock_type = sanitize_text_field($_POST['unlock_type'] ?? 'email');

        if ($locker_id) {
            wp_update_post(array(
                'ID' => $locker_id,
                'post_title' => $title,
                'post_type' => 'scl_locker',
            ));
        } else {
            $locker_id = wp_insert_post(array(
                'post_title' => $title,
                'post_type' => 'scl_locker',
                'post_status' => 'publish',
            ));
        }

        update_post_meta($locker_id, 'scl_content', $content);
        update_post_meta($locker_id, 'scl_unlock_type', $unlock_type);

        wp_send_json_success(array('locker_id' => $locker_id));
    }

    public function get_lockers() {
        check_ajax_referer('scl_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $lockers = get_posts(array(
            'post_type' => 'scl_locker',
            'posts_per_page' => -1,
        ));

        $data = array();
        foreach ($lockers as $locker) {
            $data[] = array(
                'id' => $locker->ID,
                'title' => $locker->post_title,
                'unlock_type' => get_post_meta($locker->ID, 'scl_unlock_type', true),
                'shortcode' => '[scl_locker id="' . $locker->ID . '"]',
            );
        }

        wp_send_json_success($data);
    }
}

new SmartContentLockerPro();

register_activation_hook(__FILE__, function() {
    add_option('scl_activated', true);
});

register_deactivation_hook(__FILE__, function() {
    delete_option('scl_activated');
});

?>
