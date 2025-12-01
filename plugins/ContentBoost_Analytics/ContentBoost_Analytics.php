<?php
/*
Plugin Name: ContentBoost Analytics
Plugin URI: https://contentboost.local
Description: AI-powered content performance analytics and optimization tool
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Analytics.php
License: GPL v2 or later
Text Domain: contentboost-analytics
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOOST_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentBoostAnalytics {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'track_engagement'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'contentboost_analytics';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            views INT DEFAULT 0,
            engagement_time INT DEFAULT 0,
            scroll_depth INT DEFAULT 0,
            clicks INT DEFAULT 0,
            date_recorded datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('contentboost_activated', true);
        add_option('contentboost_plan', 'free');
    }

    public function deactivate_plugin() {
        delete_option('contentboost_activated');
    }

    public function register_settings() {
        register_setting('contentboost_settings', 'contentboost_api_key');
        register_setting('contentboost_settings', 'contentboost_enable_tracking');
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost Analytics',
            'ContentBoost',
            'manage_options',
            'contentboost-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentboost-analytics',
            array($this, 'render_analytics')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-settings',
            array($this, 'render_settings')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Premium',
            'Premium',
            'manage_options',
            'contentboost-premium',
            array($this, 'render_premium')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentboost') === false) {
            return;
        }

        wp_enqueue_style(
            'contentboost-admin',
            CONTENTBOOST_PLUGIN_URL . 'css/admin-style.css',
            array(),
            CONTENTBOOST_VERSION
        );

        wp_enqueue_script(
            'contentboost-chart',
            'https://cdn.jsdelivr.net/npm/chart.js@3/dist/chart.min.js',
            array(),
            '3.0',
            true
        );

        wp_enqueue_script(
            'contentboost-admin',
            CONTENTBOOST_PLUGIN_URL . 'js/admin-script.js',
            array('jquery', 'contentboost-chart'),
            CONTENTBOOST_VERSION,
            true
        );
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'contentboost_widget',
            'ContentBoost Summary',
            array($this, 'render_dashboard_widget')
        );
    }

    public function render_dashboard_widget() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_analytics';

        $total_views = $wpdb->get_var("SELECT SUM(views) FROM $table_name");
        $total_engagement = $wpdb->get_var("SELECT SUM(engagement_time) FROM $table_name");
        $avg_scroll = $wpdb->get_var("SELECT AVG(scroll_depth) FROM $table_name");

        echo '<div class="contentboost-widget">';
        echo '<p><strong>Total Views:</strong> ' . intval($total_views) . '</p>';
        echo '<p><strong>Total Engagement (seconds):</strong> ' . intval($total_engagement) . '</p>';
        echo '<p><strong>Average Scroll Depth:</strong> ' . round($avg_scroll, 2) . '%</p>';
        echo '<p><a href="admin.php?page=contentboost-analytics" class="button">View Full Analytics</a></p>';
        echo '</div>';
    }

    public function track_engagement() {
        if (is_single()) {
            ?>
            <script>
            (function() {
                let startTime = Date.now();
                let maxScroll = 0;

                window.addEventListener('scroll', function() {
                    const windowHeight = window.innerHeight;
                    const docHeight = document.documentElement.scrollHeight;
                    const scrollTop = window.scrollY;
                    const totalScroll = scrollTop + windowHeight;
                    const percentage = (totalScroll / docHeight) * 100;
                    if (percentage > maxScroll) {
                        maxScroll = percentage;
                    }
                });

                window.addEventListener('beforeunload', function() {
                    const engagementTime = Math.floor((Date.now() - startTime) / 1000);
                    const data = {
                        action: 'contentboost_log_engagement',
                        post_id: <?php echo get_the_ID(); ?>,
                        engagement_time: engagementTime,
                        scroll_depth: Math.floor(maxScroll),
                        nonce: '<?php echo wp_create_nonce('contentboost_nonce'); ?>'
                    };
                    navigator.sendBeacon(
                        '<?php echo admin_url('admin-ajax.php'); ?>',
                        new URLSearchParams(data)
                    );
                });
            })();
            </script>
            <?php
        }
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>ContentBoost Analytics Dashboard</h1>';
        echo '<p>Welcome to ContentBoost! Start tracking your content performance and monetization opportunities.</p>';
        echo '<div class="contentboost-cards">';
        echo '<div class="card"><h3>Analytics</h3><p>Track views, engagement, and user behavior</p></div>';
        echo '<div class="card"><h3>Optimization</h3><p>Get AI-powered content recommendations</p></div>';
        echo '<div class="card"><h3>Monetization</h3><p>Identify premium content opportunities</p></div>';
        echo '</div>';
        echo '</div>';
    }

    public function render_analytics() {
        if (!current_user_can('manage_options')) {
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_analytics';
        $posts = $wpdb->get_results("SELECT post_id, SUM(views) as total_views, SUM(engagement_time) as total_engagement, AVG(scroll_depth) as avg_scroll FROM $table_name GROUP BY post_id ORDER BY total_views DESC LIMIT 10");

        echo '<div class="wrap">';
        echo '<h1>Analytics</h1>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Post</th><th>Views</th><th>Engagement (sec)</th><th>Avg Scroll Depth</th></tr></thead>';
        echo '<tbody>';
        foreach ($posts as $post) {
            $post_title = get_the_title($post->post_id);
            echo '<tr>';
            echo '<td>' . esc_html($post_title) . '</td>';
            echo '<td>' . intval($post->total_views) . '</td>';
            echo '<td>' . intval($post->total_engagement) . '</td>';
            echo '<td>' . round($post->avg_scroll, 2) . '%</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>ContentBoost Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentboost_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="contentboost_enable_tracking">Enable Tracking</label></th>
                        <td>
                            <input type="checkbox" id="contentboost_enable_tracking" name="contentboost_enable_tracking" value="1" <?php checked(get_option('contentboost_enable_tracking'), 1); ?> />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_premium() {
        ?>
        <div class="wrap">
            <h1>Upgrade to Premium</h1>
            <div class="contentboost-premium-box">
                <h2>ContentBoost Premium</h2>
                <p>Unlock advanced features:</p>
                <ul>
                    <li>AI-powered content optimization recommendations</li>
                    <li>Advanced audience segmentation</li>
                    <li>Predictive analytics for monetization</li>
                    <li>Custom report generation</li>
                    <li>Priority support</li>
                </ul>
                <p><strong>$9.99/month or $99/year</strong></p>
                <a href="#" class="button button-primary">Upgrade Now</a>
            </div>
        </div>
        <?php
    }
}

add_action('wp_ajax_contentboost_log_engagement', function() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contentboost_nonce')) {
        wp_die('Nonce verification failed');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'contentboost_analytics';

    $post_id = intval($_POST['post_id']);
    $engagement_time = intval($_POST['engagement_time']);
    $scroll_depth = intval($_POST['scroll_depth']);

    $wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'views' => 1,
            'engagement_time' => $engagement_time,
            'scroll_depth' => $scroll_depth,
        )
    );

    wp_die('logged');
});

ContentBoostAnalytics::getInstance();
?>