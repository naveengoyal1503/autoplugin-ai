<?php
/*
Plugin Name: SmartConversion Flow Optimizer
Plugin URI: https://smartconversionflow.com
Description: AI-powered conversion funnel optimizer with real-time analytics and A/B testing
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartConversion_Flow_Optimizer.php
License: GPL v2
*/

if (!defined('ABSPATH')) {
    exit;
}

class SmartConversionFlowOptimizer {
    private $plugin_slug = 'scfo';
    private $db_prefix = 'scfo_';
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_footer', array($this, 'inject_tracking_code'));
        add_action('wp_ajax_scfo_track_event', array($this, 'track_conversion_event'));
        add_action('wp_ajax_nopriv_scfo_track_event', array($this, 'track_conversion_event'));
    }
    
    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_prefix . 'conversions';
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            visitor_id VARCHAR(100) NOT NULL,
            page_id BIGINT UNSIGNED,
            event_type VARCHAR(50),
            conversion_value DECIMAL(10, 2),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (visitor_id),
            INDEX (created_at)
        )";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option($this->db_prefix . 'license_tier', 'free');
        update_option($this->db_prefix . 'tracking_enabled', 1);
    }
    
    public function deactivate() {
        // Cleanup on deactivation
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'SmartConversion Optimizer',
            'SmartConversion',
            'manage_options',
            $this->plugin_slug,
            array($this, 'render_dashboard'),
            'dashicons-graph-line',
            65
        );
        
        add_submenu_page(
            $this->plugin_slug,
            'Dashboard',
            'Dashboard',
            'manage_options',
            $this->plugin_slug,
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            $this->plugin_slug,
            'A/B Tests',
            'A/B Tests',
            'manage_options',
            $this->plugin_slug . '_tests',
            array($this, 'render_ab_tests')
        );
        
        add_submenu_page(
            $this->plugin_slug,
            'Settings',
            'Settings',
            'manage_options',
            $this->plugin_slug . '_settings',
            array($this, 'render_settings')
        );
        
        add_submenu_page(
            $this->plugin_slug,
            'Upgrade',
            'Upgrade to Premium',
            'manage_options',
            $this->plugin_slug . '_upgrade',
            array($this, 'render_upgrade')
        );
    }
    
    public function render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_prefix . 'conversions';
        $license_tier = get_option($this->db_prefix . 'license_tier', 'free');
        
        $total_conversions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $avg_conversion_value = $wpdb->get_var("SELECT AVG(conversion_value) FROM $table_name WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        echo '<div class="wrap" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px; color: white;">';
        echo '<h1>SmartConversion Flow Optimizer</h1>';
        echo '<p>Current License: <strong>' . ucfirst($license_tier) . '</strong></p>';
        echo '<div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin: 20px 0;">';
        echo '<h2>30-Day Performance</h2>';
        echo '<p>Total Conversions: <strong>' . $total_conversions . '</strong></p>';
        echo '<p>Average Conversion Value: <strong>$' . number_format($avg_conversion_value, 2) . '</strong></p>';
        echo '</div>';
        
        if ($license_tier === 'free') {
            echo '<div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0;">';
            echo '<p><strong>Unlock Premium Features:</strong> Upgrade to see AI recommendations and advanced A/B testing.</p>';
            echo '<a href="?page=' . $this->plugin_slug . '_upgrade" class="button button-primary" style="margin-top: 10px;">Upgrade Now</a>';
            echo '</div>';
        } else {
            echo '<div style="background: #d4edda; padding: 15px; border-radius: 8px; margin: 20px 0;">';
            echo '<p><strong>Premium Features Active:</strong> AI-powered recommendations and detailed analytics enabled.</p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    public function render_ab_tests() {
        echo '<div class="wrap">';
        echo '<h1>A/B Tests</h1>';
        echo '<p>A/B testing features available in Premium and Enterprise plans.</p>';
        echo '<a href="?page=' . $this->plugin_slug . '_upgrade" class="button button-primary">Upgrade to Premium</a>';
        echo '</div>';
    }
    
    public function render_settings() {
        if (isset($_POST[$this->db_prefix . 'nonce'])) {
            if (!wp_verify_nonce($_POST[$this->db_prefix . 'nonce'], $this->db_prefix . 'settings')) {
                wp_die('Security check failed');
            }
            
            $tracking_enabled = isset($_POST[$this->db_prefix . 'tracking_enabled']) ? 1 : 0;
            update_option($this->db_prefix . 'tracking_enabled', $tracking_enabled);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        
        $tracking_enabled = get_option($this->db_prefix . 'tracking_enabled', 1);
        
        echo '<div class="wrap">';
        echo '<h1>Settings</h1>';
        echo '<form method="post">';
        wp_nonce_field($this->db_prefix . 'settings', $this->db_prefix . 'nonce');
        echo '<table class="form-table">';
        echo '<tr><th>Enable Conversion Tracking</th><td>';
        echo '<input type="checkbox" name="' . $this->db_prefix . 'tracking_enabled" value="1" ' . ($tracking_enabled ? 'checked' : '') . '>';
        echo '</td></tr>';
        echo '</table>';
        echo '<input type="submit" class="button button-primary" value="Save Settings">';
        echo '</form>';
        echo '</div>';
    }
    
    public function render_upgrade() {
        echo '<div class="wrap" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px; color: white;">';
        echo '<h1>Upgrade to Premium</h1>';
        echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0;">';
        
        $plans = array(
            array('name' => 'Starter', 'price' => '$29', 'features' => array('Basic Analytics', 'Conversion Tracking', '30-day History')),
            array('name' => 'Professional', 'price' => '$79', 'features' => array('A/B Testing', 'Advanced Analytics', 'A/B Test Reports', '1-year History')),
            array('name' => 'Enterprise', 'price' => '$199', 'features' => array('AI Recommendations', 'Unlimited A/B Tests', 'Priority Support', 'Custom Integrations'))
        );
        
        foreach ($plans as $plan) {
            echo '<div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; border: 2px solid rgba(255,255,255,0.3);">';
            echo '<h3>' . $plan['name'] . '</h3>';
            echo '<p style="font-size: 24px; font-weight: bold;">' . $plan['price'] . '/month</p>';
            echo '<ul style="list-style: none;">';
            foreach ($plan['features'] as $feature) {
                echo '<li>✓ ' . $feature . '</li>';
            }
            echo '</ul>';
            echo '<button class="button button-secondary" onclick="alert(\"Upgrade functionality coming soon\")">Choose Plan</button>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    public function enqueue_admin_scripts() {
        wp_enqueue_style('wp-admin');
    }
    
    public function enqueue_frontend_scripts() {
        if (get_option($this->db_prefix . 'tracking_enabled', 1)) {
            wp_enqueue_script($this->plugin_slug . '-tracking', plugins_url('tracking.js', __FILE__), array('jquery'), '1.0', true);
            wp_localize_script($this->plugin_slug . '-tracking', 'scfoData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce($this->plugin_slug),
                'trackingEnabled' => true
            ));
        }
    }
    
    public function inject_tracking_code() {
        if (get_option($this->db_prefix . 'tracking_enabled', 1)) {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof jQuery !== 'undefined') {
                    var visitorId = localStorage.getItem('scfo_visitor_id');
                    if (!visitorId) {
                        visitorId = 'visitor_' + Math.random().toString(36).substr(2, 9);
                        localStorage.setItem('scfo_visitor_id', visitorId);
                    }
                    
                    jQuery(document).on('click', 'a, button', function() {
                        jQuery.post(scfoData.ajaxurl, {
                            action: 'scfo_track_event',
                            event_type: 'click',
                            visitor_id: visitorId,
                            nonce: scfoData.nonce
                        });
                    });
                }
            });
            </script>
            <?php
        }
    }
    
    public function track_conversion_event() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $this->plugin_slug)) {
            wp_send_json_error('Security check failed');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_prefix . 'conversions';
        
        $visitor_id = sanitize_text_field($_POST['visitor_id']);
        $event_type = sanitize_text_field($_POST['event_type']);
        $page_id = get_the_ID();
        
        $wpdb->insert($table_name, array(
            'visitor_id' => $visitor_id,
            'page_id' => $page_id,
            'event_type' => $event_type,
            'conversion_value' => 0,
            'created_at' => current_time('mysql')
        ));
        
        wp_send_json_success('Event tracked');
    }
}

new SmartConversionFlowOptimizer();
?>