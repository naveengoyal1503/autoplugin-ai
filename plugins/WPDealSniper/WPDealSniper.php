<?php
/*
Plugin Name: WPDealSniper
Plugin URI: https://example.com/wpdealsniper
Description: Aggregates and displays affiliate coupons and deals with auto-updates and geo-targeting.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WPDealSniper.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class WPDealSniper {
  private $version = '1.0';
  private $plugin_slug = 'wpdealsniper';

  public function __construct() {
    add_action('init', array($this, 'init')); 
    add_shortcode('wpdealsniper_deals', array($this, 'render_deals_shortcode'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('wp_ajax_wpd_get_deals', array($this, 'ajax_get_deals'));
    add_action('wp_ajax_nopriv_wpd_get_deals', array($this, 'ajax_get_deals'));
  }

  public function init() {
    // Register custom post type for deals (optional for future extendability)
  }

  public function enqueue_scripts() {
    wp_enqueue_style('wpd-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('wpd-script', plugin_dir_url(__FILE__) . 'wpdealsniper.js', array('jquery'), $this->version, true);
    wp_localize_script('wpd-script', 'wpd_ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
  }

  public function render_deals_shortcode($atts) {
    $atts = shortcode_atts(array(
      'category' => 'all'
    ), $atts);

    ob_start();
    ?>
    <div id="wpd-deals-container" data-category="<?php echo esc_attr($atts['category']); ?>">
      <p>Loading latest deals...</p>
    </div>
    <?php
    return ob_get_clean();
  }

  // Simulate affiliate program API fetching (mock data)
  private function fetch_mock_deals($category, $geo) {
    $all_deals = array(
      array(
        'title' => '50% Off on Popular Sneakers',
        'url' => 'https://affiliate.example.com/deal1?ref=wpdealsniper',
        'code' => 'SNKR50',
        'expires' => '2026-01-31',
        'category' => 'fashion',
        'geo' => array('US','CA'),
        'description' => 'Top brand sneakers at half price!'
      ),
      array(
        'title' => '25% Discount on Electronics',
        'url' => 'https://affiliate.example.com/deal2?ref=wpdealsniper',
        'code' => '',
        'expires' => '2026-02-15',
        'category' => 'electronics',
        'geo' => array('US','UK','AU'),
        'description' => 'Save big on laptops and gadgets.'
      ),
      array(
        'title' => 'Free Shipping Worldwide',
        'url' => 'https://affiliate.example.com/deal3?ref=wpdealsniper',
        'code' => 'FREESHIP',
        'expires' => '2026-03-31',
        'category' => 'all',
        'geo' => array('ALL'),
        'description' => 'No minimum purchase. Shop now!'
      )
    );

    $filtered = array_filter($all_deals, function($deal) use ($category, $geo){
      if($category !== 'all' && strtolower($deal['category']) !== strtolower($category)) return false;
      if(in_array('ALL', $deal['geo'])) return true;
      return in_array($geo, $deal['geo']);
    });
    return array_values($filtered);
  }

  public function ajax_get_deals() {
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : 'all';

    // Determine user country by IP (basic)
    $geo = 'US'; // Default fallback
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
      $geo = sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']);
    } elseif (!empty($_SERVER['GEOIP_COUNTRY_CODE'])) {
      $geo = sanitize_text_field($_SERVER['GEOIP_COUNTRY_CODE']);
    }

    $deals = $this->fetch_mock_deals($category, $geo);

    wp_send_json_success($deals);
  }
}

new WPDealSniper();

// Minimal CSS (style.css content to be created externally or inline enqueue)

// Minimal JS (wpdealsniper.js):
// jQuery AJAX code to load deals and render
// This will be included inside the plugin file due to 'single file' constraint below

if (!function_exists('wpd_inline_js')) {
  add_action('wp_footer', function() {
    if (is_admin()) return;
    ?>
    <script type="text/javascript">
      (function($){
        $(document).ready(function(){
          var container = $('#wpd-deals-container');
          if(!container.length) return;
          var category = container.data('category') || 'all';
          $.post(wpd_ajax_obj.ajax_url, {
            action: 'wpd_get_deals',
            category: category
          }, function(response){
            if(response.success && response.data.length > 0){
              var html = '<ul class="wpd-deals-list">';
              response.data.forEach(function(deal){
                html += '<li><a href="'+deal.url+'" target="_blank" rel="nofollow noopener">'+deal.title+'</a>';
                if(deal.code) html += ' <strong>Code:</strong> '+deal.code;
                if(deal.expires) html += ' <em>(Expires: '+deal.expires+')</em>';
                if(deal.description) html += '<br><small>'+deal.description+'</small>';
                html += '</li>';
              });
              html += '</ul>';
              container.html(html);
            } else {
              container.html('<p>No deals found for your region.</p>');
            }
          });
        });
      })(jQuery);
    </script>
    <style>
      #wpd-deals-container { font-family: Arial, sans-serif; margin: 20px 0; }
      .wpd-deals-list { list-style: disc inside; padding-left: 0; }
      .wpd-deals-list li { margin-bottom: 12px; }
      .wpd-deals-list a { color: #0073aa; text-decoration: none; }
      .wpd-deals-list a:hover { text-decoration: underline; }
      .wpd-deals-list strong { color: #d54e21; margin-left: 6px; }
      .wpd-deals-list em { color: #999; margin-left: 8px; font-style: normal; }
      .wpd-deals-list small { display: block; color: #555; margin-top: 4px; }
    </style>
    <?php
  });
}