/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Booster.php
*/
<?php
/**
 * Plugin Name: SmartAffiliate Booster
 * Description: Boost your affiliate revenue by auto-optimizing affiliate links with cloaking, automatic coupon insertion, and context-aware deal popups.
 * Version: 1.0
 * Author: YourName
 * License: GPLv2 or later
 * Text Domain: smartaffiliatebooster
 */

if (!defined('ABSPATH')) { exit; }

class SmartAffiliateBooster {

    private $version = '1.0';
    private $option_name = 'sab_settings';

    public function __construct() {
        add_filter('the_content', array($this, 'process_content_affiliate_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'inject_popup_html'));

        // Register plugin settings page
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    // Enqueue needed styles and scripts
    public function enqueue_scripts() {
        wp_enqueue_style('sab-styles', plugin_dir_url(__FILE__) . 'sab-styles.css');
        wp_enqueue_script('sab-scripts', plugin_dir_url(__FILE__) . 'sab-scripts.js', array('jquery'), $this->version, true);

        // Localize script with AJAX URL and nonce
        wp_localize_script('sab-scripts', 'sab_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sab_nonce')
        ));
    }

    // Process content to detect and cloak affiliate links, inject coupons and enable popup triggers
    public function process_content_affiliate_links($content) {
        // Simple regex to find URLs in content
        $pattern = '/https?:\/\/(www\.)?([\w\-]+)\.([\w]{2,})([\/\w\-?&=%#.]+)?/i';
        preg_match_all($pattern, $content, $matches);

        if (empty($matches) || empty($matches)) {
            return $content;
        }

        $affiliate_domains = $this->get_affiliate_domains();
        $unique_urls = array_unique($matches);

        foreach ($unique_urls as $url) {
            $parsed = parse_url($url);
            if (!$parsed || empty($parsed['host'])) continue;
            $domain = strtolower($parsed['host']);

            foreach ($affiliate_domains as $aff_domain => $affiliate_id) {
                if (stripos($domain, $aff_domain) !== false) {
                    // Cloak URL
                    $cloaked_url = esc_url(add_query_arg('ref', $affiliate_id, site_url('/go/') . '?url=' . urlencode($url)));
                    // Build coupon trigger span
                    $coupon_html = '<span class="sab-coupon-trigger" data-aff-domain="' . esc_attr($aff_domain) . '">[Special Deal]</span>';
                    // Replace URL with cloaked link plus coupon trigger
                    $link_html = '<a href="' . $cloaked_url . '" target="_blank" rel="nofollow noopener">' . $url . '</a> ' . $coupon_html;
                    $content = str_replace($url, $link_html, $content);
                    break;
                }
            }
        }
        return $content;
    }

    // Affiliate domains and IDs - in a real plugin, this would be configurable or extendable
    private function get_affiliate_domains() {
        return array(
            'amazon.com' => 'amazon123',
            'ebay.com' => 'ebay456',
            'shareasale.com' => 'shareasale789'
        );
    }

    // Inject popup container HTML
    public function inject_popup_html() {
        ?>
        <div id="sab-popup" style="display:none;">
            <div id="sab-popup-content">
                <button id="sab-popup-close" aria-label="Close popup">&times;</button>
                <div id="sab-coupon-details"></div>
            </div>
        </div>
        <style>
            #sab-popup {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.6);
                z-index: 9999;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            #sab-popup-content {
                background: #fff;
                border-radius: 8px;
                padding: 20px;
                max-width: 400px;
                width: 90%;
                box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                position: relative;
                text-align: center;
            }
            #sab-popup-close {
                position: absolute;
                top: 10px;
                right: 15px;
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #333;
            }
        </style>
        <script>
        jQuery(document).ready(function($){
            $('.sab-coupon-trigger').on('click', function(){
                var domain = $(this).data('aff-domain');
                var coupons = {
                    'amazon.com': 'Save 10% with code AMAZSAVE',
                    'ebay.com': 'Exclusive 7% off coupon: EBAYDEAL7',
                    'shareasale.com': 'Get 15% using SHARE15'
                };
                var coupon_text = coupons[domain] || 'No coupons available at the moment';
                $('#sab-coupon-details').text(coupon_text);
                $('#sab-popup').fadeIn();
            });
            $('#sab-popup-close, #sab-popup').on('click', function(e){
                if(e.target.id == 'sab-popup' || e.target.id == 'sab-popup-close') {
                    $('#sab-popup').fadeOut();
                }
            });
        });
        </script>
        <?php
    }

    // Admin menu
    public function add_settings_page() {
        add_options_page('SmartAffiliate Booster', 'SmartAffiliate Booster', 'manage_options', 'smartaffiliatebooster', array($this, 'render_settings_page'));
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name);
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Booster Settings</h1>
            <p>This plugin currently uses hardcoded affiliate domains and coupons. Future versions will allow full customization.</p>
            <p>Affiliate domains tracked:</p>
            <ul>
                <li>amazon.com</li>
                <li>ebay.com</li>
                <li>shareasale.com</li>
            </ul>
            <p>Coupons will show up when clicking the [Special Deal] links next to affiliate URLs.</p>
        </div>
        <?php
    }
}

new SmartAffiliateBooster();

// Simple redirect handler for cloaked URLs
add_action('init', function() {
    if (isset($_GET['url']) && strpos($_SERVER['REQUEST_URI'], '/go/') !== false) {
        $target = esc_url_raw($_GET['url']);
        if (filter_var($target, FILTER_VALIDATE_URL)) {
            wp_redirect($target, 302);
            exit;
        }
    }
});