<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize your WordPress site's revenue with smart affiliate link rotation, targeted ads, and exclusive offer promotion.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_offer_banner'));
        add_shortcode('wp_revenue_booster', array($this, 'shortcode_handler'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/style.css');
    }

    public function render_offer_banner() {
        if (is_user_logged_in()) return; // Only show to guests

        $offers = get_option('wp_revenue_booster_offers', array());
        if (empty($offers)) return;

        $random_offer = $offers[array_rand($offers)];
        echo '<div class="wp-revenue-booster-banner">
            <a href="' . esc_url($random_offer['url']) . '" target="_blank">' . esc_html($random_offer['title']) . '</a>
        </div>';
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'type' => 'affiliate',
        ), $atts);

        $offers = get_option('wp_revenue_booster_offers', array());
        if (empty($offers)) return '';

        $filtered = array_filter($offers, function($offer) use ($atts) {
            return $offer['type'] === $atts['type'];
        });

        if (empty($filtered)) return '';

        $random_offer = $filtered[array_rand($filtered)];
        return '<a href="' . esc_url($random_offer['url']) . '" target="_blank">' . esc_html($random_offer['title']) . '</a>';
    }
}

function wp_revenue_booster_init() {
    new WP_Revenue_Booster();
}
add_action('init', 'wp_revenue_booster_init');

// Admin menu
function wp_revenue_booster_menu() {
    add_options_page(
        'WP Revenue Booster',
        'Revenue Booster',
        'manage_options',
        'wp-revenue-booster',
        'wp_revenue_booster_settings_page'
    );
}
add_action('admin_menu', 'wp_revenue_booster_menu');

function wp_revenue_booster_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['wp_revenue_booster_save'])) {
        $offers = array();
        if (isset($_POST['offer_title']) && isset($_POST['offer_url']) && isset($_POST['offer_type'])) {
            foreach ($_POST['offer_title'] as $key => $title) {
                $offers[] = array(
                    'title' => sanitize_text_field($title),
                    'url' => esc_url($_POST['offer_url'][$key]),
                    'type' => sanitize_text_field($_POST['offer_type'][$key])
                );
            }
        }
        update_option('wp_revenue_booster_offers', $offers);
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }

    $offers = get_option('wp_revenue_booster_offers', array());
    ?>
    <div class="wrap">
        <h1>WP Revenue Booster Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Offers</th>
                    <td>
                        <div id="offers-container">
                            <?php foreach ($offers as $offer): ?>
                            <p>
                                <input type="text" name="offer_title[]" value="<?php echo esc_attr($offer['title']); ?>" placeholder="Offer Title" />
                                <input type="url" name="offer_url[]" value="<?php echo esc_url($offer['url']); ?>" placeholder="Offer URL" />
                                <select name="offer_type[]">
                                    <option value="affiliate" <?php selected($offer['type'], 'affiliate'); ?>>Affiliate</option>
                                    <option value="ad" <?php selected($offer['type'], 'ad'); ?>>Ad</option>
                                    <option value="deal" <?php selected($offer['type'], 'deal'); ?>>Exclusive Deal</option>
                                </select>
                            </p>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" onclick="addOffer()">Add Offer</button>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Offers', 'primary', 'wp_revenue_booster_save'); ?>
        </form>
    </div>
    <script>
        function addOffer() {
            const container = document.getElementById('offers-container');
            const p = document.createElement('p');
            p.innerHTML = '<input type="text" name="offer_title[]" placeholder="Offer Title" /> ' +
                         '<input type="url" name="offer_url[]" placeholder="Offer URL" /> ' +
                         '<select name="offer_type[]">' +
                         '<option value="affiliate">Affiliate</option>' +
                         '<option value="ad">Ad</option>' +
                         '<option value="deal">Exclusive Deal</option>' +
                         '</select>';
            container.appendChild(p);
        }
    </script>
    <?php
}
