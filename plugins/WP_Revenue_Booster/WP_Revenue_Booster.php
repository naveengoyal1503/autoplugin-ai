/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Boost your WordPress site's revenue by displaying smart offers, coupons, and affiliate links.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'display_offer')); 
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/style.css');
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0', true);
    }

    public function display_offer() {
        $offers = get_option('wp_revenue_booster_offers', array());
        if (empty($offers)) return;

        $offer = $offers[array_rand($offers)];
        $content = '<div id="wp-revenue-booster-offer" style="display:none;">
            <div class="wp-revenue-booster-content">
                <h3>' . esc_html($offer['title']) . '</h3>
                <p>' . esc_html($offer['description']) . '</p>
                <a href="' . esc_url($offer['link']) . '" target="_blank" class="wp-revenue-booster-cta">' . esc_html($offer['cta']) . '</a>
                <button class="wp-revenue-booster-close">Close</button>
            </div>
        </div>';
        echo $content;
    }

    public function admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page')
        );
    }

    public function settings_init() {
        register_setting('wp_revenue_booster', 'wp_revenue_booster_offers');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            $offers = array();
            if (isset($_POST['offer_title']) && is_array($_POST['offer_title'])) {
                foreach ($_POST['offer_title'] as $key => $title) {
                    $offers[] = array(
                        'title' => sanitize_text_field($title),
                        'description' => sanitize_textarea_field($_POST['offer_description'][$key]),
                        'link' => esc_url($_POST['offer_link'][$key]),
                        'cta' => sanitize_text_field($_POST['offer_cta'][$key])
                    );
                }
            }
            update_option('wp_revenue_booster_offers', $offers);
            echo '<div class="notice notice-success"><p>Offers updated.</p></div>';
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
                                <div class="offer-item">
                                    <input type="text" name="offer_title[]" value="<?php echo esc_attr($offer['title']); ?>" placeholder="Offer Title" style="width: 100%; margin-bottom: 5px;" />
                                    <textarea name="offer_description[]" placeholder="Offer Description" style="width: 100%; margin-bottom: 5px;"><?php echo esc_textarea($offer['description']); ?></textarea>
                                    <input type="url" name="offer_link[]" value="<?php echo esc_url($offer['link']); ?>" placeholder="Offer Link" style="width: 100%; margin-bottom: 5px;" />
                                    <input type="text" name="offer_cta[]" value="<?php echo esc_attr($offer['cta']); ?>" placeholder="Call to Action" style="width: 100%; margin-bottom: 5px;" />
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-offer">Add Offer</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            document.getElementById('add-offer').addEventListener('click', function() {
                var container = document.getElementById('offers-container');
                var item = document.createElement('div');
                item.className = 'offer-item';
                item.innerHTML = '<input type="text" name="offer_title[]" placeholder="Offer Title" style="width: 100%; margin-bottom: 5px;" />' +
                    '<textarea name="offer_description[]" placeholder="Offer Description" style="width: 100%; margin-bottom: 5px;"></textarea>' +
                    '<input type="url" name="offer_link[]" placeholder="Offer Link" style="width: 100%; margin-bottom: 5px;" />' +
                    '<input type="text" name="offer_cta[]" placeholder="Call to Action" style="width: 100%; margin-bottom: 5px;" />';
                container.appendChild(item);
            });
        </script>
        <?php
    }
}

new WP_Revenue_Booster();
