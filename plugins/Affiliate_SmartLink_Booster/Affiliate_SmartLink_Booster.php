/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_SmartLink_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate SmartLink Booster
 * Description: Automatically inserts optimized affiliate links into your posts based on content context and user behavior to boost affiliate revenue.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateSmartLinkBooster {
    private $affiliate_links = array(
        'WordPress' => 'https://example-affiliate.com/wordpress?ref=smartlink',
        'hosting' => 'https://example-affiliate.com/hosting?ref=smartlink',
        'SEO' => 'https://example-affiliate.com/seo-tools?ref=smartlink',
        'plugin' => 'https://example-affiliate.com/plugins?ref=smartlink'
        // Add more keywords and their affiliate URLs here
    );

    public function __construct() {
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_action('wp_footer', array($this, 'track_user_behavior_script'));
        add_action('wp_ajax_update_user_behavior', array($this, 'ajax_update_user_behavior'));
        add_action('wp_ajax_nopriv_update_user_behavior', array($this, 'ajax_update_user_behavior'));
    }

    // Inserts affiliate links dynamically by replacing keywords with links in post content
    public function insert_affiliate_links($content) {
        if (!is_single()) return $content;

        foreach ($this->affiliate_links as $keyword => $url) {
            // Regex to find whole words case-insensitive
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $link_html = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener noreferrer">' . $keyword . '</a>';
            $content = preg_replace($pattern, $link_html, $content, 1); // Replace only first occurrence per keyword
        }

        return $content;
    }

    // Adds a JS script to track scroll and clicks to infer user interest in affiliate link categories
    public function track_user_behavior_script() {
        if (!is_single()) return;
        ?>
        <script type="text/javascript">
        (function(){
            var keywords = <?php echo json_encode(array_keys($this->affiliate_links)); ?>;

            function sendBehavior(keyword, action) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.send('action=update_user_behavior&keyword=' + encodeURIComponent(keyword) + '&behavior=' + encodeURIComponent(action));
            }

            document.addEventListener('click', function(e) {
                var el = e.target;
                if (el.tagName.toLowerCase() === 'a' && el.href.indexOf('ref=smartlink') !== -1) {
                    keywords.forEach(function(keyword) {
                        if (el.textContent.toLowerCase().includes(keyword.toLowerCase())) {
                            sendBehavior(keyword, 'click');
                        }
                    });
                }
            });

            window.addEventListener('scroll', function() {
                keywords.forEach(function(keyword) {
                    // Could be expanded to detect viewport keyword presence
                    sendBehavior(keyword, 'scroll');
                });
            });
        })();
        </script>
        <?php
    }

    // AJAX callback to receive behavior data for potential future analytics or customization
    public function ajax_update_user_behavior() {
        if (!isset($_POST['keyword']) || !isset($_POST['behavior'])) {
            wp_send_json_error('Missing parameters');
        }

        $keyword = sanitize_text_field($_POST['keyword']);
        $behavior = sanitize_text_field($_POST['behavior']);

        // For demo, just log the data; in premium, store and analyze for dynamic link optimization
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AffiliateSmartLinkBooster: Behavior recorded - Keyword: $keyword, Action: $behavior");
        }

        wp_send_json_success('Behavior recorded');
    }
}

new AffiliateSmartLinkBooster();
