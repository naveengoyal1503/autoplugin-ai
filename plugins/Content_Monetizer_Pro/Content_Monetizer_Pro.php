<?php
/*
Plugin Name: Content Monetizer Pro
Plugin URI: https://example.com/content-monetizer-pro
Description: Auto-detects top blog posts and monetizes via affiliate links, ad placements, and paywalls.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Content_Monetizer_Pro.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class ContentMonetizerPro {
    private $option_name = 'cmp_top_posts';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'inject_monetization'));
        add_action('wp_ajax_cmp_fetch_top_posts', array($this, 'ajax_fetch_top_posts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page_content_monetizer_pro') return;
        wp_enqueue_script('cmp-admin-js', plugin_dir_url(__FILE__) . 'cmp-admin.js', array('jquery'), '1.0', true);
        wp_localize_script('cmp-admin-js', 'cmp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('cmp-ajax-nonce')));
    }

    public function add_admin_menu() {
        add_options_page('Content Monetizer Pro', 'Content Monetizer', 'manage_options', 'content_monetizer_pro', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('cmp_settings_group', 'cmp_settings');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Content Monetizer Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('cmp_settings_group'); ?>
                <?php $options = get_option('cmp_settings'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="cmp_affiliate_id">Affiliate ID</label></th>
                        <td><input name="cmp_settings[affiliate_id]" type="text" id="cmp_affiliate_id" value="<?php echo esc_attr($options['affiliate_id'] ?? ''); ?>" class="regular-text"></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="cmp_enable_ads">Enable Auto Ad Placements</label></th>
                        <td><input name="cmp_settings[enable_ads]" type="checkbox" id="cmp_enable_ads" value="1" <?php checked(1, $options['enable_ads'] ?? 0); ?>></td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="cmp_paywall_percentage">Paywall Content Percentage</label></th>
                        <td><input name="cmp_settings[paywall_percentage]" type="number" id="cmp_paywall_percentage" min="0" max="100" value="<?php echo intval($options['paywall_percentage'] ?? 30); ?>"> %</td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>

            <h2>Top Performing Posts</h2>
            <button id="cmp-refresh">Refresh Top Posts</button>
            <div id="cmp-top-posts">
                <em>Click refresh to load top posts by views (last 30 days).</em>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($){
            $('#cmp-refresh').click(function(){
                var data = {
                    action: 'cmp_fetch_top_posts',
                    security: '<?php echo wp_create_nonce('cmp-ajax-nonce'); ?>'
                };
                $('#cmp-top-posts').html('Loading...');
                $.post(ajaxurl, data, function(response){
                    if(response.success) {
                        var html = '<ol>';
                        $.each(response.data, function(i, post){
                            html += '<li><a href="' + post.url + '" target="_blank">' + post.title + '</a> (' + post.views + ' views)</li>';
                        });
                        html += '</ol>';
                        $('#cmp-top-posts').html(html);
                    } else {
                        $('#cmp-top-posts').html('Error fetching posts.');
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_fetch_top_posts() {
        check_ajax_referer('cmp-ajax-nonce', 'security');

        global $wpdb;
        // Use postmeta view count meta key '_cmp_view_count' as example
        // In real setup, better integrate with analytics or use more reliable method

        $results = $wpdb->get_results("SELECT p.ID, p.post_title, pm.meta_value AS views FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_status = 'publish' AND p.post_type = 'post' AND pm.meta_key = '_cmp_view_count'
            ORDER BY pm.meta_value+0 DESC
            LIMIT 5");

        if (!$results) wp_send_json_error();

        $data = array();
        foreach ($results as $post) {
            $data[] = array(
                'title' => $post->post_title,
                'views' => intval($post->views),
                'url' => get_permalink($post->ID),
            );
        }
        wp_send_json_success($data);
    }

    public function inject_monetization($content) {
        if (!is_single() || !is_main_query()) return $content;

        $options = get_option('cmp_settings');

        $affiliate_id = $options['affiliate_id'] ?? '';
        $enable_ads = !empty($options['enable_ads']);
        $paywall_percentage = intval($options['paywall_percentage'] ?? 30);

        // Insert affiliate links: very basic example replacing keywords 'buy' with affiliate link
        if ($affiliate_id) {
            $content = preg_replace_callback('/\b(buy|purchase|order)\b/i', function($matches) use ($affiliate_id) {
                $word = $matches;
                $url = esc_url('https://affiliate.example.com/?id=' . urlencode($affiliate_id));
                return '<a href="' . $url . '" target="_blank" rel="nofollow">' . $word . '</a>';
            }, $content);
        }

        // Insert ads block
        if ($enable_ads) {
            $ad_code = '<div style="margin:20px 0; padding:10px; background:#eee; text-align:center;">' .
                        '<strong>Advertisement:</strong> <a href="https://ads.example.com">Check this out!</a></div>';

            // Inject ad block roughly at middle of content
            $mid_point = intval(strlen($content) / 2);
            $content = substr_replace($content, $ad_code, $mid_point, 0);
        }

        // Implement paywall: show only first $paywall_percentage% content then prompt
        if ($paywall_percentage > 0 && $paywall_percentage < 100) {
            $text_length = strlen(strip_tags($content));
            $paywall_cutoff = intval($text_length * ($paywall_percentage / 100));

            // Limit to HTML tags handling approximately by substring - simple version
            $visible_content = wp_html_excerpt($content, $paywall_cutoff, '...');

            if (!is_user_logged_in()) {
                $paywall_notice = '<div style="background:#fffae6; padding:15px; margin-top:20px; border:1px solid #ffd42a; font-weight:bold;">' .
                                  'Subscribe or log in to read the full content.</div>';
                return $visible_content . $paywall_notice;
            }
        }

        return $content;
    }
}

new ContentMonetizerPro();
