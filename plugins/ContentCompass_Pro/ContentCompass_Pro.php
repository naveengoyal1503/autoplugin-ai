<?php
/*
Plugin Name: ContentCompass Pro
Plugin URI: https://contentcompass.local
Description: AI-powered content optimizer for WordPress with monetization insights
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentCompass_Pro.php
License: GPL2
Text Domain: contentcompass-pro
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CCP_VERSION', '1.0.0');
define('CCP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CCP_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentCompassPro {
    private $options;
    private $db_version = '1.0';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        add_action('wp_ajax_ccp_analyze_content', array($this, 'analyze_content'));
        add_action('wp_ajax_ccp_toggle_premium', array($this, 'toggle_premium'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        
        $this->options = get_option('ccp_options', $this->get_default_options());
    }

    public function get_default_options() {
        return array(
            'premium_enabled' => false,
            'api_key' => '',
            'track_analytics' => true
        );
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ccp_content_metrics';
        
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            seo_score int(3),
            readability_score int(3),
            word_count int(5),
            keyword_density float,
            monetization_potential varchar(50),
            analyzed_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('ccp_options', $this->get_default_options());
        add_option('ccp_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentCompass Pro',
            'ContentCompass Pro',
            'manage_options',
            'contentcompass-pro',
            array($this, 'render_dashboard'),
            'dashicons-chart-bar',
            80
        );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'ccp_post_analysis',
            'ContentCompass Pro Analysis',
            array($this, 'render_meta_box'),
            'post',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('ccp_post_nonce', 'ccp_nonce');
        
        $metrics = $this->get_post_metrics($post->ID);
        ?>
        <div style="padding: 15px;">
            <button type="button" id="ccp-analyze-btn" class="button button-primary">Analyze Content</button>
            <div id="ccp-analysis-results" style="margin-top: 20px;"></div>
            <?php if ($this->options['premium_enabled']): ?>
                <div style="background: #f0f7ff; padding: 15px; margin-top: 15px; border-radius: 5px;">
                    <h4>Premium Insights Available</h4>
                    <p>AI-powered recommendations and monetization strategies for this post.</p>
                </div>
            <?php endif; ?>
        </div>
        <script>
            document.getElementById('ccp-analyze-btn')?.addEventListener('click', function() {
                const postId = <?php echo $post->ID; ?>;
                const nonce = document.querySelector('input[name="ccp_nonce"]').value;
                
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=ccp_analyze_content&post_id=' + postId + '&nonce=' + nonce
                })
                .then(r => r.json())
                .then(data => {
                    const results = document.getElementById('ccp-analysis-results');
                    results.innerHTML = '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px;"><p><strong>SEO Score:</strong> ' + data.seo_score + '/100</p><p><strong>Readability:</strong> ' + data.readability_score + '/100</p><p><strong>Word Count:</strong> ' + data.word_count + '</p><p><strong>Monetization Potential:</strong> ' + data.monetization_potential + '</p></div>';
                });
            });
        </script>
        <?php
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>ContentCompass Pro Dashboard</h1>
            
            <div style="margin: 20px 0;">
                <h2>Premium Features</h2>
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <p>Unlock advanced analytics, AI recommendations, and monetization insights.</p>
                    <label>
                        <input type="checkbox" id="ccp-premium-toggle" <?php checked($this->options['premium_enabled']); ?> />
                        Enable Premium Features
                    </label>
                </div>
            </div>

            <div style="margin: 20px 0;">
                <h2>Content Metrics</h2>
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <?php echo $this->render_metrics_table(); ?>
                </div>
            </div>
        </div>
        <script>
            document.getElementById('ccp-premium-toggle')?.addEventListener('change', function() {
                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=ccp_toggle_premium&enabled=' + (this.checked ? '1' : '0')
                });
            });
        </script>
        <?php
    }

    public function render_metrics_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ccp_content_metrics';
        $metrics = $wpdb->get_results("SELECT * FROM $table_name ORDER BY analyzed_date DESC LIMIT 10");
        
        $html = '<table class="widefat"><thead><tr><th>Post</th><th>SEO Score</th><th>Readability</th><th>Word Count</th><th>Monetization</th></tr></thead><tbody>';
        
        foreach ($metrics as $metric) {
            $post = get_post($metric->post_id);
            $html .= '<tr>';
            $html .= '<td>' . esc_html($post->post_title) . '</td>';
            $html .= '<td>' . intval($metric->seo_score) . '</td>';
            $html .= '<td>' . intval($metric->readability_score) . '</td>';
            $html .= '<td>' . intval($metric->word_count) . '</td>';
            $html .= '<td>' . esc_html($metric->monetization_potential) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }

    public function analyze_content() {
        if (!isset($_POST['post_id']) || !wp_verify_nonce($_POST['nonce'], 'ccp_post_nonce')) {
            wp_send_json_error('Verification failed');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $content = $post->post_content;
        $word_count = str_word_count(strip_tags($content));
        
        // Simple scoring algorithms
        $seo_score = min(100, 20 + (strlen($post->post_title) > 50 ? 15 : 5) + min(30, $word_count / 10) + (has_tag('', $post_id) ? 20 : 0));
        $readability_score = min(100, 30 + (preg_match_all('/.{1,80}(\. )/u', $content) * 2) + (substr_count($content, '\n') * 3));
        
        // Monetization assessment
        $monetization_potential = 'Medium';
        if ($word_count > 1500 && $seo_score > 60) {
            $monetization_potential = 'High';
        } elseif ($word_count < 500 || $seo_score < 40) {
            $monetization_potential = 'Low';
        }

        // Store metrics
        global $wpdb;
        $table_name = $wpdb->prefix . 'ccp_content_metrics';
        $wpdb->insert($table_name, array(
            'post_id' => $post_id,
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'word_count' => $word_count,
            'monetization_potential' => $monetization_potential
        ));

        wp_send_json_success(array(
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'word_count' => $word_count,
            'monetization_potential' => $monetization_potential
        ));
    }

    public function toggle_premium() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $enabled = isset($_POST['enabled']) ? (bool)$_POST['enabled'] : false;
        $this->options['premium_enabled'] = $enabled;
        update_option('ccp_options', $this->options);
        
        wp_send_json_success('Premium toggled');
    }

    public function save_post_meta($post_id) {
        if (!isset($_POST['ccp_nonce']) || !wp_verify_nonce($_POST['ccp_nonce'], 'ccp_post_nonce')) {
            return;
        }
    }

    public function get_post_metrics($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ccp_content_metrics';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d ORDER BY analyzed_date DESC LIMIT 1", $post_id));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentcompass-pro') !== false || strpos($hook, 'edit.php') !== false) {
            wp_enqueue_style('ccp-admin-style', CCP_PLUGIN_URL . 'admin-style.css');
        }
    }

    public function add_action_links($links) {
        $settings_link = '<a href="admin.php?page=contentcompass-pro">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

new ContentCompassPro();
?>