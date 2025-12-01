<?php
/*
Plugin Name: Affiliate Deal Booster
Plugin URI: https://example.com/affiliate-deal-booster
Description: Aggregates, tracks, and displays affiliate deals and coupons tailored to your niche to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
License: GPL2
*/

// Security check
if (!defined('ABSPATH')) {
  exit;
}

class AffiliateDealBooster {
  private $plugin_slug = 'affiliate-deal-booster';
  private $option_name = 'adb_deals';

  public function __construct() {
    // Activation hook to add default data
    register_activation_hook(__FILE__, array($this, 'plugin_activate'));

    // Add admin menu
    add_action('admin_menu', array($this, 'admin_menu'));

    // Register shortcode
    add_shortcode('adb_deals', array($this, 'render_deals_shortcode'));

    // AJAX endpoint to track clicks
    add_action('wp_ajax_adb_track_click', array($this, 'track_click'));
    add_action('wp_ajax_nopriv_adb_track_click', array($this, 'track_click'));

    // Enqueue scripts and styles
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
  }

  public function plugin_activate() {
    if (false === get_option($this->option_name)) {
      // Sample deal
      $default_deals = array(
        array(
          'id' => 'deal1',
          'title' => '25% Off on Tech Gadgets',
          'url' => 'https://affiliate.example.com/product-tech?ref=yourid',
          'description' => 'Save 25% on selected tech gadgets.',
          'expiry' => '',
          'clicks' => 0
        )
      );
      add_option($this->option_name, $default_deals);
    }
  }

  public function admin_menu() {
    add_menu_page('Affiliate Deal Booster', 'Affiliate Deals', 'manage_options', $this->plugin_slug, array($this, 'admin_page'), 'dashicons-cart', 26);
  }

  public function admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized user');
    }

    // Process form submission
    if (isset($_POST['adb_save_deals'])) {
      check_admin_referer('adb_save_deals_nonce');
      $deals = array();
      if (!empty($_POST['deal_title']) && is_array($_POST['deal_title'])) {
        foreach ($_POST['deal_title'] as $index => $title) {
          $title = sanitize_text_field($title);
          $url = esc_url_raw($_POST['deal_url'][$index]);
          $desc = sanitize_textarea_field($_POST['deal_description'][$index]);
          $expiry = sanitize_text_field($_POST['deal_expiry'][$index]);
          $id = sanitize_text_field($_POST['deal_id'][$index]);
          $clicks = intval($_POST['deal_clicks'][$index]);
          if ($title && $url) {
            $deals[] = array(
              'id' => $id ?: uniqid('deal'),
              'title' => $title,
              'url' => $url,
              'description' => $desc,
              'expiry' => $expiry,
              'clicks' => $clicks
            );
          }
        }
        update_option($this->option_name, $deals);
        echo '<div class="updated notice"><p>Deals saved successfully.</p></div>';
      }
    }

    $deals = get_option($this->option_name, array());

    ?>
    <div class="wrap">
      <h1>Affiliate Deal Booster - Manage Deals</h1>
      <form method="post">
      <?php wp_nonce_field('adb_save_deals_nonce'); ?>
        <table class="widefat fixed" cellspacing="0">
          <thead>
            <tr>
              <th>Title</th>
              <th>Affiliate URL</th>
              <th>Description</th>
              <th>Expiry (YYYY-MM-DD)</th>
              <th>Clicks</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="adb-deals-tbody">
          <?php
          if (!empty($deals)) {
            foreach ($deals as $deal) {
              echo '<tr>' .
                   '<td><input type="text" name="deal_title[]" value="' . esc_attr($deal['title']) . '" required /></td>' .
                   '<td><input type="url" name="deal_url[]" value="' . esc_attr($deal['url']) . '" required /></td>' .
                   '<td><textarea name="deal_description[]">' . esc_textarea($deal['description']) . '</textarea></td>' .
                   '<td><input type="date" name="deal_expiry[]" value="' . esc_attr($deal['expiry']) . '" /></td>' .
                   '<td><input type="number" name="deal_clicks[]" value="' . intval($deal['clicks']) . '" readonly /></td>' .
                   '<td><button type="button" class="button adb-remove-deal">Remove</button></td>' .
                   '<input type="hidden" name="deal_id[]" value="' . esc_attr($deal['id']) . '" />' .
                   '</tr>';
            }
          }
          ?>
          </tbody>
        </table>
        <p><button type="button" class="button button-primary" id="adb-add-deal">Add New Deal</button></p>
        <p><input type="submit" name="adb_save_deals" class="button button-primary" value="Save Deals"></p>
      </form>
    </div>
    <script type="text/javascript">
      (function(){
        var addBtn = document.getElementById('adb-add-deal');
        var tbody = document.getElementById('adb-deals-tbody');

        addBtn.onclick = function () {
          var row = document.createElement('tr');
          var uniqueId = 'deal' + Date.now();
          row.innerHTML =
            '<td><input type="text" name="deal_title[]" required /></td>' +
            '<td><input type="url" name="deal_url[]" required /></td>' +
            '<td><textarea name="deal_description[]"></textarea></td>' +
            '<td><input type="date" name="deal_expiry[]" /></td>' +
            '<td><input type="number" name="deal_clicks[]" value="0" readonly /></td>' +
            '<td><button type="button" class="button adb-remove-deal">Remove</button></td>' +
            '<input type="hidden" name="deal_id[]" value="' + uniqueId + '" />';
          tbody.appendChild(row);

          // Attach remove event
          row.querySelector('.adb-remove-deal').addEventListener('click', function(){
            this.closest('tr').remove();
          });
        };

        // Remove buttons existing on page
        var removes = document.querySelectorAll('.adb-remove-deal');
        removes.forEach(function(btn){
          btn.addEventListener('click', function(){
            this.closest('tr').remove();
          });
        });
      })();
    </script>
    <?php
  }

  public function enqueue_scripts() {
    if (is_singular() || is_home() || is_front_page()) {
      wp_enqueue_script('adb-script', plugin_dir_url(__FILE__) . 'adb-script.js', array('jquery'), '1.0', true);
      wp_localize_script('adb-script', 'adb_ajax_obj', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('adb_click_nonce')
      ));
      wp_add_inline_script('adb-script', 'jQuery(document).ready(function($){$(document).on("click", ".adb-affiliate-link", function(e){var id = $(this).data("dealid");if(id){$.post(adb_ajax_obj.ajax_url, {action:"adb_track_click", deal_id:id, nonce: adb_ajax_obj.nonce});}});});');
      wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'adb-style.css');
    }
  }

  public function render_deals_shortcode($atts) {
    $atts = shortcode_atts(array(
      'count' => 5
    ), $atts, 'adb_deals');

    $deals = get_option($this->option_name, array());
    if (empty($deals)) return '<p>No deals available right now.</p>';

    // Filter out expired deals
    $today = date('Y-m-d');
    $valid_deals = array_filter($deals, function($deal) use ($today) {
      return empty($deal['expiry']) || $deal['expiry'] >= $today;
    });

    // Limit number
    $valid_deals = array_slice($valid_deals, 0, intval($atts['count']));
    if (empty($valid_deals)) return '<p>No valid deals available currently.</p>';

    $output = '<div class="adb-deals-container">';
    foreach ($valid_deals as $deal) {
      $output .= '<div class="adb-deal">';
      $output .= '<h3>' . esc_html($deal['title']) . '</h3>';
      if (!empty($deal['description'])) {
        $output .= '<p>' . esc_html($deal['description']) . '</p>';
      }
      $output .= '<a href="#" class="adb-affiliate-link" target="_blank" rel="nofollow noopener" data-dealid="' . esc_attr($deal['id']) . '" data-url="' . esc_url($deal['url']) . '">Grab this deal</a>';
      $output .= '</div>';
    }
    $output .= '</div>';

    // Inline script to redirect properly
    $output .= '<script>(function(){var links=document.querySelectorAll(".adb-affiliate-link");links.forEach(function(link){link.addEventListener("click",function(e){e.preventDefault();var url=this.getAttribute("data-url");window.open(url, "_blank");});});})();</script>';

    return $output;
  }

  public function track_click() {
    check_ajax_referer('adb_click_nonce', 'nonce');
    $deal_id = isset($_POST['deal_id']) ? sanitize_text_field($_POST['deal_id']) : '';
    if (empty($deal_id)) {
      wp_send_json_error('Missing deal ID');
    }

    $deals = get_option($this->option_name, array());
    $updated = false;
    foreach ($deals as &$deal) {
      if ($deal['id'] === $deal_id) {
        $deal['clicks'] = isset($deal['clicks']) ? intval($deal['clicks']) + 1 : 1;
        $updated = true;
        break;
      }
    }

    if ($updated) {
      update_option($this->option_name, $deals);
      wp_send_json_success('Click counted');
    } else {
      wp_send_json_error('Deal not found');
    }
  }
}

new AffiliateDealBooster();
