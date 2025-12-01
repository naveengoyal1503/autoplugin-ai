<?php
/*
Plugin Name: Smart Affiliate Link Manager Pro
Plugin URI: https://smartaffiliatelinkmanager.com
Description: Advanced affiliate link management with keyword automation, tracking, and analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

define('SALMP_VERSION', '1.0.0');
define('SALMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SALMP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SALMP_TABLE', $GLOBALS['wpdb']->prefix . 'salmp_affiliate_links');

class SmartAffiliateLinkManager {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_filter('the_content', [$this, 'autoLinkifyContent'], 9999);
        add_action('wp_ajax_salmp_create_link', [$this, 'ajaxCreateLink']);
        add_action('wp_ajax_salmp_get_stats', [$this, 'ajaxGetStats']);
        add_action('wp_ajax_salmp_delete_link', [$this, 'ajaxDeleteLink']);
        add_action('template_redirect', [$this, 'redirectAffiliateLink']);
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS " . SALMP_TABLE . " (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            keyword VARCHAR(255) NOT NULL UNIQUE,
            affiliate_url LONGTEXT NOT NULL,
            custom_text VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            click_count INT DEFAULT 0,
            conversion_count INT DEFAULT 0,
            enabled TINYINT(1) DEFAULT 1
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('salmp_version', SALMP_VERSION);
        update_option('salmp_plan', 'free');
        update_option('salmp_auto_linkify', 1);
        update_option('salmp_max_links', 50);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function addAdminMenu() {
        add_menu_page(
            'Affiliate Link Manager',
            'Affiliate Links',
            'manage_options',
            'salmp-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-link',
            30
        );
        
        add_submenu_page(
            'salmp-dashboard',
            'All Links',
            'All Links',
            'manage_options',
            'salmp-dashboard',
            [$this, 'renderDashboard']
        );
        
        add_submenu_page(
            'salmp-dashboard',
            'Create Link',
            'Create Link',
            'manage_options',
            'salmp-create',
            [$this, 'renderCreatePage']
        );
        
        add_submenu_page(
            'salmp-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'salmp-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings() {
        register_setting('salmp_settings', 'salmp_auto_linkify');
        register_setting('salmp_settings', 'salmp_link_prefix');
        register_setting('salmp_settings', 'salmp_open_new_tab');
    }

    public function enqueueFrontendScripts() {
        wp_enqueue_script('salmp-frontend', SALMP_PLUGIN_URL . 'assets/frontend.js', ['jquery'], SALMP_VERSION);
        wp_localize_script('salmp-frontend', 'salmP', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }

    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'salmp') === false) return;
        wp_enqueue_script('salmp-admin', SALMP_PLUGIN_URL . 'assets/admin.js', ['jquery'], SALMP_VERSION);
        wp_enqueue_style('salmp-admin', SALMP_PLUGIN_URL . 'assets/admin.css', [], SALMP_VERSION);
        wp_localize_script('salmp-admin', 'salmPAdmin', ['nonce' => wp_create_nonce('salmp_nonce')]);
    }

    public function renderDashboard() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM " . SALMP_TABLE . " ORDER BY click_count DESC LIMIT 100");
        $total_clicks = $wpdb->get_var("SELECT SUM(click_count) FROM " . SALMP_TABLE);
        $total_conversions = $wpdb->get_var("SELECT SUM(conversion_count) FROM " . SALMP_TABLE);
        ?>
        <div class="wrap">
            <h1>Affiliate Link Manager</h1>
            <div class="salmp-stats">
                <div class="stat-box"><strong>Total Clicks:</strong> <?php echo intval($total_clicks); ?></div>
                <div class="stat-box"><strong>Total Conversions:</strong> <?php echo intval($total_conversions); ?></div>
                <div class="stat-box"><strong>Active Links:</strong> <?php echo count($links); ?></div>
            </div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Custom Text</th>
                        <th>URL Preview</th>
                        <th>Clicks</th>
                        <th>Conversions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                    <tr>
                        <td><strong><?php echo esc_html($link->keyword); ?></strong></td>
                        <td><?php echo esc_html($link->custom_text ?: '—'); ?></td>
                        <td><?php echo esc_html(substr($link->affiliate_url, 0, 50) . '...'); ?></td>
                        <td><?php echo intval($link->click_count); ?></td>
                        <td><?php echo intval($link->conversion_count); ?></td>
                        <td><?php echo $link->enabled ? '<span class="active">Active</span>' : '<span class="inactive">Inactive</span>'; ?></td>
                        <td><button class="button delete-link" data-id="<?php echo intval($link->id); ?>">Delete</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderCreatePage() {
        ?>
        <div class="wrap">
            <h1>Create New Affiliate Link</h1>
            <form id="salmp-create-form" class="salmp-form">
                <?php wp_nonce_field('salmp_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="keyword">Keyword to Replace:</label></th>
                        <td><input type="text" id="keyword" name="keyword" required placeholder="e.g., 'best hosting'"></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_url">Affiliate URL:</label></th>
                        <td><input type="url" id="affiliate_url" name="affiliate_url" required placeholder="https://example.com/ref=abc123"></td>
                    </tr>
                    <tr>
                        <th><label for="custom_text">Link Text (optional):</label></th>
                        <td><input type="text" id="custom_text" name="custom_text" placeholder="Custom link text"></td>
                    </tr>
                </table>
                <p><button type="submit" class="button button-primary">Create Link</button></p>
            </form>
        </div>
        <?php
    }

    public function renderSettingsPage() {
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('salmp_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="salmp_auto_linkify">Auto-Linkify Content:</label></th>
                        <td><input type="checkbox" id="salmp_auto_linkify" name="salmp_auto_linkify" value="1" <?php checked(get_option('salmp_auto_linkify')); ?>> Automatically convert keywords to affiliate links in posts</td>
                    </tr>
                    <tr>
                        <th><label for="salmp_open_new_tab">Open Links in New Tab:</label></th>
                        <td><input type="checkbox" id="salmp_open_new_tab" name="salmp_open_new_tab" value="1" <?php checked(get_option('salmp_open_new_tab')); ?>></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function autoLinkifyContent($content) {
        if (!get_option('salmp_auto_linkify') || is_admin()) return $content;
        
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM " . SALMP_TABLE . " WHERE enabled = 1 ORDER BY LENGTH(keyword) DESC");
        
        foreach ($links as $link) {
            $keyword = preg_quote($link->keyword, '/');
            $target = get_option('salmp_open_new_tab') ? ' target="_blank"' : '';
            $replacement = '<a href="' . esc_url($link->affiliate_url) . '" class="salmp-link" data-link-id="' . intval($link->id) . '"' . $target . '>' . esc_html($link->custom_text ?: $link->keyword) . '</a>';
            $content = preg_replace('/\b' . $keyword . '\b/i', $replacement, $content, 1);
        }
        
        return $content;
    }

    public function redirectAffiliateLink() {
        if (!isset($_GET['salmp_link'])) return;
        
        global $wpdb;
        $link_id = intval($_GET['salmp_link']);
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . SALMP_TABLE . " WHERE id = %d", $link_id));
        
        if ($link) {
            $wpdb->update(SALMP_TABLE, ['click_count' => $link->click_count + 1], ['id' => $link_id]);
            wp_redirect($link->affiliate_url);
            exit;
        }
    }

    public function ajaxCreateLink() {
        check_ajax_referer('salmp_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        $url = esc_url_raw($_POST['affiliate_url'] ?? '');
        $custom_text = sanitize_text_field($_POST['custom_text'] ?? '');
        
        if (!$keyword || !$url) {
            wp_send_json_error('Missing required fields');
        }
        
        global $wpdb;
        $result = $wpdb->insert(SALMP_TABLE, [
            'keyword' => $keyword,
            'affiliate_url' => $url,
            'custom_text' => $custom_text
        ]);
        
        if ($result) {
            wp_send_json_success(['id' => $wpdb->insert_id]);
        } else {
            wp_send_json_error('Database error');
        }
    }

    public function ajaxGetStats() {
        check_ajax_referer('salmp_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $stats = $wpdb->get_row("SELECT COUNT(*) as total_links, SUM(click_count) as total_clicks, SUM(conversion_count) as total_conversions FROM " . SALMP_TABLE);
        wp_send_json_success($stats);
    }

    public function ajaxDeleteLink() {
        check_ajax_referer('salmp_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $link_id = intval($_POST['id'] ?? 0);
        global $wpdb;
        $result = $wpdb->delete(SALMP_TABLE, ['id' => $link_id]);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Delete failed');
        }
    }
}

SmartAffiliateLinkManager::getInstance();
?>