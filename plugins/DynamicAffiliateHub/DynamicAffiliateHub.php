<?php
/*
Plugin Name: DynamicAffiliateHub
Plugin URI: https://dynamicaffiliatehub.com
Description: Advanced affiliate link management, tracking, and optimization for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=DynamicAffiliateHub.php
License: GPL2
Text Domain: dynamic-affiliate-hub
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('DAH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DAH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DAH_VERSION', '1.0.0');

class DynamicAffiliateHub {
    private static $instance = null;
    private $db;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_nopriv_track_affiliate_click', array($this, 'track_affiliate_click'));
        add_action('wp_ajax_track_affiliate_click', array($this, 'track_affiliate_click'));
    }

    public function init() {
        load_plugin_textdomain('dynamic-affiliate-hub', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_tables();
    }

    public function create_tables() {
        $charset_collate = $this->db->get_charset_collate();
        
        $links_table = "
            CREATE TABLE IF NOT EXISTS {$this->db->prefix}dah_affiliate_links (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                link_code VARCHAR(50) UNIQUE NOT NULL,
                affiliate_url LONGTEXT NOT NULL,
                display_text VARCHAR(255),
                program_name VARCHAR(100),
                commission_rate DECIMAL(5,2),
                clicks INT DEFAULT 0,
                conversions INT DEFAULT 0,
                earnings DECIMAL(10,2) DEFAULT 0,
                active BOOLEAN DEFAULT TRUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_code (link_code)
            ) $charset_collate;
        ";
        
        $tracking_table = "
            CREATE TABLE IF NOT EXISTS {$this->db->prefix}dah_click_tracking (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                link_id BIGINT UNSIGNED NOT NULL,
                user_ip VARCHAR(45),
                user_agent TEXT,
                referrer TEXT,
                click_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                converted BOOLEAN DEFAULT FALSE,
                FOREIGN KEY (link_id) REFERENCES {$this->db->prefix}dah_affiliate_links(id) ON DELETE CASCADE
            ) $charset_collate;
        ";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($links_table);
        dbDelta($tracking_table);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Affiliate Hub', 'dynamic-affiliate-hub'),
            __('Affiliate Hub', 'dynamic-affiliate-hub'),
            'manage_options',
            'dah-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-link',
            25
        );
        
        add_submenu_page(
            'dah-dashboard',
            __('Manage Links', 'dynamic-affiliate-hub'),
            __('Manage Links', 'dynamic-affiliate-hub'),
            'manage_options',
            'dah-manage-links',
            array($this, 'render_manage_links')
        );
        
        add_submenu_page(
            'dah-dashboard',
            __('Analytics', 'dynamic-affiliate-hub'),
            __('Analytics', 'dynamic-affiliate-hub'),
            'manage_options',
            'dah-analytics',
            array($this, 'render_analytics')
        );
        
        add_submenu_page(
            'dah-dashboard',
            __('Settings', 'dynamic-affiliate-hub'),
            __('Settings', 'dynamic-affiliate-hub'),
            'manage_options',
            'dah-settings',
            array($this, 'render_settings')
        );
    }

    public function register_settings() {
        register_setting('dah_settings', 'dah_tracking_enabled');
        register_setting('dah_settings', 'dah_redirect_delay');
        register_setting('dah_settings', 'dah_custom_domain');
        register_setting('dah_settings', 'dah_auto_nofollow');
    }

    public function render_dashboard() {
        $total_links = $this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix}dah_affiliate_links");
        $total_clicks = $this->db->get_var("SELECT SUM(clicks) FROM {$this->db->prefix}dah_affiliate_links");
        $total_conversions = $this->db->get_var("SELECT SUM(conversions) FROM {$this->db->prefix}dah_affiliate_links");
        $total_earnings = $this->db->get_var("SELECT SUM(earnings) FROM {$this->db->prefix}dah_affiliate_links");
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Affiliate Hub Dashboard', 'dynamic-affiliate-hub') . '</h1>';
        echo '<div class="dah-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">';
        echo '<div class="stat-box" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        echo '<h3>Total Links</h3><p style="font-size: 24px; font-weight: bold;">' . intval($total_links) . '</p>';
        echo '</div>';
        echo '<div class="stat-box" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        echo '<h3>Total Clicks</h3><p style="font-size: 24px; font-weight: bold;">' . intval($total_clicks) . '</p>';
        echo '</div>';
        echo '<div class="stat-box" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        echo '<h3>Conversions</h3><p style="font-size: 24px; font-weight: bold;">' . intval($total_conversions) . '</p>';
        echo '</div>';
        echo '<div class="stat-box" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">';
        echo '<h3>Total Earnings</h3><p style="font-size: 24px; font-weight: bold;">' . esc_html(money_format('%.2n', floatval($total_earnings))) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function render_manage_links() {
        if (isset($_POST['dah_add_link']) && wp_verify_nonce($_POST['_wpnonce'], 'dah_add_link')) {
            $link_code = sanitize_text_field($_POST['link_code']);
            $affiliate_url = esc_url($_POST['affiliate_url']);
            $display_text = sanitize_text_field($_POST['display_text']);
            $program_name = sanitize_text_field($_POST['program_name']);
            $commission_rate = floatval($_POST['commission_rate']);
            
            $this->db->insert(
                $this->db->prefix . 'dah_affiliate_links',
                array(
                    'link_code' => $link_code,
                    'affiliate_url' => $affiliate_url,
                    'display_text' => $display_text,
                    'program_name' => $program_name,
                    'commission_rate' => $commission_rate
                )
            );
            echo '<div class="notice notice-success"><p>' . esc_html__('Link added successfully!', 'dynamic-affiliate-hub') . '</p></div>';
        }
        
        if (isset($_GET['delete'])) {
            $link_id = intval($_GET['delete']);
            $this->db->delete($this->db->prefix . 'dah_affiliate_links', array('id' => $link_id));
            echo '<div class="notice notice-success"><p>' . esc_html__('Link deleted!', 'dynamic-affiliate-hub') . '</p></div>';
        }
        
        $links = $this->db->get_results("SELECT * FROM {$this->db->prefix}dah_affiliate_links ORDER BY created_at DESC");
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Manage Affiliate Links', 'dynamic-affiliate-hub') . '</h1>';
        echo '<form method="POST" style="background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px;">';
        wp_nonce_field('dah_add_link');
        echo '<h2>' . esc_html__('Add New Link', 'dynamic-affiliate-hub') . '</h2>';
        echo '<table style="width: 100%;">';
        echo '<tr><td><label>' . esc_html__('Link Code', 'dynamic-affiliate-hub') . '</label></td><td><input type="text" name="link_code" required style="width: 100%; padding: 8px;"></td></tr>';
        echo '<tr><td><label>' . esc_html__('Affiliate URL', 'dynamic-affiliate-hub') . '</label></td><td><input type="url" name="affiliate_url" required style="width: 100%; padding: 8px;"></td></tr>';
        echo '<tr><td><label>' . esc_html__('Display Text', 'dynamic-affiliate-hub') . '</label></td><td><input type="text" name="display_text" style="width: 100%; padding: 8px;"></td></tr>';
        echo '<tr><td><label>' . esc_html__('Program Name', 'dynamic-affiliate-hub') . '</label></td><td><input type="text" name="program_name" style="width: 100%; padding: 8px;"></td></tr>';
        echo '<tr><td><label>' . esc_html__('Commission Rate (%)', 'dynamic-affiliate-hub') . '</label></td><td><input type="number" name="commission_rate" step="0.01" style="width: 100%; padding: 8px;"></td></tr>';
        echo '</table>';
        echo '<button type="submit" name="dah_add_link" class="button button-primary" style="margin-top: 10px;">' . esc_html__('Add Link', 'dynamic-affiliate-hub') . '</button>';
        echo '</form>';
        
        echo '<h2>' . esc_html__('Existing Links', 'dynamic-affiliate-hub') . '</h2>';
        echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">';
        echo '<thead><tr><th>' . esc_html__('Code', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Program', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Clicks', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Conversions', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Earnings', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Action', 'dynamic-affiliate-hub') . '</th></tr></thead>';
        echo '<tbody>';
        foreach ($links as $link) {
            echo '<tr>';
            echo '<td><code>' . esc_html($link->link_code) . '</code></td>';
            echo '<td>' . esc_html($link->program_name) . '</td>';
            echo '<td>' . intval($link->clicks) . '</td>';
            echo '<td>' . intval($link->conversions) . '</td>';
            echo '<td>$' . number_format($link->earnings, 2) . '</td>';
            echo '<td><a href="?page=dah-manage-links&delete=' . $link->id . '" class="button button-small" onclick="return confirm(\'Delete this link?\');">' . esc_html__('Delete', 'dynamic-affiliate-hub') . '</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public function render_analytics() {
        $links = $this->db->get_results("SELECT * FROM {$this->db->prefix}dah_affiliate_links ORDER BY clicks DESC LIMIT 10");
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Analytics', 'dynamic-affiliate-hub') . '</h1>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>' . esc_html__('Link Code', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Program', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Clicks', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('CTR', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Conversions', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Conv. Rate', 'dynamic-affiliate-hub') . '</th><th>' . esc_html__('Earnings', 'dynamic-affiliate-hub') . '</th></tr></thead>';
        echo '<tbody>';
        $total_clicks = $this->db->get_var("SELECT SUM(clicks) FROM {$this->db->prefix}dah_affiliate_links");
        foreach ($links as $link) {
            $click_through_rate = $total_clicks > 0 ? ($link->clicks / $total_clicks) * 100 : 0;
            $conversion_rate = $link->clicks > 0 ? ($link->conversions / $link->clicks) * 100 : 0;
            echo '<tr>';
            echo '<td>' . esc_html($link->link_code) . '</td>';
            echo '<td>' . esc_html($link->program_name) . '</td>';
            echo '<td>' . intval($link->clicks) . '</td>';
            echo '<td>' . number_format($click_through_rate, 2) . '%</td>';
            echo '<td>' . intval($link->conversions) . '</td>';
            echo '<td>' . number_format($conversion_rate, 2) . '%</td>';
            echo '<td>$' . number_format($link->earnings, 2) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public function render_settings() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Affiliate Hub Settings', 'dynamic-affiliate-hub') . '</h1>';
        echo '<form method="POST" action="options.php">';
        settings_fields('dah_settings');
        echo '<table class="form-table">';
        echo '<tr><td><label>' . esc_html__('Enable Click Tracking', 'dynamic-affiliate-hub') . '</label></td><td><input type="checkbox" name="dah_tracking_enabled" value="1" ' . checked(get_option('dah_tracking_enabled'), 1, false) . '></td></tr>';
        echo '<tr><td><label>' . esc_html__('Redirect Delay (ms)', 'dynamic-affiliate-hub') . '</label></td><td><input type="number" name="dah_redirect_delay" value="' . esc_attr(get_option('dah_redirect_delay', 0)) . '" style="width: 100px;"></td></tr>';
        echo '<tr><td><label>' . esc_html__('Add rel="nofollow"', 'dynamic-affiliate-hub') . '</label></td><td><input type="checkbox" name="dah_auto_nofollow" value="1" ' . checked(get_option('dah_auto_nofollow'), 1, false) . '></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => '',
            'text' => 'Click Here',
            'class' => ''
        ), $atts);
        
        $link = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}dah_affiliate_links WHERE link_code = %s",
            $atts['code']
        ));
        
        if (!$link) {
            return '[Invalid link code]';
        }
        
        $nofollow = get_option('dah_auto_nofollow') ? 'rel="nofollow"' : '';
        return sprintf(
            '<a href="%s" %s class="dah-affiliate-link %s" data-link-id="%d">%s</a>',
            esc_url($link->affiliate_url),
            $nofollow,
            esc_attr($atts['class']),
            intval($link->id),
            esc_html($atts['text'])
        );
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script('dah-frontend', DAH_PLUGIN_URL . 'js/frontend.js', array('jquery'), DAH_VERSION);
        wp_localize_script('dah-frontend', 'dahAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'redirectDelay' => intval(get_option('dah_redirect_delay', 0))
        ));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('dah-admin', DAH_PLUGIN_URL . 'css/admin.css', array(), DAH_VERSION);
    }

    public function track_affiliate_click() {
        $link_id = intval($_POST['link_id'] ?? 0);
        
        if ($link_id > 0) {
            $this->db->query($this->db->prepare(
                "UPDATE {$this->db->prefix}dah_affiliate_links SET clicks = clicks + 1 WHERE id = %d",
                $link_id
            ));
            
            $this->db->insert(
                $this->db->prefix . 'dah_click_tracking',
                array(
                    'link_id' => $link_id,
                    'user_ip' => $this->get_client_ip(),
                    'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                    'referrer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : ''
                )
            );
            
            wp_send_json_success(array('message' => 'Click tracked'));
        }
        
        wp_send_json_error(array('message' => 'Invalid link ID'));
    }

    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return sanitize_text_field($ip);
    }
}

DynamicAffiliateHub::get_instance();

register_activation_hook(__FILE__, function() {
    DynamicAffiliateHub::get_instance()->create_tables();
});

register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
});

?>