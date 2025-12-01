/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateDeal_Booster.php
*/
<?php
/**
 * Plugin Name: AffiliateDeal Booster
 * Plugin URI: https://example.com/affiliate-deal-booster
 * Description: Dynamically generates personalized affiliate coupon deals and discount notifications to boost affiliate conversions.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    private $cookie_name = 'adb_user_affiliates';
    private $deals = array(
        array('id' => 'amazon', 'name' => 'Amazon', 'coupon' => 'SAVE10', 'url' => 'https://amazon.com/?tag=youraffiliateid'),
        array('id' => 'ebay', 'name' => 'eBay', 'coupon' => 'EBAY20', 'url' => 'https://ebay.com/?campid=youraffiliateid'),
        array('id' => 'flipkart', 'name' => 'Flipkart', 'coupon' => 'FLIP25', 'url' => 'https://flipkart.com/?affid=youraffiliateid')
    );

    public function __construct() {
        add_action('wp_footer', array($this, 'inject_deal_notification'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_adb_track_click', array($this, 'track_click')); 
        add_action('wp_ajax_nopriv_adb_track_click', array($this, 'track_click'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('adb-main-js', plugin_dir_url(__FILE__) . 'adb-main.js', array('jquery'), '1.0', true);
        wp_localize_script('adb-main-js', 'adb_ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_add_inline_script('adb-main-js', 'var adb_deals = ' . json_encode($this->deals) . ';');
        wp_add_inline_script('adb-main-js', '(function(){
          jQuery(document).ready(function($){
            // Show deal notification randomly
            if(Math.random() < 0.5) {
              var deal = adb_deals[Math.floor(Math.random() * adb_deals.length)];
              var html = `<div id="adb-notification" style="position:fixed;bottom:20px;right:20px;background:#0085ba;color:#fff;padding:15px;border-radius:5px;box-shadow:0 0 10px rgba(0,0,0,0.3);z-index:9999;max-width:320px;font-family:sans-serif;">
                <strong>Special Offer!</strong><br />Use coupon <strong>${deal.coupon}</strong> on <a href="#" id="adb-link" style="color:#fff;text-decoration:underline;">${deal.name}</a> and save now!
              </div>`;
              $('body').append(html);
              $('#adb-link').attr('href', deal.url).on('click', function(e){
                e.preventDefault();
                $.post(adb_ajax_obj.ajax_url, {action:'adb_track_click', deal_id: deal.id}, function() {
                  window.open(deal.url, '_blank');
                  $('#adb-notification').fadeOut(400, function(){ $(this).remove(); });
                });
              });
            }
          });
        })();');
    }

    public function inject_deal_notification() {
        // Inline script done in enqueue_scripts for async
    }

    public function track_click() {
        $deal_id = sanitize_text_field($_POST['deal_id'] ?? '');
        if ($deal_id && in_array($deal_id, array_column($this->deals, 'id'))) {
            // For demo purposes log clicks in option; in production, integrate with analytics or affiliate tracking
            $clicks = get_option('adb_clicks', array());
            if (!isset($clicks[$deal_id])) $clicks[$deal_id] = 0;
            $clicks[$deal_id]++;
            update_option('adb_clicks', $clicks);
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }
}

new AffiliateDealBooster();

// Embedding JavaScript file content inline for single-file plugin compatibility
add_action('wp_print_footer_scripts', function() {
?>
<script type="text/javascript">
// Script content is added inline dynamically by wp_add_inline_script in enqueue_scripts
</script>
<?php
});
