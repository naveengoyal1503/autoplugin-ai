/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Upsell_Engine.php
*/
<?php
/**
 * Plugin Name: WP Smart Upsell Engine
 * Description: Boost sales with intelligent upsell and cross-sell recommendations.
 * Version: 1.0
 * Author: Your Company
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSmartUpsellEngine {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('woocommerce_after_single_product_summary', array($this, 'display_upsell'), 20);
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'notice_missing_woocommerce'));
            return;
        }
    }

    public function notice_missing_woocommerce() {
        echo '<div class="notice notice-error"><p>WP Smart Upsell Engine requires WooCommerce to be installed and activated.</p></div>';
    }

    public function admin_menu() {
        add_options_page(
            'WP Smart Upsell Engine',
            'Smart Upsell',
            'manage_options',
            'wp-smart-upsell-engine',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        echo '<div class="wrap"><h1>WP Smart Upsell Engine Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('wp_smart_upsell_engine_options');
        do_settings_sections('wp_smart_upsell_engine_options');
        submit_button();
        echo '</form></div>';
    }

    public function display_upsell() {
        if (!is_product()) return;

        global $product;
        $upsell_ids = $product->get_upsell_ids();
        if (empty($upsell_ids)) return;

        echo '<div class="upsell-section"><h3>You may also like</h3><ul class="product_list_widget">';
        foreach ($upsell_ids as $upsell_id) {
            $upsell_product = wc_get_product($upsell_id);
            if ($upsell_product) {
                echo '<li><a href="' . get_permalink($upsell_product->get_id()) . '">' . $upsell_product->get_name() . '</a></li>';
            }
        }
        echo '</ul></div>';
    }
}

new WPSmartUpsellEngine();
