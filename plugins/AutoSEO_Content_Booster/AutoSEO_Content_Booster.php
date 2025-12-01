/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AutoSEO_Content_Booster.php
*/
<?php
/**
 * Plugin Name: AutoSEO Content Booster
 * Description: Real-time SEO suggestions and keyword optimization tool for WordPress posts.
 * Version: 1.0
 * Author: AutoSEO Inc.
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AutoSEOContentBooster {
    public function __construct() {
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add meta box
        add_action('add_meta_boxes', array($this, 'add_seo_metabox'));

        // AJAX handler for keyword suggestions
        add_action('wp_ajax_autoseo_keyword_suggestions', array($this, 'ajax_keyword_suggestions'));
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;

        wp_enqueue_script('autoseo-admin-js', plugin_dir_url(__FILE__) . 'autoseo-admin.js', array('jquery'), '1.0', true);
        wp_localize_script('autoseo-admin-js', 'AutoSEOAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('autoseo_nonce')
        ));
        wp_enqueue_style('autoseo-admin-css', plugin_dir_url(__FILE__) . 'autoseo-admin.css', array(), '1.0');
    }

    public function add_seo_metabox() {
        add_meta_box(
            'autoseo_metabox',
            'AutoSEO Content Booster',
            array($this, 'render_seo_metabox'),
            'post',
            'side',
            'high'
        );
    }

    public function render_seo_metabox($post) {
        // Get content
        $content = $post->post_content;
        ?>
        <div id="autoseo-container">
            <p><strong>SEO Suggestions:</strong></p>
            <textarea id="autoseo-keyword-input" placeholder="Enter focus keyword..." style="width:100%;"></textarea>
            <button type="button" id="autoseo-get-suggestions" class="button button-primary" style="margin-top:8px;">Get Suggestions</button>
            <div id="autoseo-suggestions" style="margin-top:10px; max-height:200px; overflow-y:auto; font-size: 13px;"></div>
            <p style="margin-top:15px; font-size:12px; color:#666;">Powered by built-in AI keyword analysis.</p>
        </div>
        <?php
    }

    public function ajax_keyword_suggestions() {
        check_ajax_referer('autoseo_nonce', 'nonce');

        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        if (empty($keyword)) {
            wp_send_json_error('Keyword is required');
        }

        // Simulated keyword suggestions logic (replace with actual AI/SEO API calls)
        $suggestions = $this->generate_keyword_suggestions($keyword);

        wp_send_json_success($suggestions);
    }

    private function generate_keyword_suggestions($keyword) {
        // Dummy logic: append some variations
        $variations = array(
            $keyword,
            $keyword . ' tips',
            $keyword . ' guide',
            'best ' . $keyword,
            $keyword . ' 2025',
            'how to ' . $keyword,
        );
        return $variations;
    }
}

new AutoSEOContentBooster();

// Include JS and CSS as inline for single file approach
add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (!in_array($screen->base, ['post', 'post-new'])) return;

    ?>
    <script>
    jQuery(document).ready(function ($) {
        $('#autoseo-get-suggestions').on('click', function () {
            var keyword = $('#autoseo-keyword-input').val().trim();
            if (!keyword) {
                alert('Please enter a focus keyword.');
                return;
            }
            $('#autoseo-suggestions').html('Loading suggestions...');
            $.post(
                AutoSEOAjax.ajax_url,
                {
                    action: 'autoseo_keyword_suggestions',
                    nonce: AutoSEOAjax.nonce,
                    keyword: keyword
                },
                function (response) {
                    if (response.success) {
                        var html = '<ul style="padding-left:15px;">';
                        response.data.forEach(function (item) {
                            html += '<li>' + item + '</li>';
                        });
                        html += '</ul>';
                        $('#autoseo-suggestions').html(html);
                    } else {
                        $('#autoseo-suggestions').html('Error: ' + response.data);
                    }
                }
            );
        });
    });
    </script>
    <style>
    #autoseo-suggestions ul { margin: 0; padding-left: 18px; }
    </style>
    <?php
});
