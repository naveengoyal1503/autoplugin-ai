/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automate affiliate link cloaking, tracking, and monetization with A/B testing and performance analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateLinkManager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sal_save_link', array($this, 'ajax_save_link'));
        add_action('wp_ajax_sal_delete_link', array($this, 'ajax_delete_link'));
        add_filter('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'query_vars'));
        add_filter('template_redirect', array($this, 'template_redirect'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sal_pro_version')) {
            // Pro features check
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-admin-js', plugin_dir_url(__FILE__) . 'sal-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-admin-js', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_style('sal-admin-css', plugin_dir_url(__FILE__) . 'sal-admin.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Links', 'Affiliate Links', 'manage_options', 'sal-links', array($this, 'admin_page'));
    }

    public function admin_page() {
        $links = get_option('sal_links', array());
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <p>Upgrade to Pro for A/B testing and analytics ($49/year).</p>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Keyword</th><th>Affiliate URL</th><th>Clicks</th><th>Actions</th></tr></thead>
                <tbody>
        <?php foreach ($links as $id => $link): ?>
                    <tr>
                        <td><?php echo esc_html($link['keyword']); ?></td>
                        <td><?php echo esc_html($link['url']); ?></td>
                        <td><?php echo isset($link['clicks']) ? $link['clicks'] : 0; ?></td>
                        <td><button class="button sal-delete" data-id="<?php echo $id; ?>">Delete</button></td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
            <h2>Add New Link</h2>
            <form id="sal-form">
                <p><label>Keyword: <input type="text" name="keyword" required></label></p>
                <p><label>Affiliate URL: <input type="url" name="url" required style="width: 400px;"></label></p>
                <p><input type="submit" class="button-primary" value="Add Link"></p>
            </form>
        </div>
        <?php
    }

    public function ajax_save_link() {
        if (!current_user_can('manage_options')) wp_die();
        $links = get_option('sal_links', array());
        $id = time();
        $links[$id] = array(
            'keyword' => sanitize_text_field($_POST['keyword']),
            'url' => esc_url_raw($_POST['url']),
            'clicks' => 0
        );
        update_option('sal_links', $links);
        wp_send_json_success($links);
    }

    public function ajax_delete_link() {
        if (!current_user_can('manage_options')) wp_die();
        $links = get_option('sal_links', array());
        unset($links[$_POST['id']]);
        update_option('sal_links', $links);
        wp_send_json_success();
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^go/([^/]+)/?', 'index.php?sal_link=$matches[1]', 'top');
        flush_rewrite_rules();
    }

    public function query_vars($vars) {
        $vars[] = 'sal_link';
        return $vars;
    }

    public function template_redirect() {
        if ($slug = get_query_var('sal_link')) {
            $links = get_option('sal_links', array());
            foreach ($links as $id => &$link) {
                if (strtolower($link['keyword']) === strtolower($slug)) {
                    $link['clicks']++;
                    update_option('sal_links', $links);
                    wp_redirect($link['url'], 301);
                    exit;
                }
            }
        }
        // Content replacement
        if (is_singular()) {
            global $post;
            $content = $post->post_content;
            $links = get_option('sal_links', array());
            foreach ($links as $link) {
                $keyword = $link['keyword'];
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="/go/' . strtolower($keyword) . '" rel="nofollow noopener" target="_blank">' . $keyword . '</a>', $content, 1);
            }
            $post->post_content = $content;
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateLinkManager::get_instance();

// Inline JS and CSS for simplicity
add_action('admin_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('#sal-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ajaxurl, $(this).serialize() + '&action=sal_save_link', function(res) {
            if (res.success) location.reload();
        });
    });
    $('.sal-delete').on('click', function() {
        var id = $(this).data('id');
        $.post(ajaxurl, {action: 'sal_delete_link', id: id}, function() {
            location.reload();
        });
    });
});
</script>
<style>
#sal-form input { margin-bottom: 10px; }
</style>
<?php });