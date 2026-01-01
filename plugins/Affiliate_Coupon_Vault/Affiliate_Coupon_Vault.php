/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for maximum blog monetization.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50" placeholder='[{"name":"10% Off Hosting","code":"AFFHOST10","affiliate_url":"https://example.com/hosting?ref=yourid","image":""},{"name":"Free Trial","code":"AFFTRIAL","affiliate_url":"https://example.com/trial?ref=yourid","image":""}]'><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">JSON array of coupons: name, code, affiliate_url, image (optional).</p>
                <p><input type="submit" name="acv_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[acv_coupon id="0"]</code> or <code>[acv_coupon]</code> for random.</p>
            <?php if (!get_option('acv_pro')) { ?>
            <p><strong>Go Pro for unlimited coupons & analytics!</strong> <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
            <?php } ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'random'), $atts);
        $coupons = json_decode(get_option('acv_coupons', '[]'), true);
        if (empty($coupons)) return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>.</p>';

        if ($atts['id'] === 'random') {
            $coupon = $coupons[array_rand($coupons)];
        } else {
            $id = intval($atts['id']);
            $coupon = isset($coupons[$id]) ? $coupons[$id] : $coupons;
        }

        $personalized_code = $coupon['code'] . '-' . substr(md5(uniqid()), 0, 4);
        $track_url = add_query_arg(array('acv_source' => 'click', 'acv_coupon' => urlencode($personalized_code)), wp_nonce_url(admin_url('admin-ajax.php?action=acv_track_click'), 'acv_nonce'));

        ob_start();
        ?>
        <div class="acv-coupon" data-coupon-id="<?php echo esc_attr($atts['id']); ?>">
            <?php if (!empty($coupon['image'])) { ?><img src="<?php echo esc_url($coupon['image']); ?>" alt="<?php echo esc_attr($coupon['name']); ?>"><?php } ?>
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><strong>Code: <?php echo esc_html($personalized_code); ?></strong></p>
            <a href="#" class="acv-btn" data-url="<?php echo esc_url($coupon['affiliate_url']); ?>&code=<?php echo urlencode($personalized_code); ?>">Get Deal Now (Affiliate)</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('acv_nonce', 'nonce');
        $coupon = sanitize_text_field($_GET['acv_coupon'] ?? '');
        $source = sanitize_text_field($_GET['acv_source'] ?? '');
        if ($coupon && $source) {
            $clicks = get_option('acv_clicks', array()) + array($coupon => 1);
            $clicks[$coupon] = ($clicks[$coupon] ?? 0) + 1;
            update_option('acv_clicks', $clicks);
        }
        $redirect = $_GET['redirect'] ?? home_url();
        wp_redirect(esc_url_raw($redirect));
        exit;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', '[]');
        }
    }
}

AffiliateCouponVault::get_instance();

// Pro check (demo)
if (false && file_exists(plugin_dir_path(__FILE__) . 'pro/acv-pro.php')) {
    require_once plugin_dir_path(__FILE__) . 'pro/acv-pro.php';
}

// Assets would be created separately: script.js for click tracking, style.css for styling
?>