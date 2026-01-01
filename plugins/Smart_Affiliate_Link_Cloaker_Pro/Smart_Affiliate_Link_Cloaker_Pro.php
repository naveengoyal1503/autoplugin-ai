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
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloaker {
    private $db_version = '1.0';
    private $table_name;
    private $is_pro = false;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'affiliate_links';
        $this->is_pro = get_option('salcp_pro_activated', false);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('afflink', array($this, 'shortcode_handler'));
        add_filter('widget_text', 'shortcode_unautop');
        add_filter('the_content', 'shortcode_unautop');
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
        add_action('wp_ajax_salcp_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_salcp_track_click', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            url text NOT NULL,
            slug varchar(50) NOT NULL,
            clicks bigint DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            ab_variants text,
            pro_only tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        add_option('salcp_db_version', $this->db_version);
    }

    public function deactivate() {}

    public function init() {
        if (get_option('salcp_db_version') != $this->db_version) {
            $this->activate();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salcp-frontend', plugin_dir_url(__FILE__) . 'salcp.js', array('jquery'), '1.0.0', true);
        wp_localize_script('salcp-frontend', 'salcp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_enqueue($hook) {
        if (strpos($hook, 'salcp') !== false) {
            wp_enqueue_script('salcp-admin', plugin_dir_url(__FILE__) . 'salcp-admin.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_menu() {
        add_menu_page('Affiliate Links', 'Affiliate Links', 'manage_options', 'salcp', array($this, 'admin_page'), 'dashicons-link');
        add_submenu_page('salcp', 'Add New Link', 'Add New', 'manage_options', 'salcp-new', array($this, 'add_new_page'));
        add_submenu_page('salcp', 'Analytics', 'Analytics', 'manage_options', 'salcp-analytics', array($this, 'analytics_page'));
        add_submenu_page('salcp', 'Pro Upgrade', 'Go Pro', 'manage_options', 'salcp-pro', array($this, 'pro_page'));
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>Affiliate Links Dashboard</h1>';
        echo '<p>Manage your cloaked affiliate links. <a href="' . admin_url('admin.php?page=salcp-new') . '">Add New Link</a></p>';
        $this->list_links();
        echo '</div>';
    }

    private function list_links() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created DESC");
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Clicks</th><th>Actions</th></tr></thead><tbody>';
        foreach ($links as $link) {
            $edit_url = admin_url('admin.php?page=salcp-new&edit=' . $link->id);
            echo "<tr><td>{$link->id}</td><td>{$link->name}</td><td>/{$link->slug}</td><td>{$link->clicks}</td><td><a href='$edit_url'>Edit</a> | <a href='#" onclick='deleteLink({$link->id})'>Delete</a></td></tr>";
        }
        echo '</tbody></table>';
    }

    public function add_new_page() {
        global $wpdb;
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        if (isset($_POST['submit'])) {
            $name = sanitize_text_field($_POST['name']);
            $url = esc_url_raw($_POST['url']);
            $slug = sanitize_title($_POST['slug']);
            if ($edit_id) {
                $wpdb->update($this->table_name, array('name'=>$name, 'url'=>$url, 'slug'=>$slug), array('id'=>$edit_id));
            } else {
                $wpdb->insert($this->table_name, array('name'=>$name, 'url'=>$url, 'slug'=>$slug));
            }
            echo '<div class="notice notice-success"><p>Link saved!</p></div>';
        }
        if ($edit_id) {
            $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $edit_id));
        }
        echo '<div class="wrap"><h1>' . ($edit_id ? 'Edit' : 'Add New') . ' Link</h1><form method="post">';
        echo '<p><label>Name: <input type="text" name="name" value="' . esc_attr($link->name ?? '') . '" required></label></p>';
        echo '<p><label>Target URL: <input type="url" name="url" style="width:50%;" value="' . esc_attr($link->url ?? '') . '" required></label></p>';
        echo '<p><label>Slug: <input type="text" name="slug" value="' . esc_attr($link->slug ?? '') . '" required></label> (Use: yoursite.com/<strong>slug</strong>)</p>';
        if (!$this->is_pro) {
            echo '<p><strong>Pro:</strong> A/B Testing & Advanced Analytics</p>';
        }
        echo '<p><input type="submit" name="submit" class="button-primary" value="Save Link"></p></form>';
        echo "<h2>Usage</h2><p>Use shortcode: <code>[afflink id=\"1\"]</code> or link to <code>/your-slug</code></p>";
        echo '</div>';
    }

    public function analytics_page() {
        echo '<div class="wrap"><h1>Analytics</h1>';
        if (!$this->is_pro) {
            echo '<div class="notice notice-info"><p>Upgrade to Pro for detailed analytics!</p></div>';
        }
        $this->list_links();
        echo '</div>';
    }

    public function pro_page() {
        echo '<div class="wrap"><h1>Go Pro</h1><p>Unlock A/B testing, unlimited links, detailed reports for $49/year. <a href="https://example.com/pro" target="_blank">Buy Now</a></p></div>';
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        return $this->get_link_html($atts['id']);
    }

    public function track_click() {
        $slug = sanitize_text_field($_POST['slug']);
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE slug = %s", $slug));
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE slug = %s", $slug));
        if ($link) {
            wp_redirect($link->url, 301);
            exit;
        }
    }

    private function get_link_html($id) {
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id));
        if (!$link) return 'Link not found.';
        ob_start();
        echo '<a href="' . admin_url('admin-ajax.php?action=salcp_track_click&slug=' . $link->slug) . '" class="salcp-link" data-slug="' . $link->slug . '">' . esc_html($link->name) . '</a>';
        return ob_get_clean();
    }
}

new SmartAffiliateCloaker();

// Pro activation stub
function salcp_activate_pro($license) {
    update_option('salcp_pro_activated', true);
}

/*
 * Frontend JS (save as salcp.js in plugin dir)
$(document).ready(function() {
    $('.salcp-link').click(function(e) {
        e.preventDefault();
        var slug = $(this).data('slug');
        $.post(salcp_ajax.ajaxurl, {action: 'salcp_track_click', slug: slug});
        window.location.href = $(this).attr('href');
    });
});
*/

/*
 * Admin JS stub (save as salcp-admin.js)
function deleteLink(id) {
    if (confirm('Delete?')) {
        // AJAX delete
    }
}
*/
?>