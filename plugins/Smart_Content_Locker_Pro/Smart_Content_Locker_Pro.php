/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
*/
<?php
/**
 * Smart Content Locker Pro
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartContentLocker {
    private $db_version = '1.0.0';
    private $db_prefix = 'scl_';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('the_content', array($this, 'filter_content'));
        add_action('wp_ajax_scl_unlock_content', array($this, 'ajax_unlock_content'));
        add_action('wp_ajax_nopriv_scl_unlock_content', array($this, 'ajax_unlock_content'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_prefix . 'locks';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            lock_type varchar(20) DEFAULT 'email',
            lock_message text,
            unlock_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('scl_db_version', $this->db_version);
    }

    public function deactivate() {}

    public function init() {
        add_rewrite_rule('^scl-unlock/([0-9]+)/?$', 'index.php?scl_unlock=$matches[1]', 'top');
        flush_rewrite_rules(false);
    }

    public function admin_menu() {
        add_menu_page(
            'Smart Content Locker',
            'Content Locker',
            'manage_options',
            'scl-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-lock'
        );
    }

    public function dashboard_page() {
        global $wpdb;
        $table = $wpdb->prefix . $this->db_prefix . 'locks';
        $locks = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50");
        ?>
        <div class="wrap">
            <h1>Smart Content Locker Pro</h1>
            <div class="scl-dashboard">
                <h2>Locked Content Overview</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Post Title</th>
                            <th>Lock Type</th>
                            <th>Unlocks</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locks as $lock): ?>
                            <tr>
                                <td><?php echo get_the_title($lock->post_id); ?></td>
                                <td><?php echo ucfirst($lock->lock_type); ?></td>
                                <td><?php echo $lock->unlock_count; ?></td>
                                <td><?php echo $lock->created_at; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function add_meta_boxes() {
        add_meta_box(
            'scl_lock_meta',
            'Content Locker Settings',
            array($this, 'meta_box_callback'),
            'post',
            'normal'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('scl_save_meta', 'scl_nonce');
        $enabled = get_post_meta($post->ID, '_scl_enabled', true);
        $lock_type = get_post_meta($post->ID, '_scl_lock_type', true) ?: 'email';
        $lock_message = get_post_meta($post->ID, '_scl_lock_message', true) ?: 'This content is locked. Subscribe to unlock.';
        ?>
        <div style="padding: 10px;">
            <label>
                <input type="checkbox" name="scl_enabled" value="1" <?php checked($enabled, 1); ?> />
                Enable Content Lock
            </label>
            <br><br>
            <label>Lock Type:</label>
            <select name="scl_lock_type">
                <option value="email" <?php selected($lock_type, 'email'); ?>>Email Subscription</option>
                <option value="social" <?php selected($lock_type, 'social'); ?>>Social Share</option>
                <option value="payment" <?php selected($lock_type, 'payment'); ?>>Micropayment</option>
            </select>
            <br><br>
            <label>Lock Message:</label>
            <textarea name="scl_lock_message" style="width: 100%; height: 80px;"><?php echo esc_textarea($lock_message); ?></textarea>
        </div>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST['scl_nonce']) || !wp_verify_nonce($_POST['scl_nonce'], 'scl_save_meta')) return;
        update_post_meta($post_id, '_scl_enabled', isset($_POST['scl_enabled']) ? 1 : 0);
        update_post_meta($post_id, '_scl_lock_type', sanitize_text_field($_POST['scl_lock_type'] ?? 'email'));
        update_post_meta($post_id, '_scl_lock_message', sanitize_textarea_field($_POST['scl_lock_message'] ?? ''));
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.css', array(), '1.0.0');
        wp_enqueue_script('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('scl-frontend', 'sclAjax', array('url' => admin_url('admin-ajax.php')));
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style('scl-admin', SCL_PLUGIN_URL . 'assets/admin.css', array(), '1.0.0');
        wp_enqueue_script('scl-admin', SCL_PLUGIN_URL . 'assets/admin.js', array('jquery'), '1.0.0', true);
    }

    public function filter_content($content) {
        if (!is_singular('post') || !in_the_loop()) return $content;
        $post_id = get_the_ID();
        if (!get_post_meta($post_id, '_scl_enabled', true)) return $content;
        $lock_type = get_post_meta($post_id, '_scl_lock_type', true) ?: 'email';
        $lock_message = get_post_meta($post_id, '_scl_lock_message', true);
        if (!isset($_COOKIE['scl_unlock_' . $post_id])) {
            $this->log_unlock_attempt($post_id);
            return $this->render_lock_ui($post_id, $lock_type, $lock_message, $content);
        }
        return $content;
    }

    private function render_lock_ui($post_id, $lock_type, $message, $content) {
        ob_start();
        ?>
        <div class="scl-lock-container">
            <div class="scl-lock-overlay">
                <div class="scl-lock-content">
                    <div class="scl-lock-icon">🔒</div>
                    <h3><?php echo esc_html($message); ?></h3>
                    <?php if ($lock_type === 'email'): ?>
                        <form class="scl-email-form" data-post-id="<?php echo $post_id; ?>">
                            <input type="email" name="email" placeholder="Enter your email" required>
                            <button type="submit">Unlock Content</button>
                        </form>
                    <?php elseif ($lock_type === 'social'): ?>
                        <div class="scl-social-buttons">
                            <button class="scl-share-btn facebook">Share on Facebook</button>
                            <button class="scl-share-btn twitter">Share on Twitter</button>
                        </div>
                    <?php elseif ($lock_type === 'payment'): ?>
                        <button class="scl-payment-btn" data-post-id="<?php echo $post_id; ?>">Unlock for $0.99</button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="scl-preview"><?php echo wp_kses_post(wp_trim_words($content, 50)); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_unlock_content() {
        $post_id = intval($_POST['post_id'] ?? 0);
        $email = sanitize_email($_POST['email'] ?? '');
        if ($post_id && $email) {
            setcookie('scl_unlock_' . $post_id, '1', time() + (24 * 60 * 60), '/'); // 24 hours
            wp_send_json_success(array('message' => 'Content unlocked!'));
        }
        wp_send_json_error(array('message' => 'Unlock failed'));
    }

    private function log_unlock_attempt($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . $this->db_prefix . 'locks';
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id, unlock_count FROM $table WHERE post_id = %d", $post_id));
        if ($existing) {
            $wpdb->update($table, array('unlock_count' => $existing->unlock_count + 1), array('id' => $existing->id));
        } else {
            $wpdb->insert($table, array('post_id' => $post_id, 'unlock_count' => 1));
        }
    }
}

new SmartContentLocker();
?>