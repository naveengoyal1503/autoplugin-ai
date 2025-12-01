/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Automatically optimizes ad placement, affiliate links, and upsell offers to maximize revenue on any WordPress site.
 * Version: 1.0
 * Author: Revenue Labs
 * Author URI: https://example.com
 * License: GPL2
 */

// Prevent direct access
define('ABSPATH') or die('No script kiddies please!');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wp_revenue_booster_activate');
register_deactivation_hook(__FILE__, 'wp_revenue_booster_deactivate');

function wp_revenue_booster_activate() {
    // Add default options
    add_option('wp_revenue_booster_ads_enabled', 1);
    add_option('wp_revenue_booster_affiliate_enabled', 1);
    add_option('wp_revenue_booster_upsells_enabled', 1);
}

function wp_revenue_booster_deactivate() {
    // Clean up if needed
}

// Add admin menu
add_action('admin_menu', 'wp_revenue_booster_menu');
function wp_revenue_booster_menu() {
    add_menu_page(
        'WP Revenue Booster',
        'Revenue Booster',
        'manage_options',
        'wp-revenue-booster',
        'wp_revenue_booster_settings_page',
        'dashicons-chart-line'
    );
}

// Settings page
function wp_revenue_booster_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    ?>
    <div class="wrap">
        <h1>WP Revenue Booster Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wp_revenue_booster_options'); ?>
            <?php do_settings_sections('wp-revenue-booster'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Ad Optimization</th>
                    <td><input type="checkbox" name="wp_revenue_booster_ads_enabled" value="1" <?php checked(1, get_option('wp_revenue_booster_ads_enabled')); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable Affiliate Link Optimization</th>
                    <td><input type="checkbox" name="wp_revenue_booster_affiliate_enabled" value="1" <?php checked(1, get_option('wp_revenue_booster_affiliate_enabled')); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Enable Upsell Offers</th>
                    <td><input type="checkbox" name="wp_revenue_booster_upsells_enabled" value="1" <?php checked(1, get_option('wp_revenue_booster_upsells_enabled')); ?> /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action('admin_init', 'wp_revenue_booster_settings');
function wp_revenue_booster_settings() {
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_ads_enabled');
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_affiliate_enabled');
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_upsells_enabled');
}

// Main optimization logic
add_action('the_content', 'wp_revenue_booster_optimize_content');
function wp_revenue_booster_optimize_content($content) {
    if (is_admin()) return $content;

    if (get_option('wp_revenue_booster_ads_enabled')) {
        $ad_code = '<div class="wp-revenue-booster-ad">[Your Ad Here]</div>';
        $content = wp_revenue_booster_insert_after_paragraph($ad_code, 2, $content);
    }

    if (get_option('wp_revenue_booster_affiliate_enabled')) {
        $affiliate_link = '<div class="wp-revenue-booster-affiliate"><a href="https://example.com/affiliate">Check out this product!</a></div>';
        $content = wp_revenue_booster_insert_after_paragraph($affiliate_link, 4, $content);
    }

    if (get_option('wp_revenue_booster_upsells_enabled')) {
        $upsell = '<div class="wp-revenue-booster-upsell">Upgrade now for exclusive content!</div>';
        $content = wp_revenue_booster_insert_after_paragraph($upsell, 6, $content);
    }

    return $content;
}

// Helper function to insert after Nth paragraph
function wp_revenue_booster_insert_after_paragraph($insertion, $paragraph_id, $content) {
    $closing_p = '</p>';
    $paragraphs = explode($closing_p, $content);
    foreach ($paragraphs as $index => $paragraph) {
        if (trim($paragraph)) {
            $paragraphs[$index] .= $closing_p;
        }
        if ($paragraph_id == $index + 1) {
            $paragraphs[$index] .= $insertion;
        }
    }
    return implode('', $paragraphs);
}

// Add CSS
add_action('wp_head', 'wp_revenue_booster_styles');
function wp_revenue_booster_styles() {
    echo '<style>
        .wp-revenue-booster-ad, .wp-revenue-booster-affiliate, .wp-revenue-booster-upsell {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>';
}
?>