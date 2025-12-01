/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoost_Link_Manager.php
*/
<?php
/**
 * Plugin Name: AffiliateBoost Link Manager
 * Description: Manage and optimize affiliate links with automatic cloaking, categorized links, and click tracking.
 * Version: 1.0
 * Author: AffiliateBoost
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Prevent direct access

class AffiliateBoost_Link_Manager {

  private $version = '1.0';
  private $table_name;

  public function __construct() {
    global $wpdb;
    $this->table_name = $wpdb->prefix . 'affiliateboost_links';

    register_activation_hook(__FILE__, array($this, 'plugin_activate'));
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_shortcode('affiliateboost_links', array($this, 'shortcode_links_list'));
    add_action('init', array($this, 'handle_redirect'));
  }

  public function plugin_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
      id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      slug VARCHAR(191) NOT NULL UNIQUE,
      url TEXT NOT NULL,
      category VARCHAR(100) DEFAULT '',
      clicks BIGINT(20) DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  public function admin_menu() {
    add_menu_page('AffiliateBoost Links', 'Affiliate Links', 'manage_options', 'affiliateboost-links', array($this, 'admin_page'), 'dashicons-admin-links');
  }

  public function enqueue_admin_scripts($hook) {
    if ($hook != 'toplevel_page_affiliateboost-links') return;
    wp_enqueue_style('affiliateboost-admin-css', plugin_dir_url(__FILE__) . 'style.css');
  }

  public function admin_page() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;

    // Handle form submissions for new link
    if (isset($_POST['affiliateboost_add_link'])) {
      check_admin_referer('affiliateboost_add_link_nonce');
      $slug = sanitize_title($_POST['slug']);
      $url = esc_url_raw($_POST['url']);
      $category = sanitize_text_field($_POST['category']);
      if ($slug && $url) {
        $wpdb->insert($this->table_name, compact('slug', 'url', 'category'));
        echo '<div class="updated notice"><p>Link added successfully.</p></div>';
      } else {
        echo '<div class="error notice"><p>Slug and URL are required.</p></div>';
      }
    }

    // Fetch all links
    $links = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");

    ?>
    <div class="wrap">
      <h1>AffiliateBoost Link Manager</h1>
      <h2>Add New Affiliate Link</h2>
      <form method="post">
        <?php wp_nonce_field('affiliateboost_add_link_nonce'); ?>
        <table class="form-table">
          <tr><th><label for="slug">Slug (short unique keyword):</label></th>
              <td><input type="text" name="slug" id="slug" required pattern="[a-zA-Z0-9_-]+" title="Only letters, numbers, underscore, and dash allowed"></td></tr>
          <tr><th><label for="url">Affiliate URL:</label></th>
              <td><input type="url" name="url" id="url" required></td></tr>
          <tr><th><label for="category">Category (optional):</label></th>
              <td><input type="text" name="category" id="category"></td></tr>
        </table>
        <input type="submit" name="affiliateboost_add_link" class="button button-primary" value="Add Link">
      </form>

      <h2>Existing Links</h2>
      <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>Slug</th><th>URL</th><th>Category</th><th>Clicks</th><th>Shortlink</th></tr></thead>
        <tbody>
        <?php foreach ($links as $link): ?>
          <tr>
            <td><?php echo esc_html($link->slug); ?></td>
            <td><a href="<?php echo esc_url($link->url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($link->url); ?></a></td>
            <td><?php echo esc_html($link->category); ?></td>
            <td><?php echo intval($link->clicks); ?></td>
            <td><?php echo esc_url(home_url('/') . '?aff=<?php echo esc_html($link->slug); ?>'); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
  }

  public function handle_redirect() {
    if (isset($_GET['aff'])) {
      global $wpdb;
      $slug = sanitize_text_field($_GET['aff']);
      $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE slug = %s", $slug));
      if ($row && $row->url) {
        // Update clicks count
        $wpdb->query($wpdb->prepare("UPDATE {$this->table_name} SET clicks = clicks + 1 WHERE id = %d", $row->id));
        wp_redirect($row->url, 301);
        exit;
      }
    }
  }

  public function shortcode_links_list($atts) {
    global $wpdb;
    $atts = shortcode_atts(array('category' => ''), $atts, 'affiliateboost_links');

    $sql = "SELECT * FROM {$this->table_name}";
    $params = array();
    if ($atts['category']) {
      $sql .= " WHERE category = %s";
      $params[] = $atts['category'];
    }
    $sql .= " ORDER BY clicks DESC";
    $links = $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);

    if (!$links) return '<p>No affiliate links found.</p>';

    $output = '<ul class="affiliateboost-links-list">';
    foreach ($links as $link) {
      $shortlink = esc_url(home_url('/?aff=' . $link->slug));
      $output .= sprintf('<li><a href="%s" target="_blank" rel="nofollow noopener">%s</a> (%d clicks)</li>', $shortlink, esc_html($link->slug), intval($link->clicks));
    }
    $output .= '</ul>';

    return $output;
  }

}

new AffiliateBoost_Link_Manager();