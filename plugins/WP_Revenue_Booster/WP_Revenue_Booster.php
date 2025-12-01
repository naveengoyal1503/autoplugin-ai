/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Boost your WordPress site's revenue with smart affiliate, coupon, and sponsored content placement.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'inject_monetization_content'));
        add_shortcode('wp_revenue_booster', array($this, 'shortcode_handler'));
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_revenue_booster_options');
                do_settings_sections('wp-revenue-booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function inject_monetization_content($content) {
        if (is_single()) {
            $offer = $this->get_smart_offer();
            if ($offer) {
                $content .= '<div class="wp-revenue-booster-offer">' . $offer . '</div>';
            }
        }
        return $content;
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'type' => 'affiliate',
            'id' => '',
        ), $atts, 'wp_revenue_booster');

        return $this->get_offer_by_type($atts['type'], $atts['id']);
    }

    private function get_smart_offer() {
        // Logic to determine the best offer based on context
        $offers = get_option('wp_revenue_booster_offers', array());
        if (!empty($offers)) {
            $random_key = array_rand($offers);
            return $offers[$random_key];
        }
        return '';
    }

    private function get_offer_by_type($type, $id) {
        $offers = get_option('wp_revenue_booster_offers', array());
        foreach ($offers as $offer) {
            if (strpos($offer, $type) !== false && strpos($offer, $id) !== false) {
                return $offer;
            }
        }
        return '';
    }
}

function wp_revenue_booster_init() {
    new WP_Revenue_Booster();
}
add_action('plugins_loaded', 'wp_revenue_booster_init');

// Register settings
add_action('admin_init', function() {
    register_setting('wp_revenue_booster_options', 'wp_revenue_booster_offers');
    add_settings_section(
        'wp_revenue_booster_main',
        'Monetization Offers',
        null,
        'wp-revenue-booster'
    );
    add_settings_field(
        'wp_revenue_booster_offers',
        'Offers',
        function() {
            $offers = get_option('wp_revenue_booster_offers', array());
            echo '<textarea name="wp_revenue_booster_offers[]" rows="10" cols="50">' . implode('\n', $offers) . '</textarea>';
            echo '<p>Enter each offer on a new line. Use [type] and [id] for shortcode targeting.</p>';
        },
        'wp-revenue-booster',
        'wp_revenue_booster_main'
    );
});
?>