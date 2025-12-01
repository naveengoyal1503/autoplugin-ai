<?php
/*
Plugin Name: Affiliate Coupon Booster
Description: Manage exclusive affiliate coupons with expiration and click tracking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
  public function __construct() {
    add_action('admin_menu', array($this, 'register_admin_menu'));
    add_action('wp_ajax_acb_save_coupon', array($this, 'save_coupon'));
    add_shortcode('affiliate_coupons', array($this, 'render_coupon_list'));
    add_action('template_redirect', array($this, 'handle_coupon_redirect'));
    register_activation_hook(__FILE__, array($this, 'plugin_activate'));
  }

  public function plugin_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acb_coupons';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      title varchar(255) NOT NULL,
      affiliate_url text NOT NULL,
      coupon_code varchar(100),
      expiration_date date DEFAULT NULL,
      clicks int DEFAULT 0,
      created_at datetime DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }

  public function register_admin_menu() {
    add_menu_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'affiliate-coupon-booster', array($this, 'admin_page'), 'dashicons-tickets-alt');
  }

  public function admin_page() {
    ?>
    <div class="wrap">
      <h1>Affiliate Coupon Booster</h1>
      <form id="acb-new-coupon" method="post">
        <table class="form-table">
          <tr><th><label for="title">Coupon Title</label></th><td><input type="text" id="title" name="title" required style="width: 300px;"></td></tr>
          <tr><th><label for="affiliate_url">Affiliate URL</label></th><td><input type="url" id="affiliate_url" name="affiliate_url" required style="width: 300px;"></td></tr>
          <tr><th><label for="coupon_code">Coupon Code (optional)</label></th><td><input type="text" id="coupon_code" name="coupon_code" style="width: 150px;"></td></tr>
          <tr><th><label for="expiration_date">Expiration Date (optional)</label></th><td><input type="date" id="expiration_date" name="expiration_date" style="width: 150px;"></td></tr>
        </table>
        <p><button type="submit" class="button button-primary">Add Coupon</button></p>
      </form>
      <h2>Existing Coupons</h2>
      <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>Title</th><th>Coupon Code</th><th>Expiration</th><th>Clicks</th><th>Shortcode</th></tr></thead>
        <tbody>
          <?php
          global $wpdb;
          $coupons = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "acb_coupons ORDER BY created_at DESC");
          foreach($coupons as $c) {
            $expires = $c->expiration_date ? esc_html($c->expiration_date) : 'Never';
            $code = $c->coupon_code ? esc_html($c->coupon_code) : '-';
            echo "<tr>" .
                 "<td>" . esc_html($c->title) . "</td>" .
                 "<td>" . $code . "</td>" .
                 "<td>" . $expires . "</td>" .
                 "<td>" . intval($c->clicks) . "</td>" .
                 "<td>[affiliate_coupon id=\"" . intval($c->id) . "\"]</td>" .
                 "</tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
    <script>
    document.getElementById('acb-new-coupon').addEventListener('submit', function(e) {
      e.preventDefault();
      var formData = new FormData(this);
      formData.append('action', 'acb_save_coupon');
      fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      }).then(res => res.json()).then(data => {
        if(data.success) {
          alert('Coupon added successfully!');
          location.reload();
        } else {
          alert('Error: ' + data.data);
        }
      });
    });
    </script>
    <?php
  }

  public function save_coupon() {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

    $title = sanitize_text_field($_POST['title'] ?? '');
    $affiliate_url = esc_url_raw($_POST['affiliate_url'] ?? '');
    $coupon_code = sanitize_text_field($_POST['coupon_code'] ?? '');
    $expiration_date = sanitize_text_field($_POST['expiration_date'] ?? '');
    if (!$title || !$affiliate_url) wp_send_json_error('Title and Affiliate URL required');

    global $wpdb;
    $table = $wpdb->prefix . 'acb_coupons';
    $inserted = $wpdb->insert($table, [
      'title' => $title,
      'affiliate_url' => $affiliate_url,
      'coupon_code' => $coupon_code ?: null,
      'expiration_date' => $expiration_date ?: null
    ]);

    if ($inserted) wp_send_json_success();
    else wp_send_json_error('Database insert failed');
  }

  public function render_coupon_list($atts) {
    global $wpdb;
    $table = $wpdb->prefix . 'acb_coupons';
    $now = date('Y-m-d');
    $coupons = $wpdb->get_results($wpdb->prepare(
      "SELECT * FROM $table WHERE (expiration_date IS NULL OR expiration_date >= %s) ORDER BY expiration_date ASC",
      $now
    ));

    if (!$coupons) return '<p>No active coupons at this time.</p>';

    $output = '<div class="acb-coupons">';
    foreach ($coupons as $c) {
      $code_html = $c->coupon_code ? '<strong>Code:</strong> ' . esc_html($c->coupon_code) . '<br>' : '';
      $exp = $c->expiration_date ? esc_html($c->expiration_date) : 'Never';
      $link = esc_url(add_query_arg(['acb_redirect' => $c->id], home_url()));
      $output .= '<div class="acb-coupon" style="border:1px solid #ddd;padding:10px;margin-bottom:8px;">
        <h4>' . esc_html($c->title) . '</h4>
        ' . $code_html . '
        <small>Expires: ' . $exp . '</small><br>
        <a href="' . $link . '" target="_blank" rel="noopener">Get Deal</a>
      </div>';
    }
    $output .= '</div>';
    return $output;
  }

  public function handle_coupon_redirect() {
    if (isset($_GET['acb_redirect'])) {
      global $wpdb;
      $id = intval($_GET['acb_redirect']);
      $table = $wpdb->prefix . 'acb_coupons';
      $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
      if ($coupon) {
        // Check expiration
        if ($coupon->expiration_date && $coupon->expiration_date < date('Y-m-d')) {
          wp_die('This coupon has expired.');
        }
        // Increment clicks
        $wpdb->query($wpdb->prepare("UPDATE $table SET clicks = clicks + 1 WHERE id = %d", $id));
        wp_redirect($coupon->affiliate_url);
        exit;
      } else {
        wp_die('Invalid coupon.');
      }
    }
  }
}

new AffiliateCouponBooster();
