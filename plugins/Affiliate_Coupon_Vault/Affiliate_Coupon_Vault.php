/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates, manages, and displays personalized affiliate coupons with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_acv_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_acv_track_click', array($this, 'ajax_track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['acv_submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['acv_coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="acv_coupons" rows="20" cols="80" placeholder='[{"name":"10% Off","code":"SAVE10","affiliate_url":"https://affiliate.com/?coupon=SAVE10","description":"Get 10% off on all products"}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Enter JSON array of coupons: name, code, affiliate_url, description</p>
                <p><input type="submit" name="acv_submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[acv_coupon id="0"]</code> to display coupon. IDs start from 0.</p>
            <?php if (ACV_PRO) : ?>
            <h2>Pro Features</h2>
            <p>Advanced tracking and analytics unlocked.</p>
            <?php else : ?>
            <p><strong>Upgrade to Pro</strong> for unlimited coupons and click tracking ($49/year).</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        update_option('acv_coupons', sanitize_text_field($_POST['data']));
        wp_die('success');
    }

    public function ajax_track_click() {
        // Pro feature simulation
        if (!defined('ACV_PRO')) {
            wp_die('Pro feature');
        }
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'acv_clicks', array('coupon_id' => intval($_POST['id']), 'ip' => $_SERVER['REMOTE_ADDR'], 'time' => current_time('mysql')));
        wp_die('tracked');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) return '';
        $coupon = $coupons[$atts['id']];
        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo $atts['id']; ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <div class="acv-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($coupon['affiliate_url']); ?>" class="button acv-btn" target="_blank">Redeem Now & Track</a>
        </div>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
        .acv-code { font-size: 24px; font-weight: bold; color: #0073aa; margin: 10px 0; }
        .acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        </style>
        <script>jQuery(document).ready(function($){ $('.acv-btn').click(function(){ $.post(acv_ajax.ajaxurl, {action:'acv_track_click', id:$(this).closest('.acv-coupon').data('id')}); }); });</script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', json_encode(array(
                array('name' => 'Sample 20% Off', 'code' => 'WELCOME20', 'affiliate_url' => '#', 'description' => 'Demo coupon - replace with real affiliate link')
            )));
        }
        global $wpdb;
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}acv_clicks (id BIGINT(20) AUTO_INCREMENT PRIMARY KEY, coupon_id INT, ip VARCHAR(45), time DATETIME)");
    }
}

// Pro check (simulate license)
define('ACV_PRO', false);

AffiliateCouponVault::get_instance();

// Create assets dir placeholder
if (!file_exists(plugin_dir_path(__FILE__) . 'assets')) {
    mkdir(plugin_dir_path(__FILE__) . 'assets', 0755, true);
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/frontend.js', '// Frontend JS placeholder\njQuery(function($){});');
}
