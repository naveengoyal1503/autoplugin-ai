/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Rotates high-converting affiliate offers, coupons, and sponsored content based on user behavior.
 * Version: 1.0
 * Author: WP Dev Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('wp_revenue_booster', array($this, 'display_offer'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function display_offer($atts) {
        $atts = shortcode_atts(array(
            'type' => 'random',
        ), $atts, 'wp_revenue_booster');

        $offers = get_option('wp_revenue_booster_offers', array());
        if (empty($offers)) return '<p>No offers available.</p>';

        $offer = $this->get_offer_by_type($offers, $atts['type']);
        if (!$offer) return '<p>No matching offer found.</p>';

        return '<div class="wp-revenue-booster-offer">
                    <h4>' . esc_html($offer['title']) . '</h4>
                    <p>' . esc_html($offer['description']) . '</p>
                    <a href="' . esc_url($offer['url']) . '" target="_blank" class="wp-revenue-booster-cta">' . esc_html($offer['cta']) . '</a>
                </div>';
    }

    private function get_offer_by_type($offers, $type) {
        if ($type === 'random') {
            return $offers[array_rand($offers)];
        }
        // Extend with behavior-based logic in premium version
        return $offers;
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'plugin_settings_page')
        );
    }

    public function settings_init() {
        register_setting('wp_revenue_booster', 'wp_revenue_booster_offers');
    }

    public function plugin_settings_page() {
        $offers = get_option('wp_revenue_booster_offers', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Offers</th>
                        <td>
                            <div id="offers-container">
                                <?php foreach ($offers as $offer): ?>
                                    <div class="offer-item">
                                        <input type="text" name="wp_revenue_booster_offers[][title]" value="<?php echo esc_attr($offer['title']); ?>" placeholder="Offer Title" />
                                        <input type="text" name="wp_revenue_booster_offers[][description]" value="<?php echo esc_attr($offer['description']); ?>" placeholder="Description" />
                                        <input type="url" name="wp_revenue_booster_offers[][url]" value="<?php echo esc_attr($offer['url']); ?>" placeholder="URL" />
                                        <input type="text" name="wp_revenue_booster_offers[][cta]" value="<?php echo esc_attr($offer['cta']); ?>" placeholder="Call to Action" />
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addOffer()">Add Offer</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            function addOffer() {
                const container = document.getElementById('offers-container');
                const item = document.createElement('div');
                item.className = 'offer-item';
                item.innerHTML = `<input type="text" name="wp_revenue_booster_offers[][title]" placeholder="Offer Title" />
                                 <input type="text" name="wp_revenue_booster_offers[][description]" placeholder="Description" />
                                 <input type="url" name="wp_revenue_booster_offers[][url]" placeholder="URL" />
                                 <input type="text" name="wp_revenue_booster_offers[][cta]" placeholder="Call to Action" />`;
                container.appendChild(item);
            }
        </script>
        <?php
    }
}

new WP_Revenue_Booster();
?>