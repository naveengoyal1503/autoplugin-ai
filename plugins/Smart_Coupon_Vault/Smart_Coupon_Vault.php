/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Vault
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: Generate, manage, and display exclusive affiliate coupons with auto-expiration, conversion tracking, and revenue dashboards.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('scv_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_scv_track_click', array($this, 'track_click'));
    }

    public function activate() {
        $default = array(
            'coupons' => array(),
            'api_key' => ''
        );
        add_option('scv_settings', $default);
    }

    public function deactivate() {}

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function admin_menu() {
        add_menu_page('Smart Coupon Vault', 'Coupons', 'manage_options', 'scv-coupons', array($this, 'admin_page'));
    }

    public function admin_scripts($hook) {
        if ($hook != 'toplevel_page_scv-coupons') return;
        wp_enqueue_script('jquery');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scv-frontend', plugin_dir_url(__FILE__) . 'scv-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('scv-frontend', 'scv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('scv_nonce')));
    }

    public function admin_page() {
        $settings = get_option('scv_settings', array());
        if (isset($_POST['submit'])) {
            $settings['coupons'] = $_POST['coupons'];
            $settings['api_key'] = sanitize_text_field($_POST['api_key']);
            update_option('scv_settings', $settings);
            echo '<div class="notice notice-success"><p>Saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Coupon Vault</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($settings['api_key']); ?>" /></td>
                    </tr>
                </table>
                <h2>Add Coupon</h2>
                <table class="form-table">
                    <tr>
                        <th>Title</th>
                        <td><input type="text" name="coupons[title][]" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate URL</th>
                        <td><input type="url" name="coupons[url][]" /></td>
                    </tr>
                    <tr>
                        <th>Code</th>
                        <td><input type="text" name="coupons[code][]" /></td>
                    </tr>
                    <tr>
                        <th>Discount %</th>
                        <td><input type="number" name="coupons[discount][]" /></td>
                    </tr>
                    <tr>
                        <th>Expires (days)</th>
                        <td><input type="number" name="coupons[expires][]" /></td>
                    </tr>
                </table>
                <p><input type="submit" name="submit" class="button-primary" value="Save Coupons" /></p>
            </form>
            <h2>Embed Coupon</h2>
            <p>Use shortcode: <code>[scv_coupon id="1"]</code></p>
            <h2>Dashboard (Pro)</h2>
            <p>Upgrade for click tracking and revenue stats.</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Dynamic coupon rows
            $('#add-coupon').on('click', function() {
                // Add row logic
            });
        });
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('scv_settings', array());
        $coupons = $settings['coupons'];
        $id = intval($atts['id']);
        if (!isset($coupons[$id])) return '';
        $coupon = $coupons[$id];
        $expires = isset($coupon['expires']) ? time() + ($coupon['expires'] * 86400) : 0;
        if ($expires && $expires < time()) return '<p>Coupon expired.</p>';
        ob_start();
        ?>
        <div class="scv-coupon" data-id="<?php echo $id; ?>">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong> (<?php echo esc_html($coupon['discount']); ?>% off)</p>
            <a href="#" class="scv-button button" data-url="<?php echo esc_url($coupon['url']); ?>">Get Deal</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('scv_nonce', 'nonce');
        $id = intval($_POST['id']);
        // Log click (Pro feature stub)
        wp_die('Tracked');
    }
}

SmartCouponVault::get_instance();

// Frontend JS (inline for single file)
function scv_add_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.scv-button').on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            var url = $this.data('url');
            $.post(scv_ajax.ajax_url, {
                action: 'scv_track_click',
                id: $this.closest('.scv-coupon').data('id'),
                nonce: scv_ajax.nonce
            }, function() {
                window.open(url, '_blank');
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'scv_add_inline_js');
