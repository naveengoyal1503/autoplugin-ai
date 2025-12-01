/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize your site's revenue by rotating affiliate links, displaying targeted ads, and promoting exclusive offers.
 * Version: 1.0
 * Author: Your Name
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'display_offer'));
        add_shortcode('wp_revenue_booster', array($this, 'shortcode'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function display_offer() {
        if (is_user_logged_in()) return; // Only show to guests
        $offers = get_option('wp_revenue_booster_offers', array());
        if (empty($offers)) return;
        $offer = $offers[array_rand($offers)];
        echo '<div class="wp-revenue-booster-offer">';
        echo '<p>' . esc_html($offer['text']) . '</p>';
        echo '<a href="' . esc_url($offer['url']) . '" target="_blank" rel="nofollow">' . esc_html($offer['cta']) . '</a>';
        echo '</div>';
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts, 'wp_revenue_booster');
        $offers = get_option('wp_revenue_booster_offers', array());
        if (empty($offers)) return '';
        $offer = $offers[array_rand($offers)];
        return '<div class="wp-revenue-booster-offer-shortcode">
            <p>' . esc_html($offer['text']) . '</p>
            <a href="' . esc_url($offer['url']) . '" target="_blank" rel="nofollow">' . esc_html($offer['cta']) . '</a>
        </div>';
    }
}

new WP_Revenue_Booster();

// Admin settings page
add_action('admin_menu', function() {
    add_options_page(
        'WP Revenue Booster',
        'Revenue Booster',
        'manage_options',
        'wp-revenue-booster',
        function() {
            if (isset($_POST['submit'])) {
                $offers = array();
                foreach ($_POST['offer_text'] as $i => $text) {
                    if (!empty($text) && !empty($_POST['offer_url'][$i]) && !empty($_POST['offer_cta'][$i])) {
                        $offers[] = array(
                            'text' => sanitize_text_field($text),
                            'url' => esc_url_raw($_POST['offer_url'][$i]),
                            'cta' => sanitize_text_field($_POST['offer_cta'][$i])
                        );
                    }
                }
                update_option('wp_revenue_booster_offers', $offers);
                echo '<div class="updated"><p>Offers updated.</p></div>';
            }
            $offers = get_option('wp_revenue_booster_offers', array());
            ?>
            <div class="wrap">
                <h1>WP Revenue Booster</h1>
                <form method="post">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Offers</th>
                            <td>
                                <div id="offers-container">
                                    <?php foreach ($offers as $offer): ?>
                                        <p>
                                            <input type="text" name="offer_text[]" value="<?php echo esc_attr($offer['text']); ?>" placeholder="Offer text" style="width: 30%;" />
                                            <input type="url" name="offer_url[]" value="<?php echo esc_url($offer['url']); ?>" placeholder="URL" style="width: 30%;" />
                                            <input type="text" name="offer_cta[]" value="<?php echo esc_attr($offer['cta']); ?>" placeholder="Call to action" style="width: 20%;" />
                                            <button type="button" onclick="this.parentNode.remove()">Remove</button>
                                        </p>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" onclick="addOffer()">Add Offer</button>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit" class="button-primary" value="Save Offers" />
                    </p>
                </form>
            </div>
            <script>
                function addOffer() {
                    const container = document.getElementById('offers-container');
                    const p = document.createElement('p');
                    p.innerHTML = '<input type="text" name="offer_text[]" placeholder="Offer text" style="width: 30%;" />' +
                                  '<input type="url" name="offer_url[]" placeholder="URL" style="width: 30%;" />' +
                                  '<input type="text" name="offer_cta[]" placeholder="Call to action" style="width: 20%;" />' +
                                  '<button type="button" onclick="this.parentNode.remove()">Remove</button>';
                    container.appendChild(p);
                }
            </script>
            <?php
        }
    );
});

// Style
add_action('wp_head', function() {
    echo '<style>
        .wp-revenue-booster-offer, .wp-revenue-booster-offer-shortcode {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 10px 0;
            text-align: center;
        }
        .wp-revenue-booster-offer a, .wp-revenue-booster-offer-shortcode a {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>';
});
?>