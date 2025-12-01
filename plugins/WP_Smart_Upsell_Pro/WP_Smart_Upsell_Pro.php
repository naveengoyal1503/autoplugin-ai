/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Upsell_Pro.php
*/
<?php
/**
 * Plugin Name: WP Smart Upsell Pro
 * Description: Intelligent upsell and cross-sell product recommendations for WooCommerce using behavioral data.
 * Version: 1.0
 * Author: Your Name
 * Text Domain: wp-smart-upsell-pro
 */

if (!defined('ABSPATH')) exit;

class WPSmartUpsellPro {
    public function __construct() {
        add_action('woocommerce_after_single_product_summary', array($this, 'display_recommendations'), 15);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_style('wpsu-style', plugin_dir_url(__FILE__) . 'style.css');
        }
    }

    // Simulated smart recommendation based on simple purchase history
    public function get_recommendations($current_product_id) {
        if (!class_exists('WooCommerce')) return array();

        $customer_orders = wc_get_orders(array(
            'limit' => 20,
            'customer' => get_current_user_id(),
            'status' => 'completed',
        ));

        $bought_products = array();
        foreach ($customer_orders as $order) {
            foreach ($order->get_items() as $item) {
                $bought_products[] = $item->get_product_id();
            }
        }

        // Get related products by simple logic: products frequently bought with those products.
        $related_ids = array();
        foreach ($bought_products as $pid) {
            if ($pid !== $current_product_id) {
                $related_ids[] = $pid;
            }
        }

        if (empty($related_ids)) {
            $related_ids = wc_get_related_products($current_product_id, 4);
        }

        return array_slice(array_unique($related_ids), 0, 4);
    }

    public function display_recommendations() {
        global $product;
        if (!$product) return;

        $recommend_ids = $this->get_recommendations($product->get_id());
        if (empty($recommend_ids)) return;

        echo '<div class="wpsu-recommendations"><h3>' . __('You may also like', 'wp-smart-upsell-pro') . '</h3><ul class="wpsu-products">';

        foreach ($recommend_ids as $pid) {
            $rec_product = wc_get_product($pid);
            if (!$rec_product) continue;

            echo '<li class="wpsu-product">';
            echo '<a href="' . esc_url(get_permalink($pid)) . '">' . $rec_product->get_image() . '</a>';
            echo '<a href="' . esc_url(get_permalink($pid)) . '">' . esc_html($rec_product->get_name()) . '</a>';
            echo '<span class="price">' . wp_kses_post($rec_product->get_price_html()) . '</span>';
            echo '<a href="?add-to-cart=' . intval($pid) . '" class="button">' . __('Add to cart', 'wp-smart-upsell-pro') . '</a>';
            echo '</li>';
        }

        echo '</ul></div>';
    }
}

new WPSmartUpsellPro();