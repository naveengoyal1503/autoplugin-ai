/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Content_Performance_Analytics_Pro.php
*/
<?php
/**
 * Plugin Name: Content Performance Analytics Pro
 * Plugin URI: https://contentperformanceanalytics.com
 * Description: Track content engagement, identify top performers, and get AI recommendations for optimization
 * Version: 1.0.0
 * Author: Analytics Team
 * Author URI: https://contentperformanceanalytics.com
 * License: GPL v2 or later
 * Text Domain: content-analytics-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CPAP_VERSION', '1.0.0');
define('CPAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CPAP_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentPerformanceAnalyticsPro {
    private static $instance = null;
    private $license_status = 'free';

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->registerHooks();
        $this->createDatabase();
    }

    private function registerHooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
        add_action('wp_head', array($this, 'trackPageView'));
        add_action('wp_footer', array($this, 'trackUserEngagement'));
    }

    public function activate() {
        $this->createDatabase();
        add_option('cpap_license_status', 'free');
        add_option('cpap_activation_date', current_time('mysql'));
    }

    public function deactivate() {
        // Cleanup if needed
    }

    private function createDatabase() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'cpap_analytics';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            views bigint(20) DEFAULT 0,
            unique_visitors bigint(20) DEFAULT 0,
            average_time_on_page float DEFAULT 0,
            bounce_rate float DEFAULT 0,
            click_through_rate float DEFAULT 0,
            date_recorded date NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY date_recorded (date_recorded)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function addAdminMenu() {
        add_menu_page(
            'Content Analytics Pro',
            'Content Analytics',
            'manage_options',
            'cpap-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-chart-line',
            75
        );

        add_submenu_page(
            'cpap-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'cpap-dashboard',
            array($this, 'renderDashboard')
        );

        add_submenu_page(
            'cpap-dashboard',
            'Reports',
            'Reports',
            'manage_options',
            'cpap-reports',
            array($this, 'renderReports')
        );

        add_submenu_page(
            'cpap-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'cpap-settings',
            array($this, 'renderSettings')
        );
    }

    public function enqueueAdminAssets() {
        wp_enqueue_style('cpap-admin', CPAP_PLUGIN_URL . 'assets/admin.css', array(), CPAP_VERSION);
        wp_enqueue_script('cpap-admin', CPAP_PLUGIN_URL . 'assets/admin.js', array('jquery'), CPAP_VERSION, true);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1');
    }

    public function trackPageView() {
        if (!is_admin() && is_single()) {
            global $post, $wpdb;
            $table_name = $wpdb->prefix . 'cpap_analytics';
            $today = current_time('Y-m-d');

            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE post_id = %d AND date_recorded = %s",
                $post->ID,
                $today
            ));

            if ($existing) {
                $wpdb->update(
                    $table_name,
                    array('views' => $existing->views + 1),
                    array('id' => $existing->id),
                    array('%d'),
                    array('%d')
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    array(
                        'post_id' => $post->ID,
                        'views' => 1,
                        'date_recorded' => $today
                    ),
                    array('%d', '%d', '%s')
                );
            }
        }
    }

    public function trackUserEngagement() {
        if (!is_admin()) {
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var engagementTime = 0;
                setInterval(function() {
                    engagementTime += 5;
                    localStorage.setItem('cpap_engagement_time', engagementTime);
                }, 5000);
            });
            </script>
            <?php
        }
    }

    public function renderDashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cpap_analytics';
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));

        $top_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, SUM(views) as total_views 
             FROM $table_name 
             WHERE date_recorded >= %s 
             GROUP BY post_id 
             ORDER BY total_views DESC 
             LIMIT 10",
            $thirty_days_ago
        ));

        echo '<div class="wrap">';
        echo '<h1>Content Performance Analytics Pro</h1>';
        echo '<div class="cpap-dashboard">';
        echo '<h2>Top Performing Posts (Last 30 Days)</h2>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Post Title</th><th>Views</th></tr></thead><tbody>';

        foreach ($top_posts as $post) {
            $post_title = get_the_title($post->post_id);
            echo '<tr>';
            echo '<td>' . esc_html($post_title) . '</td>';
            echo '<td>' . intval($post->total_views) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';

        $this->displayUpgradePrompt();
        echo '</div>';
    }

    public function renderReports() {
        echo '<div class="wrap">';
        echo '<h1>Reports</h1>';
        echo '<p>Advanced reporting features available in Premium version.</p>';
        $this->displayUpgradePrompt();
        echo '</div>';
    }

    public function renderSettings() {
        echo '<div class="wrap">';
        echo '<h1>Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('cpap_settings');
        do_settings_sections('cpap_settings');
        submit_button();
        echo '</form>';
        $this->displayUpgradePrompt();
        echo '</div>';
    }

    private function displayUpgradePrompt() {
        echo '<div class="cpap-upgrade-prompt" style="margin-top: 30px; padding: 20px; background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 5px;">';
        echo '<h3>Upgrade to Premium</h3>';
        echo '<p>Get advanced analytics, AI recommendations, custom reports, and API access.</p>';
        echo '<a href="#" class="button button-primary">Upgrade Now</a>';
        echo '</div>';
    }
}

// Initialize plugin
add_action('plugins_loaded', function() {
    ContentPerformanceAnalyticsPro::getInstance();
});
?>