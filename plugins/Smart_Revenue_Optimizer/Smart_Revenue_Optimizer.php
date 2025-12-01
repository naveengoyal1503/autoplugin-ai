<?php
/*
Plugin Name: Smart Revenue Optimizer
Plugin URI: https://example.com/smart-revenue-optimizer
Description: Automatically optimize ad placement, affiliate links, and sponsored content for maximum monetization revenue
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Revenue_Optimizer.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: smart-revenue-optimizer
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRO_VERSION', '1.0.0');

class SmartRevenueOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('sro_monetization_widget', array($this, 'render_monetization_widget'));
        add_action('wp_footer', array($this, 'track_user_engagement'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'sro_engagement';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            post_id bigint(20),
            engagement_score float,
            click_data longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('sro_settings', array(
            'enable_ad_optimization' => true,
            'enable_affiliate_optimization' => true,
            'enable_tracking' => true,
            'premium_version' => false
        ));
    }

    public function deactivate_plugin() {
        // Cleanup if needed
    }

    public function load_textdomain() {
        load_plugin_textdomain('smart-revenue-optimizer', false, basename(dirname(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Revenue Optimizer', 'smart-revenue-optimizer'),
            __('Revenue Optimizer', 'smart-revenue-optimizer'),
            'manage_options',
            'smart-revenue-optimizer',
            array($this, 'render_admin_dashboard'),
            'dashicons-chart-line',
            25
        );
        
        add_submenu_page(
            'smart-revenue-optimizer',
            __('Settings', 'smart-revenue-optimizer'),
            __('Settings', 'smart-revenue-optimizer'),
            'manage_options',
            'sro-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'smart-revenue-optimizer',
            __('Analytics', 'smart-revenue-optimizer'),
            __('Analytics', 'smart-revenue-optimizer'),
            'manage_options',
            'sro-analytics',
            array($this, 'render_analytics_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'smart-revenue-optimizer') !== false) {
            wp_enqueue_style('sro-admin-style', SRO_PLUGIN_URL . 'admin/css/admin.css', array(), SRO_VERSION);
            wp_enqueue_script('sro-admin-script', SRO_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), SRO_VERSION, true);
            wp_localize_script('sro-admin-script', 'SRO', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sro_nonce')
            ));
        }
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script('sro-tracking', SRO_PLUGIN_URL . 'frontend/js/tracking.js', array(), SRO_VERSION, true);
        wp_localize_script('sro-tracking', 'SROTracking', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sro_tracking_nonce')
        ));
    }

    public function render_admin_dashboard() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Smart Revenue Optimizer Dashboard', 'smart-revenue-optimizer')); ?></h1>
            <div class="sro-dashboard-container">
                <div class="sro-card">
                    <h2><?php echo esc_html(__('Total Revenue', 'smart-revenue-optimizer')); ?></h2>
                    <p class="sro-stat">$<?php echo esc_html(number_format($this->get_total_revenue(), 2)); ?></p>
                </div>
                <div class="sro-card">
                    <h2><?php echo esc_html(__('Engagement Score', 'smart-revenue-optimizer')); ?></h2>
                    <p class="sro-stat"><?php echo esc_html($this->get_average_engagement_score()); ?>%</p>
                </div>
                <div class="sro-card">
                    <h2><?php echo esc_html(__('Optimized Content Pieces', 'smart-revenue-optimizer')); ?></h2>
                    <p class="sro-stat"><?php echo esc_html($this->get_optimized_content_count()); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        $settings = get_option('sro_settings');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Revenue Optimizer Settings', 'smart-revenue-optimizer')); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('sro_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_ad_optimization"><?php echo esc_html(__('Enable Ad Optimization', 'smart-revenue-optimizer')); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_ad_optimization" name="sro_enable_ad_optimization" value="1" <?php checked($settings['enable_ad_optimization'], true); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="enable_affiliate_optimization"><?php echo esc_html(__('Enable Affiliate Optimization', 'smart-revenue-optimizer')); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_affiliate_optimization" name="sro_enable_affiliate_optimization" value="1" <?php checked($settings['enable_affiliate_optimization'], true); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="enable_tracking"><?php echo esc_html(__('Enable User Tracking', 'smart-revenue-optimizer')); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_tracking" name="sro_enable_tracking" value="1" <?php checked($settings['enable_tracking'], true); ?> />
                            <p class="description"><?php echo esc_html(__('Track user engagement to optimize monetization', 'smart-revenue-optimizer')); ?></p>
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
            <h1><?php echo esc_html(__('Revenue Analytics', 'smart-revenue-optimizer')); ?></h1>
            <div id="sro-analytics-chart"></div>
        </div>
        <?php
    }

    public function render_monetization_widget($atts) {
        $atts = shortcode_atts(array(
            'type' => 'affiliate',
            'limit' => 5
        ), $atts);
        
        ob_start();
        ?>
        <div class="sro-monetization-widget sro-<?php echo esc_attr($atts['type']); ?>">
            <h3><?php echo esc_html(__('Recommended Products', 'smart-revenue-optimizer')); ?></h3>
            <div class="sro-items">
                <!-- Items populated by JavaScript -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_user_engagement() {
        if (!get_option('sro_settings')['enable_tracking']) {
            return;
        }
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var engagementData = {
                clicks: 0,
                scrollDepth: 0,
                timeOnPage: 0
            };
            
            document.addEventListener('click', function() {
                engagementData.clicks++;
            });
            
            window.addEventListener('scroll', function() {
                var scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
                if (scrollPercent > engagementData.scrollDepth) {
                    engagementData.scrollDepth = scrollPercent;
                }
            });
            
            setInterval(function() {
                engagementData.timeOnPage++;
            }, 1000);
            
            window.addEventListener('beforeunload', function() {
                navigator.sendBeacon(
                    SROTracking.ajaxurl,
                    'action=sro_track_engagement&nonce=' + SROTracking.nonce +
                    '&post_id=<?php echo esc_js(get_the_ID()); ?>&data=' + JSON.stringify(engagementData)
                );
            });
        });
        </script>
        <?php
    }

    public function register_rest_routes() {
        register_rest_route('sro/v1', '/analytics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics_data'),
            'permission_callback' => array($this, 'check_admin_permission')
        ));
    }

    public function get_analytics_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'sro_engagement';
        
        $data = $wpdb->get_results(
            "SELECT DATE(timestamp) as date, AVG(engagement_score) as avg_score, COUNT(*) as total_interactions
            FROM $table
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(timestamp)
            ORDER BY date ASC"
        );
        
        return rest_ensure_response($data);
    }

    public function check_admin_permission() {
        return current_user_can('manage_options');
    }

    public function get_total_revenue() {
        return apply_filters('sro_total_revenue', 0);
    }

    public function get_average_engagement_score() {
        global $wpdb;
        $table = $wpdb->prefix . 'sro_engagement';
        $score = $wpdb->get_var("SELECT AVG(engagement_score) FROM $table WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        return $score ? round($score, 1) : 0;
    }

    public function get_optimized_content_count() {
        return get_transient('sro_optimized_count') ?: 0;
    }
}

add_action('plugins_loaded', function() {
    SmartRevenueOptimizer::get_instance();
});
?>