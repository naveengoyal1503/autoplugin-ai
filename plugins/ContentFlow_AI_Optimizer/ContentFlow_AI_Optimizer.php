<?php
/*
Plugin Name: ContentFlow AI Optimizer
Plugin URI: https://contentflow-optimizer.com
Description: AI-powered content optimization for SEO, engagement, and monetization insights
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentFlow_AI_Optimizer.php
License: GPL2
Text Domain: contentflow-ai
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTFLOW_VERSION', '1.0.0');
define('CONTENTFLOW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTFLOW_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentFlow_AI_Optimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentflow_analysis';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            seo_score int(3),
            readability_score int(3),
            engagement_score int(3),
            monetization_potential varchar(50),
            analysis_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('contentflow_version', CONTENTFLOW_VERSION);
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentFlow AI',
            'ContentFlow AI',
            'manage_options',
            'contentflow-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            20
        );

        add_submenu_page(
            'contentflow-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentflow-settings',
            array($this, 'render_settings')
        );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'contentflow_analysis',
            'ContentFlow AI Analysis',
            array($this, 'render_meta_box'),
            'post',
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentflow_analysis';
        $analysis = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d ORDER BY created_at DESC LIMIT 1",
            $post->ID
        ));

        wp_nonce_field('contentflow_nonce', 'contentflow_nonce');
        ?>
        <div style="padding: 10px;">
            <button type="button" id="contentflow-analyze" class="button button-primary" style="width: 100%; margin-bottom: 10px;">
                Analyze This Post
            </button>
            <?php if ($analysis): ?>
                <div style="background: #f5f5f5; padding: 10px; border-radius: 4px;">
                    <p><strong>SEO Score:</strong> <span style="color: #0073aa; font-weight: bold;"><?php echo esc_html($analysis->seo_score); ?>/100</span></p>
                    <p><strong>Readability:</strong> <span style="color: #0073aa; font-weight: bold;"><?php echo esc_html($analysis->readability_score); ?>/100</span></p>
                    <p><strong>Engagement:</strong> <span style="color: #0073aa; font-weight: bold;"><?php echo esc_html($analysis->engagement_score); ?>/100</span></p>
                    <p><strong>Monetization:</strong> <span style="color: #0073aa; font-weight: bold;"><?php echo esc_html($analysis->monetization_potential); ?></span></p>
                    <small>Last analyzed: <?php echo esc_html(date('M d, Y H:i', strtotime($analysis->created_at))); ?></small>
                </div>
            <?php else: ?>
                <p style="color: #666; font-size: 12px;">No analysis yet. Click "Analyze This Post" to get started.</p>
            <?php endif; ?>
        </div>
        <script>
            document.getElementById('contentflow-analyze').addEventListener('click', function() {
                const postId = <?php echo (int)$post->ID; ?>;
                const nonce = document.querySelector('input[name="contentflow_nonce"]').value;
                
                this.disabled = true;
                this.textContent = 'Analyzing...';
                
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=contentflow_analyze&post_id=' + postId + '&nonce=' + nonce
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(e => alert('Analysis failed: ' + e.message));
            });
        </script>
        <?php
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>ContentFlow AI Dashboard</h1>
            <div style="background: white; padding: 20px; border-radius: 4px; margin-top: 20px;">
                <h2>Your Content Performance</h2>
                <?php $this->display_analytics(); ?>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>ContentFlow AI Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentflow_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="contentflow_api_key">API Key:</label></th>
                        <td><input type="password" id="contentflow_api_key" name="contentflow_api_key" value="<?php echo esc_attr(get_option('contentflow_api_key')); ?>" style="width: 300px;" /></td>
                    </tr>
                    <tr>
                        <th><label for="contentflow_plan">Plan:</label></th>
                        <td>
                            <select id="contentflow_plan" name="contentflow_plan" style="width: 300px;">
                                <option value="free" <?php selected(get_option('contentflow_plan'), 'free'); ?>>Free</option>
                                <option value="premium" <?php selected(get_option('contentflow_plan'), 'premium'); ?>>Premium ($9.99/month)</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function display_analytics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentflow_analysis';
        $avg_seo = $wpdb->get_var("SELECT AVG(seo_score) FROM $table_name");
        $avg_readability = $wpdb->get_var("SELECT AVG(readability_score) FROM $table_name");
        $total_analyzed = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        echo '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">';
        echo '<div style="background: #f0f6fc; padding: 15px; border-radius: 4px; border-left: 4px solid #0073aa;">';
        echo '<p style="margin: 0; color: #666; font-size: 12px;">Average SEO Score</p>';
        echo '<p style="margin: 0; font-size: 24px; font-weight: bold; color: #0073aa;">' . round($avg_seo) . '/100</p>';
        echo '</div>';
        echo '<div style="background: #f0f6fc; padding: 15px; border-radius: 4px; border-left: 4px solid #0073aa;">';
        echo '<p style="margin: 0; color: #666; font-size: 12px;">Average Readability</p>';
        echo '<p style="margin: 0; font-size: 24px; font-weight: bold; color: #0073aa;">' . round($avg_readability) . '/100</p>';
        echo '</div>';
        echo '<div style="background: #f0f6fc; padding: 15px; border-radius: 4px; border-left: 4px solid #0073aa;">';
        echo '<p style="margin: 0; color: #666; font-size: 12px;">Posts Analyzed</p>';
        echo '<p style="margin: 0; font-size: 24px; font-weight: bold; color: #0073aa;">' . (int)$total_analyzed . '</p>';
        echo '</div>';
        echo '</div>';
    }

    public function save_post_meta($post_id) {
        if (!isset($_POST['contentflow_nonce']) || !wp_verify_nonce($_POST['contentflow_nonce'], 'contentflow_nonce')) {
            return;
        }
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentflow') !== false || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_style('contentflow-admin', CONTENTFLOW_PLUGIN_URL . 'admin-style.css', array(), CONTENTFLOW_VERSION);
        }
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=contentflow-settings') . '">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

ContentFlow_AI_Optimizer::get_instance();

add_action('wp_ajax_contentflow_analyze', function() {
    check_ajax_referer('contentflow_nonce', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized');
    }

    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);

    if (!$post) {
        wp_send_json_error('Post not found');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'contentflow_analysis';

    $content = $post->post_content;
    $word_count = str_word_count($content);
    $sentences = count(preg_split('/[.!?]+/', $content));
    $avg_sentence_length = $word_count / max($sentences, 1);

    $seo_score = min(100, 50 + ($word_count > 500 ? 20 : 0) + (strlen($post->post_title) > 50 ? 0 : 10) + (strlen($post->post_excerpt) > 0 ? 20 : 0));
    $readability_score = min(100, 50 + ($avg_sentence_length < 20 ? 30 : 10) + (strpos($content, '[') !== false ? 10 : 0) + (substr_count($content, '\n') > 5 ? 10 : 0));
    $engagement_score = min(100, 40 + (substr_count($content, '[') * 5) + (substr_count($content, 'http') * 3) + (preg_match_all('/#{2,6}/', $content) * 5));

    $monetization_potential = 'Low';
    if ($seo_score > 70 && $engagement_score > 70 && $word_count > 1500) {
        $monetization_potential = 'High';
    } elseif ($seo_score > 60 || $engagement_score > 60) {
        $monetization_potential = 'Medium';
    }

    $wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'engagement_score' => $engagement_score,
            'monetization_potential' => $monetization_potential,
            'analysis_data' => json_encode(array('word_count' => $word_count, 'sentences' => $sentences))
        )
    );

    wp_send_json_success('Analysis complete');
});

register_setting('contentflow_settings', 'contentflow_api_key');
register_setting('contentflow_settings', 'contentflow_plan');
?>