<?php
/*
Plugin Name: WP Revenue Booster
Description: Boost revenue with smart affiliate, coupon, and sponsored content placement.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'inject_monetized_content'));
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
                settings_fields('wp_revenue_booster_options');
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

    public function inject_monetized_content($content) {
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

        return $this->get_smart_offer($atts['type'], $atts['id']);
    }

    private function get_smart_offer($type = '', $id = '') {
        // Simulate smart offer selection
        $offers = array(
            'affiliate' => '<p><strong>Recommended Product:</strong> <a href="https://example.com/affiliate-link" target="_blank">Check out this great tool!</a></p>',
            'coupon' => '<p><strong>Exclusive Coupon:</strong> Use code <code>WPREVBOOST</code> for 10% off!</p>',
            'sponsored' => '<p><strong>Sponsored Content:</strong> This section is sponsored by Example Brand.</p>'
        );

        if ($type && isset($offers[$type])) {
            return $offers[$type];
        }

        // Randomly pick an offer for automatic injection
        return $offers[array_rand($offers)];
    }
}

function wp_revenue_booster_init() {
    new WP_Revenue_Booster();
}
add_action('plugins_loaded', 'wp_revenue_booster_init');

// Style for the injected content
function wp_revenue_booster_style() {
    echo '<style>
        .wp-revenue-booster-offer {
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>';
}
add_action('wp_head', 'wp_revenue_booster_style');
?>