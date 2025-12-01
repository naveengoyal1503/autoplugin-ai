<?php
/*
Plugin Name: ContentLock Pro
Plugin URI: https://contentlockpro.com
Description: Premium content monetization with memberships, paywalls, and micro-transactions
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
define('CONTENTLOCK_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTLOCK_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentLockPro {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_content_lock_metabox'));
        add_action('save_post', array($this, 'save_content_lock_meta'));
        add_filter('the_content', array($this, 'apply_content_lock'), 999);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_contentlock_unlock', array($this, 'handle_unlock_request'));
        add_shortcode('contentlock_preview', array($this, 'render_preview_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'contentlock_access';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            content_id bigint(20) NOT NULL,
            access_type varchar(50) NOT NULL,
            expires_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_content (user_id, content_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('contentlock_pro_activated', time());
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentLock Pro',
            'ContentLock Pro',
            'manage_options',
            'contentlock-pro',
            array($this, 'render_admin_dashboard'),
            'dashicons-lock',
            25
        );
        
        add_submenu_page(
            'contentlock-pro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentlock-pro',
            array($this, 'render_admin_dashboard')
        );
        
        add_submenu_page(
            'contentlock-pro',
            'Settings',
            'Settings',
            'manage_options',
            'contentlock-pro-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'contentlock-pro',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentlock-pro-analytics',
            array($this, 'render_analytics_page')
        );
    }

    public function add_content_lock_metabox() {
        add_meta_box(
            'contentlock_pro_settings',
            'ContentLock Pro Settings',
            array($this, 'render_metabox'),
            array('post', 'page'),
            'normal',
            'high'
        );
    }

    public function render_metabox($post) {
        wp_nonce_field('contentlock_pro_nonce', 'contentlock_pro_nonce_field');
        
        $lock_enabled = get_post_meta($post->ID, '_contentlock_enabled', true);
        $lock_type = get_post_meta($post->ID, '_contentlock_type', true) ?: 'paywall';
        $lock_price = get_post_meta($post->ID, '_contentlock_price', true);
        $preview_percentage = get_post_meta($post->ID, '_contentlock_preview', true) ?: 50;
        $membership_required = get_post_meta($post->ID, '_contentlock_membership', true);
        
        ?>
        <div style="padding: 10px;">
            <label>
                <input type="checkbox" name="contentlock_enabled" value="1" <?php checked($lock_enabled, 1); ?> />
                Enable Content Lock
            </label>
            
            <div style="margin-top: 15px;">
                <label for="contentlock_type">Lock Type:</label>
                <select name="contentlock_type" id="contentlock_type">
                    <option value="paywall" <?php selected($lock_type, 'paywall'); ?>>Pay-Per-View</option>
                    <option value="membership" <?php selected($lock_type, 'membership'); ?>>Membership Only</option>
                    <option value="preview" <?php selected($lock_type, 'preview'); ?>>Preview + Paywall</option>
                </select>
            </div>
            
            <div style="margin-top: 10px;" id="price_field" style="<?php echo ($lock_type === 'membership') ? 'display: none;' : ''; ?>">
                <label for="contentlock_price">Price (USD):</label>
                <input type="number" name="contentlock_price" id="contentlock_price" value="<?php echo esc_attr($lock_price); ?>" step="0.01" min="0" />
            </div>
            
            <div style="margin-top: 10px;" id="preview_field" style="<?php echo ($lock_type === 'preview') ? '' : 'display: none;'; ?>">
                <label for="contentlock_preview">Show Preview (%):</label>
                <input type="number" name="contentlock_preview" id="contentlock_preview" value="<?php echo esc_attr($preview_percentage); ?>" min="10" max="90" />
            </div>
            
            <div style="margin-top: 10px;">
                <label>
                    <input type="checkbox" name="contentlock_membership" value="1" <?php checked($membership_required, 1); ?> />
                    Require Specific Membership Level
                </label>
            </div>
        </div>
        <?php
    }

    public function save_content_lock_meta($post_id) {
        if (!isset($_POST['contentlock_pro_nonce_field']) || !wp_verify_nonce($_POST['contentlock_pro_nonce_field'], 'contentlock_pro_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        update_post_meta($post_id, '_contentlock_enabled', isset($_POST['contentlock_enabled']) ? 1 : 0);
        update_post_meta($post_id, '_contentlock_type', sanitize_text_field($_POST['contentlock_type'] ?? 'paywall'));
        update_post_meta($post_id, '_contentlock_price', floatval($_POST['contentlock_price'] ?? 0));
        update_post_meta($post_id, '_contentlock_preview', intval($_POST['contentlock_preview'] ?? 50));
        update_post_meta($post_id, '_contentlock_membership', isset($_POST['contentlock_membership']) ? 1 : 0);
    }

    public function apply_content_lock($content) {
        global $post;
        
        if (!is_single() || !isset($post)) {
            return $content;
        }
        
        $lock_enabled = get_post_meta($post->ID, '_contentlock_enabled', true);
        if (!$lock_enabled) {
            return $content;
        }
        
        if (current_user_can('edit_post', $post->ID)) {
            return $content;
        }
        
        $lock_type = get_post_meta($post->ID, '_contentlock_type', true) ?: 'paywall';
        $price = get_post_meta($post->ID, '_contentlock_price', true);
        $preview_percentage = get_post_meta($post->ID, '_contentlock_preview', true) ?: 50;
        
        if ($this->user_has_access($post->ID)) {
            return $content;
        }
        
        if ($lock_type === 'preview') {
            $content = $this->apply_preview_lock($content, $preview_percentage, $post->ID, $price);
        } else {
            $content = $this->apply_paywall($post->ID, $price);
        }
        
        return $content;
    }

    private function user_has_access($post_id) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false;
        }
        
        $table_name = $wpdb->prefix . 'contentlock_access';
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND content_id = %d AND (expires_at IS NULL OR expires_at > NOW())",
            $user_id,
            $post_id
        ));
        
        return $result > 0;
    }

    private function apply_preview_lock($content, $preview_percentage, $post_id, $price) {
        $word_count = str_word_count(wp_strip_all_tags($content));
        $preview_words = max(1, intval($word_count * ($preview_percentage / 100)));
        
        $words = explode(' ', wp_strip_all_tags($content));
        $preview_content = implode(' ', array_slice($words, 0, $preview_words));
        
        return $preview_content . '<div class="contentlock-paywall"><p>Content locked. Purchase to continue reading.</p><button class="contentlock-unlock-btn" data-post-id="' . $post_id . '" data-price="' . $price . '">Unlock ($' . number_format($price, 2) . ')</button></div>';
    }

    private function apply_paywall($post_id, $price) {
        return '<div class="contentlock-paywall"><h3>This content is locked</h3><p>Purchase access to read the full article.</p><button class="contentlock-unlock-btn" data-post-id="' . $post_id . '" data-price="' . $price . '">Unlock ($' . number_format($price, 2) . ')</button></div>';
    }

    public function render_preview_shortcode($atts) {
        global $post;
        $content = apply_filters('the_content', $post->post_content);
        $word_count = str_word_count(wp_strip_all_tags($content));
        $preview_words = max(1, intval($word_count * 0.3));
        $words = explode(' ', wp_strip_all_tags($content));
        return implode(' ', array_slice($words, 0, $preview_words)) . '...';
    }

    public function handle_unlock_request() {
        check_ajax_referer('contentlock_nonce');
        
        $post_id = intval($_POST['post_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentlock_access';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'content_id' => $post_id,
                'access_type' => 'purchased',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+365 days'))
            ),
            array('%d', '%d', '%s', '%s')
        );
        
        do_action('contentlock_purchase_completed', $post_id, $user_id);
        
        wp_send_json_success('Content unlocked');
    }

    public function render_admin_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentlock_access';
        
        $total_access = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_revenue = $wpdb->get_var("SELECT SUM(CAST(meta_value AS DECIMAL(10,2))) FROM {$wpdb->postmeta} WHERE meta_key = '_contentlock_price'");
        $locked_content = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_contentlock_enabled' AND meta_value = 1");
        
        ?>
        <div class="wrap">
            <h1>ContentLock Pro Dashboard</h1>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 20px;">
                <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                    <h3>Total Accesses</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $total_access; ?></p>
                </div>
                <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                    <h3>Locked Content Items</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $locked_content; ?></p>
                </div>
                <div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">
                    <h3>Estimated Revenue</h3>
                    <p style="font-size: 24px; font-weight: bold;"><?php echo $total_revenue ? '$' . number_format($total_revenue, 2) : '$0.00'; ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>ContentLock Pro Settings</h1>
            <form method="post">
                <?php wp_nonce_field('contentlock_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Payment Gateway</th>
                        <td>
                            <select name="contentlock_payment_gateway">
                                <option>Stripe</option>
                                <option>PayPal</option>
                                <option>Square</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_analytics_page() {
        ?>
        <div class="wrap">
            <h1>ContentLock Pro Analytics</h1>
            <p>Detailed analytics and reporting coming soon.</p>
        </div>
        <?php
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('contentlock-frontend', CONTENTLOCK_PRO_PLUGIN_URL . 'css/frontend.css', array(), CONTENTLOCK_PRO_VERSION);
        wp_enqueue_script('contentlock-frontend', CONTENTLOCK_PRO_PLUGIN_URL . 'js/frontend.js', array('jquery'), CONTENTLOCK_PRO_VERSION, true);
        wp_localize_script('contentlock-frontend', 'contentlockData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentlock_nonce')
        ));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('contentlock-admin', CONTENTLOCK_PRO_PLUGIN_URL . 'js/admin.js', array('jquery'), CONTENTLOCK_PRO_VERSION, true);
    }
}

ContentLockPro::get_instance();
?>
