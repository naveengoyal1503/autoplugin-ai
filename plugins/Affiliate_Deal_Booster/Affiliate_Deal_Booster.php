/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Plugin URI: https://example.com/affiliate-deal-booster
 * Description: Curates and displays affiliate coupons and deals automatically to increase affiliate conversions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateDealBooster {
    public function __construct() {
        add_shortcode('affiliate_deals', array($this, 'render_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function add_admin_menu() {
        add_options_page(
            'Affiliate Deal Booster',
            'Affiliate Deal Booster',
            'manage_options',
            'affiliate-deal-booster',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('adb_settings_group', 'adb_deals_json');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('adb_settings_group'); ?>
                <?php do_settings_sections('adb_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Deals JSON</th>
                        <td>
                            <textarea name="adb_deals_json" rows="15" cols="70" placeholder='[{"title":"Deal Title","link":"http://affiliate-link.com","description":"Short description","coupon":"CODE123"}]'><?php echo esc_textarea(get_option('adb_deals_json')); ?></textarea>
                            <p class="description">Enter the deals info in JSON format. Each deal needs title, link, description, and optional coupon code.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_deals_shortcode() {
        $deals_json = get_option('adb_deals_json');
        if (empty($deals_json)) {
            return '<p>No deals available currently.</p>';
        }

        $deals = json_decode($deals_json, true);
        if (!is_array($deals) || empty($deals)) {
            return '<p>Invalid deals data.</p>';
        }

        $output = '<div class="adb-deals-container">';
        foreach ($deals as $deal) {
            $title = isset($deal['title']) ? esc_html($deal['title']) : 'Untitled Deal';
            $link = isset($deal['link']) ? esc_url($deal['link']) : '#';
            $description = isset($deal['description']) ? esc_html($deal['description']) : '';
            $coupon = isset($deal['coupon']) ? esc_html($deal['coupon']) : '';

            $output .= '<div class="adb-deal">';
            $output .= '<h3 class="adb-deal-title"><a href="' . $link . '" target="_blank" rel="nofollow noopener">' . $title . '</a></h3>';
            if ($description) {
                $output .= '<p class="adb-deal-description">' . $description . '</p>';
            }
            if ($coupon) {
                $output .= '<p class="adb-deal-coupon">Coupon Code: <strong>' . $coupon . '</strong></p>';
            }
            $output .= '<p><a class="adb-deal-button" href="' . $link . '" target="_blank" rel="nofollow noopener">Grab This Deal</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }
}

new AffiliateDealBooster();

// Basic styling injected for the deals
add_action('wp_head', function() {
    ?>
    <style>
    .adb-deals-container { display: flex; flex-wrap: wrap; gap: 20px; margin: 20px 0; }
    .adb-deal { border: 1px solid #ddd; padding: 15px; width: 100%; max-width: 320px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); border-radius: 5px; background: #fff; }
    .adb-deal-title { margin-top: 0; font-size: 1.2em; }
    .adb-deal-description { font-size: 0.95em; color: #555; }
    .adb-deal-coupon { font-weight: bold; color: #0073aa; }
    .adb-deal-button { display: inline-block; padding: 10px 15px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 3px; }
    .adb-deal-button:hover { background: #005177; }
    @media (max-width: 600px) {
        .adb-deal { max-width: 100%; }
    }
    </style>
    <?php
});
