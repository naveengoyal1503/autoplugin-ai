<?php
/*
Plugin Name: Affiliate Deal Booster
Plugin URI: https://example.com/affiliate-deal-booster
Description: Automatically replaces affiliate links with user-personalized coupon and cashback offers to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateDealBooster {

    public function __construct() {
        add_filter('the_content', array($this, 'replace_affiliate_links_with_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles() {
        wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    // Mock partner merchant deals for demonstration
    private function get_partner_deals($affiliate_url) {
        // In real plugin, this would query API or database for deals based on affiliate URL
        $deals = array(
            'amazon' => array('coupon' => 'SAVE10', 'cashback' => '5% Back'),
            'ebay' => array('coupon' => 'EBAY15', 'cashback' => '3% Back'),
            'default' => array('coupon' => 'WELCOME', 'cashback' => '2% Back')
        );

        foreach ($deals as $merchant => $deal) {
            if (stripos($affiliate_url, $merchant) !== false) {
                return $deal;
            }
        }
        return $deals['default'];
    }

    public function replace_affiliate_links_with_deals($content) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        $links = $dom->getElementsByTagName('a');

        $to_replace = array();

        // Collect affiliate links for replacement
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if ($this->is_affiliate_link($href)) {
                $deals = $this->get_partner_deals($href);
                $to_replace[] = array('node' => $link, 'coupon' => $deals['coupon'], 'cashback' => $deals['cashback']);
            }
        }

        // Replace links with decorated version
        foreach ($to_replace as $item) {
            $link = $item['node'];
            $coupon = htmlspecialchars($item['coupon']);
            $cashback = htmlspecialchars($item['cashback']);

            $new_html = $link->ownerDocument->createDocumentFragment();
            $html = sprintf('<span class="adb-affiliate-link">%s <span class="adb-badge">Coupon: %s | %s</span></span>', $link->C14N(), $coupon, $cashback);
            $new_html->appendXML($html);

            $link->parentNode->replaceChild($new_html, $link);
        }

        // Save back modified content
        $body = $dom->getElementsByTagName('body')->item(0);
        $innerHTML = '';
        foreach ($body->childNodes as $child) {
            $innerHTML .= $dom->saveHTML($child);
        }

        return $innerHTML;
    }

    private function is_affiliate_link($url) {
        // Basic heuristic: check if URL contains typical affiliate tracking params or domains
        if (empty($url)) return false;
        $affiliate_params = array('affid=', 'affiliate', 'ref=', 'partner=');
        foreach ($affiliate_params as $param) {
            if (stripos($url, $param) !== false) return true;
        }

        $known_affiliates = array('amazon.com', 'ebay.com', 'clickbank.net');
        foreach ($known_affiliates as $domain) {
            if (stripos($url, $domain) !== false) return true;
        }

        return false;
    }

}

new AffiliateDealBooster();

// Minimal CSS for badges
add_action('wp_head', function() {
    echo '<style>.adb-affiliate-link {background:#f0f9ff; border:1px solid #9ecfff; padding:3px 6px; border-radius:3px; margin-left:5px; font-size:90%; color:#0366d6;}
    .adb-badge {background:#0366d6; color:#fff; border-radius:2px; padding:1px 4px; margin-left:4px; font-weight:bold;}</style>';
});
