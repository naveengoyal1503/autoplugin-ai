<?php
/*
Plugin Name: ContentBoost Premium
Plugin URI: https://contentboost.local
Description: AI-powered content optimization and monetization platform for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Premium.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOOST_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentBoostPremium {
    private static $instance = null;
    private $db_version = '1.0';
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'));
        add_action('rest_api_init', array($this, 'registerRestRoutes'));
        add_action('the_content', array($this, 'injectOptimizedContent'), 999);
        add_action('wp_footer', array($this, 'trackContentPerformance'));
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_stats = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentboost_stats (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            views int(11) DEFAULT 0,
            clicks int(11) DEFAULT 0,
            revenue decimal(10, 2) DEFAULT 0,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        $table_ads = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentboost_ads (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20),
            ad_type varchar(50),
            ad_network varchar(100),
            position varchar(50),
            enabled tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($table_stats);
        dbDelta($table_ads);
        
        add_option('contentboost_db_version', $this->db_version);
        add_option('contentboost_settings', array(
            'adsense_id' => '',
            'enable_affiliate' => false,
            'tracking_enabled' => true,
            'premium_enabled' => false,
            'license_key' => ''
        ));
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('contentboost_daily_report');
    }
    
    public function addAdminMenu() {
        add_menu_page(
            'ContentBoost Premium',
            'ContentBoost',
            'manage_options',
            'contentboost',
            array($this, 'renderDashboard'),
            'dashicons-trending-up',
            75
        );
        
        add_submenu_page(
            'contentboost',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentboost',
            array($this, 'renderDashboard')
        );
        
        add_submenu_page(
            'contentboost',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-settings',
            array($this, 'renderSettings')
        );
        
        add_submenu_page(
            'contentboost',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentboost-analytics',
            array($this, 'renderAnalytics')
        );
    }
    
    public function renderDashboard() {
        global $wpdb;
        $stats = $wpdb->get_results("SELECT SUM(revenue) as total_revenue, SUM(clicks) as total_clicks FROM {$wpdb->prefix}contentboost_stats WHERE date_created > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        ?>
        <div class="wrap">
            <h1>ContentBoost Premium Dashboard</h1>
            <div class="contentboost-dashboard">
                <div class="dashboard-card">
                    <h3>Total Revenue (30 days)</h3>
                    <p class="metric">$<?php echo number_format($stats[0]->total_revenue, 2); ?></p>
                </div>
                <div class="dashboard-card">
                    <h3>Total Clicks (30 days)</h3>
                    <p class="metric"><?php echo intval($stats[0]->total_clicks); ?></p>
                </div>
                <div class="dashboard-card">
                    <h3>Plugin Status</h3>
                    <p class="metric">Active</p>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function renderSettings() {
        $settings = get_option('contentboost_settings');
        ?>
        <div class="wrap">
            <h1>ContentBoost Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentboost_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="adsense_id">Google AdSense ID</label></th>
                        <td><input type="text" name="contentboost_adsense_id" value="<?php echo esc_attr($settings['adsense_id']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="enable_affiliate">Enable Affiliate Marketing</label></th>
                        <td><input type="checkbox" name="contentboost_enable_affiliate" <?php checked($settings['enable_affiliate']); ?> value="1"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tracking_enabled">Enable Analytics Tracking</label></th>
                        <td><input type="checkbox" name="contentboost_tracking_enabled" <?php checked($settings['tracking_enabled']); ?> value="1"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function renderAnalytics() {
        global $wpdb;
        $posts_data = $wpdb->get_results("SELECT post_id, SUM(views) as total_views, SUM(clicks) as total_clicks, SUM(revenue) as total_revenue FROM {$wpdb->prefix}contentboost_stats GROUP BY post_id ORDER BY total_revenue DESC LIMIT 20");
        ?>
        <div class="wrap">
            <h1>ContentBoost Analytics</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Views</th>
                        <th>Clicks</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts_data as $row): ?>
                    <tr>
                        <td><?php echo get_the_title($row->post_id); ?></td>
                        <td><?php echo intval($row->total_views); ?></td>
                        <td><?php echo intval($row->total_clicks); ?></td>
                        <td>$<?php echo number_format($row->total_revenue, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function injectOptimizedContent($content) {
        if (is_singular('post') && !is_admin()) {
            $settings = get_option('contentboost_settings');
            if ($settings['adsense_id']) {
                $ad_code = '<div class="contentboost-ad-block" style="text-align: center; margin: 20px 0;">';
                $ad_code .= '<!-- AdSense Placeholder for ' . esc_attr($settings['adsense_id']) . ' -->';
                $ad_code .= '</div>';
                $content = substr_replace($content, $ad_code, strlen($content)/2, 0);
            }
        }
        return $content;
    }
    
    public function trackContentPerformance() {
        if (is_singular('post')) {
            ?>
            <script>
            (function() {
                const postId = <?php echo get_the_ID(); ?>;
                const data = {
                    post_id: postId,
                    action: 'contentboost_track'
                };
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams(data)
                });
            })();
            </script>
            <?php
        }
    }
    
    public function registerRestRoutes() {
        register_rest_route('contentboost/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'getStatsRest'),
            'permission_callback' => array($this, 'checkAdminPermission')
        ));
    }
    
    public function getStatsRest($request) {
        global $wpdb;
        $stats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}contentboost_stats ORDER BY date_created DESC LIMIT 100");
        return rest_ensure_response($stats);
    }
    
    public function checkAdminPermission() {
        return current_user_can('manage_options');
    }
    
    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'contentboost') !== false) {
            wp_enqueue_style('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'admin-style.css', array(), CONTENTBOOST_VERSION);
        }
    }
    
    public function enqueueFrontendScripts() {
        wp_enqueue_script('contentboost-tracker', CONTENTBOOST_PLUGIN_URL . 'tracker.js', array(), CONTENTBOOST_VERSION, true);
    }
}

ContentBoostPremium::getInstance();

add_action('wp_ajax_contentboost_track', function() {
    global $wpdb;
    if (isset($_POST['post_id'])) {
        $wpdb->insert(
            $wpdb->prefix . 'contentboost_stats',
            array('post_id' => intval($_POST['post_id']), 'views' => 1),
            array('%d', '%d')
        );
    }
    wp_die();
});
?>