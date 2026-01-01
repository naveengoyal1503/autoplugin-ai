/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automate affiliate link creation, cloaking, tracking, and performance analytics to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateLinkManager {
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
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_shortcode('salink', array($this, 'shortcode_handler'));
        add_filter('the_content', array($this, 'auto_replace_links'), 20);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sal_pro_activated')) {
            // Pro features
        }
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sal_links';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            affiliate_url tinytext NOT NULL,
            cloaked_url tinytext NOT NULL,
            clicks bigint DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-front', plugin_dir_url(__FILE__) . 'sal-front.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Links', 'Affiliate Links', 'manage_options', 'sal-links', array($this, 'admin_page'));
    }

    public function admin_enqueue_scripts($hook) {
        if ('settings_page_sal-links' !== $hook) return;
        wp_enqueue_script('sal-admin', plugin_dir_url(__FILE__) . 'sal-admin.js', array('jquery'), '1.0.0', true);
    }

    public function admin_page() {
        if (isset($_POST['sal_add_link'])) {
            $this->add_link($_POST['name'], $_POST['affiliate_url']);
        }
        if (isset($_GET['delete'])) {
            $this->delete_link($_GET['delete']);
        }
        $links = $this->get_links();
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    private function add_link($name, $url) {
        global $wpdb;
        $cloaked = home_url('/go/' . sanitize_title($name));
        $wpdb->insert($wpdb->prefix . 'sal_links', array(
            'name' => sanitize_text_field($name),
            'affiliate_url' => esc_url_raw($url),
            'cloaked_url' => $cloaked
        ));
    }

    private function delete_link($id) {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'sal_links', array('id' => intval($id)));
    }

    private function get_links() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "sal_links ORDER BY created DESC");
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $link = $this->get_link_by_id($atts['id']);
        if ($link) {
            return '<a href="' . $link->cloaked_url . '" class="sal-link" data-id="' . $link->id . '">' . $link->name . '</a>';
        }
        return '';
    }

    private function get_link_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "sal_links WHERE id = %d", $id));
    }

    public function auto_replace_links($content) {
        if (get_option('sal_auto_replace')) {
            global $wpdb;
            $keywords = $wpdb->get_results("SELECT name, cloaked_url FROM " . $wpdb->prefix . "sal_links");
            foreach ($keywords as $kw) {
                $content = preg_replace('/\b' . preg_quote($kw->name, '/') . '\b/i', '<a href="' . $kw->cloaked_url . '" class="sal-link">$0</a>', $content, 1);
            }
        }
        return $content;
    }
}

add_action('init', array('SmartAffiliateLinkManager', 'get_instance'));

// Rewrite rules
add_action('init', function() {
    add_rewrite_rule('^go/([^/]+)/?', 'index.php?sal_go=$matches[1]', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'sal_go';
    return $vars;
});

add_action('template_redirect', function() {
    $go = get_query_var('sal_go');
    if ($go) {
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "sal_links WHERE cloaked_url LIKE %s", '%/' . $go));
        if ($link) {
            $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "sal_links SET clicks = clicks + 1 WHERE id = %d", $link->id));
            wp_redirect($link->affiliate_url, 301);
            exit;
        }
    }
});

// Admin page template
if (!file_exists(plugin_dir_path(__FILE__) . 'admin-page.php')) {
    $admin_page = '<?php if (!defined("ABSPATH")) exit; ?>
    <div class="wrap">
        <h1>Smart Affiliate Links</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Name/Keyword</th>
                    <td><input type="text" name="name" required /></td>
                </tr>
                <tr>
                    <th>Affiliate URL</th>
                    <td><input type="url" name="affiliate_url" style="width:50%;" required /></td>
                </tr>
            </table>
            <p><input type="submit" name="sal_add_link" class="button-primary" value="Add Link" /></p>
        </form>
        <h2>Your Links</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Name</th><th>Cloaked URL</th><th>Clicks</th><th>Actions</th></tr></thead>
            <tbody>';
    foreach ($links as $link) {
        $admin_page .= '<tr><td>' . esc_html($link->name) . '</td><td>' . esc_html($link->cloaked_url) . '</td><td>' . $link->clicks . '</td><td><a href="?page=sal-links&delete=' . $link->id . '" onclick="return confirm(\'Delete?\')">Delete</a></td></tr>';
    }
    $admin_page .= '</tbody></table>
        <p><label><input type="checkbox" name="sal_auto_replace" ' . checked(get_option('sal_auto_replace'), 1, false) . ' /> Auto-replace keywords in posts</label></p>
    </div>';
    file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', $admin_page);
}

// JS files would be created similarly if needed, but for single file, inline
add_action('admin_footer-settings_page_sal-links', function() {
    echo '<script> jQuery(document).ready(function($) { $("form").on("submit", function() { $("input[type=submit]", this).val("Adding..."); }); }); </script>';
});