<?php
/*
Plugin Name: WooCommerce Smart Discount Optimizer
Plugin URI: https://example.com/plugins/woo-smart-discount-optimizer
Description: Applies personalized discounts dynamically based on user behavior and purchase history.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WooCommerce_Smart_Discount_Optimizer.php
Text Domain: wc-smart-discount
Requires at least: 5.0
Tested up to: 6.3
WC requires at least: 3.0
WC tested up to: 7.0
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class WC_Smart_Discount_Optimizer {
    public function __construct() {
        // Hook into WooCommerce cart
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_smart_discounts'), 20);
        add_action('woocommerce_cart_calculate_fees', array($this, 'apply_additional_discounts'));
        add_action('woocommerce_checkout_order_processed', array($this, 'track_order_for_discounts'), 10, 1);
    }

    // Apply dynamic discounts based on user purchase frequency
    public function apply_smart_discounts($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;
        if (did_action('woocommerce_before_calculate_totals') >= 2) return;

        $user_id = get_current_user_id();
        $purchase_count = 0;

        if ($user_id) {
            $orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'limit' => -1,
                'return' => 'ids',
                'status' => array('completed','processing','on-hold'),
            ));
            $purchase_count = count($orders);
        }

        $discount_percentage = 0;

        // Tiered discount based on number of past orders
        if ($purchase_count >= 10) {
            $discount_percentage = 15; // loyal customers
        } elseif ($purchase_count >= 5) {
            $discount_percentage = 10;
        } elseif ($purchase_count >= 1) {
            $discount_percentage = 5;
        } else {
            $discount_percentage = 0;
        }

        if ($discount_percentage > 0) {
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                $price = $cart_item['data']->get_price();
                $new_price = $price - ($price * ($discount_percentage / 100));
                $cart_item['data']->set_price($new_price);
            }
        }
    }

    // Apply additional fees or discounts (for future premium features)
    public function apply_additional_discounts($cart) {
        // Example: no additional fee in free version
    }

    // Track order count for analytics or advanced discount logic (future-proofing)
    public function track_order_for_discounts($order_id) {
        // Could extend to track and analyze more data
    }
}

new WC_Smart_Discount_Optimizer();
