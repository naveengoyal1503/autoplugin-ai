/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Upsell_Pro.php
*/
<?php
/**
 * Plugin Name: WP Smart Upsell Pro
 * Description: Automatically recommends upsell and cross-sell offers to customers based on their browsing and purchase behavior.
 * Version: 1.0
 * Author: Your Company
 */

if (!defined('ABSPATH')) exit;

// Main plugin class
class WPSmartUpsellPro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('woocommerce_after_single_product', array($this, 'render_upsell'));
        add_action('woocommerce_after_cart', array($this, 'render_cart_upsell'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'notice_woocommerce_required'));
        }
    }

    public function notice_woocommerce_required() {
        echo '<div class="notice notice-error"><p>WP Smart Upsell Pro requires WooCommerce to be installed and activated.</p></div>';
    }

    public function admin_menu() {
        add_options_page(
            'WP Smart Upsell Pro',
            'Smart Upsell',
            'manage_options',
            'wp-smart-upsell-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Smart Upsell Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_smart_upsell_pro_options');
                do_settings_sections('wp-smart-upsell-pro');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-smart-upsell-pro', plugin_dir_url(__FILE__) . 'assets/style.css');
    }

    public function render_upsell() {
        if (!is_product()) return;

        $product_id = get_the_ID();
        $upsell_ids = $this->get_upsell_products($product_id);

        if (empty($upsell_ids)) return;

        echo '<div class="wp-smart-upsell-pro-upsell">
                <h3>Recommended for you</h3>
                <div class="wp-smart-upsell-pro-products">';

        foreach ($upsell_ids as $id) {
            $product = wc_get_product($id);
            if ($product) {
                echo '<div class="wp-smart-upsell-pro-product">
                        <a href="' . get_permalink($id) . '">
                            ' . $product->get_image() . '<br>
                            ' . $product->get_name() . '<br>
                            ' . $product->get_price_html() . '
                        </a>
                      </div>';
            }
        }

        echo '</div></div>';
    }

    public function render_cart_upsell() {
        $upsell_ids = $this->get_cart_upsell_products();

        if (empty($upsell_ids)) return;

        echo '<div class="wp-smart-upsell-pro-upsell">
                <h3>Complete your order</h3>
                <div class="wp-smart-upsell-pro-products">';

        foreach ($upsell_ids as $id) {
            $product = wc_get_product($id);
            if ($product) {
                echo '<div class="wp-smart-upsell-pro-product">
                        <a href="' . get_permalink($id) . '">
                            ' . $product->get_image() . '<br>
                            ' . $product->get_name() . '<br>
                            ' . $product->get_price_html() . '
                        </a>
                      </div>';
            }
        }

        echo '</div></div>';
    }

    private function get_upsell_products($product_id) {
        $upsell_ids = get_post_meta($product_id, '_upsell_ids', true);
        if (!$upsell_ids) {
            $upsell_ids = array();
            $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
            if ($categories) {
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => 3,
                    'post__not_in' => array($product_id),
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => $categories
                        )
                    )
                );
                $products = get_posts($args);
                foreach ($products as $p) {
                    $upsell_ids[] = $p->ID;
                }
            }
        }
        return $upsell_ids;
    }

    private function get_cart_upsell_products() {
        $upsell_ids = array();
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $upsell_ids = array_merge($upsell_ids, $this->get_upsell_products($product_id));
        }
        return array_unique($upsell_ids);
    }
}

new WPSmartUpsellPro();
