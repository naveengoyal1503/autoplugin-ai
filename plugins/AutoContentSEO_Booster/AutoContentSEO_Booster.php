<?php
/*
Plugin Name: AutoContentSEO Booster
Plugin URI: https://example.com/autocontentseo-booster
Description: Automatically generates SEO-optimized content snippets and metadata suggestions using AI.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AutoContentSEO_Booster.php
License: GPLv2 or later
Text Domain: autocontentseo-booster
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AutoContentSEOBooster {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_seo_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_acsb_generate_snippet', array($this, 'ajax_generate_snippet'));
    }

    public function add_meta_box() {
        add_meta_box(
            'acsb_seo_meta_box',
            __('SEO Optimizer', 'autocontentseo-booster'),
            array($this, 'render_meta_box'),
            ['post', 'page'],
            'side',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('acsb_save_seo_meta', 'acsb_seo_meta_nonce');

        $seo_snippet = get_post_meta($post->ID, '_acsb_seo_snippet', true);
        $seo_title = get_post_meta($post->ID, '_acsb_seo_title', true);
        $seo_description = get_post_meta($post->ID, '_acsb_seo_description', true);

        echo '<p><button type="button" class="button button-primary" id="acsb-generate-btn">Generate SEO Snippet</button></p>';
        echo '<p><label for="acsb_seo_title">'.__('SEO Title', 'autocontentseo-booster').'</label><br />';
        echo '<input type="text" id="acsb_seo_title" name="acsb_seo_title" value="' . esc_attr($seo_title) . '" style="width:100%;" /></p>';

        echo '<p><label for="acsb_seo_description">'.__('SEO Description', 'autocontentseo-booster').'</label><br />';
        echo '<textarea id="acsb_seo_description" name="acsb_seo_description" rows="4" style="width:100%;">' . esc_textarea($seo_description) . '</textarea></p>';

        echo '<p><label>'.__('SEO Snippet Preview', 'autocontentseo-booster').'</label><br />';
        echo '<div id="acsb-seo-snippet-preview" style="border:1px solid #ddd;padding:10px;background:#fafafa;font-family:Arial,sans-serif;">' . esc_html($seo_snippet) . '</div></p>';
    }

    public function save_seo_meta($post_id) {
        if (!isset($_POST['acsb_seo_meta_nonce']) || !wp_verify_nonce($_POST['acsb_seo_meta_nonce'], 'acsb_save_seo_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['acsb_seo_title'])) {
            update_post_meta($post_id, '_acsb_seo_title', sanitize_text_field($_POST['acsb_seo_title']));
        }

        if (isset($_POST['acsb_seo_description'])) {
            update_post_meta($post_id, '_acsb_seo_description', sanitize_textarea_field($_POST['acsb_seo_description']));
        }

        // Regenerate snippet based on title and description
        $title = sanitize_text_field($_POST['acsb_seo_title']);
        $description = sanitize_textarea_field($_POST['acsb_seo_description']);

        $snippet = $this->create_snippet_preview($title, $description);

        update_post_meta($post_id, '_acsb_seo_snippet', $snippet);
    }

    private function create_snippet_preview($title, $description) {
        $site_name = get_bloginfo('name');
        $snippet = "$title | $site_name\n$description";
        return $snippet;
    }

    public function enqueue_admin_scripts($hook) {
        if ('post.php' != $hook && 'post-new.php' != $hook) {
            return;
        }
        wp_enqueue_script('acsb-admin-js', plugin_dir_url(__FILE__) . 'acsb-admin.js', array('jquery'), '1.0', true);
        wp_localize_script('acsb-admin-js', 'acsb_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function ajax_generate_snippet() {
        if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
            wp_send_json_error('Invalid post ID');
            wp_die();
        }
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
            wp_die();
        }

        // Simulate AI generation for SEO title & description:
        $content = strip_tags($post->post_content);
        $summary = wp_trim_words($content, 25, '...');
        $title = $post->post_title;

        $seo_title = $title . ' | ' . get_bloginfo('name');
        $seo_description = $summary;

        $seo_snippet = $this->create_snippet_preview($seo_title, $seo_description);

        wp_send_json_success(array(
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'seo_snippet' => $seo_snippet
        ));
        wp_die();
    }
}

AutoContentSEOBooster::get_instance();

// Inline JS for admin (single file plugin limitation)
add_action('admin_footer', function() {
    $screen = get_current_screen();
    if ($screen->base === 'post') {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#acsb-generate-btn').on('click', function(e) {
                e.preventDefault();
                var postID = $('#post_ID').val();
                var btn = $(this);
                btn.prop('disabled', true).text('Generating...');
                $.post(ajaxurl, {
                    action: 'acsb_generate_snippet',
                    post_id: postID
                }, function(response) {
                    if (response.success) {
                        $('#acsb_seo_title').val(response.data.seo_title);
                        $('#acsb_seo_description').val(response.data.seo_description);
                        $('#acsb-seo-snippet-preview').text(response.data.seo_snippet);
                    } else {
                        alert('Error generating snippet: ' + response.data);
                    }
                    btn.prop('disabled', false).text('Generate SEO Snippet');
                });
            });
        });
        </script>
        <?php
    }
});
