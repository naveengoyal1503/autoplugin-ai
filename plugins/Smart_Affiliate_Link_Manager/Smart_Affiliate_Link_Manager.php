/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate-manager
 * Description: Automatically cloak, track, and manage affiliate links with performance analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            original_url text NOT NULL,
            shortcode varchar(50) NOT NULL,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY shortcode (shortcode)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sam-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sam-tracker', 'sam_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sam_nonce')));
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'sam') !== false) {
            wp_enqueue_script('sam-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('sam-admin', 'sam_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
        }
    }

    public function cloak_links($content) {
        if (is_feed() || is_preview()) return $content;

        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name");

        foreach ($links as $link) {
            $short_url = '[sam]' . $link->shortcode . '[/sam]';
            $cloaked = '<a href="' . $this->get_cloaked_url($link->shortcode) . '" class="sam-link" data-id="' . $link->id . '">' . $link->shortcode . '</a>';
            $content = str_replace($short_url, $cloaked, $content);
        }

        return $content;
    }

    private function get_cloaked_url($shortcode) {
        return add_query_arg('sam', $shortcode, home_url('/'));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Manager', 'Affiliate Manager', 'manage_options', 'smart-affiliate-manager', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['add_link'])) {
            $this->add_link($_POST['original_url'], $_POST['shortcode']);
        }
        if (isset($_GET['delete'])) {
            $this->delete_link($_GET['delete']);
        }
        $this->display_admin_page();
    }

    private function add_link($url, $shortcode) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $wpdb->insert($table_name, array('original_url' => $url, 'shortcode' => sanitize_text_field($shortcode)));
    }

    private function delete_link($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $wpdb->delete($table_name, array('id' => intval($id)));
    }

    private function display_admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Original URL</th>
                        <td><input type="url" name="original_url" required style="width: 400px;"></td>
                    </tr>
                    <tr>
                        <th>Shortcode</th>
                        <td><input type="text" name="shortcode" required style="width: 200px;" placeholder="aff1"></td>
                    </tr>
                </table>
                <p><input type="submit" name="add_link" class="button-primary" value="Add Link"></p>
            </form>
            <h2>Links & Stats</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Shortcode</th><th>Clicks</th><th>Original URL</th><th>Shortcode</th><th>Actions</th></tr></thead>
                <tbody>
        <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo $link->id; ?></td>
                        <td><?php echo esc_html($link->shortcode); ?></td>
                        <td id="clicks-<?php echo $link->id; ?>"><?php echo $link->clicks; ?></td>
                        <td><?php echo esc_html($link->original_url); ?></td>
                        <td><code>[sam]<?php echo $link->shortcode; ?>[/sam]</code></td>
                        <td><a href="?page=smart-affiliate-manager&delete=<?php echo $link->id; ?>" onclick="return confirm('Delete?')">Delete</a></td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sam_nonce')) {
            wp_die('Security check failed');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'sam_links';
        $shortcode = sanitize_text_field($_POST['shortcode']);
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE shortcode = %s", $shortcode));
        $link = $wpdb->get_row($wpdb->prepare("SELECT original_url FROM $table_name WHERE shortcode = %s", $shortcode));
        if ($link) {
            wp_redirect($link->original_url);
            exit;
        }
    }
}

add_action('wp_ajax_sam_track', array(SmartAffiliateManager::get_instance(), 'track_click'));
add_action('template_redirect', array(SmartAffiliateManager::get_instance(), 'handle_cloaked_link'));

SmartAffiliateManager::get_instance();

// Handle cloaked link clicks
function handle_cloaked_link() {
    if (isset($_GET['sam'])) {
        $shortcode = sanitize_text_field($_GET['sam']);
        wp_redirect(home_url('/wp-admin/admin-ajax.php?action=sam_track&shortcode=' . urlencode($shortcode) . '&nonce=' . wp_create_nonce('sam_nonce')));
        exit;
    }
}

// Dummy JS files - in real plugin, create them
function sam_tracker_js() { ?>
<script>
jQuery(document).ready(function($) {
    $('.sam-link').on('click', function(e) {
        var id = $(this).data('id');
        $.post(sam_ajax.ajaxurl, {
            action: 'sam_track',
            shortcode: $(this).data('shortcode'),
            nonce: sam_ajax.nonce
        });
    });
});
</script>
<?php }

?>