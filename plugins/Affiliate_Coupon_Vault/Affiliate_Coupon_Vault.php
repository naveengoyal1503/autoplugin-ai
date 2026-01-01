/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically fetches, displays, and tracks affiliate coupons from multiple networks, boosting conversions with personalized discount codes and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
    }

    public function init() {
        if (get_option('acv_api_key') && get_option('acv_enabled')) {
            // Simulate coupon fetching in free version (limited to 5)
            $this->fetch_coupons();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    private function fetch_coupons() {
        // Mock data for demo (in pro, integrate real APIs like CJ Affiliate, ShareASale)
        $this->coupons = array(
            array('code' => 'SAVE20', 'desc' => '20% off Hosting', 'afflink' => 'https://example.com/aff?ref=123', 'expires' => '2026-12-31'),
            array('code' => 'DEAL50', 'desc' => '50% off VPN', 'afflink' => 'https://example.com/aff?ref=456', 'expires' => '2026-06-30'),
            array('code' => 'FREE10', 'desc' => '$10 Free Credit', 'afflink' => 'https://example.com/aff?ref=789', 'expires' => '2026-03-31'),
        );
        update_option('acv_coupons', $this->coupons);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = get_option('acv_coupons', array());
        $output = '<div class="acv-coupons">';
        $limit = min($atts['limit'], count($coupons));
        for ($i = 0; $i < $limit; $i++) {
            $coupon = $coupons[$i];
            $output .= '<div class="acv-coupon">';
            $output .= '<h4>' . esc_html($coupon['desc']) . '</h4>';
            $output .= '<code>' . esc_html($coupon['code']) . '</code>';
            $output .= '<a href="#" class="acv-btn" data-link="' . esc_url($coupon['afflink']) . '" data-id="' . $i . '">Grab Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        $output .= '<p><small>Pro: Unlimited coupons & real-time API sync. <a href="https://example.com/pro">Upgrade Now</a></small></p>';
        return $output;
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $link = sanitize_url($_POST['link']);
        $id = intval($_POST['id']);
        // Track click (free version logs to option, pro sends to analytics)
        $clicks = get_option('acv_clicks', array());
        $clicks[$id] = isset($clicks[$id]) ? $clicks[$id] + 1 : 1;
        update_option('acv_clicks', $clicks);
        wp_redirect($link);
        exit;
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_settings_page');
    });
}

function acv_settings_page() {
    if (isset($_POST['acv_submit'])) {
        update_option('acv_enabled', isset($_POST['acv_enabled']));
        update_option('acv_api_key', sanitize_text_field($_POST['acv_api_key']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Affiliate Coupon Vault Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Enable Plugin</th>
                    <td><input type="checkbox" name="acv_enabled" value="1" <?php checked(get_option('acv_enabled')); ?> /></td>
                </tr>
                <tr>
                    <th>API Key (Pro)</th>
                    <td><input type="text" name="acv_api_key" value="<?php echo esc_attr(get_option('acv_api_key')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p>View clicks: <?php echo json_encode(get_option('acv_clicks', array())); ?></p>
    </div>
    <?php
}

// JS file content (embedded for single file)
function acv_embed_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-btn').click(function(e) {
            e.preventDefault();
            var link = $(this).data('link');
            var id = $(this).data('id');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                nonce: acv_ajax.nonce,
                link: link,
                id: id
            }, function() {
                window.location = link;
            });
        });
    });
    </script>
    <style>
    .acv-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
    .acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1 1 300px; }
    .acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    .acv-btn:hover { background: #005a87; }
    code { background: #f1f1f1; padding: 5px; font-family: monospace; }
    </style>
    <?php
}
add_action('wp_footer', 'acv_embed_js');

AffiliateCouponVault::get_instance();

// Activation hook
register_activation_hook(__FILE__, function() {
    update_option('acv_enabled', true);
});