<?php
/*
Plugin Name: SmartAffiliate Revenue Optimizer
Plugin URI: https://smartaffiliate.local
Description: Intelligent affiliate link management and optimization for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Revenue_Optimizer.php
License: GPL v2 or later
*/

if (!defined('ABSPATH')) exit;

class SmartAffiliatePlugin {
    private $plugin_version = '1.0.0';
    private $plugin_slug = 'smart-affiliate';
    private $db_version = 1;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('init', array($this, 'init_plugin'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        add_shortcode('smart_affiliate', array($this, 'render_affiliate_shortcode'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }

    public function init_plugin() {
        load_plugin_textdomain($this->plugin_slug, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'smart_affiliate_links';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            affiliate_id varchar(255) NOT NULL,
            product_name varchar(255) NOT NULL,
            affiliate_url text NOT NULL,
            commission_rate varchar(50),
            clicks bigint(20) DEFAULT 0,
            conversions bigint(20) DEFAULT 0,
            revenue decimal(10, 2) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option($this->plugin_slug . '_db_version', $this->db_version);
    }

    public function add_admin_menu() {
        add_menu_page(
            'SmartAffiliate Revenue Optimizer',
            'SmartAffiliate',
            'manage_options',
            $this->plugin_slug,
            array($this, 'admin_page'),
            'dashicons-chart-line',
            25
        );
        
        add_submenu_page(
            $this->plugin_slug,
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            $this->plugin_slug . '_links',
            array($this, 'manage_links_page')
        );
        
        add_submenu_page(
            $this->plugin_slug,
            'Analytics',
            'Analytics',
            'manage_options',
            $this->plugin_slug . '_analytics',
            array($this, 'analytics_page')
        );
        
        add_submenu_page(
            $this->plugin_slug,
            'Settings',
            'Settings',
            'manage_options',
            $this->plugin_slug . '_settings',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting($this->plugin_slug, $this->plugin_slug . '_api_key');
        register_setting($this->plugin_slug, $this->plugin_slug . '_enable_tracking');
        register_setting($this->plugin_slug, $this->plugin_slug . '_auto_insert');
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliate_links';
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $table_name");
        $total_conversions = $wpdb->get_var("SELECT SUM(conversions) FROM $table_name");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table_name");
        
        echo '<div class="wrap">';
        echo '<h1>SmartAffiliate Dashboard</h1>';
        echo '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">';
        echo '<div style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
        echo '<h3>Total Links</h3>';
        echo '<p style="font-size: 24px; font-weight: bold;">' . ($total_links ?: 0) . '</p>';
        echo '</div>';
        echo '<div style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
        echo '<h3>Total Clicks</h3>';
        echo '<p style="font-size: 24px; font-weight: bold;">' . ($total_clicks ?: 0) . '</p>';
        echo '</div>';
        echo '<div style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
        echo '<h3>Conversions</h3>';
        echo '<p style="font-size: 24px; font-weight: bold;">' . ($total_conversions ?: 0) . '</p>';
        echo '</div>';
        echo '<div style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
        echo '<h3>Revenue</h3>';
        echo '<p style="font-size: 24px; font-weight: bold; color: #27ae60;">' . wc_price($total_revenue ?: 0) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function manage_links_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliate_links';
        
        if (isset($_POST['add_link']) && wp_verify_nonce($_POST['_wpnonce'], 'add_affiliate_link')) {
            $wpdb->insert($table_name, array(
                'affiliate_id' => sanitize_text_field($_POST['affiliate_id']),
                'product_name' => sanitize_text_field($_POST['product_name']),
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'commission_rate' => sanitize_text_field($_POST['commission_rate'])
            ));
            echo '<div class="notice notice-success"><p>Affiliate link added successfully!</p></div>';
        }
        
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_date DESC");
        
        echo '<div class="wrap">';
        echo '<h1>Manage Affiliate Links</h1>';
        echo '<form method="post" style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0;">';
        wp_nonce_field('add_affiliate_link');
        echo '<table style="width: 100%;">';
        echo '<tr><td><label>Affiliate ID: <input type="text" name="affiliate_id" required style="width: 100%; padding: 8px;"/></label></td></tr>';
        echo '<tr><td><label>Product Name: <input type="text" name="product_name" required style="width: 100%; padding: 8px;"/></label></td></tr>';
        echo '<tr><td><label>Affiliate URL: <input type="url" name="affiliate_url" required style="width: 100%; padding: 8px;"/></label></td></tr>';
        echo '<tr><td><label>Commission Rate: <input type="text" name="commission_rate" placeholder="e.g., 5%" style="width: 100%; padding: 8px;"/></label></td></tr>';
        echo '<tr><td><button type="submit" name="add_link" class="button button-primary" style="margin-top: 10px;">Add Link</button></td></tr>';
        echo '</table>';
        echo '</form>';
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Product</th><th>Affiliate ID</th><th>Clicks</th><th>Conversions</th><th>Revenue</th><th>Commission</th></tr></thead>';
        echo '<tbody>';
        foreach ($links as $link) {
            echo '<tr>';
            echo '<td>' . esc_html($link->product_name) . '</td>';
            echo '<td>' . esc_html($link->affiliate_id) . '</td>';
            echo '<td>' . $link->clicks . '</td>';
            echo '<td>' . $link->conversions . '</td>';
            echo '<td>' . wc_price($link->revenue) . '</td>';
            echo '<td>' . esc_html($link->commission_rate) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function analytics_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliate_links';
        $top_performers = $wpdb->get_results("SELECT product_name, clicks, conversions, revenue FROM $table_name ORDER BY revenue DESC LIMIT 10");
        
        echo '<div class="wrap">';
        echo '<h1>Analytics</h1>';
        echo '<h2>Top Performing Affiliate Links</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Product</th><th>Clicks</th><th>Conversions</th><th>Revenue</th><th>CTR</th></tr></thead>';
        echo '<tbody>';
        foreach ($top_performers as $performer) {
            $ctr = $performer->clicks > 0 ? round(($performer->conversions / $performer->clicks) * 100, 2) . '%' : '0%';
            echo '<tr>';
            echo '<td>' . esc_html($performer->product_name) . '</td>';
            echo '<td>' . $performer->clicks . '</td>';
            echo '<td>' . $performer->conversions . '</td>';
            echo '<td>' . wc_price($performer->revenue) . '</td>';
            echo '<td>' . $ctr . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>SmartAffiliate Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields($this->plugin_slug);
        echo '<table class="form-table">';
        echo '<tr><th><label>API Key:</label></th><td><input type="text" name="' . $this->plugin_slug . '_api_key" value="' . get_option($this->plugin_slug . '_api_key') . '" style="width: 100%; padding: 8px;"/></td></tr>';
        echo '<tr><th><label><input type="checkbox" name="' . $this->plugin_slug . '_enable_tracking" value="1" ' . checked(get_option($this->plugin_slug . '_enable_tracking'), 1, false) . '/> Enable Click Tracking</label></th></tr>';
        echo '<tr><th><label><input type="checkbox" name="' . $this->plugin_slug . '_auto_insert" value="1" ' . checked(get_option($this->plugin_slug . '_auto_insert'), 1, false) . '/> Auto-Insert Affiliate Links</label></th></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function render_affiliate_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliate_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE affiliate_id = %s", $atts['id']));
        
        if ($link) {
            return '<a href="' . esc_url($link->affiliate_url) . '" class="smart-affiliate-link" data-affiliate-id="' . esc_attr($link->affiliate_id) . '" target="_blank" rel="nofollow">' . esc_html($link->product_name) . '</a>';
        }
        return '';
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            $this->plugin_slug . '_widget',
            'SmartAffiliate Quick Stats',
            array($this, 'dashboard_widget_content')
        );
    }

    public function dashboard_widget_content() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliate_links';
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table_name");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $table_name");
        echo '<p>Total Revenue: <strong>' . wc_price($total_revenue ?: 0) . '</strong></p>';
        echo '<p>Total Clicks: <strong>' . ($total_clicks ?: 0) . '</strong></p>';
    }
}

new SmartAffiliatePlugin();
?>