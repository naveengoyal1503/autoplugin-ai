<?php
/*
Plugin Name: AffiliateSmartLink
Description: Converts product links into affiliate-enabled links with coupons and cashback enhancements to boost affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateSmartLink.php
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateSmartLink {

    private $affiliate_id = 'youraffiliateID'; // Replace with your affiliate ID

    public function __construct() {
        add_filter('the_content', array($this, 'convert_links_to_affiliate'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles() {
        wp_add_inline_style('wp-block-library', '.affiliate-smartlink-tooltip { position: relative; cursor: help; border-bottom: 1px dotted #0073aa; } .affiliate-smartlink-tooltip:hover .affiliate-smartlink-tooltiptext { visibility: visible; opacity: 1; } .affiliate-smartlink-tooltiptext { visibility: hidden; width: 220px; background-color: #000; color: #fff; text-align: center; border-radius: 6px; padding: 5px; position: absolute; z-index: 1000; bottom: 125%; left: 50%; margin-left: -110px; opacity: 0; transition: opacity 0.3s; font-size: 12px; } .affiliate-smartlink-tooltiptext::after { content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px; border-width: 5px; border-style: solid; border-color: #000 transparent transparent transparent; }');
    }

    public function convert_links_to_affiliate($content) {
        if (is_admin()) return $content;

        // Use DOMDocument to parse content links
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        $links = $xpath->query('//a[@href]');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            // Check if link is a product link (basic heuristic: amazon.com as example)
            if (strpos($href, 'amazon.com') !== false) {
                // Add or update affiliate tag
                $new_url = $this->add_amazon_affiliate_tag($href);
                $link->setAttribute('href', $new_url);

                // Add tooltip with coupon and cashback info
                $tooltip_span = $dom->createElement('span', 'Coupon & Cashback Available');
                $tooltip_span->setAttribute('class', 'affiliate-smartlink-tooltiptext');

                $wrapper_span = $dom->createElement('span');
                $wrapper_span->setAttribute('class', 'affiliate-smartlink-tooltip');

                // Wrap original link text with the tooltip
                while ($link->hasChildNodes()) {
                    $child = $link->firstChild;
                    $link->removeChild($child);
                    $wrapper_span->appendChild($child);
                }

                $wrapper_span->appendChild($tooltip_span);
                $link->appendChild($wrapper_span);
            }
        }

        return $this->save_dominnerhtml($dom);
    }

    private function add_amazon_affiliate_tag($url) {
        // Parse URL
        $parts = parse_url($url);
        if (!isset($parts['query'])) {
            $parts['query'] = '';
        }

        parse_str($parts['query'], $query_array);

        // Add or overwrite tag with affiliate ID
        $query_array['tag'] = $this->affiliate_id;

        $new_query = http_build_query($query_array);

        $new_url = $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . $new_query;

        return $new_url;
    }

    private function save_dominnerhtml(DOMDocument $dom) {
        // Extract the body innerHTML
        $body = $dom->getElementsByTagName('body')->item(0);
        $html = '';
        foreach ($body->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }
        return $html;
    }

}

new AffiliateSmartLink();
