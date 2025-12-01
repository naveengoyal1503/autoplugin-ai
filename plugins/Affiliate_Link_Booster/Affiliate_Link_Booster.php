<?php
/*
Plugin Name: Affiliate Link Booster
Description: Enhance affiliate links with coupons, price comparisons, and track clicks to boost your affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Booster.php
*/

if (!defined('ABSPATH')) exit;

class Affiliate_Link_Booster {

    public function __construct() {
        add_filter('the_content', array($this, 'enhance_affiliate_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_alb_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_alb_track_click', array($this, 'track_click'));
    }

    // Enqueue necessary JS for click tracking
    public function enqueue_scripts() {
        wp_enqueue_script('alb-script', plugin_dir_url(__FILE__) . 'alb-script.js', array('jquery'), '1.0', true);
        wp_localize_script('alb-script', 'alb_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    // Enhance affiliate links in the post content
    public function enhance_affiliate_links($content) {
        // Regex to find typical affiliate URLs (e.g., amazon, clickbank, etc.)
        $pattern = '/(https?:\/\/(?:www\.)?(?:amazon|clickbank|ebay|shareasale)\.com[^"\'\s<]+)/i';

        $content = preg_replace_callback($pattern, array($this, 'augment_link'), $content);

        return $content;
    }

    // Callback to add coupons, price comparison, and tracking
    private function augment_link($matches) {
        $url = esc_url($matches);

        // For demonstration, attach dummy coupon and price info
        $coupon = 'SAVE10';
        $price_comparison = '$19.99 vs $21.49 elsewhere';

        // Create enhanced link with data attributes
        $enhanced_link = '<a href="' . $url . '" class="alb-affiliate-link" data-url="' . esc_attr($url) . '" data-coupon="' . esc_attr($coupon) . '" target="_blank" rel="nofollow noopener">' . $url . '</a>';

        // Append coupon and price info
        $info = '<span class="alb-info" style="font-size:0.9em;color:#090;margin-left:5px;">Coupon: ' . $coupon . ' | Price: ' . $price_comparison . '</span>';

        return $enhanced_link . $info;
    }

    // AJAX function to track clicks
    public function track_click() {
        if (!isset($_POST['url'])) {
            wp_send_json_error('Missing URL');
        }

        $url = esc_url_raw($_POST['url']);

        // Store click count in options (for demo; use custom DB table in production)
        $clicks = get_option('alb_clicks', array());
        $clicks[$url] = isset($clicks[$url]) ? $clicks[$url] + 1 : 1;
        update_option('alb_clicks', $clicks);

        wp_send_json_success(array('message' => 'Click tracked', 'count' => $clicks[$url]));
    }
}

new Affiliate_Link_Booster();

// JavaScript for click tracking inline to keep single file
add_action('wp_footer', function() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.alb-affiliate-link').on('click', function(e) {
            var url = $(this).data('url');
            $.post(alb_ajax.ajaxurl, {
                action: 'alb_track_click',
                url: url
            });
        });
    });
    </script>
    <?php
});
