<?php
/*
Plugin Name: ContentBoost Pro
Plugin URI: https://example.com/contentboost
Description: AI-powered content monetization and analytics optimizer
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Pro.php
License: GPL v2 or later
Text Domain: contentboost
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOOST_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentBoostPro {
    private static $instance = null;
    private $settings = array();
    private $db_version = '1.0';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'action_links'));
        add_shortcode('contentboost_stats', array($this, 'stats_shortcode'));
        add_shortcode('contentboost_affiliate', array($this, 'affiliate_shortcode'));
    }

    public function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentboost_tracking (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            visitor_count bigint(20) DEFAULT 0,
            engagement_score decimal(5,2) DEFAULT 0,
            affiliate_clicks bigint(20) DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('contentboost_db_version', $this->db_version);
        update_option('contentboost_settings', array(
            'premium' => false,
            'api_key' => '',
            'tracking_enabled' => true,
            'display_stats' => true,
            'affiliate_program' => 'amazon'
        ));
    }

    public function deactivate() {
        // Clean up scheduled events if any
        wp_clear_scheduled_hook('contentboost_daily_report');
    }

    public function init() {
        load_plugin_textdomain('contentboost', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Track page views
        if (!is_admin()) {
            add_action('wp_head', array($this, 'track_pageview'));
        }
    }

    public function track_pageview() {
        global $wpdb, $post;
        
        if (!is_single() || !isset($post)) {
            return;
        }
        
        $settings = get_option('contentboost_settings');
        
        if (!$settings['tracking_enabled']) {
            return;
        }
        
        $table = $wpdb->prefix . 'contentboost_tracking';
        $current_visitors = $wpdb->get_var($wpdb->prepare(
            "SELECT visitor_count FROM $table WHERE post_id = %d",
            $post->ID
        ));
        
        if ($current_visitors !== null) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $table SET visitor_count = visitor_count + 1 WHERE post_id = %d",
                $post->ID
            ));
        } else {
            $wpdb->insert($table, array(
                'post_id' => $post->ID,
                'visitor_count' => 1,
                'engagement_score' => 0
            ));
        }
        
        // Output tracking pixel
        echo '<img src="' . esc_url(admin_url('admin-ajax.php?action=contentboost_track')) . '" style="display:none;" alt="" />';
    }

    public function admin_menu() {
        add_menu_page(
            __('ContentBoost Pro', 'contentboost'),
            __('ContentBoost', 'contentboost'),
            'manage_options',
            'contentboost',
            array($this, 'admin_dashboard'),
            'dashicons-chart-line',
            25
        );
        
        add_submenu_page(
            'contentboost',
            __('Dashboard', 'contentboost'),
            __('Dashboard', 'contentboost'),
            'manage_options',
            'contentboost',
            array($this, 'admin_dashboard')
        );
        
        add_submenu_page(
            'contentboost',
            __('Affiliate Links', 'contentboost'),
            __('Affiliate Links', 'contentboost'),
            'manage_options',
            'contentboost_affiliates',
            array($this, 'admin_affiliates')
        );
        
        add_submenu_page(
            'contentboost',
            __('Settings', 'contentboost'),
            __('Settings', 'contentboost'),
            'manage_options',
            'contentboost_settings',
            array($this, 'admin_settings')
        );
    }

    public function admin_dashboard() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'contentboost_tracking';
        $stats = $wpdb->get_results("SELECT * FROM $table ORDER BY visitor_count DESC LIMIT 10");
        $total_visitors = $wpdb->get_var("SELECT SUM(visitor_count) FROM $table");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table");
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('ContentBoost Pro Dashboard', 'contentboost')); ?></h1>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="background: #f5f5f5; padding: 20px; border-radius: 5px;">
                    <h3><?php echo esc_html(__('Total Visitors', 'contentboost')); ?></h3>
                    <p style="font-size: 28px; font-weight: bold; margin: 10px 0;"><?php echo number_format($total_visitors); ?></p>
                </div>
                <div style="background: #f5f5f5; padding: 20px; border-radius: 5px;">
                    <h3><?php echo esc_html(__('Total Revenue', 'contentboost')); ?></h3>
                    <p style="font-size: 28px; font-weight: bold; margin: 10px 0;"><?php echo '$' . number_format($total_revenue, 2); ?></p>
                </div>
                <div style="background: #fff3cd; padding: 20px; border-radius: 5px;">
                    <h3><?php echo esc_html(__('Plan', 'contentboost')); ?></h3>
                    <p style="font-size: 18px; font-weight: bold; margin: 10px 0;"><?php echo esc_html(get_option('contentboost_settings')['premium'] ? 'Premium' : 'Free'); ?></p>
                </div>
            </div>
            
            <h2><?php echo esc_html(__('Top Performing Posts', 'contentboost')); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html(__('Post Title', 'contentboost')); ?></th>
                        <th><?php echo esc_html(__('Visitors', 'contentboost')); ?></th>
                        <th><?php echo esc_html(__('Engagement', 'contentboost')); ?></th>
                        <th><?php echo esc_html(__('Revenue', 'contentboost')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($stats as $stat) {
                        $post = get_post($stat->post_id);
                        echo '<tr>';
                        echo '<td>' . esc_html($post->post_title) . '</td>';
                        echo '<td>' . number_format($stat->visitor_count) . '</td>';
                        echo '<td>' . number_format($stat->engagement_score, 2) . '%</td>';
                        echo '<td>$' . number_format($stat->revenue, 2) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function admin_affiliates() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('Manage Affiliate Links', 'contentboost')); ?></h1>
            
            <div style="background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p><strong><?php echo esc_html(__('Premium Feature:', 'contentboost')); ?></strong> <?php echo esc_html(__('Advanced affiliate link management, automatic product recommendations, and revenue tracking are available in the Premium plan.', 'contentboost')); ?></p>
            </div>
            
            <h2><?php echo esc_html(__('Supported Programs', 'contentboost')); ?></h2>
            <ul style="font-size: 16px;">
                <li>✓ Amazon Associates</li>
                <li>✓ CJ Affiliate</li>
                <li>✓ ShareASale</li>
                <li>✓ Rakuten</li>
                <li>✓ ClickBank</li>
            </ul>
        </div>
        <?php
    }

    public function admin_settings() {
        if (isset($_POST['contentboost_settings_submit'])) {
            check_admin_referer('contentboost_settings_nonce');
            
            $settings = get_option('contentboost_settings');
            $settings['tracking_enabled'] = isset($_POST['tracking_enabled']) ? 1 : 0;
            $settings['display_stats'] = isset($_POST['display_stats']) ? 1 : 0;
            $settings['affiliate_program'] = sanitize_text_field($_POST['affiliate_program']);
            
            update_option('contentboost_settings', $settings);
            echo '<div class="notice notice-success"><p>' . esc_html(__('Settings saved successfully!', 'contentboost')) . '</p></div>';
        }
        
        $settings = get_option('contentboost_settings');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(__('ContentBoost Settings', 'contentboost')); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('contentboost_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="tracking_enabled"><?php echo esc_html(__('Enable Tracking', 'contentboost')); ?></label></th>
                        <td>
                            <input type="checkbox" id="tracking_enabled" name="tracking_enabled" <?php checked($settings['tracking_enabled'], 1); ?> />
                            <p class="description"><?php echo esc_html(__('Track visitor statistics and engagement metrics', 'contentboost')); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="display_stats"><?php echo esc_html(__('Display Public Stats', 'contentboost')); ?></label></th>
                        <td>
                            <input type="checkbox" id="display_stats" name="display_stats" <?php checked($settings['display_stats'], 1); ?> />
                            <p class="description"><?php echo esc_html(__('Show visitor count and engagement stats on posts', 'contentboost')); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="affiliate_program"><?php echo esc_html(__('Default Affiliate Program', 'contentboost')); ?></label></th>
                        <td>
                            <select id="affiliate_program" name="affiliate_program">
                                <option value="amazon" <?php selected($settings['affiliate_program'], 'amazon'); ?>>Amazon Associates</option>
                                <option value="cj" <?php selected($settings['affiliate_program'], 'cj'); ?>>CJ Affiliate</option>
                                <option value="rakuten" <?php selected($settings['affiliate_program'], 'rakuten'); ?>>Rakuten</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
                <input type="hidden" name="contentboost_settings_submit" value="1" />
            </form>
            
            <hr />
            <h2><?php echo esc_html(__('Upgrade to Premium', 'contentboost')); ?></h2>
            <div style="background: #fff3cd; padding: 20px; border-radius: 5px; border-left: 4px solid #ffc107;">
                <h3><?php echo esc_html(__('Unlock Advanced Features', 'contentboost')); ?></h3>
                <ul>
                    <li>✓ Advanced Analytics Dashboard</li>
                    <li>✓ Unlimited Affiliate Link Tracking</li>
                    <li>✓ AI-Powered Revenue Recommendations</li>
                    <li>✓ Custom Monetization Reports</li>
                    <li>✓ Priority Support</li>
                </ul>
                <p style="margin-top: 15px;">
                    <a href="#" class="button button-primary">Upgrade to Premium - $9.99/month</a>
                </p>
            </div>
        </div>
        <?php
    }

    public function stats_shortcode($atts) {
        global $wpdb, $post;
        
        if (!$post) {
            return '';
        }
        
        $settings = get_option('contentboost_settings');
        if (!$settings['display_stats']) {
            return '';
        }
        
        $table = $wpdb->prefix . 'contentboost_tracking';
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d",
            $post->ID
        ));
        
        if (!$stats) {
            return '';
        }
        
        return '<div class="contentboost-stats" style="background: #f9f9f9; padding: 10px 15px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #0073aa;">'
            . '<strong>' . esc_html(__('Article Stats:', 'contentboost')) . '</strong> ' 
            . number_format($stats->visitor_count) . ' ' . esc_html(__('views', 'contentboost'))
            . '</div>';
    }

    public function affiliate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Learn More'
        ), $atts);
        
        if (empty($atts['url'])) {
            return '';
        }
        
        return '<a href="' . esc_url($atts['url']) . '" class="contentboost-affiliate-link" target="_blank" rel="noopener noreferrer">'
            . esc_html($atts['text'])
            . '</a>';
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'contentboost') === false) {
            return;
        }
        
        wp_enqueue_style('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'css/admin.css', array(), CONTENTBOOST_VERSION);
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('contentboost-frontend', CONTENTBOOST_PLUGIN_URL . 'css/frontend.css', array(), CONTENTBOOST_VERSION);
    }

    public function action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=contentboost_settings')) . '">' . esc_html(__('Settings', 'contentboost')) . '</a>';
        array_unshift($links, $settings_link);
        
        $premium_link = '<a href="#" style="color: #d63638;" target="_blank">' . esc_html(__('Upgrade to Premium', 'contentboost')) . '</a>';
        array_unshift($links, $premium_link);
        
        return $links;
    }
}

// Initialize plugin
ContentBoostPro::get_instance();

// AJAX tracking endpoint
add_action('wp_ajax_nopriv_contentboost_track', function() {
    wp_die();
});
add_action('wp_ajax_contentboost_track', function() {
    wp_die();
});
?>