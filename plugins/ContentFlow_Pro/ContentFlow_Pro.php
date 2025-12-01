<?php
/*
Plugin Name: ContentFlow Pro
Plugin URI: https://contentflowpro.com
Description: AI-powered content repurposing and distribution engine for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentFlow_Pro.php
License: GPL v2 or later
Text Domain: contentflow-pro
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTFLOW_PRO_VERSION', '1.0.0');
define('CONTENTFLOW_PRO_PATH', plugin_dir_path(__FILE__));
define('CONTENTFLOW_PRO_URL', plugin_dir_url(__FILE__));

class ContentFlowPro {
    private static $instance = null;
    private $db_version = '1.0';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->setup_hooks();
        $this->load_dependencies();
        $this->register_activation();
    }

    private function setup_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('init', array($this, 'register_custom_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_repurposing_meta'));
        add_action('wp_ajax_contentflow_generate_variations', array($this, 'ajax_generate_variations'));
        add_action('wp_ajax_contentflow_get_analytics', array($this, 'ajax_get_analytics'));
    }

    private function load_dependencies() {
        require_once CONTENTFLOW_PRO_PATH . 'includes/class-db-handler.php';
        require_once CONTENTFLOW_PRO_PATH . 'includes/class-content-generator.php';
        require_once CONTENTFLOW_PRO_PATH . 'includes/class-analytics-tracker.php';
    }

    public function register_activation() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentflow_repurposing (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            original_title text NOT NULL,
            twitter_variation text,
            linkedin_variation text,
            instagram_caption text,
            email_subject varchar(255),
            email_body longtext,
            youtube_description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentflow_analytics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            repurposing_id mediumint(9) NOT NULL,
            platform varchar(50),
            clicks int DEFAULT 0,
            impressions int DEFAULT 0,
            conversions int DEFAULT 0,
            revenue decimal(10, 2) DEFAULT 0.00,
            tracked_date date,
            PRIMARY KEY (id),
            KEY repurposing_id (repurposing_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);

        update_option('contentflow_pro_db_version', $this->db_version);
        update_option('contentflow_pro_activated', time());
    }

    public function deactivate() {
        delete_option('contentflow_pro_activated');
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentFlow Pro',
            'ContentFlow Pro',
            'manage_options',
            'contentflow-pro',
            array($this, 'render_dashboard'),
            'dashicons-layout',
            25
        );

        add_submenu_page(
            'contentflow-pro',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentflow-pro',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'contentflow-pro',
            'Content Repurposer',
            'Repurposer',
            'manage_options',
            'contentflow-repurposer',
            array($this, 'render_repurposer')
        );

        add_submenu_page(
            'contentflow-pro',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentflow-analytics',
            array($this, 'render_analytics')
        );

        add_submenu_page(
            'contentflow-pro',
            'Settings',
            'Settings',
            'manage_options',
            'contentflow-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style('contentflow-admin', CONTENTFLOW_PRO_URL . 'assets/css/admin.css', array(), CONTENTFLOW_PRO_VERSION);
        wp_enqueue_script('contentflow-admin', CONTENTFLOW_PRO_URL . 'assets/js/admin.js', array('jquery'), CONTENTFLOW_PRO_VERSION, true);
        wp_localize_script('contentflow-admin', 'contentflowAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentflow_nonce')
        ));
    }

    public function register_custom_post_type() {
        register_post_type('cf_repurposing', array(
            'labels' => array('name' => 'Content Repurposing'),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title', 'editor'),
        ));
    }

    public function add_meta_boxes() {
        add_meta_box(
            'contentflow_repurposing_box',
            'ContentFlow Pro - Repurposing Options',
            array($this, 'render_meta_box'),
            'post',
            'side'
        );
    }

    public function render_meta_box() {
        global $post;
        $repurposing_data = get_post_meta($post->ID, '_contentflow_repurposing', true);
        wp_nonce_field('contentflow_meta_nonce', 'contentflow_nonce_field');
        ?>
        <div id="contentflow-meta-box">
            <p>
                <label><input type="checkbox" name="contentflow_enable" value="1" <?php checked(isset($repurposing_data['enabled']), true); ?>> Enable Repurposing</label>
            </p>
            <p>
                <label>Focus Keywords: <input type="text" name="contentflow_keywords" style="width:100%;" value="<?php echo isset($repurposing_data['keywords']) ? esc_attr($repurposing_data['keywords']) : ''; ?>"></label>
            </p>
            <button type="button" class="button button-primary" id="contentflow-generate-btn">Generate Variations</button>
            <div id="contentflow-loading" style="display:none;">Generating...</div>
        </div>
        <?php
    }

    public function save_repurposing_meta($post_id) {
        if (!isset($_POST['contentflow_nonce_field']) || !wp_verify_nonce($_POST['contentflow_nonce_field'], 'contentflow_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $meta_data = array(
            'enabled' => isset($_POST['contentflow_enable']) ? 1 : 0,
            'keywords' => isset($_POST['contentflow_keywords']) ? sanitize_text_field($_POST['contentflow_keywords']) : ''
        );
        update_post_meta($post_id, '_contentflow_repurposing', $meta_data);
    }

    public function ajax_generate_variations() {
        check_ajax_referer('contentflow_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
        }
        $generator = new ContentFlowProContentGenerator();
        $variations = $generator->generate_variations($post->post_title, $post->post_content);
        wp_send_json_success($variations);
    }

    public function ajax_get_analytics() {
        check_ajax_referer('contentflow_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        global $wpdb;
        $analytics = $wpdb->get_results(
            "SELECT platform, SUM(clicks) as total_clicks, SUM(impressions) as total_impressions, SUM(conversions) as total_conversions, SUM(revenue) as total_revenue 
            FROM {$wpdb->prefix}contentflow_analytics 
            WHERE DATE(tracked_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
            GROUP BY platform"
        );
        wp_send_json_success($analytics);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>ContentFlow Pro - Dashboard</h1>
            <div class="contentflow-dashboard">
                <div class="postbox">
                    <h2>Quick Stats</h2>
                    <div id="contentflow-stats">
                        <p>Loading statistics...</p>
                    </div>
                </div>
                <div class="postbox">
                    <h2>Recent Repurposing</h2>
                    <div id="contentflow-recent"></div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_repurposer() {
        ?>
        <div class="wrap">
            <h1>Content Repurposer</h1>
            <div class="contentflow-repurposer">
                <p>Select posts to automatically generate variations for social media, email, and other platforms.</p>
                <div id="contentflow-post-selector">
                    <?php
                    $posts = get_posts(array('numberposts' => 20));
                    foreach ($posts as $post) {
                        echo '<div class="contentflow-post-item"><input type="checkbox" value="' . $post->ID . '"> ' . esc_html($post->post_title) . '</div>';
                    }
                    ?>
                </div>
                <button class="button button-primary" id="contentflow-batch-generate">Generate All</button>
            </div>
        </div>
        <?php
    }

    public function render_analytics() {
        ?>
        <div class="wrap">
            <h1>Analytics & Revenue Tracking</h1>
            <div class="contentflow-analytics">
                <div id="contentflow-chart" style="height:400px;"></div>
                <table class="wp-list-table fixed striped">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Clicks</th>
                            <th>Impressions</th>
                            <th>Conversions</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody id="contentflow-analytics-body">
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>ContentFlow Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentflow_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="contentflow_api_key">API Key</label></th>
                        <td><input type="password" id="contentflow_api_key" name="contentflow_api_key" value="<?php echo esc_attr(get_option('contentflow_api_key')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="contentflow_enable_tracking">Enable Analytics Tracking</label></th>
                        <td><input type="checkbox" id="contentflow_enable_tracking" name="contentflow_enable_tracking" value="1" <?php checked(get_option('contentflow_enable_tracking'), 1); ?>></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="contentflow_affiliate_links">Enable Affiliate Link Tracking</label></th>
                        <td><input type="checkbox" id="contentflow_affiliate_links" name="contentflow_affiliate_links" value="1" <?php checked(get_option('contentflow_affiliate_links'), 1); ?>></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

if (!function_exists('contentflow_pro_init')) {
    function contentflow_pro_init() {
        return ContentFlowPro::get_instance();
    }
    contentflow_pro_init();
}

class ContentFlowProContentGenerator {
    public function generate_variations($title, $content) {
        $variations = array(
            'twitter' => $this->generate_twitter($title, $content),
            'linkedin' => $this->generate_linkedin($title, $content),
            'instagram' => $this->generate_instagram($title, $content),
            'email_subject' => $this->generate_email_subject($title),
            'email_body' => $this->generate_email_body($content),
        );
        return $variations;
    }

    private function generate_twitter($title, $content) {
        $excerpt = wp_trim_words($title, 15);
        return $excerpt . '... ' . '#content #marketing';
    }

    private function generate_linkedin($title, $content) {
        return 'Interesting insight: ' . $title . '. Read more to discover key takeaways. #industry #insights #learning';
    }

    private function generate_instagram($title, $content) {
        return wp_trim_words($title, 12) . ' 🚀 #socialmedia #contentmarketing';
    }

    private function generate_email_subject($title) {
        return 'You won\'t believe this: ' . wp_trim_words($title, 8);
    }

    private function generate_email_body($content) {
        return '<p>Hi there!</p><p>' . wp_trim_words($content, 50) . '</p><p>Read the full article now!</p>';
    }
}

class ContentFlowProDBHandler {
    public static function insert_repurposing($post_id, $data) {
        global $wpdb;
        return $wpdb->insert(
            $wpdb->prefix . 'contentflow_repurposing',
            array(
                'post_id' => $post_id,
                'original_title' => $data['title'],
                'twitter_variation' => $data['twitter'] ?? '',
                'linkedin_variation' => $data['linkedin'] ?? '',
                'instagram_caption' => $data['instagram'] ?? '',
            )
        );
    }

    public static function track_analytics($repurposing_id, $platform, $clicks, $impressions, $conversions, $revenue) {
        global $wpdb;
        return $wpdb->insert(
            $wpdb->prefix . 'contentflow_analytics',
            array(
                'repurposing_id' => $repurposing_id,
                'platform' => $platform,
                'clicks' => $clicks,
                'impressions' => $impressions,
                'conversions' => $conversions,
                'revenue' => $revenue,
                'tracked_date' => current_time('mysql'),
            )
        );
    }
}
?>