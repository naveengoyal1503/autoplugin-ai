/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Smart Affiliate Link Manager Pro
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateLinkManager {
    private $db_version = '1.0';
    private $option_prefix = 'salm_';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_footer', array($this, 'track_clicks'));
        add_shortcode('affiliate_link', array($this, 'affiliate_shortcode'));
        add_filter('the_content', array($this, 'auto_link_content'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'salm_links';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            affiliate_url longtext NOT NULL,
            short_code varchar(255) UNIQUE NOT NULL,
            category varchar(100),
            clicks bigint(20) DEFAULT 0,
            commissions decimal(10, 2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option($this->option_prefix . 'db_version', $this->db_version);
        update_option($this->option_prefix . 'api_key', '');
        update_option($this->option_prefix . 'premium', false);
    }

    public function deactivate() {
        wp_clear_scheduled_hook('salm_daily_analytics');
    }

    public function add_admin_menu() {
        add_menu_page(
            'Smart Affiliate Links',
            'Affiliate Manager',
            'manage_options',
            'salm_dashboard',
            array($this, 'render_dashboard'),
            'dashicons-link',
            25
        );
        
        add_submenu_page(
            'salm_dashboard',
            'Manage Links',
            'Manage Links',
            'manage_options',
            'salm_links',
            array($this, 'render_links_page')
        );
        
        add_submenu_page(
            'salm_dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'salm_analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'salm_dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'salm_settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('salm_settings_group', $this->option_prefix . 'api_key');
        register_setting('salm_settings_group', $this->option_prefix . 'auto_link_keywords');
        register_setting('salm_settings_group', $this->option_prefix . 'tracking_enabled');
    }

    public function render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'salm_links';
        $user_id = get_current_user_id();
        
        $total_clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(clicks) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        $total_commission = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(commissions) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        $link_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        $is_premium = get_option($this->option_prefix . 'premium', false);
        
        echo '<div class="wrap"><h1>Smart Affiliate Link Manager Dashboard</h1>';
        echo '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">';
        echo '<div style="background: #f1f1f1; padding: 20px; border-radius: 5px;"><h3>Total Clicks</h3><p style="font-size: 24px; font-weight: bold;">' . ($total_clicks ?: 0) . '</p></div>';
        echo '<div style="background: #f1f1f1; padding: 20px; border-radius: 5px;"><h3>Total Commission</h3><p style="font-size: 24px; font-weight: bold;">$' . number_format($total_commission ?: 0, 2) . '</p></div>';
        echo '<div style="background: #f1f1f1; padding: 20px; border-radius: 5px;"><h3>Active Links</h3><p style="font-size: 24px; font-weight: bold;">' . $link_count . '</p></div>';
        echo '</div>';
        echo '<p><strong>Status:</strong> ' . ($is_premium ? '<span style="color: green;">Premium Active</span>' : '<span style="color: orange;">Free Version</span>') . '</p>';
        echo '</div>';
    }

    public function render_links_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'salm_links';
        $user_id = get_current_user_id();
        
        if (isset($_POST['add_link'])) {
            check_admin_referer('salm_add_link_nonce');
            
            $url = sanitize_url($_POST['affiliate_url']);
            $category = sanitize_text_field($_POST['category']);
            $short_code = sanitize_text_field($_POST['short_code']);
            
            $wpdb->insert($table_name, array(
                'user_id' => $user_id,
                'affiliate_url' => $url,
                'short_code' => $short_code,
                'category' => $category,
                'clicks' => 0,
                'commissions' => 0
            ));
            
            echo '<div class="notice notice-success"><p>Link added successfully!</p></div>';
        }
        
        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));
        
        echo '<div class="wrap"><h1>Manage Affiliate Links</h1>';
        echo '<form method="POST" style="background: #f9f9f9; padding: 20px; margin-bottom: 20px; border-radius: 5px;">';
        wp_nonce_field('salm_add_link_nonce');
        echo '<input type="hidden" name="add_link" value="1">';
        echo '<p><label>Affiliate URL: <input type="url" name="affiliate_url" required style="width: 100%; margin-top: 5px; padding: 8px;"></label></p>';
        echo '<p><label>Short Code: <input type="text" name="short_code" required style="width: 100%; margin-top: 5px; padding: 8px;"></label></p>';
        echo '<p><label>Category: <input type="text" name="category" style="width: 100%; margin-top: 5px; padding: 8px;"></label></p>';
        echo '<button type="submit" class="button button-primary">Add Link</button>';
        echo '</form>';
        
        echo '<table class="widefat" style="margin-top: 20px;">';
        echo '<thead><tr><th>Short Code</th><th>URL</th><th>Category</th><th>Clicks</th><th>Commission</th><th>Created</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($links as $link) {
            echo '<tr>';
            echo '<td>' . esc_html($link->short_code) . '</td>';
            echo '<td>' . esc_html(substr($link->affiliate_url, 0, 50)) . '...</td>';
            echo '<td>' . esc_html($link->category) . '</td>';
            echo '<td>' . $link->clicks . '</td>';
            echo '<td>$' . number_format($link->commissions, 2) . '</td>';
            echo '<td>' . esc_html($link->created_at) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></div>';
    }

    public function render_analytics_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'salm_links';
        $user_id = get_current_user_id();
        
        $top_links = $wpdb->get_results($wpdb->prepare(
            "SELECT short_code, clicks, commissions FROM $table_name WHERE user_id = %d ORDER BY clicks DESC LIMIT 10",
            $user_id
        ));
        
        echo '<div class="wrap"><h1>Analytics</h1>';
        echo '<h2>Top Performing Links</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Link</th><th>Clicks</th><th>Commission</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($top_links as $link) {
            echo '<tr>';
            echo '<td>' . esc_html($link->short_code) . '</td>';
            echo '<td>' . $link->clicks . '</td>';
            echo '<td>$' . number_format($link->commissions, 2) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></div>';
    }

    public function render_settings_page() {
        echo '<div class="wrap"><h1>Settings</h1>';
        echo '<form method="POST" action="options.php">';
        settings_fields('salm_settings_group');
        echo '<table class="form-table">';
        echo '<tr><th scope="row"><label for="api_key">API Key</label></th>';
        echo '<td><input type="text" name="' . $this->option_prefix . 'api_key" value="' . esc_attr(get_option($this->option_prefix . 'api_key')) . '" style="width: 100%; max-width: 400px; padding: 8px;"></td></tr>';
        echo '<tr><th scope="row"><label><input type="checkbox" name="' . $this->option_prefix . 'tracking_enabled" value="1" ' . checked(get_option($this->option_prefix . 'tracking_enabled'), 1, false) . '> Enable Click Tracking</label></th></tr>';
        echo '</table>';
        submit_button('Save Settings');
        echo '</form></div>';
    }

    public function affiliate_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        global $wpdb;
        
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT affiliate_url FROM {$wpdb->prefix}salm_links WHERE short_code = %s",
            $atts['code']
        ));
        
        if ($link) {
            return '<a href="' . esc_url($link->affiliate_url) . '" target="_blank" rel="noopener">View Product</a>';
        }
        
        return '';
    }

    public function auto_link_content($content) {
        if (!is_admin()) {
            $keywords = get_option($this->option_prefix . 'auto_link_keywords', '');
            if (!empty($keywords)) {
                $keywords = array_map('trim', explode(',', $keywords));
                foreach ($keywords as $keyword) {
                    $content = str_ireplace($keyword, '[affiliate_link code="' . sanitize_text_field($keyword) . '"]' . $keyword . '[/affiliate_link]', $content);
                }
            }
        }
        return $content;
    }

    public function track_clicks() {
        if (isset($_GET['salm_track'])) {
            global $wpdb;
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}salm_links SET clicks = clicks + 1 WHERE short_code = %s",
                sanitize_text_field($_GET['salm_track'])
            ));
        }
    }
}

if (is_admin() || !is_admin()) {
    new SmartAffiliateLinkManager();
}
?>