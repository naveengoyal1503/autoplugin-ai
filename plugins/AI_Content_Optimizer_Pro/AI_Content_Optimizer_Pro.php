/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_optimize', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_links'));
        }
    }

    public function activate() {
        add_option('aco_license_key', '');
        add_option('aco_pro_activated', false);
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('aco-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side', 'high');
        add_meta_box('aco-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side', 'high');
    }

    public function meta_box_html($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_meta_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        $suggestions = get_post_meta($post->ID, '_aco_suggestions', true);
        echo '<div id="aco-panel">';
        echo '<p><strong>Content Score:</strong> ' . esc_html($score ?: 'Not analyzed') . '/100</p>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        echo '<button id="aco-optimize" class="button" style="display:none;">Optimize</button>';
        echo '<div id="aco-suggestions"></div>';
        echo '<div id="aco-pro-upsell"><p><em>Upgrade to Pro for unlimited optimizations and advanced AI suggestions!</em></p></div>';
        echo '</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_meta_nonce']) || !wp_verify_nonce($_POST['aco_meta_nonce'], 'aco_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function ajax_optimize() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate AI analysis with heuristics
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $this->flesch_readability($content);
        $keywords = $this->extract_keywords($content);
        $headings = preg_match_all('/<h[1-6][^>]*>/i', $content);

        $score = min(100, (int)(($word_count / 10 > 30 ? 20 : 0) + ($readability > 60 ? 25 : 10) + ($headings > 2 ? 20 : 0) + (count($keywords) > 5 ? 25 : 0) + 10));

        $suggestions = array(
            'word_count' => $word_count < 500 ? 'Aim for 500+ words for better SEO.' : 'Good length.',
            'readability' => $readability < 60 ? 'Improve readability (target 60-70).' : 'Excellent readability.',
            'headings' => $headings < 3 ? 'Add more H2/H3 headings.' : 'Good structure.',
            'keywords' => 'Primary keywords: ' . implode(', ', array_slice($keywords, 0, 3)),
        );

        update_post_meta($post_id, '_aco_score', $score);
        update_post_meta($post_id, '_aco_suggestions', $suggestions);

        if (get_option('aco_pro_activated')) {
            // Pro: Auto-optimize
            $optimized = $this->generate_optimized_content($content, $suggestions);
            wp_update_post(array('ID' => $post_id, 'post_content' => $optimized));
        }

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
    }

    private function flesch_readability($text) {
        $sentence_count = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentence_count);
        $word_count = str_word_count($text);
        $syllables = $this->count_syllables($text);
        if ($sentence_count === 0 || $word_count === 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllables / $word_count;
        return 206.835 - 1.015 * $asl - 84.6 * $asw;
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy]/', $word) - preg_match_all('/ed|ing|es$/i', $word);
        }
        return max(1, $syllables);
    }

    private function extract_keywords($content) {
        $words = explode(' ', strtolower(strip_tags($content)));
        $freq = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($freq);
        return array_keys(array_slice($freq, 0, 10, true));
    }

    private function generate_optimized_content($content, $suggestions) {
        // Pro feature simulation: Add headings, improve structure
        $content .= '\n\n<h2>Conclusion</h2><p>Optimized content summary here.</p>';
        return $content;
    }

    public function plugin_links($links) {
        $settings_link = '<a href="options-general.php?page=aco-settings">Settings</a>';
        $pro_link = '<a style="color:#1d9bf0; font-weight:bold;" href="https://example.com/pro">Go Pro</a>';
        array_unshift($links, $settings_link, $pro_link);
        return $links;
    }
}

AIContentOptimizer::get_instance();

// Admin JS (embedded for single file)
function aco_embed_js() {
    if (isset($_GET['page']) && $_GET['page'] === 'aco-settings') return;
    ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    jQuery('#aco-analyze').click(function(e) {
        e.preventDefault();
        var post_id = jQuery('#post_ID').val();
        jQuery.post(aco_ajax.ajax_url, {
            action: 'aco_optimize',
            nonce: aco_ajax.nonce,
            post_id: post_id
        }, function(response) {
            if (response.success) {
                jQuery('#aco-panel strong').text(response.data.score);
                var s = '';
                jQuery.each(response.data.suggestions, function(k,v) {
                    s += '<p><strong>' + k + ':</strong> ' + v + '</p>';
                });
                jQuery('#aco-suggestions').html(s);
                jQuery('#aco-optimize').show();
            }
        });
    });
});
</script>
    <?php
}
add_action('admin_footer-post.php', 'aco_embed_js');
add_action('admin_footer-post-new.php', 'aco_embed_js');

// Minimal CSS

function aco_embed_css() {
    echo '<style>#aco-panel { padding: 10px; } #aco-panel button { margin: 5px 0; }</style>';
}
add_action('admin_head-post.php', 'aco_embed_css');
add_action('admin_head-post-new.php', 'aco_embed_css');