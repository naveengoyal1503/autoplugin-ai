<?php
/*
Plugin Name: ContentMoat Pro
Plugin URI: https://contentmoat.local
Description: AI-powered content monetization analytics for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentMoat_Pro.php
License: GPL v2 or later
Text Domain: contentmoat-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTMOAT_VERSION', '1.0.0');
define('CONTENTMOAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTMOAT_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentMoatPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_shortcode('contentmoat_dashboard', array($this, 'render_dashboard'));
        add_action('wp_ajax_contentmoat_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_nopriv_contentmoat_track_click', array($this, 'ajax_track_click'));
        add_action('init', array($this, 'register_post_type'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentmoat_tracking';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            link_url text NOT NULL,
            click_count bigint(20) DEFAULT 0,
            conversion_count bigint(20) DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0.00,
            link_type varchar(50) DEFAULT 'affiliate',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('contentmoat_db_version', CONTENTMOAT_VERSION);
    }

    public function deactivate_plugin() {
        // Cleanup if needed
    }

    public function register_post_type() {
        register_post_type('contentmoat_link', array(
            'public' => false,
            'show_ui' => true,
            'label' => 'ContentMoat Tracked Links',
            'supports' => array('title'),
            'capability_type' => 'post'
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentMoat Pro',
            'ContentMoat Pro',
            'manage_options',
            'contentmoat-pro',
            array($this, 'render_admin_page'),
            'dashicons-chart-line',
            20
        );
        add_submenu_page(
            'contentmoat-pro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentmoat-pro',
            array($this, 'render_admin_page')
        );
        add_submenu_page(
            'contentmoat-pro',
            'Settings',
            'Settings',
            'manage_options',
            'contentmoat-settings',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_admin_assets() {
        wp_enqueue_script('contentmoat-admin', CONTENTMOAT_PLUGIN_URL . 'assets/admin.js', array('jquery'), CONTENTMOAT_VERSION);
        wp_enqueue_style('contentmoat-admin', CONTENTMOAT_PLUGIN_URL . 'assets/admin.css', array(), CONTENTMOAT_VERSION);
        wp_localize_script('contentmoat-admin', 'contentmoatAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentmoat_nonce')
        ));
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_script('contentmoat-tracking', CONTENTMOAT_PLUGIN_URL . 'assets/tracking.js', array('jquery'), CONTENTMOAT_VERSION);
        wp_localize_script('contentmoat-tracking', 'contentmoatTrack', array(
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
    }

    public function render_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentmoat_tracking';
        $total_clicks = $wpdb->get_var("SELECT SUM(click_count) FROM $table_name");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table_name");
        $top_posts = $wpdb->get_results("SELECT post_id, SUM(click_count) as clicks FROM $table_name GROUP BY post_id ORDER BY clicks DESC LIMIT 5");
        ?>
        <div class="wrap contentmoat-admin-wrap">
            <h1>ContentMoat Pro Dashboard</h1>
            <div class="contentmoat-stats-grid">
                <div class="stat-card">
                    <h3>Total Clicks</h3>
                    <p class="stat-value"><?php echo intval($total_clicks) ?: 0; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p class="stat-value">$<?php echo number_format(floatval($total_revenue) ?: 0, 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Tracked Links</h3>
                    <p class="stat-value"><?php echo intval($wpdb->get_var("SELECT COUNT(*) FROM $table_name")); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Top Performing Posts</h3>
                    <ul>
                        <?php foreach ($top_posts as $post) {
                            $post_title = get_the_title($post->post_id);
                            echo '<li>' . esc_html($post_title) . ' - ' . intval($post->clicks) . ' clicks</li>';
                        } ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>ContentMoat Pro Settings</h1>
            <form method="post">
                <?php wp_nonce_field('contentmoat_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="contentmoat_api_key">API Key</label></th>
                        <td><input type="text" id="contentmoat_api_key" name="contentmoat_api_key" value="<?php echo esc_attr(get_option('contentmoat_api_key')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="contentmoat_tracking_enabled">Enable Tracking</label></th>
                        <td><input type="checkbox" id="contentmoat_tracking_enabled" name="contentmoat_tracking_enabled" <?php checked(get_option('contentmoat_tracking_enabled'), 1); ?> value="1" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_dashboard() {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return 'Access denied';
        }
        ob_start();
        ?>
        <div class="contentmoat-frontend-dashboard">
            <h2>Your Monetization Dashboard</h2>
            <div id="contentmoat-stats-container" class="contentmoat-stats"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_get_stats() {
        check_ajax_referer('contentmoat_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentmoat_tracking';
        $stats = $wpdb->get_results("SELECT * FROM $table_name ORDER BY updated_at DESC LIMIT 10");
        wp_send_json_success($stats);
    }

    public function ajax_track_click() {
        if (!isset($_POST['link_id'])) {
            wp_send_json_error('Missing link ID');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentmoat_tracking';
        $link_id = intval($_POST['link_id']);
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET click_count = click_count + 1 WHERE id = %d",
            $link_id
        ));
        wp_send_json_success('Click tracked');
    }
}

ContentMoatPro::get_instance();
?>