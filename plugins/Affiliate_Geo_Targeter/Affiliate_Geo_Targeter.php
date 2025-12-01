/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Geo_Targeter.php
*/
<?php
/**
 * Plugin Name: Affiliate Geo Targeter
 * Description: Automatically insert and cloak affiliate links based on visitor geolocation to maximize affiliate earnings.
 * Version: 1.0
 * Author: Generated
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateGeoTargeter {
    private $affiliate_links = array();

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('geo_affiliate', array($this, 'affiliate_shortcode'));
        add_action('wp_footer', array($this, 'inject_js_data'));

        // Define affiliate links per region
        // Format: 'region_code' => 'affiliate_url'
        $this->affiliate_links = array(
            'US' => 'https://example.com/affiliate/us?ref=123',
            'CA' => 'https://example.com/affiliate/ca?ref=123',
            'GB' => 'https://example.com/affiliate/uk?ref=123',
            'IN' => 'https://example.com/affiliate/in?ref=123',
            'DEFAULT' => 'https://example.com/affiliate/global?ref=123'
        );
    }

    public function enqueue_scripts() {
        // Enqueue only on frontend
        if (!is_admin()) {
            wp_enqueue_script('affiliate-geo-targeter', plugin_dir_url(__FILE__) . 'affiliate-geo-targeter.js', array('jquery'), '1.0', true);

            // Provide ajax URL and affiliate data
            wp_localize_script('affiliate-geo-targeter', 'AGTData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'links' => $this->affiliate_links,
                'nonce' => wp_create_nonce('agt_nonce')
            ));
        }
    }

    // Shortcode handler: [geo_affiliate text="Buy Now"]
    public function affiliate_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array('text' => 'Buy Here'), $atts, 'geo_affiliate');

        // Placeholder link that will be replaced by JS
        $text = esc_html($atts['text']);
        $link = '<a href="#" class="agt-link" target="_blank" rel="nofollow noopener noreferrer">' . $text . '</a>';

        return $link;
    }

    // Inject small inline JS data for geolocation fallback
    public function inject_js_data() {
        ?>
        <script>
          // This script replaces all .agt-link href attributes with geotargeted links
          (function(){
            function fetchGeo(callback) {
              // Simple free geolocation API
              var xhr = new XMLHttpRequest();
              xhr.onreadystatechange = function() {
                if(xhr.readyState==4 && xhr.status==200) {
                  try {
                    var data=JSON.parse(xhr.responseText);
                    callback(data.country_code || 'DEFAULT');
                  } catch (e) { callback('DEFAULT'); }
                }
              };
              xhr.open('GET', 'https://ipapi.co/json/', true);
              xhr.send();
            }

            function replaceLinks(region) {
              var links = document.querySelectorAll('.agt-link');
              var affiliateLinks = AGTData.links || {};
              var targetLink = affiliateLinks[region] || affiliateLinks['DEFAULT'] || '#';

              for(var i=0; i<links.length; i++) {
                links[i].href = targetLink;
              }
            }

            fetchGeo(function(region){ replaceLinks(region); });
          })();
        </script>
        <?php
    }
}

new AffiliateGeoTargeter();
