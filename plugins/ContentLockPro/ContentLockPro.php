<?php
/*
Plugin Name: ContentLockPro
Plugin URI: https://contentlockpro.local
Description: Lock content behind paywalls with flexible monetization options
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentLockPro.php
License: GPL v2 or later
Text Domain: contentlockpro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTLOCKPRO_VERSION', '1.0.0');
define('CONTENTLOCKPRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTLOCKPRO_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentLockPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_filter('the_content', array($this, 'filter_content'));
        add_shortcode('content-lock', array($this, 'shortcode_content_lock'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_unlock_content', array($this, 'ajax_unlock_content'));
        add_action('wp_ajax_nopriv_unlock_content', array($this, 'ajax_unlock_content'));
    }

    public function init_plugin() {
        $this->create_tables();
    }

    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentlock_access';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                post_id bigint(20) NOT NULL,
                access_type varchar(50) NOT NULL,
                expires_at datetime DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_post (user_id, post_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentLockPro',
            'ContentLockPro',
            'manage_options',
            'contentlockpro',
            array($this, 'admin_dashboard'),
            'dashicons-lock',
            20
        );

        add_submenu_page(
            'contentlockpro',
            'Settings',
            'Settings',
            'manage_options',
            'contentlockpro-settings',
            array($this, 'admin_settings')
        );
    }

    public function admin_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentlock_access';
        $total_unlocks = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        ?>
        <div class="wrap">
            <h1>ContentLockPro Dashboard</h1>
            <div class="postbox" style="padding: 20px; margin-top: 20px;">
                <h2>Statistics</h2>
                <p><strong>Total Content Unlocks:</strong> <?php echo intval($total_unlocks); ?></p>
                <p><strong>Plugin Version:</strong> <?php echo CONTENTLOCKPRO_VERSION; ?></p>
            </div>
        </div>
        <?php
    }

    public function admin_settings() {
        if (isset($_POST['contentlockpro_save_settings'])) {
            check_admin_referer('contentlockpro_settings_nonce');
            update_option('contentlockpro_stripe_key', sanitize_text_field($_POST['stripe_key'] ?? ''));
            update_option('contentlockpro_lock_message', wp_kses_post($_POST['lock_message'] ?? ''));
            echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
        }

        $stripe_key = get_option('contentlockpro_stripe_key', '');
        $lock_message = get_option('contentlockpro_lock_message', 'This content is locked. Please unlock to view.');
        ?>
        <div class="wrap">
            <h1>ContentLockPro Settings</h1>
            <form method="POST" style="max-width: 600px;">
                <?php wp_nonce_field('contentlockpro_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="stripe_key">Stripe API Key</label></th>
                        <td><input type="text" id="stripe_key" name="stripe_key" value="<?php echo esc_attr($stripe_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="lock_message">Lock Message</label></th>
                        <td><textarea id="lock_message" name="lock_message" class="large-text"><?php echo esc_textarea($lock_message); ?></textarea></td>
                    </tr>
                </table>
                <p><input type="submit" name="contentlockpro_save_settings" value="Save Settings" class="button button-primary" /></p>
            </form>
        </div>
        <?php
    }

    public function add_meta_boxes() {
        add_meta_box(
            'contentlockpro_meta',
            'Content Lock Settings',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'normal',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('contentlockpro_nonce', 'contentlockpro_nonce_field');

        $is_locked = get_post_meta($post->ID, '_contentlockpro_locked', true);
        $lock_type = get_post_meta($post->ID, '_contentlockpro_type', true);
        $lock_price = get_post_meta($post->ID, '_contentlockpro_price', true);
        ?>
        <div style="padding: 10px;">
            <label style="display: block; margin-bottom: 10px;">
                <input type="checkbox" name="contentlockpro_locked" value="1" <?php checked($is_locked, '1'); ?> />
                <strong>Lock this content</strong>
            </label>

            <label style="display: block; margin-bottom: 10px;">
                Lock Type:
                <select name="contentlockpro_type" style="margin-left: 10px;">
                    <option value="free" <?php selected($lock_type, 'free'); ?>>Free (Email required)</option>
                    <option value="paid" <?php selected($lock_type, 'paid'); ?>>Paid</option>
                    <option value="subscription" <?php selected($lock_type, 'subscription'); ?>>Subscription</option>
                </select>
            </label>

            <label style="display: block; margin-bottom: 10px;">
                Price ($):
                <input type="number" name="contentlockpro_price" value="<?php echo esc_attr($lock_price); ?>" step="0.01" style="margin-left: 10px; width: 100px;" />
            </label>
        </div>
        <?php
    }

    public function filter_content($content) {
        global $post;

        if (!is_main_query() || !is_singular()) {
            return $content;
        }

        $is_locked = get_post_meta($post->ID, '_contentlockpro_locked', true);

        if (!$is_locked) {
            return $content;
        }

        $user_id = get_current_user_id();
        $has_access = $this->user_has_access($user_id, $post->ID);

        if ($has_access) {
            return $content;
        }

        $lock_type = get_post_meta($post->ID, '_contentlockpro_type', true);
        $lock_message = get_option('contentlockpro_lock_message', 'This content is locked. Please unlock to view.');
        $preview = wp_trim_words($content, 30);

        $output = '<div class="contentlockpro-preview" style="background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;">';
        $output .= '<p>' . wp_kses_post($preview) . '...</p>';
        $output .= '<p style="text-align: center; margin-top: 20px;">' . wp_kses_post($lock_message) . '</p>';
        $output .= '<div id="contentlockpro-unlock-' . $post->ID . '" style="text-align: center; margin-top: 15px;">';

        if ($lock_type === 'free') {
            $output .= '<form class="contentlockpro-unlock-form" style="display: inline;">';
            $output .= '<input type="email" placeholder="Enter your email" required style="padding: 8px; margin-right: 10px;" />';
            $output .= '<button type="button" class="button button-primary contentlockpro-unlock-btn" data-post-id="' . $post->ID . '" data-type="free">Unlock Free</button>';
            $output .= '</form>';
        } else {
            $price = get_post_meta($post->ID, '_contentlockpro_price', true);
            $output .= '<button type="button" class="button button-primary contentlockpro-unlock-btn" data-post-id="' . $post->ID . '" data-type="' . esc_attr($lock_type) . '" data-price="' . esc_attr($price) . '">Unlock for \$' . number_format($price, 2) . '</button>';
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    public function shortcode_content_lock($atts, $content = '') {
        $atts = shortcode_atts(array(
            'type' => 'free',
            'price' => 0,
        ), $atts);

        $user_id = get_current_user_id();
        $post_id = get_the_ID();

        if ($user_id && $this->user_has_access($user_id, $post_id)) {
            return do_shortcode($content);
        }

        $output = '<div class="contentlockpro-locked" style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0;">';
        $output .= '<p><strong>This content is locked.</strong></p>';

        if ($atts['type'] === 'free') {
            $output .= '<button class="button contentlockpro-unlock-btn" data-post-id="' . $post_id . '" data-type="free">Unlock Free</button>';
        } else {
            $output .= '<button class="button button-primary contentlockpro-unlock-btn" data-post-id="' . $post_id . '" data-type="paid" data-price="' . esc_attr($atts['price']) . '">Unlock for \$' . number_format($atts['price'], 2) . '</button>';
        }

        $output .= '</div>';
        return $output;
    }

    private function user_has_access($user_id, $post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentlock_access';

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d AND user_id = %d AND (expires_at IS NULL OR expires_at > NOW())",
            $post_id,
            $user_id
        ));

        return !empty($result);
    }

    public function ajax_unlock_content() {
        check_ajax_referer('contentlockpro_nonce');

        $post_id = intval($_POST['post_id'] ?? 0);
        $user_id = get_current_user_id();
        $lock_type = sanitize_text_field($_POST['type'] ?? '');

        if ($lock_type === 'free') {
            $email = sanitize_email($_POST['email'] ?? '');
            if (empty($email)) {
                wp_send_json_error(array('message' => 'Email is required'));
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'contentlock_access';
            $wpdb->insert($table_name, array(
                'post_id' => $post_id,
                'user_id' => $user_id ?: 0,
                'access_type' => 'free',
            ));

            wp_send_json_success(array('message' => 'Content unlocked! Refresh to view.'));
        } else {
            wp_send_json_error(array('message' => 'Payment processing not yet implemented'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('contentlockpro-frontend', CONTENTLOCKPRO_PLUGIN_URL . 'js/frontend.js', array('jquery'), CONTENTLOCKPRO_VERSION, true);
        wp_localize_script('contentlockpro-frontend', 'contentlockpro', array(
            'nonce' => wp_create_nonce('contentlockpro_nonce'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ));
    }

    public function admin_enqueue_scripts() {
        wp_enqueue_style('contentlockpro-admin', CONTENTLOCKPRO_PLUGIN_URL . 'css/admin.css', array(), CONTENTLOCKPRO_VERSION);
    }
}

// Initialize the plugin
ContentLockPro::get_instance();

register_activation_hook(__FILE__, function() {
    ContentLockPro::get_instance()->init_plugin();
});

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('contentlockpro_cleanup');
});

// Create frontend.js
if (!file_exists(CONTENTLOCKPRO_PLUGIN_DIR . 'js/frontend.js')) {
    wp_mkdir_p(CONTENTLOCKPRO_PLUGIN_DIR . 'js');
    $frontend_js = "jQuery(document).ready(function($) {
        $('.contentlockpro-unlock-btn').on('click', function() {
            var postId = $(this).data('post-id');
            var type = $(this).data('type');
            var email = $(this).closest('form').find('input[type=email]').val() || '';
            
            $.ajax({
                url: contentlockpro.ajax_url,
                type: 'POST',
                data: {
                    action: 'unlock_content',
                    post_id: postId,
                    type: type,
                    email: email,
                    nonce: contentlockpro.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    }
                },
                error: function(response) {
                    alert('Error: ' + response.responseJSON.data.message);
                }
            });
        });
    });
    ";
    file_put_contents(CONTENTLOCKPRO_PLUGIN_DIR . 'js/frontend.js', $frontend_js);
}

// Create admin.css
if (!file_exists(CONTENTLOCKPRO_PLUGIN_DIR . 'css/admin.css')) {
    wp_mkdir_p(CONTENTLOCKPRO_PLUGIN_DIR . 'css');
    $admin_css = ".contentlockpro-preview { background: #f5f5f5; padding: 20px; border-radius: 5px; }
    .contentlockpro-locked { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; }
    ";
    file_put_contents(CONTENTLOCKPRO_PLUGIN_DIR . 'css/admin.css', $admin_css);
}
?>