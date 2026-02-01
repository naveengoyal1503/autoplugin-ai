/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for better SEO and readability with AI insights. Freemium version.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerLite {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aco_analysis_count', 0);
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('aco-script', plugin_dir_url(__FILE__) . 'aco-script.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-script', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
        }
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer Settings', 'AI Content Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Lite</h1>
            <p>Free analyses left this month: <strong id="aco-count"><?php echo 5 - get_option('aco_analysis_count', 0); ?></strong>/5</p>
            <p><a href="https://example.com/premium" target="_blank" class="button button-primary">Upgrade to Premium - Unlimited Analyses!</a></p>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#aco-count').text(5 - <?php echo get_option('aco_analysis_count', 0); ?>);
            });
        </script>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('aco-content-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $count = get_option('aco_analysis_count', 0);
        if ($count >= 5) {
            echo '<p>Free limit reached. <a href="https://example.com/premium" target="_blank">Upgrade to Premium</a></p>';
            return;
        }
        echo '<button id="aco-analyze" class="button button-secondary">Analyze Content</button>';
        echo '<div id="aco-results"></div>';
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (get_option('aco_analysis_count', 0) >= 5) {
            wp_send_json_error('Limit reached. Upgrade to premium.');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in premium, integrate real API like OpenAI)
        $word_count = str_word_count(strip_tags($content));
        $readability = $word_count > 300 ? 'Good' : 'Improve length';
        $seo_score = min(100, 50 + ($word_count / 10));
        $suggestions = array(
            'Add more headings (H2/H3) for structure.',
            'Include keywords in first paragraph.',
            'Aim for 300+ words for better SEO.'
        );

        update_option('aco_analysis_count', get_option('aco_analysis_count', 0) + 1);

        ob_start();
        ?>
        <div class="aco-results">
            <p><strong>SEO Score:</strong> <?php echo $seo_score; ?>/100</p>
            <p><strong>Readability:</strong> <?php echo $readability; ?></p>
            <p><strong>Word Count:</strong> <?php echo $word_count; ?></p>
            <h4>Suggestions:</h4>
            <ul><?php foreach ($suggestions as $sug) { echo '<li>' . esc_html($sug) . '</li>'; } ?></ul>
            <p>Free analyses left: <?php echo 5 - get_option('aco_analysis_count', 0); ?>/5</p>
            <p><a href="https://example.com/premium" target="_blank">Go Premium for AI-powered advanced analysis & unlimited use!</a></p>
        </div>
        <?php
        wp_send_json_success(ob_get_clean());
    }
}

new AIContentOptimizerLite();

// Inline JS for simplicity (self-contained)
function aco_add_inline_script() {
    if (isset($_GET['post'])) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function() {
                var postId = $('#post_ID').val();
                $('#aco-results').html('<p>Analyzing...</p>');
                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    post_id: postId,
                    nonce: aco_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#aco-results').html(response.data);
                    } else {
                        $('#aco-results').html('<p>Error: ' + response.data + '</p>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer-post.php', 'aco_add_inline_script');
add_action('admin_footer-post-new.php', 'aco_add_inline_script');
?>