/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCoupons {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-pro', plugin_dir_url(__FILE__) . 'sac-pro.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sac-pro', plugin_dir_url(__FILE__) . 'sac-pro.css', array(), '1.0.0');
        wp_localize_script('sac-pro', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_enqueue($hook) {
        if ('toplevel_page_sac-settings' !== $hook) return;
        wp_enqueue_script('sac-admin', plugin_dir_url(__FILE__) . 'sac-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-admin', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_menu_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'sac-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons Pro Settings</h1>
            <form id="sac-form">
                <?php wp_nonce_field('sac_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="coupons" rows="10" cols="50" id="coupons" placeholder="Add coupon: Brand|Code|Discount|AffLink|Description"></textarea></td>
                    </tr>
                    <tr>
                        <th>Pro Features</th>
                        <td><label><input type="checkbox" name="pro_tracking" value="1"> Enable Click Tracking (Pro)</label> | <a href="#" class="pro-upgrade">Upgrade to Pro</a></td>
                    </tr>
                </table>
                <p><input type="submit" class="button-primary" value="Save Settings"></p>
            </form>
            <div id="coupons-display"></div>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        check_ajax_referer('sac_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $coupons = sanitize_textarea_field($_POST['coupons']);
        update_option('sac_coupons', $coupons);
        if (isset($_POST['pro_tracking'])) {
            update_option('sac_pro_tracking', 1);
        }
        wp_send_json_success('Settings saved!');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('sac_coupons', '');
        $lines = explode("\n", trim($coupons));
        $output = '';
        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) >= 5) {
                list($brand, $code, $discount, $afflink, $desc) = $parts;
                $track_id = uniqid();
                $tracked_link = add_query_arg('sac_ref', $track_id, $afflink);
                $output .= '<div class="sac-coupon"><h3>' . esc_html($brand) . '</h3><p>' . esc_html($desc) . ' <strong>' . esc_html($discount) . '</strong></p><input readonly value="' . esc_attr($code) . '" onclick="this.select()"><a href="' . esc_url($tracked_link) . '" target="_blank" class="sac-button">Get Deal</a></div>';
            }
        }
        if (get_option('sac_pro_tracking')) {
            // Pro tracking logic (simplified)
            $output .= '<script>console.log("Pro tracking active");</script>';
        }
        return $output;
    }

    public function activate() {
        add_option('sac_coupons', "Example|SAVE10|10% Off|https://affiliate.link|Great first deal");
    }
}

SmartAffiliateCoupons::get_instance();

// Inline CSS and JS for self-contained
function sac_inline_assets() {
    ?>
    <style>
    .sac-coupon { border: 1px solid #ddd; padding: 20px; margin: 10px 0; background: #f9f9f9; border-radius: 8px; }
    .sac-coupon h3 { color: #333; margin: 0 0 10px; }
    .sac-coupon input { font-size: 18px; padding: 10px; width: 200px; border: 2px dashed #0073aa; }
    .sac-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-left: 10px; }
    .sac-button:hover { background: #005a87; }
    </style>
    <script>jQuery(document).ready(function($) { $('#sac-form').on('submit', function(e) { e.preventDefault(); $.post(sac_ajax.ajax_url, { action: 'save_coupon', coupons: $('#coupons').val(), pro_tracking: $('[name=pro_tracking]').is(':checked') ? 1 : 0, nonce: sac_ajax.nonce }, function(res) { if (res.success) alert('Saved!'); }); }); });</script>
    <?php
}
add_action('wp_head', 'sac_inline_assets');
add_action('admin_head', 'sac_inline_assets');