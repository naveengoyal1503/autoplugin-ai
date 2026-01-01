/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and limits.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
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
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ecp-script', 'ecp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ecp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $links = get_option('ecp_affiliate_links', "");
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <p><label>Affiliate Links (one per line: Name|URL|Code Prefix):</label></p>
                <textarea name="affiliate_links" rows="10" cols="80"><?php echo esc_textarea($links); ?></textarea>
                <p class="submit"><input type="submit" name="ecp_save" class="button-primary" value="Save Settings"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[exclusive_coupon id="1"]</code> or <code>[exclusive_coupon]</code> for random.</p>
            <p><strong>Pro Features:</strong> Unlock analytics, unlimited coupons, custom designs ($49/year).</p>
        </div>
        <?php
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ecp_nonce', 'nonce');
        $id = intval($_POST['id']);
        $links = $this->parse_links(get_option('ecp_affiliate_links', ''));
        if (isset($links[$id])) {
            $link = $links[$id];
            $code = $link['prefix'] . wp_generate_uuid4() . rand(1000,9999);
            $used = get_option('ecp_used_' . $code, false);
            if (!$used) {
                update_option('ecp_used_' . $code, true);
                $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
                wp_send_json_success(array('code' => $code, 'link' => $link['url'], 'expires' => $expires));
            } else {
                wp_send_json_error('Coupon already used');
            }
        }
        wp_send_json_error('Invalid coupon');
    }

    private function parse_links($text) {
        $links = array();
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            if (strpos($line, '|') !== false) {
                list($name, $url, $prefix) = explode('|', $line, 3);
                $links[] = array('name' => trim($name), 'url' => esc_url_raw(trim($url)), 'prefix' => sanitize_text_field(trim($prefix)));
            }
        }
        return $links;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => -1), $atts);
        $links = $this->parse_links(get_option('ecp_affiliate_links', ''));
        if (empty($links)) return '<p>No coupons configured.</p>';

        $id = intval($atts['id']);
        if ($id < 0 || !isset($links[$id])) {
            $id = array_rand($links);
        }
        $link = $links[$id];

        ob_start();
        ?>
        <div id="ecp-coupon-<?php echo $id; ?>" class="ecp-coupon" data-id="<?php echo $id; ?>">
            <h3><?php echo esc_html($link['name']); ?> Exclusive Deal</h3>
            <p>Click to generate your unique coupon!</p>
            <button id="ecp-generate-<?php echo $id; ?>" class="button ecp-btn">Get Coupon</button>
            <div id="ecp-result-<?php echo $id; ?>" style="display:none;">
                <p>Your code: <strong id="ecp-code"></strong></p>
                <p>Expires: <span id="ecp-expires"></span></p>
                <a id="ecp-link" class="button" target="_blank">Redeem Now</a>
            </div>
        </div>
        <style>
        .ecp-coupon { border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9; }
        .ecp-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
        .ecp-btn:hover { background: #005a87; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('#ecp-generate-<?php echo $id; ?>').click(function() {
                $.post(ecp_ajax.ajax_url, {
                    action: 'generate_coupon',
                    id: <?php echo $id; ?>,
                    nonce: ecp_ajax.nonce
                }, function(resp) {
                    if (resp.success) {
                        $('#ecp-code').text(resp.data.code);
                        $('#ecp-expires').text(resp.data.expires);
                        $('#ecp-link').attr('href', resp.data.link + '?coupon=' + resp.data.code);
                        $('#ecp-generate-<?php echo $id; ?>').hide();
                        $('#ecp-result-<?php echo $id; ?>').show();
                    } else {
                        alert(resp.data);
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!wp_next_scheduled('ecp_cleanup')) {
            wp_schedule_event(time(), 'daily', 'ecp_cleanup');
        }
    }
}

ExclusiveCouponsPro::get_instance();

add_action('ecp_cleanup', function() {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ecp_%' OR option_name LIKE 'ecp_used_%'");
});

/* Pro Upsell */
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !get_option('ecp_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
    }
});