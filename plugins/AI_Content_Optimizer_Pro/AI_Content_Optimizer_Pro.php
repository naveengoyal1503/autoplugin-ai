/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyze and optimize your content for better readability, SEO, and engagement. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerPro {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->is_premium = $this->is_premium_user();
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'premium_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function is_premium_user() {
        // Simulate license check; in real, integrate with Freemius or similar
        return get_option('aco_premium_license') === 'valid';
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_nonce')));
    }

    public function add_meta_box() {
        add_meta_box('aco-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
        if (!$this->is_premium) {
            echo '<p><strong>Premium:</strong> Unlock auto-optimization & advanced AI suggestions. <a href="#" id="aco-upgrade">Upgrade Now</a></p>';
        }
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Basic analysis (free)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(($word_count / $sentence_count), 1) : 0; // Avg words per sentence
        $seo_score = min(100, (20 + ($word_count / 100) + (substr_count(strtolower($content), 'keyword') * 5))); // Simulated

        $results = array(
            'word_count' => $word_count,
            'readability' => $readability < 20 ? 'Good' : 'Improve (too complex)',
            'seo_score' => $seo_score,
            'suggestions' => $this->is_premium ? $this->premium_suggestions($content) : array('Upgrade for detailed AI suggestions!')
        );

        if (!$this->is_premium) {
            $results['upgrade'] = true;
        }

        wp_send_json_success($results);
    }

    private function premium_suggestions($content) {
        // Simulated premium AI suggestions
        return array(
            'Shorten sentences under 20 words.',
            'Add more headings for SEO.',
            'Include calls to action.'
        );
    }

    public function premium_nag() {
        if (!$this->is_premium && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> premium for auto-optimization! <a href="https://example.com/upgrade">Get it now</a></p></div>';
        }
    }

    public function activate() {
        add_option('aco_activated', time());
    }
}

// Enqueue fake JS file content inline for single-file
add_action('admin_footer', function() {
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'post') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function(e) {
                e.preventDefault();
                var postId = $('#post_ID').val();
                $.post(aco_ajax.ajax_url, {
                    action: 'aco_analyze_content',
                    nonce: aco_ajax.nonce,
                    post_id: postId
                }, function(response) {
                    if (response.success) {
                        var html = '<ul>';
                        html += '<li>Words: ' + response.data.word_count + '</li>';
                        html += '<li>Readability: ' + response.data.readability + '</li>';
                        html += '<li>SEO Score: ' + response.data.seo_score + '/100</li>';
                        $.each(response.data.suggestions, function(i, sug) {
                            html += '<li>' + sug + '</li>';
                        });
                        html += '</ul>';
                        if (response.data.upgrade) {
                            html += '<p><a href="#" id="aco-upgrade" class="button button-secondary">Upgrade to Pro</a></p>';
                        }
                        $('#aco-results').html(html);
                    }
                });
            });
            $(document).on('click', '#aco-upgrade', function(e) {
                e.preventDefault();
                alert('Redirecting to premium upgrade page... (Integrate with payment gateway)');
            });
        });
        </script>
        <?php
    }
});

AIContentOptimizerPro::get_instance();