/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Plugin URI: https://example.com/smart-affiliate-manager
 * Description: Manage, cloak, and track affiliate links effortlessly. Free version with Pro upgrade.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateManager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('wp_ajax_save_link', array($this, 'ajax_save_link'));
            add_action('wp_ajax_delete_link', array($this, 'ajax_delete_link'));
        } else {
            add_filter('the_content', array($this, 'cloak_links'));
            add_action('template_redirect', array($this, 'handle_redirect'));
        }
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
    }

    public function activate() {
        add_option('sam_links', array());
        add_option('sam_pro_active', false);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function admin_menu() {
        add_options_page('Affiliate Links', 'Affiliate Links', 'manage_options', 'smart-affiliate-manager', array($this, 'admin_page'));
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page_smart-affiliate-manager') return;
        wp_enqueue_script('sam-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sam-admin', 'sam_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sam_nonce')));
        wp_enqueue_style('sam-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function admin_page() {
        $links = get_option('sam_links', array());
        $pro_active = get_option('sam_pro_active', false);
        include 'admin-page.php'; // Assume inline HTML below
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager <?php echo $pro_active ? '<span style="color:green;">[PRO]</span>' : '[Free]'; ?></h1>
            <p>Manage your affiliate links. <a href="#" id="sam-pro-upsell">Upgrade to Pro for analytics & more!</a></p>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Alias</th><th>Target URL</th><th>Actions</th></tr></thead>
                <tbody id="sam-links-list">
                    <?php foreach ($links as $id => $link): ?>
                    <tr>
                        <td><?php echo esc_html($id); ?></td>
                        <td><?php echo esc_html($link['alias']); ?></td>
                        <td><?php echo esc_html($link['url']); ?></td>
                        <td><button class="button delete-link" data-id="<?php echo $id; ?>">Delete</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h2>Add New Link</h2>
            <form id="sam-add-link">
                <p><label>Alias (e.g., go/amazon):</label> <input type="text" name="alias" required></p>
                <p><label>Target URL:</label> <input type="url" name="url" required style="width:100%;"></p>
                <?php wp_nonce_field('sam_nonce'); ?>
                <p><button type="submit" class="button-primary">Add Link</button></p>
            </form>
            <script>/* Inline JS for demo, move to file */</script>
        </div>
        <?php
    }

    public function ajax_save_link() {
        check_ajax_referer('sam_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $links = get_option('sam_links', array());
        $alias = sanitize_text_field($_POST['alias']);
        $url = esc_url_raw($_POST['url']);
        if (isset($links[$alias])) {
            wp_send_json_error('Alias exists');
        }
        $links[$alias] = array('url' => $url, 'clicks' => 0);
        update_option('sam_links', $links);
        wp_send_json_success($links);
    }

    public function ajax_delete_link() {
        check_ajax_referer('sam_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $links = get_option('sam_links', array());
        $alias = sanitize_text_field($_POST['id']);
        unset($links[$alias]);
        update_option('sam_links', $links);
        wp_send_json_success($links);
    }

    public function cloak_links($content) {
        $links = get_option('sam_links', array());
        foreach ($links as $alias => $link) {
            $shortcode = '[afflink id="' . $alias . '"]';
            $content = str_replace($alias, $shortcode, $content);
        }
        return $content;
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $links = get_option('sam_links', array());
        if (!isset($links[$atts['id']])) return 'Invalid link';
        $link = $links[$atts['id']];
        $nonce = wp_create_nonce('sam_click_' . $atts['id']);
        $redirect_url = home_url('/go/' . $atts['id'] . '/?nocache=' . time() . '&nonce=' . $nonce);
        return '<a href="' . esc_url($redirect_url) . '" target="_blank" rel="nofollow">' . esc_html($atts['id']) . '</a>';
    }

    public function handle_redirect() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (strpos($path, '/go/') !== 0) return;
        $parts = explode('/', trim($path, '/'));
        if (count($parts) < 2 || $parts !== 'go') return;
        $alias = sanitize_text_field($parts[1]);
        $links = get_option('sam_links', array());
        if (!isset($links[$alias])) {
            wp_die('Link not found');
        }
        // Verify nonce for security
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'sam_click_' . $alias)) {
            wp_die('Invalid access');
        }
        $links[$alias]['clicks']++;
        update_option('sam_links', $links);
        if (get_option('sam_pro_active')) {
            // Pro: Log analytics
            error_log('Pro click on ' . $alias);
        }
        wp_redirect($links[$alias]['url'], 301);
        exit;
    }
}

SmartAffiliateManager::get_instance();

// Pro activation stub
function sam_activate_pro($license_key) {
    // Simulate license check
    update_option('sam_pro_active', true);
}

/*
admin.js content:
jQuery(document).ready(function($) {
    $('#sam-add-link').submit(function(e) {
        e.preventDefault();
        $.post(sam_ajax.ajaxurl, {
            action: 'save_link',
            nonce: sam_ajax.nonce,
            alias: $('[name="alias"]').val(),
            url: $('[name="url"]').val()
        }, function(resp) {
            if (resp.success) location.reload();
            else alert(resp.data);
        });
    });
    $('.delete-link').click(function() {
        var id = $(this).data('id');
        $.post(sam_ajax.ajaxurl, {
            action: 'delete_link',
            nonce: sam_ajax.nonce,
            id: id
        }, function(resp) {
            if (resp.success) location.reload();
        });
    });
});

admin.css:
#sam-links-list { margin: 20px 0; }

Note: For production, extract JS/CSS to separate files and enqueue properly.
*/
?>