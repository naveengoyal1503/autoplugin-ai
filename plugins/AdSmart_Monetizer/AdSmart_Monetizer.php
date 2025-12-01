/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AdSmart_Monetizer.php
*/
<?php
/**
 * Plugin Name: AdSmart Monetizer
 * Description: Automatically adds targeted affiliate offers, sponsored content, and native ads based on user behavior and post context.
 * Version: 1.0
 * Author: Generated
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class AdSmartMonetizer {
    private $affiliate_links = array(
        'technology' => 'https://affiliate.techstore.com/deal',
        'fashion' => 'https://affiliate.fashionhub.com/discount',
        'travel' => 'https://affiliate.travelworld.com/special',
        'default' => 'https://affiliate.generalstore.com/promo'
    );

    private $sponsored_content = array(
        'technology' => '<div style="border:1px solid #ccc;padding:10px;margin:15px 0;background:#f9f9f9;"><strong>Sponsored:</strong> Check out the latest tech gadgets at unbeatable prices! <a href="https://sponsor.techgadgets.com" target="_blank" rel="nofollow">Buy Now</a></div>',
        'fashion' => '<div style="border:1px solid #fcc;padding:10px;margin:15px 0;background:#fff0f0;"><strong>Sponsored:</strong> Upgrade your wardrobe with our exclusive fashion offers! <a href="https://sponsor.fashionstore.com" target="_blank" rel="nofollow">Shop Today</a></div>',
        'travel' => '<div style="border:1px solid #6cc;padding:10px;margin:15px 0;background:#f0fff0;"><strong>Sponsored:</strong> Explore the world with discounted travel packages! <a href="https://sponsor.traveldeals.com" target="_blank" rel="nofollow">Explore Now</a></div>',
        'default' => '<div style="border:1px solid #ccc;padding:10px;margin:15px 0;background:#fafafa;"><strong>Sponsored:</strong> Discover amazing deals curated just for you! <a href="https://sponsor.deals.com" target="_blank" rel="nofollow">Check Offers</a></div>',
    );

    public function __construct() {
        add_filter('the_content', array($this, 'inject_monetization'));
    }

    private function detect_category() {
        if (is_single()) {
            $categories = get_the_category();
            if (!empty($categories)) {
                foreach ($categories as $cat) {
                    $slug = strtolower($cat->slug);
                    if (array_key_exists($slug, $this->affiliate_links)) {
                        return $slug;
                    }
                }
            }
        }
        return 'default';
    }

    public function inject_monetization($content) {
        if (!is_singular('post') || is_admin()) return $content;

        $category = $this->detect_category();

        // Insert affiliate link as a smart call-to-action button at the end of content
        $affiliate_link = esc_url($this->affiliate_links[$category]);
        $affiliate_button = '<p style="text-align:center; margin:20px 0;"><a href="'.$affiliate_link.'" target="_blank" rel="nofollow noopener" style="background:#0073aa;color:#fff;padding:12px 20px;border-radius:4px;text-decoration:none;font-weight:bold;">Exclusive Offer &amp; Discount</a></p>';

        // Insert sponsored content block after first paragraph
        $pattern = '/(<p.*?</p>)/i';
        $replacement = '$1' . $this->sponsored_content[$category];
        $modified_content = preg_replace($pattern, $replacement, $content, 1);

        return $modified_content . $affiliate_button;
    }
}

new AdSmartMonetizer();
