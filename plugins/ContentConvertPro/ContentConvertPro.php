<?php
/*
Plugin Name: ContentConvertPro
Plugin URI: https://contentconvertpro.com
Description: Convert blog posts into multiple content formats automatically
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentConvertPro.php
License: GPL v2 or later
*/

if (!defined('ABSPATH')) exit;

define('CONTENTCONVERT_VERSION', '1.0.0');
define('CONTENTCONVERT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTCONVERT_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentConvertPro {
    private static $instance = null;
    private $db;
    private $table_name;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table_name = $this->db->prefix . 'contentconvert_conversions';

        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_convert_content', array($this, 'ajax_convert_content'));
        add_action('wp_ajax_get_conversions', array($this, 'ajax_get_conversions'));
        add_action('post_row_actions', array($this, 'add_post_action'), 10, 2);
    }

    public function activate() {
        $this->create_tables();
        update_option('contentconvert_activated', true);
    }

    public function deactivate() {
        delete_option('contentconvert_activated');
    }

    private function create_tables() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            conversion_type VARCHAR(50) NOT NULL,
            conversion_data LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentConvertPro',
            'ContentConvertPro',
            'manage_options',
            'contentconvertpro',
            array($this, 'render_dashboard'),
            'dashicons-layout',
            90
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentconvertpro') === false) return;
        
        wp_enqueue_script('contentconvert-admin', CONTENTCONVERT_PLUGIN_URL . 'assets/admin.js', array('jquery'), CONTENTCONVERT_VERSION, true);
        wp_enqueue_style('contentconvert-admin', CONTENTCONVERT_PLUGIN_URL . 'assets/admin.css', array(), CONTENTCONVERT_VERSION);
        
        wp_localize_script('contentconvert-admin', 'contentConvertAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentconvert_nonce')
        ));
    }

    public function render_dashboard() {
        $is_premium = get_option('contentconvert_premium', false);
        $conversions = $this->get_user_conversions();
        ?>
        <div class="wrap contentconvert-dashboard">
            <h1>ContentConvertPro Dashboard</h1>
            <div class="contentconvert-status">
                <p>Status: <strong><?php echo $is_premium ? 'Premium' : 'Free'; ?></strong></p>
            </div>
            <div class="contentconvert-grid">
                <div class="contentconvert-card">
                    <h2>Select Content to Convert</h2>
                    <select id="contentconvert-posts" style="width: 100%; padding: 8px;">
                        <option value="">Choose a post...</option>
                        <?php $this->render_posts_dropdown(); ?>
                    </select>
                </div>
                <div class="contentconvert-card">
                    <h2>Conversion Options</h2>
                    <label><input type="checkbox" class="contentconvert-format" value="summary"> Generate Summary</label>
                    <label><input type="checkbox" class="contentconvert-format" value="social"> Social Media Posts</label>
                    <label><input type="checkbox" class="contentconvert-format" value="infographic"> Infographic Script</label>
                    <?php if ($is_premium): ?>
                    <label><input type="checkbox" class="contentconvert-format" value="video"> Video Script</label>
                    <?php endif; ?>
                    <button id="contentconvert-btn" class="button button-primary" style="margin-top: 10px; width: 100%;">Convert Content</button>
                </div>
            </div>
            <div class="contentconvert-card">
                <h2>Recent Conversions</h2>
                <div id="contentconvert-results"></div>
            </div>
        </div>
        <?php
    }

    private function render_posts_dropdown() {
        $posts = get_posts(array('numberposts' => 50, 'post_type' => 'post', 'post_status' => 'publish'));
        foreach ($posts as $post) {
            echo '<option value="' . $post->ID . '">' . $post->post_title . '</option>';
        }
    }

    public function ajax_convert_content() {
        check_ajax_referer('contentconvert_nonce');
        
        $post_id = intval($_POST['post_id']);
        $formats = isset($_POST['formats']) ? array_map('sanitize_text_field', $_POST['formats']) : array();
        
        if (empty($post_id) || empty($formats)) {
            wp_send_json_error('Invalid parameters');
        }

        $post = get_post($post_id);
        $results = array();

        foreach ($formats as $format) {
            $converted = $this->convert_content($post, $format);
            $this->save_conversion($post_id, $format, $converted);
            $results[$format] = $converted;
        }

        wp_send_json_success($results);
    }

    private function convert_content($post, $format) {
        $content = $post->post_content;
        $title = $post->post_title;

        switch ($format) {
            case 'summary':
                return $this->generate_summary($content);
            case 'social':
                return $this->generate_social_posts($title, $content);
            case 'infographic':
                return $this->generate_infographic_script($content);
            case 'video':
                return $this->generate_video_script($title, $content);
            default:
                return '';
        }
    }

    private function generate_summary($content) {
        $words = array_slice(str_word_count(strip_tags($content), 1), 0, 50);
        return implode(' ', $words) . '...';
    }

    private function generate_social_posts($title, $content) {
        $posts = array(
            "Check out our latest: " . $title . " #blog #content",
            "Did you know? " . substr(wp_strip_all_tags($content), 0, 100) . "... Read more!",
            $title . " - A must read for everyone interested in this topic! #insights"
        );
        return $posts;
    }

    private function generate_infographic_script($content) {
        $text = wp_strip_all_tags($content);
        $sentences = explode('.', $text);
        $key_points = array_slice($sentences, 0, 5);
        return 'Key Points:\n' . implode('\n', array_map(function($s) { return '- ' . trim($s); }, $key_points));
    }

    private function generate_video_script($title, $content) {
        return "[INTRO]\nWelcome to: " . $title . "\n\n[MAIN CONTENT]\n" . substr(wp_strip_all_tags($content), 0, 300) . "\n\n[OUTRO]\nThanks for watching!";
    }

    private function save_conversion($post_id, $type, $data) {
        $this->db->insert(
            $this->table_name,
            array(
                'post_id' => $post_id,
                'conversion_type' => $type,
                'conversion_data' => is_array($data) ? json_encode($data) : $data
            )
        );
    }

    private function get_user_conversions($limit = 10) {
        return $this->db->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT {$limit}");
    }

    public function ajax_get_conversions() {
        check_ajax_referer('contentconvert_nonce');
        $conversions = $this->get_user_conversions();
        wp_send_json_success($conversions);
    }

    public function add_post_action($actions, $post) {
        if ($post->post_type === 'post') {
            $actions['contentconvert'] = '<a href="' . admin_url('admin.php?page=contentconvertpro&post=' . $post->ID) . '">Convert</a>';
        }
        return $actions;
    }
}

ContentConvertPro::getInstance();
?>