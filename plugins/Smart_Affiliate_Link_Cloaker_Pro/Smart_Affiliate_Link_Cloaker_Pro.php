/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak, track, and optimize affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCloaker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sac_save_link', array($this, 'ajax_save_link'));
        add_action('wp_ajax_sac_delete_link', array($this, 'ajax_delete_link'));
        add_shortcode('sac_link', array($this, 'shortcode_link'));
        add_filter('widget_text', 'shortcode_unautop');
        add_filter('the_content', 'shortcode_unautop');
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_pro_version')) {
            // Premium features loaded
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-js', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sac-css', plugin_dir_url(__FILE__) . 'sac-style.css', array(), '1.0.0');
        wp_localize_script('sac-js', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'sac-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sac_submit'])) {
            update_option('sac_links', sanitize_text_field($_POST['sac_links']));
        }
        $links = get_option('sac_links', '[]');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Settings</h1>
            <form method="post">
                <textarea name="sac_links" rows="10" cols="80" placeholder='[{"name":"Amazon Link","url":"https://amazon.com/aff?id=123","description":"Test link"}]'><?php echo esc_textarea($links); ?></textarea>
                <p class="description">JSON array of links: {"name":"Name","url":"Affiliate URL","description":"Desc"}</p>
                <p><input type="submit" name="sac_submit" class="button-primary" value="Save Links"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: [sac_link id="0"] or [sac_link name="Amazon Link"]</p>
            <?php if (!get_option('sac_pro_version')) { ?>
            <div class="notice notice-info"><p><strong>Go Pro</strong> for analytics, A/B testing & unlimited links! <a href="#">Upgrade Now</a></p></div>
            <?php } ?>
        </div>
        <?php
    }

    public function ajax_save_link() {
        check_ajax_referer('sac_nonce', 'nonce');
        if (current_user_can('manage_options')) {
            $links = get_option('sac_links', '[]');
            $links_array = json_decode($links, true);
            $new_link = array(
                'name' => sanitize_text_field($_POST['name']),
                'url' => esc_url_raw($_POST['url']),
                'description' => sanitize_text_field($_POST['description'])
            );
            $links_array[] = $new_link;
            update_option('sac_links', json_encode($links_array));
            wp_send_json_success('Link saved');
        }
        wp_send_json_error('Unauthorized');
    }

    public function ajax_delete_link() {
        check_ajax_referer('sac_nonce', 'nonce');
        if (current_user_can('manage_options')) {
            $index = intval($_POST['index']);
            $links = get_option('sac_links', '[]');
            $links_array = json_decode($links, true);
            if (isset($links_array[$index])) {
                array_splice($links_array, $index, 1);
                update_option('sac_links', json_encode($links_array));
                wp_send_json_success('Link deleted');
            }
        }
        wp_send_json_error('Error');
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('id' => '', 'name' => ''), $atts);
        $links = get_option('sac_links', '[]');
        $links_array = json_decode($links, true);
        $link = null;
        if (!empty($atts['id']) && isset($links_array[$atts['id']])) {
            $link = $links_array[$atts['id']];
        } elseif (!empty($atts['name'])) {
            foreach ($links_array as $l) {
                if ($l['name'] === $atts['name']) {
                    $link = $l;
                    break;
                }
            }
        }
        if (!$link) return 'Link not found.';

        $slug = sanitize_title($link['name']);
        $track_id = uniqid();
        $cloaked_url = home_url('/go/' . $slug . '/' . $track_id . '/');

        // Track click (basic)
        $click_data = array(
            'url' => $link['url'],
            'timestamp' => current_time('mysql'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        );
        $clicks = get_option('sac_clicks', array());
        $clicks[$track_id] = $click_data;
        if (count($clicks) > 1000 || get_option('sac_pro_version')) { // Pro unlimited
            update_option('sac_clicks', $clicks);
        }

        return '<a href="' . $cloaked_url . '" target="_blank" rel="nofollow noopener">' . esc_html($link['name']) . '</a> ' . esc_html($link['description']);
    }

    public function activate() {
        if (!get_option('sac_links')) {
            update_option('sac_links', '[]');
        }
        if (!get_option('sac_clicks')) {
            update_option('sac_clicks', array());
        }
    }
}

// Rewrite rules for cloaked links
add_action('init', function() {
    add_rewrite_rule('^go/([^/]+)/([^/]+)/?$', 'index.php?sac_go=$matches[1]&sac_id=$matches[2]', 'top');
});
add_filter('query_vars', function($vars) {
    $vars[] = 'sac_go';
    $vars[] = 'sac_id';
    return $vars;
});
add_action('template_redirect', function() {
    $go = get_query_var('sac_go');
    $id = get_query_var('sac_id');
    if ($go && $id) {
        $links = get_option('sac_links', '[]');
        $links_array = json_decode($links, true);
        foreach ($links_array as $link) {
            if (sanitize_title($link['name']) === $go) {
                wp_redirect($link['url'], 301);
                exit;
            }
        }
    }
    flush_rewrite_rules();
});

SmartAffiliateCloaker::get_instance();

// Assets (inline for single file)
add_action('wp_head', function() { ?>
<style>
.sac-admin { background: #f1f1f1; padding: 20px; }
.sac-notice { border-left: 4px solid #00a0d2; }
</style>
<script>
jQuery(document).ready(function($) {
    // Basic analytics display (pro tease)
    $('.sac-link').on('click', function() {
        if (!<?php echo get_option('sac_pro_version') ? 'true' : 'false'; ?>) {
            alert('Upgrade to Pro for full tracking!');
        }
    });
});
</script>
<?php });

// Freemium check
function sac_is_pro() {
    return get_option('sac_pro_version') === '1.0';
}

?>