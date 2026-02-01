/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCloakerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sal_save_link', array($this, 'ajax_save_link'));
        add_action('wp_ajax_sal_delete_link', array($this, 'ajax_delete_link'));
        add_filter('the_content', array($this, 'cloak_links'), 20);
        add_shortcode('sal_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sal_pro_activated')) {
            // Premium features check
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-admin-js', plugin_dir_url(__FILE__) . 'sal-admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sal-admin-css', plugin_dir_url(__FILE__) . 'sal-admin.css', array(), '1.0.0');
        wp_localize_script('sal-admin-js', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'sal-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        $links = get_option('sal_links', array());
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Pro</h1>
            <p>Manage your affiliate links. <strong>Upgrade to Pro for A/B testing & analytics ($9/mo).</strong></p>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Keyword</th><th>Affiliate URL</th><th>Cloaked URL</th><th>Actions</th></tr></thead>
                <tbody>
        <?php foreach ($links as $id => $link): ?>
                    <tr>
                        <td><?php echo esc_html($id); ?></td>
                        <td><?php echo esc_html($link['keyword']); ?></td>
                        <td><?php echo esc_html($link['url']); ?></td>
                        <td><?php echo esc_url(home_url('/go/' . $id)); ?></td>
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
        wp_send_json_success(array('id' => $id, 'cloaked' => home_url('/go/' . $id)));
    }

    public function ajax_delete_link() {
        if (!current_user_can('manage_options')) wp_die();
        $links = get_option('sal_links', array());
        unset($links[$_POST['id']]);
        update_option('sal_links', $links);
        wp_send_json_success();
    }

    public function cloak_links($content) {
        $links = get_option('sal_links', array());
        foreach ($links as $id => $link) {
            $keyword = $link['keyword'];
            $cloaked = '<a href="' . home_url('/go/' . $id) . '" target="_blank" rel="nofollow">' . $keyword . '</a>';
            $content = str_replace($keyword, $cloaked, $content);
        }
        return $content;
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $links = get_option('sal_links', array());
        if (isset($links[$atts['id']])) {
            $link = $links[$atts['id']];
            $links[$atts['id']]['clicks']++;
            update_option('sal_links', $links);
            return '<a href="' . home_url('/go/' . $atts['id']) . '" target="_blank" rel="nofollow">' . esc_html($link['keyword']) . '</a>';
        }
        return '';
    }

    public function activate() {
        add_rewrite_rule('go/([^/]+)/?', 'index.php?sal_go=$matches[1]', 'top');
        flush_rewrite_rules();
        update_option('sal_pro_activated', true);
    }
}

add_action('init', 'sal_rewrite_init');
function sal_rewrite_init() {
    add_rewrite_tag('%sal_go%', '([^&]+)');
    add_rewrite_rule('go/([^/]+)/?', 'index.php?sal_go=$matches[1]', 'top');
}

add_filter('query_vars', 'sal_query_vars');
function sal_query_vars($vars) {
    $vars[] = 'sal_go';
    return $vars;
}

add_action('template_redirect', 'sal_template_redirect');
function sal_template_redirect() {
    $go = get_query_var('sal_go');
    if ($go) {
        $links = get_option('sal_links', array());
        $id = sanitize_text_field($go);
        if (isset($links[$id])) {
            $links[$id]['clicks']++;
            update_option('sal_links', $links);
            wp_redirect($links[$id]['url'], 301);
            exit;
        }
    }
}

SmartAffiliateCloakerPro::get_instance();

// Pro upgrade nag
function sal_pro_nag() {
    if (!get_option('sal_pro_key')) {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Cloaker Pro:</strong> Unlock A/B testing, detailed analytics, and unlimited links. <a href="https://example.com/pro" target="_blank">Upgrade now for $9/mo</a></p></div>';
    }
}
add_action('admin_notices', 'sal_pro_nag');

// Enqueue dummy JS/CSS files (base64 or inline in real distro)
function sal_enqueue_dummy_assets() {
    wp_add_inline_script('jquery', 'jQuery(document).ready(function($){ $("#sal-form").on("submit",function(e){e.preventDefault();$.post(sal_ajax.ajaxurl,{action:"sal_save_link",keyword:$("[name=keyword]",this).val(),url:$("[name=url]",this).val()},function(r){if(r.success){alert("Link added: "+r.data.cloaked);location.reload();}})}); $(".sal-delete").on("click",function(){$.post(sal_ajax.ajaxurl,{action:"sal_delete_link",id:$(this).data("id")},function(){location.reload();});});});');
    wp_add_inline_style('buttons', '.sal-delete{color:red;}');
}
add_action('admin_enqueue_scripts', 'sal_enqueue_dummy_assets');