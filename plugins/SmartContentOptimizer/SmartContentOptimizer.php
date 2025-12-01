<?php
/*
Plugin Name: SmartContentOptimizer
Description: AI-powered real-time content SEO and optimization assistant for WordPress posts and pages.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartContentOptimizer.php
*/

if ( !defined( 'ABSPATH' ) ) exit;

class SmartContentOptimizer {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post_meta')); 
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_sco_optimize_content', array($this, 'ajax_optimize_content'));
    }

    public function add_meta_box() {
        add_meta_box('sco_meta_box', 'Smart Content Optimizer', array($this, 'render_meta_box'), ['post','page'], 'side', 'high');
    }

    public function render_meta_box($post) {
        wp_nonce_field('sco_save_meta', 'sco_nonce');
        $optimized = get_post_meta($post->ID, '_sco_optimized_content', true);
        ?>
        <button type="button" class="button button-primary" id="sco-optimize-btn">Optimize Content</button>
        <div id="sco-result" style="margin-top:10px; font-size: small;"></div>
        <?php
    }

    public function save_post_meta($post_id) {
        if (!isset($_POST['sco_nonce']) || !wp_verify_nonce($_POST['sco_nonce'], 'sco_save_meta')) {
            return;
        }
        // Nothing to save for now as optimizations run via AJAX
    }

    public function enqueue_admin_scripts($hook) {
        if (in_array($hook, ['post.php', 'post-new.php'])) {
            wp_enqueue_script('sco_admin_js', plugin_dir_url(__FILE__) . 'sco-admin.js', array('jquery'), '1.0', true);
            wp_localize_script('sco_admin_js', 'sco_ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
        }
    }

    public function ajax_optimize_content() {
        if ( !current_user_can('edit_posts') ) {
            wp_send_json_error('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        if (!$content) {
            wp_send_json_error('No content found');
        }

        // Simulated AI optimization process (in real app connect to AI API)
        $optimized_content = $this->simulate_ai_optimization($content);

        // Save optimized content as meta (for demo, not replacing post content)
        update_post_meta($post_id, '_sco_optimized_content', $optimized_content);

        wp_send_json_success(array('optimized_content' => $optimized_content));
    }

    private function simulate_ai_optimization($content) {
        // This is placeholder logic to simulate AI modification
        // In production use external AI APIs or algorithms
        $seo_friendly = preg_replace('/\s+/', ' ', strip_tags($content));
        $seo_friendly = substr($seo_friendly, 0, 500) . '...';
        $seo_friendly .= '\n\n[This is an AI-optimized summary and SEO friendly snippet.]';
        return $seo_friendly;
    }
}

new SmartContentOptimizer();

// Below admin JS is inline for a single file demo
add_action('admin_footer', function() {
    $screen = get_current_screen();
    if (in_array($screen->base, ['post', 'post-new'])) {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#sco-optimize-btn').on('click', function(){
            var post_id = $('#post_ID').val();
            $('#sco-result').text('Optimizing...');
            $.post(sco_ajax_obj.ajax_url, {
                action: 'sco_optimize_content',
                post_id: post_id
            }, function(response){
                if(response.success){
                    $('#sco-result').html('<strong>Optimized Content Snippet:</strong><br><pre style="white-space: pre-wrap; background:#f9f9f9; padding:10px; max-height:200px; overflow:auto;">'+ response.data.optimized_content +'</pre>');
                } else {
                    $('#sco-result').text('Failed: ' + response.data);
                }
            });
        });
    });
    </script>
    <?php
    }
});
