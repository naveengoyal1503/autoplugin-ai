<?php
/*
Plugin Name: Affiliate Deal Booster
Description: Aggregate affiliate coupons and deals dynamically to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {

    private $plugin_slug = 'affiliate-deal-booster';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('affiliate_deals', array($this, 'render_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', $this->plugin_slug, array($this, 'admin_page'), 'dashicons-tickets', 100);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-deal-booster-style', plugins_url('style.css', __FILE__));
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        echo '<div class="wrap"><h1>Affiliate Deal Booster Settings</h1>';
        echo '<p>This plugin dynamically fetches and displays affiliate coupon codes and deals.</p>';
        echo '<p>Settings and premium feature setup coming soon.</p></div>';
    }

    // Example static coupon data for demo purposes
    private function get_coupons() {
        return array(
            array('title' => '25% off on ShopX', 'code' => 'SHOPX25', 'url' => 'https://affiliate.link/shopx?coupon=SHOPX25'),
            array('title' => 'Free Shipping at BuyFast', 'code' => 'FREESHIP', 'url' => 'https://affiliate.link/buyfast?coupon=FREESHIP'),
            array('title' => '10$ off Electronics Hub', 'code' => 'ELEC10', 'url' => 'https://affiliate.link/electronicshub?coupon=ELEC10')
        );
    }

    public function render_deals_shortcode($atts) {
        $coupons = $this->get_coupons();
        ob_start();
        echo '<div class="affiliate-deal-booster-container">';
        echo '<ul class="affiliate-deal-list">';
        foreach ($coupons as $coupon) {
            echo '<li class="deal-item">';
            echo '<strong>' . esc_html($coupon['title']) . '</strong><br />';
            echo 'Code: <code>' . esc_html($coupon['code']) . '</code> '; 
            echo '<a href="' . esc_url($coupon['url']) . '" target="_blank" rel="nofollow noopener">Use Deal</a>';
            echo '</li>';
        }
        echo '</ul></div>';
        return ob_get_clean();
    }
}

new AffiliateDealBooster();
