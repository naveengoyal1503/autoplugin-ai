/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI-Driven_Dynamic_Pricing_for_WooCommerce.php
*/
<?php
/**
 * Plugin Name: AI-Driven Dynamic Pricing for WooCommerce
 * Plugin URI: https://example.com/plugins/ai-dynamic-pricing
 * Description: Dynamically adjusts WooCommerce product prices using demand, inventory, and competitor data.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AIDynamicPricing {
    public function __construct() {
        add_action('woocommerce_product_options_pricing', array($this, 'add_dynamic_pricing_field'));
        add_action('woocommerce_admin_process_product_object', array($this, 'save_dynamic_pricing_field'));
        add_filter('woocommerce_get_price', array($this, 'adjust_dynamic_price'), 10, 2);
    }

    public function add_dynamic_pricing_field() {
        woocommerce_wp_text_input(array(
            'id' => '_dynamic_pricing_enabled',
            'label' => __('Enable Dynamic Pricing', 'ai-dynamic-pricing'),
            'description' => __('Check to enable AI-driven dynamic pricing for this product.', 'ai-dynamic-pricing'),
            'type' => 'checkbox',
            'desc_tip' => true
        ));

        woocommerce_wp_text_input(array(
            'id' => '_dynamic_min_price',
            'label' => __('Minimum Price', 'ai-dynamic-pricing'),
            'description' => __('Minimum price allowed during dynamic adjustment.', 'ai-dynamic-pricing'),
            'type' => 'number',
            'custom_attributes' => array('step' => '0.01', 'min' => '0'),
            'desc_tip' => true
        ));

        woocommerce_wp_text_input(array(
            'id' => '_dynamic_max_price',
            'label' => __('Maximum Price', 'ai-dynamic-pricing'),
            'description' => __('Maximum price allowed during dynamic adjustment.', 'ai-dynamic-pricing'),
            'type' => 'number',
            'custom_attributes' => array('step' => '0.01', 'min' => '0'),
            'desc_tip' => true
        ));
    }

    public function save_dynamic_pricing_field($product) {
        $enabled = isset($_POST['_dynamic_pricing_enabled']) ? 'yes' : 'no';
        $product->update_meta_data('_dynamic_pricing_enabled', $enabled);

        if (isset($_POST['_dynamic_min_price'])) {
            $min_price = floatval($_POST['_dynamic_min_price']);
            $product->update_meta_data('_dynamic_min_price', $min_price);
        }

        if (isset($_POST['_dynamic_max_price'])) {
            $max_price = floatval($_POST['_dynamic_max_price']);
            $product->update_meta_data('_dynamic_max_price', $max_price);
        }
    }

    public function adjust_dynamic_price($price, $product) {
        $enabled = $product->get_meta('_dynamic_pricing_enabled');
        if ($enabled !== 'yes') {
            return $price;
        }

        $min_price = floatval($product->get_meta('_dynamic_min_price'));
        $max_price = floatval($product->get_meta('_dynamic_max_price'));

        // Simplified demand and competitor-based adjustment logic
        $demand_factor = $this->get_demand_factor($product->get_id());
        $competitor_factor = $this->get_competitor_factor($product->get_id());

        // Calculate new price modifiers
        $adjusted_price = $price * $demand_factor * $competitor_factor;

        // Clamp price to min and max
        if ($min_price > 0 && $adjusted_price < $min_price) {
            $adjusted_price = $min_price;
        }

        if ($max_price > 0 && $adjusted_price > $max_price) {
            $adjusted_price = $max_price;
        }

        return round($adjusted_price, 2);
    }

    private function get_demand_factor($product_id) {
        // Placeholder: demand factor based on inventory level
        $product = wc_get_product($product_id);
        $stock_quantity = $product->get_stock_quantity();

        if ($stock_quantity === null) {
            return 1.0; // No stock management, no adjustment
        }

        if ($stock_quantity <= 5) {
            return 1.2; // Increase price by 20% if stock is low
        } elseif ($stock_quantity <= 20) {
            return 1.05; // Slight increase for moderate stock
        } else {
            return 0.95; // Discount if stock is plenty
        }
    }

    private function get_competitor_factor($product_id) {
        // Placeholder: static competitor pricing factor
        // In real plugin, integrate competitor pricing API or input

        return 1.0; // No adjustment by default
    }
}

new AIDynamicPricing();
