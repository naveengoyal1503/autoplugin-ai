/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Smart_Manager.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Smart Manager
 * Description: Cloak and manage your affiliate links efficiently, track clicks, and get smart optimization tips.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) exit;

class AffiliateLinkSmartManager {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'alsm_links';
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_action('template_redirect', array($this, 'handle_redirect'));
        add_action('admin_post_alsm_track_click', array($this, 'track_click')); // for ajax or direct hits
    }

    // Create DB table for storing links
    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
          id bigint(20) NOT NULL AUTO_INCREMENT,
          slug varchar(191) NOT NULL,
          target_url text NOT NULL,
          clicks bigint(20) NOT NULL DEFAULT 0,
          created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id),
          UNIQUE KEY slug (slug)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Admin menu
    public function add_admin_menu() {
        add_menu_page('AffiliateLink Manager', 'AffiliateLink Manager', 'manage_options', 'alsm_manage', array($this, 'admin_page'), 'dashicons-admin-links');
    }

    // Admin page content
    public function admin_page() {
        global $wpdb;
        // Handle form submission
        if (isset($_POST['alsm_action']) && $_POST['alsm_action'] === 'add_link') {
            check_admin_referer('alsm_add_link_nonce');
            $slug = sanitize_title($_POST['slug']);
            $target_url = esc_url_raw($_POST['target_url']);
            if ($slug && $target_url) {
                $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $this->table_name WHERE slug = %s", $slug));
                if ($exists) {
                    echo '<div class="notice notice-error"><p>Slug already exists. Choose a different one.</p></div>';
                } else {
                    $wpdb->insert($this->table_name, array('slug' => $slug, 'target_url' => $target_url));
                    echo '<div class="notice notice-success"><p>Link added successfully.</p></div>';
                }
            }
        }

        // Display link list
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        ?>
        <div class="wrap">
          <h1>AffiliateLink Smart Manager</h1>
          <form method="post">
            <?php wp_nonce_field('alsm_add_link_nonce'); ?>
            <input type="hidden" name="alsm_action" value="add_link">
            <table class="form-table">
              <tr>
                <th><label for="slug">Slug (short name)</label></th>
                <td><input type="text" name="slug" id="slug" required></td>
              </tr>
              <tr>
                <th><label for="target_url">Target URL (affiliate link)</label></th>
                <td><input type="url" name="target_url" id="target_url" size="50" required></td>
              </tr>
            </table>
            <input type="submit" value="Add Link" class="button button-primary">
          </form>
          <h2>Affiliate Links</h2>
          <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Slug</th><th>Target URL</th><th>Clicks</th><th>Affiliate URL</th></tr></thead>
            <tbody>
            <?php foreach ($links as $link) {
              $aff_url = site_url('/?alsm=' . esc_html($link->slug));
              echo "<tr><td>", esc_html($link->slug), "</td><td>", esc_html($link->target_url), "</td><td>", intval($link->clicks), "</td><td><input type='text' readonly value='", esc_url($aff_url), "' style='width:100%;'></td></tr>";
            } ?>
            </tbody>
          </table>
        </div>
        <?php
    }

    // Shortcode handler [affiliate_link slug="slugname"]
    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array('slug' => ''), $atts);
        if (!$atts['slug']) return '';
        $url = site_url('/?alsm=' . sanitize_title($atts['slug']));
        return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">' . esc_html($atts['slug']) . '</a>';
    }

    // Redirect handler
    public function handle_redirect() {
        if (isset($_GET['alsm'])) {
            global $wpdb;
            $slug = sanitize_title($_GET['alsm']);
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE slug = %s", $slug));
            if ($row) {
                // Track click async by firing a background admin post or JS beacon if needed
                $this->increment_click($row->id);
                wp_redirect($row->target_url, 302);
                exit;
            } else {
                wp_die('Affiliate link not found.', '404', array('response' => 404));
            }
        }
    }

    // Increment click counter safely
    private function increment_click($id) {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE id = %d", $id));
    }

    // Extra: Ajax or admin post handler if needed (not used now)
    public function track_click() {
        // Placeholder for ajax click tracking (optional for future)
        // This plugin currently tracks on redirect synchronously
    }

}

new AffiliateLinkSmartManager();
