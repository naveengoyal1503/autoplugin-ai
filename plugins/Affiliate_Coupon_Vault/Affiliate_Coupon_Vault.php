/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and displays personalized deals to boost conversions and commissions.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            return; // Pro version active
        }
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            coupon_id varchar(50) NOT NULL,
            affiliate_link text NOT NULL,
            ip varchar(45) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        add_option('acv_limit', 10);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default',
            'title' => 'Exclusive Deal',
            'discount' => '20% OFF',
            'afflink' => '#',
            'image' => ''
        ), $atts);

        $limit = get_option('acv_limit', 10);
        global $wpdb;
        $clicks = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->prefix . "acv_clicks WHERE coupon_id = %s", $atts['id']));

        if ($limit > 0 && $clicks >= $limit) {
            return '<div class="acv-coupon expired">Coupon limit reached! <a href="' . admin_url('options-general.php?page=acv-settings') . '">Upgrade to Pro</a></div>';
        }

        $img = $atts['image'] ? '<img src="' . esc_url($atts['image']) . '" alt="Coupon" style="max-width:200px;">' : '';

        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo esc_attr($atts['id']); ?>" data-link="<?php echo esc_url($atts['afflink']); ?>">
            <?php echo $img; ?>
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <p><?php echo esc_html($atts['discount']); ?> - Limited time!</p>
            <button class="acv-btn">Get Coupon & Shop</button>
            <p class="acv-clicks">Used: <?php echo intval($clicks); ?>/<?php echo intval($limit); ?></p>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #007cba; padding: 20px; text-align: center; margin: 20px 0; background: #f9f9f9; }
        .acv-btn { background: #007cba; color: white; border: none; padding: 10px 20px; cursor: pointer; }
        .acv-btn:hover { background: #005a87; }
        .acv-coupon.expired { border-color: #dc3232; background: #ffebee; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $coupon_id = sanitize_text_field($_POST['coupon_id']);
        $aff_link = esc_url_raw($_POST['aff_link']);
        $ip = $_SERVER['REMOTE_ADDR'];

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'acv_clicks', array(
            'coupon_id' => $coupon_id,
            'affiliate_link' => $aff_link,
            'ip' => $ip
        ));

        wp_send_json_success(array('redirect' => $aff_link));
    }
}

// Admin menu
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_settings_page');
    });

    function acv_settings_page() {
        if (isset($_POST['acv_limit'])) {
            update_option('acv_limit', intval($_POST['acv_limit']));
        }
        $limit = get_option('acv_limit', 10);
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1>
        <form method="post"><table class="form-table">
        <tr><th>Click Limit (Free)</th><td><input type="number" name="acv_limit" value="' . $limit . '" /> <p>Pro: Unlimited</p></td></tr>
        </table><p><a href="#" class="button button-primary" onclick="return confirm('"Upgrade to Pro?"')">Upgrade to Pro ($49)</a></p>
        <p><strong>Usage:</strong> [affiliate_coupon_vault id="unique1" title="20% Off Hosting" discount="20% OFF" afflink="https://aff.link" image="img.jpg"]</p>
        </form></div>';
    }
}

AffiliateCouponVault::get_instance();

// JS file content (inline for single file)
function acv_inline_script() {
    ?>
    <script>jQuery(document).ready(function($) {
        $('.acv-btn').click(function() {
            var $coupon = $(this).closest('.acv-coupon');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                nonce: acv_ajax.nonce,
                coupon_id: $coupon.data('id'),
                aff_link: $coupon.data('link')
            }, function(res) {
                if (res.success) window.location = res.data.redirect;
            });
        });
    });</script>
    <?php
}
add_action('wp_footer', 'acv_inline_script');

// Pro upsell notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock unlimited coupons & analytics with Pro! <a href="' . admin_url('options-general.php?page=acv-settings') . '">Upgrade Now</a></p></div>';
    }
});