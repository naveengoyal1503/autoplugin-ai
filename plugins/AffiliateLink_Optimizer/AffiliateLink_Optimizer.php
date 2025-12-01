/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Optimizer.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Optimizer
 * Description: Detects and optimizes affiliate links automatically to boost affiliate commission.
 * Version: 1.0.0
 * Author: ChatGPT
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateLinkOptimizer {

    public function __construct() {
        add_filter('the_content', array($this, 'optimize_affiliate_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    // Enqueue styles for affiliate links
    public function enqueue_styles() {
        wp_register_style('alo-styles', false);
        wp_enqueue_style('alo-styles');
        $custom_css = ".alo-affiliate-link { color: #d35400 !important; text-decoration: underline; font-weight: bold; } .alo-affiliate-link:hover { color: #e67e22 !important; }";
        wp_add_inline_style('alo-styles', $custom_css);
    }

    // Auto-detect and cloak affiliate links inside content
    public function optimize_affiliate_links($content) {
        // Parse content and find all URLs
        if (empty($content)) return $content;

        $pattern = '/(https?:\/\/[^\s"'>]+)/i';

        $content = preg_replace_callback($pattern, function($matches) {
            $url = esc_url_raw($matches[1]);
            // Check if URL matches common affiliate patterns (simple example for Amazon/Affiliate)*
            if ($this->is_affiliate_url($url)) {
                $cloaked_url = $this->cloak_affiliate_url($url);
                return '<a href="' . esc_url($cloaked_url) . '" target="_blank" rel="nofollow noopener" class="alo-affiliate-link">' . esc_html($cloaked_url) . '</a>';
            }
            return $matches[1];
        }, $content);

        return $content;
    }

    // Simple check if URL contains affiliate patterns
    private function is_affiliate_url($url) {
        $affiliate_domains = array(
            'amzn.to', 'amazon.com', 'clickbank.net', 'cj.com', 'shareasale.com', 'affiliatelink.com'
        );
        foreach ($affiliate_domains as $domain) {
            if (stripos($url, $domain) !== false) {
                return true;
            }
        }
        return false;
    }

    // Cloak affiliate URL to site domain
    private function cloak_affiliate_url($url) {
        $site_url = home_url('/');
        $encoded_url = rawurlencode($url);
        return add_query_arg('alo_redirect', $encoded_url, $site_url);
    }

}

// Handle redirect on cloaked URLs
function alo_handle_redirect() {
    if (isset($_GET['alo_redirect'])) {
        $url = rawurldecode(sanitize_text_field($_GET['alo_redirect']));
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            wp_redirect($url, 302);
            exit;
        }
    }
}
add_action('template_redirect', 'alo_handle_redirect');

// Initialize plugin
new AffiliateLinkOptimizer();
