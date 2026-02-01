/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('admin_notices', array($this, 'pro_notice'));
        register_setting('aco_settings', 'aco_pro_key');
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('aco-js', plugin_dir_url(__FILE__) . 'aco.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-js', 'aco_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        wp_enqueue_style('aco-css', plugin_dir_url(__FILE__) . 'aco.css', array(), '1.0.0');
    }

    public function add_meta_box() {
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('aco-meta-box', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_nonce');
        $score = get_post_meta($post->ID, '_aco_score', true);
        $suggestions = get_post_meta($post->ID, '_aco_suggestions', true);
        echo '<div id="aco-score">Score: <strong>' . esc_html($score ?: 'Not analyzed') . '%</strong></div>';
        echo '<button id="aco-analyze" class="button">Analyze Content</button>';
        echo '<button id="aco-optimize" class="button button-primary" style="display:none;">Optimize (Pro)</button>';
        if ($suggestions) {
            echo '<div id="aco-suggestions"><h4>Suggestions:</h4><ul>';
            foreach ($suggestions as $sugg) {
                echo '<li>' . esc_html($sugg) . '</li>';
            }
            echo '</ul></div>';
        }
        echo '<div id="aco-loader" style="display:none;">Analyzing...</div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_nonce']) || !wp_verify_nonce($_POST['aco_nonce'], 'aco_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajax_optimize_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate AI analysis (free version)
        $word_count = str_word_count($content);
        $score = min(95, 50 + ($word_count / 10));
        $suggestions = array(
            'Add more headings for structure.',
            'Include keywords naturally.',
            'Shorten sentences for readability.'
        );

        update_post_meta($post_id, '_aco_score', $score);
        update_post_meta($post_id, '_aco_suggestions', $suggestions);

        // Pro check (simulate license)
        $pro_key = get_option('aco_pro_key');
        $is_pro = !empty($pro_key) && hash('sha256', $pro_key) === 'pro_verified_hash';

        wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions, 'is_pro' => $is_pro));
    }

    public function pro_notice() {
        if (!current_user_can('manage_options')) return;
        $screen = get_current_screen();
        if ($screen->id === 'settings_page_aco-settings') return;
        echo '<div class="notice notice-info"><p>Unlock AI auto-rewrites with <strong>AI Content Optimizer Pro</strong> - <a href="' . admin_url('options-general.php?page=aco-settings') . '">Upgrade now</a>!</p></div>';
    }
}

// Settings page
add_action('admin_menu', function() {
    add_options_page('AI Content Optimizer Settings', 'AI Content Optimizer', 'manage_options', 'aco-settings', function() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aco_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="aco_pro_key" value="<?php echo esc_attr(get_option('aco_pro_key')); ?>" class="regular-text" /> <p class="description">Enter your Pro key to unlock advanced features.</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    });
});

new AIContentOptimizer();

// Frontend display of score
add_filter('the_content', function($content) {
    if (is_single()) {
        global $post;
        $score = get_post_meta($post->ID, '_aco_score', true);
        if ($score) {
            $content .= '<div class="aco-badge">AI Score: ' . $score . '% <span class="dashicons dashicons-star-filled"></span></div>';
        }
    }
    return $content;
});

// Basic CSS
add_action('admin_head-post.php', function() {
    echo '<style>#aco-score {font-size:16px;margin:10px 0;} #aco-loader {color:#0073aa;} .aco-badge {position:fixed;bottom:20px;right:20px;background:#0073aa;color:white;padding:10px;border-radius:5px;}</style>';
});

// JS placeholder (inline for single file)
add_action('admin_footer-post.php', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#aco-analyze').click(function() {
            $('#aco-loader').show();
            $.post(aco_ajax.ajaxurl, {
                action: 'aco_optimize_content',
                nonce: aco_ajax.nonce,
                post_id: $('#post_ID').val()
            }, function(res) {
                $('#aco-loader').hide();
                $('#aco-score strong').text(res.data.score + '%');
                if (res.data.suggestions) {
                    let suggHtml = '<h4>Suggestions:</h4><ul>';
                    res.data.suggestions.forEach(function(s) { suggHtml += '<li>' + s + '</li>'; });
                    suggHtml += '</ul>';
                    $('#aco-suggestions').html(suggHtml);
                }
                if (res.data.is_pro) {
                    $('#aco-optimize').show();
                }
            });
        });
    });
    </script>
    <?php
});