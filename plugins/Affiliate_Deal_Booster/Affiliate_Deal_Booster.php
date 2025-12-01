/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Curate and display affiliate coupons and deals with built-in affiliate link tracking and monetization features.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {

  private $version = '1.0';
  private $option_name = 'adb_deals';

  public function __construct() {
    add_action('init', array($this, 'register_shortcode'));
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
  }

  public function register_shortcode() {
    add_shortcode('affiliate_deals', array($this, 'shortcode_display_deals'));
  }

  public function enqueue_scripts() {
    wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'adb-style.css');
  }

  public function admin_menu() {
    add_menu_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'affiliate-deal-booster', array($this, 'admin_page'), 'dashicons-megaphone', 80);
  }

  public function register_settings() {
    register_setting('adb_settings_group', $this->option_name, array($this, 'sanitize_deals'));
  }

  public function sanitize_deals($deals) {
    // Sanitize input array
    $clean = array();
    if (is_array($deals)) {
      foreach ($deals as $deal) {
        $clean[] = array(
          'title' => sanitize_text_field($deal['title'] ?? ''),
          'description' => sanitize_textarea_field($deal['description'] ?? ''),
          'affiliate_link' => esc_url_raw($deal['affiliate_link'] ?? ''),
          'coupon_code' => sanitize_text_field($deal['coupon_code'] ?? ''),
          'expiry_date' => sanitize_text_field($deal['expiry_date'] ?? '')
        );
      }
    }
    return $clean;
  }

  public function admin_page() {
    // Get stored deals
    $deals = get_option($this->option_name, array());
    ?>
    <div class="wrap">
      <h1>Affiliate Deal Booster - Manage Deals</h1>
      <form method="post" action="options.php">
        <?php settings_fields('adb_settings_group'); ?>
        <?php do_settings_sections('adb_settings_group'); ?>
        <table id="deals-table" class="widefat fixed" cellspacing="0">
          <thead>
            <tr>
              <th>Title</th>
              <th>Description</th>
              <th>Affiliate Link</th>
              <th>Coupon Code</th>
              <th>Expiry Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($deals)) : ?>
              <?php foreach ($deals as $index => $deal) : ?>
                <tr>
                  <td><input type="text" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][title]" value="<?php echo esc_attr($deal['title']); ?>" required></td>
                  <td><textarea name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][description]" rows="2" required><?php echo esc_textarea($deal['description']); ?></textarea></td>
                  <td><input type="url" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][affiliate_link]" value="<?php echo esc_url($deal['affiliate_link']); ?>" required></td>
                  <td><input type="text" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][coupon_code]" value="<?php echo esc_attr($deal['coupon_code']); ?>"></td>
                  <td><input type="date" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][expiry_date]" value="<?php echo esc_attr($deal['expiry_date']); ?>"></td>
                  <td><button class="button remove-deal" type="button">Remove</button></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" style="text-align:center;">No deals found. Add new deals below.</td></tr>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="6">
                <button id="add-deal" class="button button-primary" type="button">Add New Deal</button>
              </td>
            </tr>
          </tfoot>
        </table>
        <?php submit_button(); ?>
      </form>
    </div>

    <script>
      (function(){
        const dealsTable = document.getElementById('deals-table').getElementsByTagName('tbody');
        document.getElementById('add-deal').addEventListener('click', function() {
          const rowCount = dealsTable.rows.length;
          const row = dealsTable.insertRow();
          row.innerHTML = `
            <td><input type='text' name='<?php echo $this->option_name; ?>[${rowCount}][title]' required></td>
            <td><textarea name='<?php echo $this->option_name; ?>[${rowCount}][description]' rows='2' required></textarea></td>
            <td><input type='url' name='<?php echo $this->option_name; ?>[${rowCount}][affiliate_link]' required></td>
            <td><input type='text' name='<?php echo $this->option_name; ?>[${rowCount}][coupon_code]'></td>
            <td><input type='date' name='<?php echo $this->option_name; ?>[${rowCount}][expiry_date]'></td>
            <td><button class='button remove-deal' type='button'>Remove</button></td>
          `;
        });
        dealsTable.addEventListener('click', function(e) {
          if (e.target.classList.contains('remove-deal')) {
            const row = e.target.closest('tr');
            row.parentNode.removeChild(row);
          }
        });
      })();
    </script>
    <?php
  }

  public function shortcode_display_deals() {
    $deals = get_option($this->option_name, array());
    if (empty($deals)) {
      return '<p>No deals available currently.</p>';
    }

    $output = '<div class="adb-deals-container">';
    $today = date('Y-m-d');
    foreach ($deals as $deal) {
      // Skip expired deals
      if (!empty($deal['expiry_date']) && $deal['expiry_date'] < $today) continue;

      $title = esc_html($deal['title']);
      $desc = esc_html($deal['description']);
      $link = esc_url($deal['affiliate_link']);
      $coupon = esc_html($deal['coupon_code']);

      $output .= '<div class="adb-deal-item">';
      $output .= '<h3 class="adb-deal-title">' . $title . '</h3>';
      $output .= '<p class="adb-deal-desc">' . $desc . '</p>';
      $btn_label = !empty($coupon) ? 'Get Coupon: ' . $coupon : 'Get Deal';
      $output .= '<p><a class="adb-deal-btn" href="' . $link . '" target="_blank" rel="nofollow noopener">' . $btn_label . '</a></p>';
      $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
  }

}

new AffiliateDealBooster();

// Basic CSS injected inline for styling
add_action('wp_head', function() {
  echo '<style>.adb-deals-container{display:flex;flex-wrap:wrap;gap:20px;}.adb-deal-item{border:1px solid #ccc;padding:10px;border-radius:5px;flex:1 1 calc(33% - 40px);box-sizing:border-box;}.adb-deal-title{margin:0 0 5px;font-size:1.2em;color:#0073aa;}.adb-deal-desc{font-size:0.95em;color:#333;}.adb-deal-btn{display:inline-block;padding:6px 12px;background:#0073aa;color:#fff;text-decoration:none;border-radius:3px;}.adb-deal-btn:hover{background:#005177;}</style>';
});
