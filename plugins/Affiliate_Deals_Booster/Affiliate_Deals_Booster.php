<?php
/*
Plugin Name: Affiliate Deals Booster
Description: Dynamically manages affiliate coupon codes and limited-time discount offers with visitor targeting to increase conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deals_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealsBooster {
    public function __construct() {
        add_shortcode('affiliate_deal', array($this, 'render_deal_shortcode'));
        add_action('wp_footer', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_adb_fetch_deal', array($this, 'ajax_fetch_deal'));
        add_action('wp_ajax_nopriv_adb_fetch_deal', array($this, 'ajax_fetch_deal'));
    }

    // Enqueue JS and CSS
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('adb-script', plugin_dir_url(__FILE__) . 'adb-script.js', array('jquery'), '1.0', true);
        wp_localize_script('adb-script', 'adb_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_add_inline_script('adb-script', "
            jQuery(document).ready(function($){
                $('.adb-deal').each(function(){
                    var el = $(this);
                    var dealId = el.data('deal-id');
                    $.post(adb_ajax.ajaxurl, {action: 'adb_fetch_deal', deal_id: dealId}, function(response){
                        if(response.success){
                            el.html(response.data.html);
                        } else {
                            el.html('<em>Deal not available.</em>');
                        }
                    });
                });
            });
        ");
    }

    // AJAX handler to fetch deal data simulating dynamic targeting
    public function ajax_fetch_deal() {
        $deal_id = intval($_POST['deal_id'] ?? 0);
        if (!$deal_id) wp_send_json_error('Invalid deal ID');

        // Simulated deals data (in real use, fetch from DB or Affiliate APIs)
        $deals = array(
            1 => array(
                'title' => '20% OFF Premium Headphones',
                'coupon_code' => 'HEAD20',
                'url' => 'https://affiliate.example.com/product/headphones?aff_id=123',
                'expires' => date('Y-m-d H:i:s', strtotime('+2 days'))
            ),
            2 => array(
                'title' => 'Free Shipping on All Orders Over $50',
                'coupon_code' => 'FREESHIP',
                'url' => 'https://affiliate.example.com/shop?aff_id=123',
                'expires' => date('Y-m-d H:i:s', strtotime('+5 days'))
            ),
        );

        if (!isset($deals[$deal_id])) {
            wp_send_json_error('Deal not found');
        }

        $deal = $deals[$deal_id];

        // Check expiration
        if (strtotime($deal['expires']) < current_time('timestamp')) {
            wp_send_json_error('Deal expired');
        }

        $html = '<div class="adb-deal-container">';
        $html .= '<strong>' . esc_html($deal['title']) . '</strong><br>';
        $html .= 'Use coupon code: <code>' . esc_html($deal['coupon_code']) . '</code><br>';
        $html .= '<a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener noreferrer">Shop Now</a>';
        $html .= '</div>';

        wp_send_json_success(array('html' => $html));
    }

    // Shortcode handler [affiliate_deal id="1"]
    public function render_deal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        $deal_id = intval($atts['id']);

        if (!$deal_id) return '<em>No deal specified.</em>';

        // Output placeholder div for AJAX
        return '<div class="adb-deal" data-deal-id="' . esc_attr($deal_id) . '">Loading deal...</div>';
    }
}

new AffiliateDealsBooster();
