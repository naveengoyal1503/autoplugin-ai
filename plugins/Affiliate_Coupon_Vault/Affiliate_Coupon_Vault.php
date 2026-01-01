/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with tracking, boosting conversions for bloggers and eCommerce sites.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('affiliate-coupon-vault', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options', array($this, 'sanitize_settings'));
        add_settings_section('acv_main', 'Main Settings', null, 'acv');
        add_settings_field('acv_coupons', 'Coupons', array($this, 'coupons_field'), 'acv', 'acv_main');
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        if (isset($input['coupons'])) {
            $sanitized['coupons'] = array_map(function($coupon) {
                return array(
                    'name' => sanitize_text_field($coupon['name']),
                    'code' => sanitize_text_field($coupon['code']),
                    'affiliate_link' => esc_url_raw($coupon['affiliate_link']),
                    'discount' => sanitize_text_field($coupon['discount']),
                    'expires' => sanitize_text_field($coupon['expires'])
                );
            }, $input['coupons']);
        }
        $sanitized['pro'] = isset($input['pro']) ? 1 : 0;
        return $sanitized;
    }

    public function coupons_field() {
        $options = get_option('acv_options', array('coupons' => array()));
        $coupons = $options['coupons'];
        echo '<div id="acv-coupons">';
        if (empty($coupons)) {
            $coupons = array(array('name' => '', 'code' => '', 'affiliate_link' => '', 'discount' => '', 'expires' => ''));
        }
        foreach ($coupons as $index => $coupon) {
            echo '<div class="acv-coupon-row">';
            echo '<input type="text" name="acv_options[coupons][' . $index . '][name]" placeholder="Coupon Name" value="' . esc_attr($coupon['name']) . '" />';
            echo '<input type="text" name="acv_options[coupons][' . $index . '][code]" placeholder="Code" value="' . esc_attr($coupon['code']) . '" />';
            echo '<input type="text" name="acv_options[coupons][' . $index . '][discount]" placeholder="Discount %" value="' . esc_attr($coupon['discount']) . '" />';
            echo '<input type="url" name="acv_options[coupons][' . $index . '][affiliate_link]" placeholder="Affiliate Link" value="' . esc_attr($coupon['affiliate_link']) . '" />';
            echo '<input type="date" name="acv_options[coupons][' . $index . '][expires]" value="' . esc_attr($coupon['expires']) . '" />';
            echo '<button type="button" class="button acv-remove">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="acv-add-coupon" class="button">Add Coupon</button>';
        echo '<p class="description">Free version limited to 3 coupons. <strong>Upgrade to Pro for unlimited!</strong></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Features:</strong> Unlimited coupons, click tracking, analytics dashboard, custom designs. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p>
        </div>
        <style>
        .acv-coupon-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .acv-coupon-row input { width: 18%; margin-right: 1%; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('#acv-add-coupon').click(function() {
                var index = $('.acv-coupon-row').length;
                $('#acv-coupons').append(
                    '<div class="acv-coupon-row">' +
                    '<input type="text" name="acv_options[coupons][' + index + '][name]" placeholder="Coupon Name" />' +
                    '<input type="text" name="acv_options[coupons][' + index + '][code]" placeholder="Code" />' +
                    '<input type="text" name="acv_options[coupons][' + index + '][discount]" placeholder="Discount %" />' +
                    '<input type="url" name="acv_options[coupons][' + index + '][affiliate_link]" placeholder="Affiliate Link" />' +
                    '<input type="date" name="acv_options[coupons][' + index + '][expires]" />' +
                    '<button type="button" class="button acv-remove">Remove</button>' +
                    '</div>'
                );
            });
            $(document).on('click', '.acv-remove', function() {
                $(this).closest('.acv-coupon-row').remove();
            });
        });
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $options = get_option('acv_options', array('coupons' => array()));
        $coupons = $options['coupons'];
        if (count($coupons) > 3 && !isset($options['pro'])) {
            return '<p>Upgrade to Pro for more coupons!</p>';
        }
        if (!isset($coupons[$atts['id']])) {
            return '';
        }
        $coupon = $coupons[$atts['id']];
        $expired = !empty($coupon['expires']) && strtotime($coupon['expires']) < current_time('timestamp');
        ob_start();
        ?>
        <div class="acv-coupon" data-coupon-id="<?php echo esc_attr($atts['id']); ?>">
            <?php if (!$expired) : ?>
                <h3><?php echo esc_html($coupon['name']); ?> - <?php echo esc_html($coupon['discount']); ?> OFF!</h3>
                <p><strong>Code:</strong> <span class="acv-code"><?php echo esc_html($coupon['code']); ?></span> <button class="acv-copy button">Copy</button></p>
                <a href="<?php echo esc_url($coupon['affiliate_link']); ?}" class="button acv-claim" target="_blank">Claim Now <?php echo isset($options['pro']) ? '(Tracked)' : ''; ?></a>
            <?php else : ?>
                <p>This coupon has expired.</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('acv_options', array('coupons' => array()));
    }
}

// Initialize
AffiliateCouponVault::get_instance();

// AJAX for tracking (Pro feature stub)
add_action('wp_ajax_acv_track_click', 'acv_track_click');
function acv_track_click() {
    if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
        wp_die();
    }
    // Pro tracking logic here
    wp_send_json_success();
}

// Create CSS file placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'style.css')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', '.acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; } .acv-code { font-size: 1.5em; font-weight: bold; color: #0073aa; } .acv-claim { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }');
}

// Create JS file placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'script.js')) {
    file_put_contents(plugin_dir_path(__FILE__) . 'script.js', "jQuery(document).ready(function($) { $('.acv-copy').click(function() { var code = $(this).siblings('.acv-code').text(); navigator.clipboard.writeText(code).then(function() { $(this).text('Copied!'); }); }); $('.acv-claim').click(function(e) { var pro = " + (isset($options['pro']) ? 'true' : 'false') + "; if (pro) { $.post(acv_ajax.ajax_url, {action: 'acv_track_click', coupon_id: $(this).closest('.acv-coupon').data('coupon-id'), nonce: acv_ajax.nonce}); } }); });");
}
?>