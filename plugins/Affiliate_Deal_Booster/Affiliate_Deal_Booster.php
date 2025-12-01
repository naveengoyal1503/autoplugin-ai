/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Plugin URI: https://example.com/affiliatdealbooster
 * Description: Auto-aggregate and display affiliate coupon deals with optimized widgets to boost conversions.
 * Version: 1.0
 * Author: YourName
 * Text Domain: affiliate-deal-booster
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateDealBooster {
    private $option_name = 'adb_deals_cache';
    private $nonce_action = 'adb_refresh_deals';

    public function __construct() {
        add_action('admin_menu', array($this, 'adb_add_admin_menu'));
        add_action('admin_post_adb_refresh_deals', array($this, 'adb_refresh_deals_handler'));
        add_shortcode('affiliate_deals', array($this, 'adb_shortcode_display'));
        add_action('wp_enqueue_scripts', array($this, 'adb_enqueue_scripts'));
        add_filter('the_content', array($this, 'adb_inject_deals_in_content'));
    }

    public function adb_add_admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'affiliate_deal_booster', array($this, 'adb_admin_page'));
    }

    public function adb_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        $deals = get_option($this->option_name, array());

        echo '<div class="wrap"><h1>Affiliate Deal Booster</h1>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field($this->nonce_action);
        echo '<input type="hidden" name="action" value="adb_refresh_deals">';
        echo '<p><input type="submit" class="button button-primary" value="Refresh Deals Now"></p>';
        echo '</form>';

        echo '<h2>Cached Deals (' . count($deals) . ' items)</h2>';
        if (!empty($deals)) {
            echo '<table style="width:100%;border-collapse:collapse;" border="1"><thead><tr><th>Merchant</th><th>Coupon</th><th>Description</th><th>URL</th></tr></thead><tbody>';
            foreach ($deals as $deal) {
                echo '<tr>';
                echo '<td>' . esc_html($deal['merchant']) . '</td>';
                echo '<td>' . esc_html($deal['coupon']) . '</td>';
                echo '<td>' . esc_html($deal['description']) . '</td>';
                echo '<td><a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener">Link</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No deals cached yet. Click "Refresh Deals Now" to fetch.</p>';
        }

        echo '<p>Use shortcode <code>[affiliate_deals]</code> to display deals on posts or pages.</p>';
        echo '</div>';
    }

    public function adb_refresh_deals_handler() {
        if (!current_user_can('manage_options') || !check_admin_referer($this->nonce_action)) {
            wp_die('Permission denied');
        }

        $this->adb_fetch_and_cache_deals();

        wp_redirect(admin_url('admin.php?page=affiliate_deal_booster&updated=1'));
        exit;
    }

    private function adb_fetch_and_cache_deals() {
        // For demo purpose, static deals. In real app, fetch via APIs or RSS from affiliate programs
        $sample_deals = array(
            array(
                'merchant' => 'Amazon',
                'coupon' => 'SAVE10',
                'description' => 'Save 10% on electronics.',
                'url' => 'https://www.amazon.com?tag=youraffid'
            ),
            array(
                'merchant' => 'eBay',
                'coupon' => 'EBAY20',
                'description' => 'Get 20% off selected items.',
                'url' => 'https://www.ebay.com?campid=youraffid'
            ),
            array(
                'merchant' => 'Best Buy',
                'coupon' => 'BEST5',
                'description' => '5% off storewide.',
                'url' => 'https://www.bestbuy.com?ref=youraffid'
            ),
        );

        update_option($this->option_name, $sample_deals);
    }

    public function adb_shortcode_display($atts) {
        $deals = get_option($this->option_name, array());
        if (empty($deals)) {
            return '<p>No deals available currently. Please check back later.</p>';
        }

        $output = '<div class="adb-deals-widget" style="border:1px solid #ccc;padding:10px;margin:15px 0;">';
        $output .= '<h3 style="margin-bottom:10px;">Exclusive Deals & Coupons</h3><ul style="list-style:none;padding:0;margin:0;">';

        foreach ($deals as $deal) {
            $output .= '<li style="margin-bottom:8px;">';
            $output .= '<strong>' . esc_html($deal['merchant']) . '</strong>: '; 
            $output .= esc_html($deal['description']) . ' '; 
            $output .= '<a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener" style="color:#0073aa;">Use Coupon: ' . esc_html($deal['coupon']) . '</a>';
            $output .= '</li>';
        }

        $output .= '</ul></div>';

        return $output;
    }

    public function adb_enqueue_scripts() {
        // Optional: enqueue styles if needed
    }

    public function adb_inject_deals_in_content($content) {
        if (is_singular() && is_main_query()) {
            $deals_html = $this->adb_shortcode_display(array());
            // Inject below first paragraph
            $pos = strpos($content, '</p>');
            if ($pos !== false) {
                return substr_replace($content, '</p>' . $deals_html, $pos, 4);
            } else {
                return $content . $deals_html;
            }
        }
        return $content;
    }
}

new AffiliateDealBooster();
