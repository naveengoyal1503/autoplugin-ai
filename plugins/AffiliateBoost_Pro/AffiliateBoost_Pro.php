/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoost_Pro.php
*/
<?php
/**
 * Plugin Name: AffiliateBoost Pro
 * Description: Automate affiliate link management and monetize your WordPress site with smart affiliate optimization and analytics.
 * Version: 1.0
 * Author: Your Name
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
  exit;
}

class AffiliateBoostPro {
  private $version = '1.0';

  public function __construct() {
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('wp_footer', array($this, 'insert_affiliate_script'));
    add_shortcode('aff_boost_report', array($this, 'display_report'));
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    add_action('wp_ajax_abp_track_click', array($this, 'track_click_ajax'));
  }

  public function activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abp_clicks';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id bigint(20) NOT NULL AUTO_INCREMENT,
      affiliate_url varchar(255) NOT NULL,
      clicked_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
      user_ip varchar(100) DEFAULT '' NOT NULL,
      user_agent varchar(255) DEFAULT '' NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  public function deactivate() {
    // Optional: cleanup or leave data
  }

  public function admin_menu() {
    add_menu_page(
      'AffiliateBoost Pro',
      'AffiliateBoost Pro',
      'manage_options',
      'affiliateboostpro',
      array($this, 'settings_page'),
      'dashicons-chart-line',
      90
    );
  }

  public function settings_page() {
    if (!current_user_can('manage_options')) {
      return;
    }
    echo '<div class="wrap"><h1>AffiliateBoost Pro Dashboard</h1>';
    echo '<p>Use the shortcode <code>[aff_boost_report]</code> to display your affiliate clicks report on any page.</p>';
    echo $this->generate_report_html();
    echo '</div>';
  }

  public function insert_affiliate_script() {
    // Insert JS to convert affiliate links dynamically and track clicks
    ?>
    <script type="text/javascript">
      document.addEventListener('DOMContentLoaded', function() {
        const links = document.querySelectorAll('a[href*="affiliate"]'); // Simplified example, user to put affiliate links containing 'affiliate'
        links.forEach(link => {
          link.addEventListener('click', function(e) {
            const url = encodeURIComponent(link.href);
            navigator.sendBeacon(ajaxurl + '?action=abp_track_click&url=' + url);
          });
        });
      });
    </script>
    <?php
  }

  public function track_click_ajax() {
    global $wpdb;
    $url = isset($_GET['url']) ? esc_url_raw(htmlspecialchars_decode($_GET['url'])) : '';
    if (!$url) {
      wp_send_json_error('No URL');
    }
    $table_name = $wpdb->prefix . 'abp_clicks';
    $wpdb->insert($table_name, array(
      'affiliate_url' => $url,
      'user_ip' => $_SERVER['REMOTE_ADDR'],
      'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ));
    wp_send_json_success();
  }

  public function generate_report_html() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'abp_clicks';
    $results = $wpdb->get_results("SELECT affiliate_url, COUNT(*) as clicks FROM $table_name GROUP BY affiliate_url ORDER BY clicks DESC LIMIT 10");
    if (!$results) {
      return '<p>No click data available yet.</p>';
    }
    $html = '<table style="width:100%;border-collapse:collapse;"><thead><tr><th style="border:1px solid #ccc;padding:8px;text-align:left;">Affiliate URL</th><th style="border:1px solid #ccc;padding:8px;text-align:right;">Clicks</th></tr></thead><tbody>';
    foreach ($results as $row) {
      $html .= '<tr><td style="border:1px solid #ccc;padding:8px;"><a href="' . esc_url($row->affiliate_url) . '" target="_blank" rel="nofollow noopener noreferrer">' . esc_html($row->affiliate_url) . '</a></td><td style="border:1px solid #ccc;padding:8px;text-align:right;">' . intval($row->clicks) . '</td></tr>';
    }
    $html .= '</tbody></table>';
    return $html;
  }

  public function display_report() {
    if (!current_user_can('manage_options')) {
      return '<p>Insufficient permissions to view report.</p>';
    }
    return $this->generate_report_html();
  }

}

new AffiliateBoostPro();
