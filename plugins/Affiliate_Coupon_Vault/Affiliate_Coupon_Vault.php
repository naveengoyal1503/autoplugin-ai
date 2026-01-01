/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with click tracking for higher conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options', array($this, 'sanitize_settings'));
        add_settings_section('acv_main', 'Coupon Settings', null, 'acv-settings');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'acv-settings', 'acv_main');
    }

    public function coupons_field() {
        $options = get_option('acv_options', array());
        $coupons = isset($options['coupons']) ? $options['coupons'] : array(
            array('title' => 'Sample Coupon', 'code' => 'SAVE20', 'afflink' => '#', 'description' => '20% off on first purchase')
        );
        echo '<textarea name="acv_options[coupons]" rows="10" cols="80">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>JSON format: [{"title":"Title","code":"CODE","afflink":"https://aff.link","description":"Desc"}]</p>';
    }

    public function sanitize_settings($input) {
        $input['coupons'] = json_decode(wp_kses_post($input['coupons']), true);
        return $input;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv-settings');
                submit_button();
                ?>
            </form>
            <p>Use <code>[acv_coupon id="0"]</code> shortcode to display coupons. Pro version coming soon for advanced features.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $options = get_option('acv_options', array());
        $coupons = isset($options['coupons']) ? $options['coupons'] : array();
        $id = intval($atts['id']);
        if (!isset($coupons[$id])) return '';
        $coupon = $coupons[$id];
        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo $id; ?>" data-afflink="<?php echo esc_attr($coupon['afflink']); ?>">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><strong>Code:</strong> <span class="acv-code"><?php echo esc_html($coupon['code']); ?></span></p>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <button class="acv-reveal button">Reveal & Track Click</button>
            <div class="acv-overlay" style="display:none;"><a href="#" class="acv-link">Get Deal Now</a></div>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
        .acv-code { font-size: 1.5em; color: #d63638; font-weight: bold; }
        .acv-overlay { text-align: center; margin-top: 10px; }
        .acv-link { display: inline-block; padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $id = intval($_POST['id']);
        $options = get_option('acv_options', array());
        $coupons = isset($options['coupons']) ? $options['coupons'] : array();
        if (isset($coupons[$id])) {
            // Log click (free version: simple count)
            $clicks = get_option('acv_clicks_' . $id, 0);
            update_option('acv_clicks_' . $id, $clicks + 1);
            wp_redirect($coupons[$id]['afflink']);
            exit;
        }
        wp_die('Invalid coupon');
    }

    public function activate() {
        add_option('acv_options', array('coupons' => array(
            array('title' => 'Sample Coupon', 'code' => 'SAVE20', 'afflink' => 'https://example.com/aff', 'description' => '20% off')
        )));
    }

    public function deactivate() {
        // Cleanup optional
    }
}

AffiliateCouponVault::get_instance();

// Frontend JS (embedded for single file)
function acv_add_inline_js() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.acv-reveal').click(function(e) {
            e.preventDefault();
            var $coupon = $(this).closest('.acv-coupon');
            var id = $coupon.data('id');
            var afflink = $coupon.data('afflink');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                nonce: acv_ajax.nonce,
                id: id
            }, function() {
                window.location.href = afflink;
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'acv_add_inline_js');