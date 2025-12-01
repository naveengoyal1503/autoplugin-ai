<?php
/*
Plugin Name: Content Performance Optimizer
Plugin URI: https://contentperformanceoptimizer.com
Description: Analyzes content performance and provides monetization recommendations
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Content_Performance_Optimizer.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

define('CPO_VERSION', '1.0.0');
define('CPO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CPO_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentPerformanceOptimizer {
    private static $instance = null;
    private $db_version = '1.0';
    private $table_name;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cpo_analytics';
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_footer', array($this, 'track_page_view'));
        add_action('save_post', array($this, 'track_post_save'), 10, 2);
        add_action('wp_ajax_cpo_get_analytics', array($this, 'get_analytics_data'));
        add_action('wp_ajax_cpo_get_recommendations', array($this, 'get_recommendations'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            views bigint(20) DEFAULT 0,
            clicks bigint(20) DEFAULT 0,
            avg_time_on_page int(11) DEFAULT 0,
            bounce_rate float DEFAULT 0,
            conversion_clicks int(11) DEFAULT 0,
            revenue float DEFAULT 0,
            monetization_method varchar(50),
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY created_date (created_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('cpo_db_version', $this->db_version);
        add_option('cpo_subscription_tier', 'free');
    }

    public function deactivate() {
        wp_clear_scheduled_hook('cpo_daily_analysis');
    }

    public function add_admin_menu() {
        add_menu_page(
            'Content Performance',
            'CPO Analytics',
            'manage_options',
            'cpo-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'cpo-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'cpo-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'cpo-') === false) return;
        
        wp_enqueue_script('jquery');
        wp_enqueue_chart_js();
        wp_enqueue_script('cpo-admin', CPO_PLUGIN_URL . 'admin.js', array('jquery'), CPO_VERSION, true);
        wp_enqueue_style('cpo-admin', CPO_PLUGIN_URL . 'admin.css', array(), CPO_VERSION);
        
        wp_localize_script('cpo-admin', 'cpo_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cpo_nonce'),
            'subscription_tier' => get_option('cpo_subscription_tier')
        ));
    }

    public function track_page_view() {
        if (is_singular('post') && !is_user_logged_in()) {
            global $post, $wpdb;
            $post_id = get_the_ID();
            
            $wpdb->query($wpdb->prepare(
                "INSERT INTO {$this->table_name} (post_id, views) VALUES (%d, 1)
                 ON DUPLICATE KEY UPDATE views = views + 1",
                $post_id
            ));
        }
    }

    public function track_post_save($post_id, $post) {
        if ($post->post_type !== 'post' || wp_is_post_revision($post_id)) return;
        
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$this->table_name} (post_id) VALUES (%d) ON DUPLICATE KEY UPDATE updated_date = NOW()",
            $post_id
        ));
    }

    public function get_analytics_data() {
        check_ajax_referer('cpo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        $limit = get_option('cpo_subscription_tier') === 'premium' ? 500 : 10;
        
        $results = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY views DESC LIMIT {$limit}"
        );
        
        wp_send_json_success($results);
    }

    public function get_recommendations() {
        check_ajax_referer('cpo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        $high_traffic_posts = $wpdb->get_results(
            "SELECT post_id, views FROM {$this->table_name} WHERE views > 100 ORDER BY views DESC LIMIT 5"
        );
        
        $recommendations = array();
        
        foreach ($high_traffic_posts as $post) {
            $post_obj = get_post($post->post_id);
            if (!$post_obj) continue;
            
            $recommendations[] = array(
                'post_id' => $post->post_id,
                'title' => $post_obj->post_title,
                'views' => $post->views,
                'suggestions' => array(
                    'Add internal links to monetized pages',
                    'Consider converting to memberonly content',
                    'Place affiliate links strategically',
                    'Repurpose into video content',
                    'Create a follow-up premium resource'
                )
            );
        }
        
        wp_send_json_success($recommendations);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>Content Performance Dashboard</h1>
            <div id="cpo-dashboard-container">
                <div class="cpo-card">
                    <h2>Top Performing Posts</h2>
                    <div id="cpo-analytics"></div>
                </div>
                <div class="cpo-card">
                    <h2>Monetization Recommendations</h2>
                    <div id="cpo-recommendations"></div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>CPO Settings</h1>
            <form method="post">
                <?php wp_nonce_field('cpo_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="cpo_tier">Subscription Tier</label></th>
                        <td>
                            <select name="cpo_subscription_tier" id="cpo_tier">
                                <option value="free" <?php selected(get_option('cpo_subscription_tier'), 'free'); ?>>Free</option>
                                <option value="premium" <?php selected(get_option('cpo_subscription_tier'), 'premium'); ?>>Premium ($9.99/month)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize plugin
ContentPerformanceOptimizer::get_instance();

// Placeholder functions for CSS and JS
function wp_enqueue_chart_js() {
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
}

?>
