/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartConversion_Flow_Optimizer.php
*/
<?php
/**
 * Plugin Name: SmartConversion Flow Optimizer
 * Plugin URI: https://smartconversionoptimizer.com
 * Description: AI-powered conversion rate optimization plugin with heat mapping and flow analysis
 * Version: 1.0.0
 * Author: Conversion Experts
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SCO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCO_VERSION', '1.0.0');

class SmartConversionOptimizer {
    private $db;
    private $license_key;
    private $is_premium = false;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->license_key = get_option('sco_license_key', '');
        $this->is_premium = $this->validate_license();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('plugins_loaded', array($this, 'create_tables'));
        add_shortcode('sco_heatmap', array($this, 'render_heatmap'));
        add_action('wp_ajax_sco_track_event', array($this, 'track_user_event'));
        add_action('wp_ajax_nopriv_sco_track_event', array($this, 'track_user_event'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function create_tables() {
        $table_name = $this->db->prefix . 'sco_events';
        $charset_collate = $this->db->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            page_id int NOT NULL,
            event_type varchar(50) NOT NULL,
            x_position int,
            y_position int,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            user_id varchar(100),
            conversion_value decimal(10, 2),
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'SmartConversion Optimizer',
            'SmartConversion',
            'manage_options',
            'sco-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            90
        );

        add_submenu_page(
            'sco-dashboard',
            'Heat Maps',
            'Heat Maps',
            'manage_options',
            'sco-heatmaps',
            array($this, 'render_heatmaps')
        );

        add_submenu_page(
            'sco-dashboard',
            'Conversion Reports',
            'Reports',
            'manage_options',
            'sco-reports',
            array($this, 'render_reports')
        );

        add_submenu_page(
            'sco-dashboard',
            'License & Upgrade',
            'License',
            'manage_options',
            'sco-license',
            array($this, 'render_license')
        );
    }

    public function register_settings() {
        register_setting('sco_settings', 'sco_license_key');
        register_setting('sco_settings', 'sco_tracking_enabled');
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'sco-') === false) {
            return;
        }
        wp_enqueue_script('sco-admin', SCO_PLUGIN_URL . 'js/admin.js', array('jquery'), SCO_VERSION);
        wp_enqueue_style('sco-admin', SCO_PLUGIN_URL . 'css/admin.css', array(), SCO_VERSION);
    }

    public function enqueue_frontend_scripts() {
        if (get_option('sco_tracking_enabled')) {
            wp_enqueue_script('sco-tracker', SCO_PLUGIN_URL . 'js/tracker.js', array('jquery'), SCO_VERSION);
            wp_localize_script('sco-tracker', 'scoData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sco_nonce')
            ));
        }
    }

    public function track_user_event() {
        check_ajax_referer('sco_nonce');
        
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';
        $x_pos = isset($_POST['x']) ? intval($_POST['x']) : 0;
        $y_pos = isset($_POST['y']) ? intval($_POST['y']) : 0;
        $conversion = isset($_POST['conversion']) ? floatval($_POST['conversion']) : 0;

        $this->db->insert(
            $this->db->prefix . 'sco_events',
            array(
                'page_id' => $page_id,
                'event_type' => $event_type,
                'x_position' => $x_pos,
                'y_position' => $y_pos,
                'user_id' => wp_json_encode($_SERVER['HTTP_USER_AGENT']),
                'conversion_value' => $conversion
            )
        );

        wp_send_json_success('Event tracked');
    }

    public function validate_license() {
        if (empty($this->license_key)) {
            return false;
        }
        return strlen($this->license_key) > 10;
    }

    public function render_dashboard() {
        echo '<div class="wrap"><h1>SmartConversion Dashboard</h1>';
        
        $table_name = $this->db->prefix . 'sco_events';
        $total_events = $this->db->get_var("SELECT COUNT(*) FROM $table_name");
        $avg_conversion = $this->db->get_var("SELECT AVG(conversion_value) FROM $table_name");

        echo '<div class="sco-stats">';
        echo '<div class="stat-card"><h3>Total Events Tracked</h3><p>' . intval($total_events) . '</p></div>';
        echo '<div class="stat-card"><h3>Avg Conversion Value</h3><p>$' . number_format($avg_conversion, 2) . '</p></div>';
        echo '<div class="stat-card"><h3>Status</h3><p>' . ($this->is_premium ? 'Premium' : 'Free') . '</p></div>';
        echo '</div>';
        
        if (!$this->is_premium) {
            echo '<div class="notice notice-warning"><p><strong>Upgrade to Premium</strong> to unlock advanced analytics and AI-powered recommendations. <a href="?page=sco-license">View Plans</a></p></div>';
        }
        
        echo '</div>';
    }

    public function render_heatmaps() {
        echo '<div class="wrap"><h1>Heat Maps</h1>';
        echo '<p>' . ($this->is_premium ? 'Advanced heat map features available' : 'Heat map tracking available in Premium plan') . '</p>';
        echo '</div>';
    }

    public function render_reports() {
        echo '<div class="wrap"><h1>Conversion Reports</h1>';
        echo '<p>' . ($this->is_premium ? 'Detailed reports available' : 'Reports available in Premium plan') . '</p>';
        echo '</div>';
    }

    public function render_license() {
        echo '<div class="wrap"><h1>License & Upgrade</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('sco_settings');
        echo '<table class="form-table">';
        echo '<tr><th><label for="sco_license_key">License Key:</label></th>';
        echo '<td><input type="text" id="sco_license_key" name="sco_license_key" value="' . esc_attr($this->license_key) . '" /></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '<div class="sco-pricing"><h2>Upgrade Plans</h2>';
        echo '<p><strong>Free:</strong> Basic event tracking | <strong>Premium:</strong> $29/month - Advanced analytics + AI recommendations | <strong>Pro:</strong> $49/month - Heat maps + Funnel analysis</p>';
        echo '</div></div>';
    }

    public function render_heatmap() {
        return '<div id="sco-heatmap" class="sco-heatmap"></div>';
    }
}

// Initialize plugin
if (is_admin()) {
    new SmartConversionOptimizer();
}

register_activation_hook(__FILE__, function() {
    update_option('sco_tracking_enabled', 1);
});

register_deactivation_hook(__FILE__, function() {
    update_option('sco_tracking_enabled', 0);
});
?>