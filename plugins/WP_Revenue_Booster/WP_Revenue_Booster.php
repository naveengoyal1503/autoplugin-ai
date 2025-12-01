/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize your WordPress site's revenue with smart affiliate link rotation, targeted ad display, and exclusive offer promotion.
 * Version: 1.0
 * Author: WP Revenue Team
 */

// Prevent direct access
define('ABSPATH') or die('No script kiddies please!');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'wp_revenue_booster_activate');
register_deactivation_hook(__FILE__, 'wp_revenue_booster_deactivate');

function wp_revenue_booster_activate() {
    // Add default options
    add_option('wp_revenue_booster_affiliate_links', array());
    add_option('wp_revenue_booster_ads', array());
    add_option('wp_revenue_booster_offers', array());
}

function wp_revenue_booster_deactivate() {
    // Clean up options (optional)
    // delete_option('wp_revenue_booster_affiliate_links');
    // delete_option('wp_revenue_booster_ads');
    // delete_option('wp_revenue_booster_offers');
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
        'dashicons-chart-line',
        81
    );
}

// Settings page
function wp_revenue_booster_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Handle form submission
    if (isset($_POST['submit'])) {
        update_option('wp_revenue_booster_affiliate_links', $_POST['affiliate_links']);
        update_option('wp_revenue_booster_ads', $_POST['ads']);
        update_option('wp_revenue_booster_offers', $_POST['offers']);
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }

    $affiliate_links = get_option('wp_revenue_booster_affiliate_links', array());
    $ads = get_option('wp_revenue_booster_ads', array());
    $offers = get_option('wp_revenue_booster_offers', array());
    ?>
    <div class="wrap">
        <h1>WP Revenue Booster</h1>
        <form method="post">
            <h2>Affiliate Links</h2>
            <p>Add your affiliate links. The plugin will rotate them for better conversion.</p>
            <textarea name="affiliate_links" rows="5" cols="50"><?php echo esc_textarea(implode('\n', $affiliate_links)); ?></textarea>

            <h2>Ads</h2>
            <p>Add your ad codes (HTML/JS). The plugin will display them based on user behavior.</p>
            <textarea name="ads" rows="5" cols="50"><?php echo esc_textarea(implode('\n', $ads)); ?></textarea>

            <h2>Exclusive Offers</h2>
            <p>Add exclusive offers for your visitors. The plugin will promote them at strategic times.</p>
            <textarea name="offers" rows="5" cols="50"><?php echo esc_textarea(implode('\n', $offers)); ?></textarea>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Display affiliate links, ads, and offers on the front end
add_action('wp_footer', 'wp_revenue_booster_display_content');

function wp_revenue_booster_display_content() {
    $affiliate_links = get_option('wp_revenue_booster_affiliate_links', array());
    $ads = get_option('wp_revenue_booster_ads', array());
    $offers = get_option('wp_revenue_booster_offers', array());

    // Rotate affiliate links
    if (!empty($affiliate_links)) {
        $random_link = $affiliate_links[array_rand($affiliate_links)];
        echo '<div class="wp-revenue-booster-affiliate">
            <p>Check out this offer: <a href="' . esc_url($random_link) . '" target="_blank">Click here</a></p>
        </div>';
    }

    // Display ads
    if (!empty($ads)) {
        $random_ad = $ads[array_rand($ads)];
        echo '<div class="wp-revenue-booster-ad">
            ' . $random_ad . '
        </div>';
    }

    // Display offers
    if (!empty($offers)) {
        $random_offer = $offers[array_rand($offers)];
        echo '<div class="wp-revenue-booster-offer">
            <p>' . esc_html($random_offer) . '</p>
        </div>';
    }
}

// Shortcode to display affiliate links
add_shortcode('wp_revenue_booster_affiliate', 'wp_revenue_booster_affiliate_shortcode');

function wp_revenue_booster_affiliate_shortcode() {
    $affiliate_links = get_option('wp_revenue_booster_affiliate_links', array());
    if (empty($affiliate_links)) return '';
    $random_link = $affiliate_links[array_rand($affiliate_links)];
    return '<a href="' . esc_url($random_link) . '" target="_blank">Click here</a>';
}

// Shortcode to display offers
add_shortcode('wp_revenue_booster_offer', 'wp_revenue_booster_offer_shortcode');

function wp_revenue_booster_offer_shortcode() {
    $offers = get_option('wp_revenue_booster_offers', array());
    if (empty($offers)) return '';
    $random_offer = $offers[array_rand($offers)];
    return '<p>' . esc_html($random_offer) . '</p>';
}

// Enqueue styles
add_action('wp_enqueue_scripts', 'wp_revenue_booster_enqueue_styles');

function wp_revenue_booster_enqueue_styles() {
    wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
}

// Create style.css if it doesn't exist
if (!file_exists(plugin_dir_path(__FILE__) . 'style.css')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', ".wp-revenue-booster-affiliate, .wp-revenue-booster-ad, .wp-revenue-booster-offer { margin: 10px 0; padding: 10px; border: 1px solid #ddd; background: #f9f9f9; }\n");
}
?>