/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Boost your WordPress site revenue with smart affiliate, coupon, and sponsored content placement.
 * Version: 1.0
 * Author: WP Revenue Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'inject_monetization_content'));
        add_shortcode('wp_revenue_booster', array($this, 'shortcode_handler'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_revenue_booster_settings');
                do_settings_sections('wp-revenue-booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugins_url('style.css', __FILE__));
    }

    public function inject_monetization_content($content) {
        if (is_single()) {
            $offer = $this->get_smart_offer();
            if ($offer) {
                $content .= '<div class="wp-revenue-offer">' . $offer . '</div>';
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
        // Simulate smart offer selection
        $offers = array(
            '<p><strong>Special Offer:</strong> Get 20% off with code <em>WPREV20</em> at our partner store!</p>',
            '<p><strong>Sponsored:</strong> Try our recommended tool for boosting your site traffic.</p>',
            '<p><strong>Coupon:</strong> Use code <em>SAVE15</em> for 15% off your next purchase.</p>'
        );
        return $offers[array_rand($offers)];
    }

    private function get_offer_by_type($type, $id) {
        return '<p>Custom ' . esc_html($type) . ' offer (ID: ' . esc_html($id) . ').</p>';
    }
}

function wp_revenue_booster_init() {
    new WP_Revenue_Booster();
}
add_action('plugins_loaded', 'wp_revenue_booster_init');

// Style for the offer
function wp_revenue_booster_style() {
    echo '<style>
        .wp-revenue-offer {
            background: #f0f8ff;
            border-left: 4px solid #0073aa;
            padding: 15px;
            margin: 20px 0;
            font-size: 16px;
        }
    </style>';
}
add_action('wp_head', 'wp_revenue_booster_style');
?>