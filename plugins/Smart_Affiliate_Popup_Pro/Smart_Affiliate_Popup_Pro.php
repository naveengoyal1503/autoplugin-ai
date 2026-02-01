/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Popup_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Popup Pro
 * Plugin URI: https://example.com/smart-affiliate-popup
 * Description: AI-powered popup plugin that displays personalized affiliate product recommendations based on visitor behavior to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-popup
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliatePopup {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_smart_affiliate_popup', array($this, 'handle_ajax'));
        add_action('wp_ajax_nopriv_smart_affiliate_popup', array($this, 'handle_ajax'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sap_pro_version') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
        add_shortcode('sap_popup', array($this, 'popup_shortcode'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('sap-script', plugin_dir_url(__FILE__) . 'sap-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sap-script', 'sap_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sap_nonce')));
        wp_enqueue_style('sap-style', plugin_dir_url(__FILE__) . 'sap-style.css', array(), '1.0.0');
    }

    public function popup_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_link' => 'https://example.com/affiliate-product',
            'image' => 'https://example.com/product-image.jpg',
            'title' => 'Recommended for You',
            'delay' => 5000
        ), $atts);
        ob_start();
        ?>
        <div id="sap-popup" class="sap-popup" style="display:none;">
            <div class="sap-overlay"></div>
            <div class="sap-content">
                <span class="sap-close">&times;</span>
                <img src="<?php echo esc_url($atts['image']); ?>" alt="Product">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                <p>Check out this amazing product tailored just for you!</p>
                <a href="<?php echo esc_url($atts['affiliate_link']); ?}" class="sap-button" target="_blank">Get It Now (Affiliate Link)</a>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                setTimeout(function() {
                    $('#sap-popup').fadeIn();
                }, <?php echo intval($atts['delay']); ?>);
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_ajax() {
        check_ajax_referer('sap_nonce', 'nonce');
        $behavior = sanitize_text_field($_POST['behavior']);
        // Simulate personalized affiliate based on behavior (page category, etc.)
        $products = array(
            'tech' => array('link' => 'https://amazon.com/tech-product?tag=youraffiliateid', 'image' => 'https://example.com/tech.jpg'),
            'fashion' => array('link' => 'https://amazon.com/fashion?tag=youraffiliateid', 'image' => 'https://example.com/fashion.jpg'),
            'default' => array('link' => 'https://example.com/default-affiliate', 'image' => 'https://example.com/default.jpg')
        );
        $category = get_the_category()->slug ?? 'default';
        if (strpos($behavior, 'tech') !== false) $category = 'tech';
        if (strpos($behavior, 'fashion') !== false) $category = 'fashion';
        wp_send_json_success($products[$category] ?? $products['default']);
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Popup Pro:</strong> Upgrade to Pro for advanced targeting, A/B testing, and unlimited popups! <a href="https://example.com/pro" target="_blank">Get Pro Now</a></p></div>';
    }

    public function activate() {
        add_option('sap_pro_version', 'no');
        flush_rewrite_rules();
    }
}

new SmartAffiliatePopup();

// Pro check function
if (!function_exists('sap_is_pro')) {
    function sap_is_pro() {
        return get_option('sap_pro_version') === 'yes';
    }
}

/*
CSS File Content (save as sap-style.css in plugin folder):
.sap-popup { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; }
.sap-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); }
.sap-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 400px; text-align: center; }
.sap-close { position: absolute; right: 15px; top: 10px; font-size: 24px; cursor: pointer; }
.sap-button { background: #ff6b6b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
.sap-button:hover { background: #ff5252; }

JS File Content (save as sap-script.js in plugin folder):
jQuery(document).ready(function($) {
    $(document).on('click', '.sap-close, .sap-overlay', function() {
        $('#sap-popup').fadeOut();
    });

    // Track behavior and show personalized popup
    let behavior = $('body').hasClass('tech-category') ? 'tech' : ($('body').hasClass('fashion-category') ? 'fashion' : 'general');
    $.post(sap_ajax.ajax_url, {
        action: 'smart_affiliate_popup',
        nonce: sap_ajax.nonce,
        behavior: behavior
    }, function(response) {
        if (response.success) {
            // Dynamically update popup with affiliate data
            $('.sap-content img').attr('src', response.data.image);
            $('.sap-button').attr('href', response.data.link);
            $('#sap-popup').fadeIn();
        }
    });
});
*/
?>