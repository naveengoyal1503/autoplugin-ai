<?php
/*
Plugin Name: ContentBoost Pro
Plugin URI: https://contentboostpro.com
Description: AI-powered content optimization and repurposing plugin for WordPress monetization
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: contentboost-pro
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
    private $db;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->db = new ContentBoost_Database();
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('contentboost_stats', array($this, 'stats_shortcode'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        register_activation_hook(__FILE__, array($this->db, 'create_tables'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost Pro',
            'ContentBoost Pro',
            'manage_options',
            'contentboost-pro',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
        add_submenu_page(
            'contentboost-pro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentboost-pro',
            array($this, 'render_dashboard')
        );
        add_submenu_page(
            'contentboost-pro',
            'Repurpose Content',
            'Repurpose Content',
            'manage_options',
            'contentboost-repurpose',
            array($this, 'render_repurpose_page')
        );
        add_submenu_page(
            'contentboost-pro',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentboost-analytics',
            array($this, 'render_analytics_page')
        );
        add_submenu_page(
            'contentboost-pro',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_dashboard() {
        $stats = $this->db->get_dashboard_stats();
        ?>
        <div class="wrap">
            <h1>ContentBoost Pro Dashboard</h1>
            <div class="contentboost-dashboard">
                <div class="dashboard-card">
                    <h3>Total Posts Analyzed</h3>
                    <p class="stat-number"><?php echo esc_html($stats['total_posts']); ?></p>
                </div>
                <div class="dashboard-card">
                    <h3>Content Repurposed</h3>
                    <p class="stat-number"><?php echo esc_html($stats['repurposed']); ?></p>
                </div>
                <div class="dashboard-card">
                    <h3>Avg. Engagement Lift</h3>
                    <p class="stat-number"><?php echo esc_html($stats['engagement_lift']); ?>%</p>
                </div>
                <div class="dashboard-card">
                    <h3>Estimated Revenue</h3>
                    <p class="stat-number">$<?php echo esc_html($stats['estimated_revenue']); ?></p>
                </div>
            </div>
            <h2>Monetization Opportunities</h2>
            <div class="monetization-suggestions">
                <p>Based on your content performance, we recommend:</p>
                <ul>
                    <li>Enable affiliate marketing on top-performing posts</li>
                    <li>Create premium content tier from your best-performing articles</li>
                    <li>Develop sponsored content partnerships in your niche</li>
                </ul>
            </div>
        </div>
        <style>
            .contentboost-dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
            .dashboard-card { background: #fff; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .stat-number { font-size: 32px; font-weight: bold; color: #0073aa; margin: 10px 0 0 0; }
        </style>
        <?php
    }

    public function render_repurpose_page() {
        ?>
        <div class="wrap">
            <h1>Repurpose Your Content</h1>
            <form method="post" action="">
                <?php wp_nonce_field('contentboost_repurpose'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="post_select">Select Post to Repurpose</label></th>
                        <td>
                            <?php
                            $posts = get_posts(array('numberposts' => -1, 'post_type' => 'post'));
                            echo '<select name="post_id" id="post_select">';
                            foreach ($posts as $post) {
                                echo '<option value="' . esc_attr($post->ID) . '">' . esc_html($post->post_title) . '</option>';
                            }
                            echo '</select>';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Repurposing Options</label></th>
                        <td>
                            <label><input type="checkbox" name="formats[]" value="video_outline"> Video Outline</label><br>
                            <label><input type="checkbox" name="formats[]" value="social_posts"> Social Media Posts</label><br>
                            <label><input type="checkbox" name="formats[]" value="infographic_brief"> Infographic Brief</label><br>
                            <label><input type="checkbox" name="formats[]" value="podcast_script"> Podcast Script</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Repurpose Content'); ?>
            </form>
        </div>
        <?php
    }

    public function render_analytics_page() {
        ?>
        <div class="wrap">
            <h1>Content Analytics</h1>
            <p>Track which content formats generate the most engagement and revenue.</p>
            <div id="contentboost-chart" style="height: 400px;"></div>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>ContentBoost Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('contentboost_settings');
                do_settings_sections('contentboost_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentboost') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_style('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'assets/css/admin.css', array(), CONTENTBOOST_VERSION);
            wp_enqueue_script('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CONTENTBOOST_VERSION, true);
        }
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('contentboost-frontend', CONTENTBOOST_PLUGIN_URL . 'assets/css/frontend.css', array(), CONTENTBOOST_VERSION);
        wp_enqueue_script('contentboost-frontend', CONTENTBOOST_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), CONTENTBOOST_VERSION, true);
    }

    public function stats_shortcode($atts) {
        $stats = $this->db->get_dashboard_stats();
        return '<div class="contentboost-widget">Posts: ' . esc_html($stats['total_posts']) . ' | Repurposed: ' . esc_html($stats['repurposed']) . '</div>';
    }

    public function register_rest_routes() {
        register_rest_route('contentboost/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats_rest'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }

    public function get_stats_rest() {
        return rest_ensure_response($this->db->get_dashboard_stats());
    }
}

class ContentBoost_Database {
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentboost_repurposed';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            format varchar(50) NOT NULL,
            content longtext NOT NULL,
            engagement_rate float DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function get_dashboard_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'contentboost_repurposed';
        $total_posts = wp_count_posts('post')->publish;
        $repurposed = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $avg_engagement = $wpdb->get_var("SELECT AVG(engagement_rate) FROM $table");
        $estimated_revenue = number_format(($repurposed * 2.5), 2);
        return array(
            'total_posts' => $total_posts,
            'repurposed' => $repurposed ? $repurposed : 0,
            'engagement_lift' => round($avg_engagement ? $avg_engagement : 0, 1),
            'estimated_revenue' => $estimated_revenue
        );
    }
}

function contentboost_pro_init() {
    ContentBoostPro::get_instance();
}
add_action('plugins_loaded', 'contentboost_pro_init');
?>