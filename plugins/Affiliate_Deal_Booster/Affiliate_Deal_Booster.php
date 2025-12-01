/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Auto-detect affiliate products in content to show deals and affiliate links for increased commissions.
 * Version: 1.0
 * Author: YourName
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    private $affiliate_keywords = ['Amazon', 'eBay', 'Best Buy']; // Example, extendable
    private $affiliate_links = [
        'Amazon' => 'https://amazon.com/s?k=',
        'eBay' => 'https://ebay.com/sch/i.html?_nkw=',
        'Best Buy' => 'https://bestbuy.com/site/searchpage.jsp?st='
    ];

    public function __construct() {
        add_filter('the_content', [$this, 'augment_content']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles() {
        wp_enqueue_style('affiliate-deal-booster-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function augment_content($content) {
        foreach ($this->affiliate_keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $deal_html = $this->generate_deal_html($keyword);
                $content = $content . $deal_html;
            }
        }
        return $content;
    }

    private function generate_deal_html($keyword) {
        $search_url = $this->affiliate_links[$keyword];
        $product_search = urlencode($keyword);
        $affiliate_url = $search_url . $product_search . '&tag=youraffiliatetag'; // sample tag

        $html = '<div class="affiliate-deal-booster">';
        $html .= '<h3>Check out deals on ' . esc_html($keyword) . ':</h3>';
        $html .= '<a href="' . esc_url($affiliate_url) . '" target="_blank" rel="nofollow noopener noreferrer">';
        $html .= 'Find the best discounts and coupons on ' . esc_html($keyword) . ' &#8594;</a>';
        $html .= '</div>';
        return $html;
    }
}

new AffiliateDealBooster();
