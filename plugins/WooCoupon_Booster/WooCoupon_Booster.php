/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WooCoupon_Booster.php
*/
<?php
/**
 * Plugin Name: WooCoupon Booster
 * Description: Generates personalized coupon codes dynamically based on user browsing and purchase behavior to increase WooCommerce sales.
 * Version: 1.0
 * Author: YourName
 * Text Domain: woocoupon-booster
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WooCouponBooster {
    public function __construct() {
        // Hook into WooCommerce coupons
        add_action('woocommerce_before_cart', array($this, 'generate_personalized_coupon'));
        add_action('woocommerce_cart_calculate_fees', array($this, 'apply_dynamic_coupon_discount'));
        add_action('woocommerce_cart_coupon_removed', array($this, 'handle_coupon_removal'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('woocoupon_booster_offer', array($this, 'display_coupon_offer'));
    }

    public function enqueue_scripts() {
        // For simple UI notifications
        wp_enqueue_style('woocoupon-booster-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    // Generate or set coupon code in session based on user behavior
    public function generate_personalized_coupon() {
        if ( ! is_user_logged_in() ) {
            // For guests, do not personalize, or use generic coupon
            return;
        }

        if ( WC()->session->get('woocoupon_booster_code') ) {
            // Coupon code already generated
            return;
        }

        $user_id = get_current_user_id();

        // Basic strategy: provide discount based on total spent
        $total_spent = wc_get_customer_total_spent($user_id);

        if ( $total_spent > 500 ) {
            $discount = 20; // 20% for big spenders
        } elseif ( $total_spent > 100 ) {
            $discount = 10; // 10% for medium spenders
        } else {
            $discount = 5; // 5% for others
        }

        $coupon_code = 'WCB-' . strtoupper( wp_generate_password(5, false, false) );

        // Create WooCommerce coupon programmatically
        $coupon = new WC_Coupon();
        $coupon->set_code($coupon_code);
        $coupon->set_discount_type('percent');
        $coupon->set_amount($discount);
        $coupon->set_individual_use(true);
        $coupon->set_usage_limit(1);
        $coupon->set_description('Personalized discount done by WooCoupon Booster');
        $coupon->save();

        WC()->session->set('woocoupon_booster_code', $coupon_code);

        // Show coupon notice
        wc_add_notice(sprintf(__('Special for you: Use coupon <strong>%s</strong> to get %d%% off!','woocoupon-booster'), $coupon_code, $discount), 'success');
    }

    // Apply the coupon discount dynamically if coupon is in session
    public function apply_dynamic_coupon_discount() {
        $coupon_code = WC()->session->get('woocoupon_booster_code');
        if ( $coupon_code && ! WC()->cart->has_discount($coupon_code) ) {
            WC()->cart->apply_coupon($coupon_code);
        }
    }

    // Handle coupon removal - allow reapply next page load
    public function handle_coupon_removal($coupon_code) {
        if ( $coupon_code === WC()->session->get('woocoupon_booster_code') ) {
            WC()->session->__unset('woocoupon_booster_code');
            wc_add_notice(__('Your personalized coupon was removed. It can be regenerated on next visit.','woocoupon-booster'),'notice');
        }
    }

    // Shortcode to display coupon offer anywhere on site
    public function display_coupon_offer() {
        $coupon_code = WC()->session->get('woocoupon_booster_code');
        if ( ! $coupon_code ) {
            return '<div class="woocoupon-booster-offer">No personalized coupon available. Try adding items to cart.</div>';
        }
        $coupon = new WC_Coupon($coupon_code);
        $amount = $coupon->get_amount();

        return '<div class="woocoupon-booster-offer">Use coupon <strong>' . esc_html($coupon_code) . '</strong> to get <strong>' . esc_html($amount) . '% off</strong> your order!</div>';
    }
}

new WooCouponBooster();
