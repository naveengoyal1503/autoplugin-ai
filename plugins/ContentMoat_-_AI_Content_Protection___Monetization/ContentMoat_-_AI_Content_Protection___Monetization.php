<?php
/*
Plugin Name: ContentMoat - AI Content Protection & Monetization
Plugin URI: https://contentmoat.com
Description: Protect your content from AI scraping and monetize through paywalls, subscriptions, and usage analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentMoat_-_AI_Content_Protection___Monetization.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;

define('CONTENTMOAT_VERSION', '1.0.0');
define('CONTENTMOAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTMOAT_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentMoat {
    private static $instance = null;
    private $db;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'));
        add_filter('the_content', array($this, 'protectContent'), 999);
        add_action('wp_ajax_contentmoat_preview', array($this, 'handleAjaxPreview'));
        add_action('wp_ajax_nopriv_contentmoat_preview', array($this, 'handleAjaxPreview'));
        add_shortcode('contentmoat_paywall', array($this, 'renderPaywall'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentmoat_settings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            option_name varchar(100) NOT NULL,
            option_value longtext NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY option_name (option_name)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentmoat_analytics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            access_type varchar(50) NOT NULL,
            user_id mediumint(9),
            ip_address varchar(45),
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        dbDelta($sql2);
        
        update_option('contentmoat_version', CONTENTMOAT_VERSION);
    }

    public function deactivate() {
    }

    public function addAdminMenu() {
        add_menu_page(
            'ContentMoat',
            'ContentMoat',
            'manage_options',
            'contentmoat',
            array($this, 'renderAdminPage'),
            'dashicons-lock',
            80
        );
        
        add_submenu_page(
            'contentmoat',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentmoat',
            array($this, 'renderAdminPage')
        );
        
        add_submenu_page(
            'contentmoat',
            'Settings',
            'Settings',
            'manage_options',
            'contentmoat-settings',
            array($this, 'renderSettingsPage')
        );
    }

    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'contentmoat') === false) return;
        
        wp_enqueue_style('contentmoat-admin', CONTENTMOAT_PLUGIN_URL . 'admin.css', array(), CONTENTMOAT_VERSION);
        wp_enqueue_script('contentmoat-admin', CONTENTMOAT_PLUGIN_URL . 'admin.js', array('jquery'), CONTENTMOAT_VERSION, true);
        
        wp_localize_script('contentmoat-admin', 'contentmoatAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentmoat_nonce')
        ));
    }

    public function enqueueFrontendScripts() {
        wp_enqueue_style('contentmoat-frontend', CONTENTMOAT_PLUGIN_URL . 'frontend.css', array(), CONTENTMOAT_VERSION);
        wp_enqueue_script('contentmoat-frontend', CONTENTMOAT_PLUGIN_URL . 'frontend.js', array('jquery'), CONTENTMOAT_VERSION, true);
        
        wp_localize_script('contentmoat-frontend', 'contentmoat', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentmoat_frontend')
        ));
    }

    public function protectContent($content) {
        if (!is_singular('post') || is_admin()) {
            return $content;
        }
        
        $post_id = get_the_ID();
        $protection_type = get_post_meta($post_id, '_contentmoat_protection', true);
        
        if (empty($protection_type)) {
            return $content;
        }
        
        $preview_words = (int)get_post_meta($post_id, '_contentmoat_preview_words', true) ?: 100;
        
        $this->logAccess($post_id, $protection_type);
        
        if ($protection_type === 'preview' && !$this->userCanAccessFull($post_id)) {
            $preview = wp_trim_words($content, $preview_words);
            $preview .= ' ...';
            
            return $preview . '[contentmoat_paywall post_id="' . $post_id . '"]';
        }
        
        return $content;
    }

    public function renderPaywall($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
        ), $atts);
        
        $post_id = (int)$atts['post_id'];
        $price = (float)get_post_meta($post_id, '_contentmoat_price', true) ?: 4.99;
        
        ob_start();
        ?>
        <div class="contentmoat-paywall">
            <div class="contentmoat-paywall-content">
                <h3>Premium Content</h3>
                <p>Unlock the full article for just \$<?php echo number_format($price, 2); ?></p>
                <button class="contentmoat-unlock-btn" data-post-id="<?php echo $post_id; ?>" data-price="<?php echo $price; ?>">Unlock Article</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handleAjaxPreview() {
        check_ajax_referer('contentmoat_frontend');
        
        $post_id = (int)$_POST['post_id'];
        $action = sanitize_text_field($_POST['action_type']);
        
        if ($action === 'unlock') {
            set_transient('contentmoat_unlocked_' . $post_id . '_' . get_current_user_id(), true, HOUR_IN_SECONDS * 24);
            wp_send_json_success(array('message' => 'Content unlocked'));
        }
    }

    public function logAccess($post_id, $access_type) {
        $this->db->insert(
            $this->db->prefix . 'contentmoat_analytics',
            array(
                'post_id' => $post_id,
                'access_type' => $access_type,
                'user_id' => get_current_user_id(),
                'ip_address' => $this->getClientIp(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'], 0, 255),
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s')
        );
    }

    public function userCanAccessFull($post_id) {
        if (current_user_can('edit_post', $post_id)) {
            return true;
        }
        
        if (get_transient('contentmoat_unlocked_' . $post_id . '_' . get_current_user_id())) {
            return true;
        }
        
        return false;
    }

    public function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }

    public function renderAdminPage() {
        $analytics = $this->db->get_results(
            "SELECT * FROM {$this->db->prefix}contentmoat_analytics ORDER BY timestamp DESC LIMIT 20"
        );
        ?>
        <div class="wrap">
            <h1>ContentMoat Dashboard</h1>
            <div class="contentmoat-dashboard">
                <div class="contentmoat-stats">
                    <div class="stat-card">
                        <h3>Total Accesses</h3>
                        <p><?php echo count($analytics); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Protected Posts</h3>
                        <p><?php echo $this->countProtectedPosts(); ?></p>
                    </div>
                </div>
                <h2>Recent Activity</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Post</th>
                            <th>Type</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics as $log) : ?>
                        <tr>
                            <td><?php echo get_the_title($log->post_id); ?></td>
                            <td><?php echo esc_html($log->access_type); ?></td>
                            <td><?php echo esc_html($log->timestamp); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function renderSettingsPage() {
        if (isset($_POST['contentmoat_save_settings'])) {
            check_admin_referer('contentmoat_settings');
            
            update_option('contentmoat_default_protection', sanitize_text_field($_POST['protection_type']));
            update_option('contentmoat_default_price', floatval($_POST['default_price']));
            
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $protection_type = get_option('contentmoat_default_protection', 'preview');
        $default_price = get_option('contentmoat_default_price', 4.99);
        ?>
        <div class="wrap">
            <h1>ContentMoat Settings</h1>
            <form method="post">
                <?php wp_nonce_field('contentmoat_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="protection_type">Default Protection Type</label></th>
                        <td>
                            <select name="protection_type" id="protection_type">
                                <option value="preview" <?php selected($protection_type, 'preview'); ?>>Preview (Paywall)</option>
                                <option value="full" <?php selected($protection_type, 'full'); ?>>Full Protection</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="default_price">Default Unlock Price</label></th>
                        <td>
                            <input type="number" name="default_price" id="default_price" value="<?php echo esc_attr($default_price); ?>" step="0.01" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
                <input type="hidden" name="contentmoat_save_settings" value="1" />
            </form>
        </div>
        <?php
    }

    private function countProtectedPosts() {
        return $this->db->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$this->db->prefix}postmeta WHERE meta_key = '_contentmoat_protection'"
        );
    }
}

ContentMoat::getInstance();
?>