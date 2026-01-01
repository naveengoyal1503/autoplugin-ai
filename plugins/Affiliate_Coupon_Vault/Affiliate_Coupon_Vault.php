/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals to boost conversions and commissions.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        // Free version limits
        $this->coupons = get_option('acv_coupons', array(
            array('code' => 'SAVE10', 'afflink' => '', 'desc' => '10% off any purchase', 'used' => 0)
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        if (!isset($this->coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $this->coupons[$atts['id']];
        $used = $coupon['used'];
        $pro_limit = false; // Free: 100 uses per coupon
        if ($used >= 100 && !$this->is_pro()) {
            return '<p class="acv-expired">Coupon limit reached. <a href="https://example.com/pro">Upgrade to Pro</a></p>';
        }
        ob_start();
        ?>
        <div class="acv-vault">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <div class="acv-code">Code: <strong><?php echo esc_html($coupon['code']); ?></strong></div>
            <a href="<?php echo esc_url($coupon['afflink']); ?>" target="_blank" class="acv-button">Get Deal & Shop</a>
            <small>(Used: <?php echo $used; ?>/100)</small>
        </div>
        <?php
        return ob_get_clean();
    }

    private function is_pro() {
        return get_option('acv_pro') === 'yes';
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        register_setting('acv_settings', 'acv_pro');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_text_field_deep($_POST['coupons']));
        }
        $coupons = get_option('acv_coupons', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="">
                <h2>Add/Edit Coupons</h2>
                <?php for($i=0; $i<5; $i++): // Free: 5 coupons ?>
                <div class="acv-coupon">
                    <label>Coupon <?php echo $i+1; ?> Code: <input name="coupons[<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupons[$i]['code'] ?? ''); ?>" /></label><br>
                    <label>Description: <input name="coupons[<?php echo $i; ?>][desc]" value="<?php echo esc_attr($coupons[$i]['desc'] ?? ''); ?>" /></label><br>
                    <label>Affiliate Link: <input name="coupons[<?php echo $i; ?>][afflink]" value="<?php echo esc_attr($coupons[$i]['afflink'] ?? ''); ?>" style="width:300px;" /></label>
                </div>
                <?php endfor; ?>
                <p><strong>Pro: Unlimited coupons, no usage limits, analytics.</strong> <a href="https://example.com/pro">Get Pro ($49/yr)</a></p>
                <?php submit_button(); ?>
            </form>
            <h3>Usage</h3>
            <p>Use shortcode: <code>[affiliate_coupon_vault id="0"]</code> Replace 0 with coupon index.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('acv_coupons', array(
            array('code' => 'SAVE10', 'afflink' => '#', 'desc' => '10% off any purchase', 'used' => 0)
        ));
    }

    // Track usage via AJAX
    public function init_ajax() {
        add_action('wp_ajax_acv_track', array($this, 'track_usage'));
    }

    public function track_usage() {
        $id = intval($_POST['id']);
        $coupons = get_option('acv_coupons', array());
        if (isset($coupons[$id])) {
            $coupons[$id]['used']++;
            update_option('acv_coupons', $coupons);
            wp_send_json_success('Tracked');
        }
        wp_send_json_error();
    }
}

AffiliateCouponVault::get_instance();

// Dummy style
/* Add to plugin dir as style.css
.acv-vault { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
.acv-code { font-size: 24px; margin: 10px 0; }
.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }
.acv-expired { color: red; }
*/
?>