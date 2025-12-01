/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deals___Cashback_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deals & Cashback Booster
 * Plugin URI: https://example.com/affiliate-deals-cashback-booster
 * Description: Aggregates affiliate coupons, deals, and cashback offers relevant to your site's niche.
 * Version: 1.0
 * Author: Plugin Creator
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateDealsCashbackBooster {

    private $option_name = 'adcb_offers_cache';
    private $cache_time = 3600; // Cache offers for 1 hour

    public function __construct() {
        add_shortcode('affiliate_deals', array($this, 'render_offers'));
        add_action('admin_menu', array($this, 'add_admin_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_page() {
        add_options_page('Affiliate Deals Booster', 'Affiliate Deals Booster', 'manage_options', 'affiliate-deals-booster', array($this, 'admin_page'));
    }

    public function register_settings() {
        register_setting('adcb_settings_group', 'adcb_affiliate_network');
        register_setting('adcb_settings_group', 'adcb_keywords');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deals & Cashback Booster Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('adcb_settings_group'); ?>
                <?php do_settings_sections('adcb_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate Network API URL</th>
                        <td><input type="text" name="adcb_affiliate_network" value="<?php echo esc_attr(get_option('adcb_affiliate_network', 'https://api.example-affiliate.com/deals')); ?>" size="50" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Keywords for Deals (comma separated)</th>
                        <td><input type="text" name="adcb_keywords" value="<?php echo esc_attr(get_option('adcb_keywords', 'electronics, fashion, gadgets')); ?>" size="50" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode <code>[affiliate_deals]</code> to display deals.</p>
        </div>
        <?php
    }

    // Fetch offers from API with caching
    private function fetch_offers() {
        $cache = get_transient($this->option_name);
        if ($cache !== false) {
            return $cache;
        }

        $api_url = esc_url_raw(get_option('adcb_affiliate_network', 'https://api.example-affiliate.com/deals'));
        $keywords = explode(',', get_option('adcb_keywords', 'electronics,fashion,gadgets'));
        $keywords = array_map('trim', $keywords);

        // Build query for keywords
        $query = '?keywords=' . urlencode(implode(',', $keywords));

        $response = wp_remote_get($api_url . $query, array('timeout' => 5));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $offers = json_decode($body, true);

        if (!is_array($offers)) {
            return [];
        }

        // Cache for 1 hour
        set_transient($this->option_name, $offers, $this->cache_time);

        return $offers;
    }

    public function render_offers() {
        $offers = $this->fetch_offers();

        if (empty($offers)) {
            return '<p>No affiliate deals available at the moment. Please check back later.</p>';
        }

        ob_start();
        echo '<div class="affiliate-deals-list" style="border:1px solid #ddd;padding:10px;margin:10px 0;">';
        echo '<h3>Exclusive Deals & Cashback Offers</h3>';
        echo '<ul style="list-style-type:none;padding-left:0;">';
        foreach ($offers as $offer) {
            $title = esc_html($offer['title'] ?? 'Deal');
            $desc = esc_html($offer['description'] ?? '');
            $link = esc_url($offer['affiliate_url'] ?? '#');
            $cashback = esc_html($offer['cashback'] ?? '');
            echo '<li style="margin-bottom:10px;border-bottom:1px solid #eee;padding-bottom:5px;">';
            echo '<a href="' . $link . '" target="_blank" rel="nofollow noopener" style="font-weight:bold;">' . $title . '</a><br>';
            if ($cashback) {
                echo '<small style="color:green;">Cashback: ' . $cashback . '</small><br>';
            }
            echo '<small>' . $desc . '</small>';
            echo '</li>';
        }
        echo '</ul></div>';
        return ob_get_clean();
    }
}

new AffiliateDealsCashbackBooster();
