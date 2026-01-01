/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => 'SAVE10',
            'affiliate_id' => 'default',
            'discount' => '10%',
            'expires' => date('Y-m-d', strtotime('+30 days')),
            'link' => '',
        ), $atts);

        $tracking_id = $this->get_tracking_id($atts['affiliate_id']);
        $coupon_link = $atts['link'] ? $atts['link'] . '?coupon=' . $atts['code'] . '&ref=' . $tracking_id : '#';

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-tracking="<?php echo esc_attr($tracking_id); ?>">
            <div class="coupon-code"><?php echo esc_html($atts['code']); ?></div>
            <div class="coupon-discount"><?php echo esc_html($atts['discount']); ?> OFF</div>
            <div class="coupon-expires">Expires: <?php echo esc_html($atts['expires']); ?></div>
            <a href="<?php echo esc_url($coupon_link); ?}" class="coupon-button" target="_blank">Get Deal</a>
            <div class="coupon-stats">Clicks: <span class="click-count">0</span></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_tracking_id($affiliate_id) {
        $defaults = get_option('affiliate_coupon_defaults', array('default' => uniqid('ref_')));
        return isset($defaults[$affiliate_id]) ? $defaults[$affiliate_id] : uniqid('ref_');
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_settings', 'affiliate_coupon_defaults');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('affiliate_coupon_defaults', $_POST['affiliate_coupon_defaults']);
        }
        $defaults = get_option('affiliate_coupon_defaults', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Affiliate IDs & Tracking Codes</th>
                        <td>
                            <textarea name="affiliate_coupon_defaults[default]" rows="10" cols="50" placeholder="ID: tracking_code\nblogger1: ref_user1"> <?php echo esc_textarea(implode("\n", $defaults)); ?></textarea>
                            <p class="description">Format: affiliate_id:tracking_code (one per line)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics dashboard, auto-expiry, and custom templates for $49/year.</p>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('affiliate_coupon_defaults')) {
            update_option('affiliate_coupon_defaults', array('default' => 'ref_free'));
        }
    }
}

AffiliateCouponVault::get_instance();

/* Inline CSS and JS for single file */
function affiliate_coupon_vault_styles() {
    echo '<style>
        .affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; text-align: center; background: #f9f9f9; border-radius: 10px; max-width: 300px; margin: 20px auto; }
        .coupon-code { font-size: 2em; font-weight: bold; color: #007cba; background: white; padding: 10px; border-radius: 5px; display: inline-block; }
        .coupon-discount { font-size: 1.2em; color: #28a745; margin: 10px 0; }
        .coupon-expires { font-size: 0.9em; color: #6c757d; }
        .coupon-button { display: inline-block; background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; font-weight: bold; }
        .coupon-button:hover { background: #218838; }
        .coupon-stats { margin-top: 10px; font-size: 0.9em; }
    </style>';
}
add_action('wp_head', 'affiliate_coupon_vault_styles');

function affiliate_coupon_vault_scripts() {
    echo '<script>
        jQuery(document).ready(function($) {
            $(".affiliate-coupon-vault .coupon-button").on("click", function() {
                var $container = $(this).closest(".affiliate-coupon-vault");
                var count = parseInt($container.find(".click-count").text()) + 1;
                $container.find(".click-count").text(count);
                // Track click (Pro feature simulates analytics)
                console.log("Coupon clicked: " + $container.data("tracking"));
            });
        });
    </script>';
}
add_action('wp_footer', 'affiliate_coupon_vault_scripts');