/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Upsell_Pro.php
*/
<?php
/**
 * Plugin Name: WP Smart Upsell Pro
 * Description: Increase sales with smart upsell and cross-sell suggestions.
 * Version: 1.0
 * Author: Your Company
 */

if (!defined('ABSPATH')) exit;

// Main plugin class
class WPSmartUpsellPro {

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        // Register hooks
        add_action('woocommerce_after_single_product_summary', array($this, 'display_upsell'), 20);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_style('wp-smart-upsell-pro', plugin_dir_url(__FILE__) . 'assets/style.css');
            wp_enqueue_script('wp-smart-upsell-pro', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0', true);
        }
    }

    public function display_upsell() {
        global $product;

        $upsell_ids = $this->get_smart_upsells($product->get_id());

        if (!empty($upsell_ids)) {
            echo '<div class="wp-smart-upsell-pro">
                    <h3>Customers also bought these:</h3>
                    <div class="upsell-products">';

            foreach ($upsell_ids as $id) {
                $upsell_product = wc_get_product($id);
                if ($upsell_product) {
                    echo '<div class="upsell-item">
                            <a href="' . get_permalink($id) . '">
                                ' . $upsell_product->get_image() . '
                                <p>' . $upsell_product->get_name() . '</p>
                                <span>' . $upsell_product->get_price_html() . '</span>
                            </a>
                          </div>';
                }
            }

            echo '</div></div>';
        }
    }

    private function get_smart_upsells($product_id) {
        // Simple logic: get products from same category
        $categories = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => 3,
            'post__not_in' => array($product_id),
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $categories,
                ),
            ),
        );

        $upsell_query = new WP_Query($args);
        $upsell_ids = array();
        while ($upsell_query->have_posts()) {
            $upsell_query->the_post();
            $upsell_ids[] = get_the_ID();
        }
        wp_reset_postdata();

        return $upsell_ids;
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
        echo '<div class="wrap">
                <h1>WP Smart Upsell Pro Settings</h1>
                <p>Configure your upsell and cross-sell suggestions here.</p>
                <form method="post" action="options.php">
                    ' . wp_nonce_field('update-options') . '
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Enable Smart Upsell</th>
                            <td><input type="checkbox" name="wp_smart_upsell_enable" value="1" ' . checked(1, get_option('wp_smart_upsell_enable'), false) . ' /></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="Save Changes" />
                    </p>
                </form>
              </div>';
    }
}

// Initialize plugin
new WPSmartUpsellPro();
