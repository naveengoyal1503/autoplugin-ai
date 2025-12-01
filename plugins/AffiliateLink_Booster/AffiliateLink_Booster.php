/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Booster.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Booster
 * Description: Automatically enhances affiliate links with CTAs, tracks clicks, and provides reports.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) {
  exit;
}

class AffiliateLinkBooster {

  private $option_name = 'alb_options';
  private $clicks_meta_key = '_alb_clicks';

  public function __construct() {
    add_filter('the_content', array($this, 'process_content_affiliate_links'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_styles'));
    add_action('wp_ajax_alb_track_click', array($this, 'ajax_track_click'));
    add_action('wp_ajax_nopriv_alb_track_click', array($this, 'ajax_track_click'));
    add_action('admin_menu', array($this, 'admin_add_menu'));
  }

  // Enqueue CSS and JS
  public function enqueue_scripts_styles() {
    wp_enqueue_style('alb-style', plugin_dir_url(__FILE__) . 'alb-style.css');
    wp_enqueue_script('alb-script', plugin_dir_url(__FILE__) . 'alb-script.js', array('jquery'), false, true);
    wp_localize_script('alb-script', 'alb_ajax_obj', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('alb_nonce')
    ));
  }

  // Process content to find affiliate links and add CTA buttons
  public function process_content_affiliate_links($content) {
    // Simple regex to detect URLs with affiliate parameters
    $pattern = '/<a\s+([^>]*href=["']([^"']+(?:aff_id=|affiliate=|ref=|partner=)[^"']*)["'][^>]*)>/i';

    // Replace such links with enhanced markup
    $content = preg_replace_callback($pattern, array($this, 'replace_affiliate_link'), $content);
    return $content;
  }

  // Replace affiliate link to add CTA button and tracking
  private function replace_affiliate_link($matches) {
    $original_tag = $matches;
    $href = $matches[2];

    // Construct button HTML with data attribute
    $button_html = '<span class="alb-affiliate-container">';
    $button_html .= preg_replace('/<a /', '<a class="alb-affiliate-link" data-href="' . esc_attr($href) . '" ', $original_tag, 1);
    $button_html .= ' <button class="alb-cta-button" data-link="' . esc_attr($href) . '">Save with Affiliate!</button>';
    $button_html .= '</span>';

    return $button_html;
  }

  // AJAX handler to track clicks
  public function ajax_track_click() {
    check_ajax_referer('alb_nonce', 'nonce');

    $link = isset($_POST['link']) ? esc_url_raw($_POST['link']) : '';
    if (!$link) {
      wp_send_json_error('No link provided');
    }

    // Simplified: store clicks in transient or DB - here using options with sanitized key
    $key = 'alb_click_' . md5($link);
    $clicks = (int) get_option($key, 0);
    update_option($key, $clicks + 1);

    wp_send_json_success(array('clicks' => $clicks + 1));
  }

  // Admin menu
  public function admin_add_menu() {
    add_menu_page('AffiliateLink Booster', 'AffiliateLink Booster', 'manage_options', 'alb-reports', array($this, 'admin_reports_page'), 'dashicons-chart-line');
  }

  // Admin report page
  public function admin_reports_page() {
    if (!current_user_can('manage_options')) {
      wp_die('Access denied');
    }

    echo '<div class="wrap"><h1>AffiliateLink Booster - Clicks Report</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Affiliate Link</th><th>Clicks</th></tr></thead><tbody>';

    global $wpdb;
    $all_options = wp_load_alloptions();
    foreach ($all_options as $key => $value) {
      if (strpos($key, 'alb_click_') === 0) {
        $link = base64_decode(strtr(substr($key, 9), '-_', '+/')); // no base64 here, so fallback
        // Fix: We stored md5 hash, so cannot reverse, display hash
        $link_hash = substr($key, 10);
        $clicks = intval($value);
        echo '<tr><td>' . esc_html($link_hash) . '</td><td>' . esc_html($clicks) . '</td></tr>';
      }
    }

    echo '</tbody></table></div>';
  }

}

new AffiliateLinkBooster();

// Basic inline CSS/JS for demonstration

add_action('wp_head', function() {
  ?>
  <style>
    .alb-cta-button {
      background-color: #0073aa;
      color: #fff;
      border: none;
      padding: 4px 8px;
      cursor: pointer;
      font-size: 0.9em;
      border-radius: 3px;
      margin-left: 5px;
    }
    .alb-cta-button:hover {
      background-color: #005177;
    }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.alb-cta-button').forEach(function(button) {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          var linkUrl = this.getAttribute('data-link');
          if (!linkUrl) return;

          // AJAX post to track click
          var data = new FormData();
          data.append('action', 'alb_track_click');
          data.append('link', linkUrl);
          data.append('nonce', '<?php echo wp_create_nonce('alb_nonce'); ?>');

          fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            credentials: 'same-origin',
            body: data
          }).then(function(response) {
            // open affiliate link in new window after tracking
            window.open(linkUrl, '_blank');
          });
        });
      });
    });
  </script>
  <?php
});
