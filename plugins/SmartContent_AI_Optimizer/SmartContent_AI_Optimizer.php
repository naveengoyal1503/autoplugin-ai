<?php
/*
Plugin Name: SmartContent AI Optimizer
Plugin URI: https://example.com/smartcontent-ai-optimizer
Description: AI-powered plugin to optimize WordPress posts for SEO and readability.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartContent_AI_Optimizer.php
License: GPL2
*/

if(!defined('ABSPATH')) exit;

class SmartContentAIOptimizer {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_optimization_metabox'));
        add_action('save_post', array($this, 'maybe_optimize_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_smartcontent_ai_optimize', array($this, 'ajax_optimize_handler'));
    }

    public function add_optimization_metabox() {
        add_meta_box('smartcontent_ai_optimizer', 'SmartContent AI Optimizer', array($this, 'render_metabox'), 'post', 'side', 'high');
    }

    public function render_metabox($post) {
        echo '<p>Click below to analyze and improve your post content using AI-powered suggestions on SEO, readability, and engagement.</p>';
        echo '<p><button type="button" class="button button-primary" id="smartcontent-optimize-btn">Optimize Content</button></p>';
        echo '<div id="smartcontent-results" style="margin-top:10px;"></div>';
        wp_nonce_field('smartcontent_optimize_nonce', 'smartcontent_optimize_nonce_field');
    }

    public function enqueue_admin_scripts($hook) {
        if($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('smartcontent-admin-js', plugin_dir_url(__FILE__).'/smartcontent-admin.js', array('jquery'), '1.0', true);
            wp_localize_script('smartcontent-admin-js', 'SmartContentAJAX', array('ajax_url' => admin_url('admin-ajax.php')));
        }
    }

    public function maybe_optimize_content($post_id) {
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if(!isset($_POST['smartcontent_optimize_nonce_field']) || !wp_verify_nonce($_POST['smartcontent_optimize_nonce_field'], 'smartcontent_optimize_nonce')) return;
        if(!current_user_can('edit_post', $post_id)) return;
        // No automatic optimization on save; user triggers via AJAX button
    }

    public function ajax_optimize_handler() {
        if(!isset($_POST['post_id']) || !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'smartcontent_optimize_nonce')) {
            wp_send_json_error('Invalid request');
        }

        $post_id = intval($_POST['post_id']);
        if(!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permission denied');
        }

        $post = get_post($post_id);
        if(!$post) {
            wp_send_json_error('Post not found');
        }

        $original_content = $post->post_content;

        // Simple Example AI Optimization Simulation
        $optimized_content = $this->ai_optimize_content($original_content);

        // Suggest SEO title and meta description (mocked)
        $seo_title = $this->generate_seo_title($post->post_title);
        $meta_description = $this->generate_meta_description($original_content);

        wp_send_json_success(array(
            'optimized_content' => $optimized_content,
            'seo_title' => $seo_title,
            'meta_description' => $meta_description
        ));
    }

    private function ai_optimize_content($content) {
        // In a real plugin, connect to an AI API here.
        // For demonstration, add an SEO and readability improvement message.
        $improvement_note = "\n\n<!-- Optimized: Added keyword-rich intro and improved readability. -->";
        // Dummy example: append a summary or note
        return $content . $improvement_note;
    }

    private function generate_seo_title($title) {
        // Mock: append a strong keyword (example)
        return $title . ' | Optimized for SEO';
    }

    private function generate_meta_description($content) {
        // Mock: take first 150 chars as meta description
        $clean_content = wp_strip_all_tags($content);
        $desc = substr($clean_content, 0, 150);
        return $desc . (strlen($clean_content) > 150 ? '...' : '');
    }
}

new SmartContentAIOptimizer();

// JavaScript for AJAX button
add_action('admin_footer', function() {
    global $post;
    if('post' !== get_post_type($post)) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#smartcontent-optimize-btn').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            button.attr('disabled', 'disabled').text('Optimizing...');
            $('#smartcontent-results').html('');
            $.post(ajaxurl, {
                action: 'smartcontent_ai_optimize',
                post_id: <?php echo intval($post->ID); ?>,
                nonce: $('input[name="smartcontent_optimize_nonce_field"]').val()
            }, function(response) {
                if(response.success) {
                    var res = response.data;
                    var resultHtml = '<h4>Optimization Suggestions:</h4>' +
                        '<p><strong>SEO Title:</strong> ' + res.seo_title + '</p>' +
                        '<p><strong>Meta Description:</strong> ' + res.meta_description + '</p>' +
                        '<p><strong>Optimized Content Preview:</strong></p>' +
                        '<textarea style="width:100%;height:150px;" readonly>' + res.optimized_content + '</textarea>';
                    $('#smartcontent-results').html(resultHtml);
                } else {
                    $('#smartcontent-results').html('<p style="color:red;">Error: ' + response.data + '</p>');
                }
                button.removeAttr('disabled').text('Optimize Content');
            });
        });
    });
    </script>
    <?php
});
