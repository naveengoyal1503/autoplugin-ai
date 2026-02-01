/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart AI Content Optimizer
 * Plugin URI: https://example.com/smart-ai-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_saico_analyze', array($this, 'ajax_analyze'));
        add_action('admin_notices', array($this, 'premium_notice'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('saico-script', plugin_dir_url(__FILE__) . 'saico.js', array('jquery'), '1.0.0', true);
        wp_localize_script('saico-script', 'saico_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('saico_nonce')));
    }

    public function add_meta_box() {
        add_meta_box(
            'saico-meta-box',
            'Smart AI Content Optimizer',
            array($this, 'render_meta_box'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('saico_meta_nonce', 'saico_meta_nonce');
        $score = get_post_meta($post->ID, '_saico_score', true);
        echo '<div id="saico-results">';
        if ($score) {
            echo '<p><strong>AI Score:</strong> ' . esc_html($score) . '/100</p>';
            echo '<p id="saico-suggestions"></p>';
        }
        echo '<button id="saico-analyze" class="button button-primary">Analyze Content</button>';
        echo '<p><small>Free: Basic analysis. <a href="#" id="saico-upgrade">Upgrade to Premium</a> for AI rewriting & keywords.</small></p>';
        echo '</div>';
    }

    public function save_meta_box_data($post_id) {
        if (!isset($_POST['saico_meta_nonce']) || !wp_verify_nonce($_POST['saico_meta_nonce'], 'saico_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['saico_score'])) {
            update_post_meta($post_id, '_saico_score', sanitize_text_field($_POST['saico_score']));
        }
    }

    public function ajax_analyze() {
        check_ajax_referer('saico_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate basic AI analysis (word count, readability proxy)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $readability = $sentence_count > 0 ? round(180 - ($word_count / $sentence_count * 10), 0) : 50;
        $score = min(100, max(0, 50 + ($readability / 2) + (min(500, $word_count) / 10)));

        // Basic suggestions
        $suggestions = array();
        if ($word_count < 300) {
            $suggestions[] = 'Add more content (aim for 500+ words).';
        }
        if ($readability < 60) {
            $suggestions[] = 'Improve sentence variety for better readability.';
        }
        $suggestions_str = implode(' ', $suggestions);

        wp_send_json_success(array(
            'score' => $score,
            'suggestions' => $suggestions_str,
            'is_premium' => false // Simulate premium check
        ));
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock <strong>Smart AI Content Optimizer Premium</strong>: AI rewriting, keyword research & more! <a href="https://example.com/premium" target="_blank">Upgrade now for $9.99/mo</a></p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

SmartAIContentOptimizer::get_instance();

// Inline JS for simplicity (self-contained)
function saico_inline_js() {
    if (('post.php' === $GLOBALS['pagenow'] || 'post-new.php' === $GLOBALS['pagenow']) && (get_post_type() === 'post' || get_post_type() === 'page')) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#saico-analyze').click(function(e) {
                e.preventDefault();
                var postId = $('#post_ID').val();
                $.post(saico_ajax.ajax_url, {
                    action: 'saico_analyze',
                    post_id: postId,
                    nonce: saico_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#saico-results p:first').html('<strong>AI Score:</strong> ' + response.data.score + '/100');
                        $('#saico-suggestions').html('<strong>Suggestions:</strong> ' + response.data.suggestions);
                        if (!response.data.is_premium) {
                            $('#saico-upgrade').show();
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'saico_inline_js');