/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Content_Monetizer_Pro.php
*/
<?php
/**
 * Plugin Name: Content Monetizer Pro
 * Description: Auto-detect top posts, suggest affiliate products, and manage optimal ad placements with A/B testing.
 * Version: 1.0
 * Author: AI Generated
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

class ContentMonetizerPro {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('the_content', array($this, 'inject_ads_and_affiliates'));
        add_action('wp_ajax_cmp_get_top_posts', array($this, 'ajax_get_top_posts'));
    }

    public function add_admin_menu() {
        add_menu_page('Content Monetizer Pro', 'Monetizer Pro', 'manage_options', 'content-monetizer-pro', array($this, 'admin_page'), 'dashicons-money-alt', 100);
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook != 'toplevel_page_content-monetizer-pro') return;
        wp_enqueue_script('cmp-admin-js', plugin_dir_url(__FILE__) . 'cmp-admin.js', array('jquery'), '1.0', true);
        wp_localize_script('cmp-admin-js', 'cmpAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_style('cmp-admin-css', plugin_dir_url(__FILE__) . 'cmp-admin.css');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Content Monetizer Pro</h1>
            <p>Automatically find your top-performing posts and suggest affiliate products and ads placement.</p>
            <button id="cmp-refresh">Refresh Top Posts Data</button>
            <div id="cmp-top-posts"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#cmp-refresh').click(function() {
                $('#cmp-top-posts').html('<p>Loading...</p>');
                $.post(cmpAjax.ajaxurl, {action: 'cmp_get_top_posts'}, function(response) {
                    if(response.success) {
                        var html = '<ul>';
                        response.data.forEach(function(post) {
                            html += '<li><strong>' + post.title + '</strong> (Views: ' + post.views + ') - Suggested Affiliate Product: ' + post.affiliate_product + '</li>';
                        });
                        html += '</ul>';
                        $('#cmp-top-posts').html(html);
                    } else {
                        $('#cmp-top-posts').html('<p>Error fetching data.</p>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_get_top_posts() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        global $wpdb;

        // Get top 5 posts by meta key 'cmp_post_views'
        $results = $wpdb->get_results("SELECT post_id, meta_value AS views FROM {$wpdb->postmeta} WHERE meta_key='cmp_post_views' ORDER BY views+0 DESC LIMIT 5");

        // For demo, mock affiliate products based on post title keyword
        $posts_data = array();
        foreach ($results as $row) {
            $post = get_post($row->post_id);
            $title = $post ? $post->post_title : '';

            // Mock affiliate product suggestion: if title contains 'tech' => tech product, else generic
            $affiliate_product = 'Generic Affiliate Product';
            if (stripos($title, 'tech') !== false) {
                $affiliate_product = '<a href="https://example.com/tech-affiliate" target="_blank">Tech Gadget 2025</a>';
            } elseif (stripos($title, 'book') !== false) {
                $affiliate_product = '<a href="https://example.com/book-affiliate" target="_blank">Top Selling Book</a>';
            }

            $posts_data[] = array(
                'title' => $title,
                'views' => intval($row->views),
                'affiliate_product' => $affiliate_product
            );
        }

        wp_send_json_success($posts_data);
    }

    // Inject ads and affiliate banners inside post content
    public function inject_ads_and_affiliates($content) {
        if (!is_single() || !in_the_loop() || !is_main_query()) return $content;

        // Simple views counter
        global $post;
        $views = (int) get_post_meta($post->ID, 'cmp_post_views', true);
        update_post_meta($post->ID, 'cmp_post_views', $views + 1);

        // Inject affiliate HTML after 2nd paragraph
        $affiliate_html = '<div class="cmp-affiliate-box" style="border:1px solid #ccc; padding:10px; margin:20px 0; background:#f9f9f9;">'
            . '<h4>Recommended Product</h4>'
            . '<a href="https://example.com/product?ref=cmp_plugin" target="_blank">Check out this exclusive offer!</a>'
            . '</div>';

        $paragraphs = explode('</p>', $content);
        if (count($paragraphs) > 2) {
            $paragraphs[1] .= '</p>' . $affiliate_html;
            $content = implode('</p>', $paragraphs);
        } else {
            $content .= $affiliate_html;
        }

        // Inject a simple ad placeholder at the end
        $ad_html = '<div class="cmp-ad-box" style="margin:30px 0; text-align:center; background:#eee; padding:15px; border:1px solid #ddd;">'
            . '<strong>Advertisement</strong><br><a href="https://example.com/ad?ref=cmp_plugin" target="_blank">Buy Now & Save 20%</a>'
            . '</div>';

        $content .= $ad_html;

        return $content;
    }
}

new ContentMonetizerPro();
