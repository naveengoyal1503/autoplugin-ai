/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Generate exclusive affiliate coupons with tracking and auto-expiration to maximize commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }
    }

    public function admin_menu() {
        add_menu_page(
            'Smart Coupons',
            'Affiliate Coupons',
            'manage_options',
            'smart-affiliate-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('sac_coupons', '[]');
        ?>
        <div class="wrap">
            <h1>Manage Affiliate Coupons</h1>
            <form method="post">
                <textarea name="coupons" rows="20" cols="80" style="width:100%;"><?php echo esc_textarea($coupons); ?></textarea>
                <p>Format: JSON array e.g. [{ "code": "SAVE20", "afflink": "https://aff.link", "desc": "20% off", "expires": "2026-12-31" }]</p>
                <p class="submit"><input type="submit" name="sac_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[sac_coupon id="0"]</code> or <code>[sac_coupon]</code> for random.</p>
            <p><strong>Premium:</strong> Unlimited coupons, analytics, auto-expiry. <a href="#" onclick="alert('Upgrade to Pro!')">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => -1), $atts);
        $coupons = json_decode(get_option('sac_coupons', '[]'), true);
        if (empty($coupons) || !is_array($coupons)) {
            return '<p>No coupons available.</p>';
        }
        $id = intval($atts['id']);
        if ($id < 0 || $id >= count($coupons)) {
            $id = array_rand($coupons);
        }
        $coupon = $coupons[$id];
        $now = current_time('mysql');
        if (isset($coupon['expires']) && $now > $coupon['expires']) {
            return '<p>This coupon has expired.</p>';
        }
        $track_id = uniqid('sac_');
        $tracked_link = add_query_arg('sac', $track_id, $coupon['afflink']);
        ob_start();
        ?>
        <div class="sac-coupon" style="border:2px dashed #0073aa; padding:20px; margin:20px 0; background:#f9f9f9;">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <div style="font-size:48px; font-weight:bold; color:#0073aa; margin:10px 0;"><?php echo esc_html($coupon['code']); ?></div>
            <p>Limited time offer! <a href="<?php echo esc_url($tracked_link); ?>" target="_blank" class="button button-large button-primary" style="padding:10px 20px;">Get Deal Now</a></p>
            <small>Tracked via Smart Affiliate Coupons <?php echo esc_html($track_id); ?></small>
        </div>
        <style>
        .sac-coupon { text-align:center; border-radius:10px; }
        .sac-coupon .button { text-decoration:none; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sac-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function admin_scripts($hook) {
        if ('toplevel_page_smart-affiliate-coupons' !== $hook) return;
        wp_enqueue_code_editor(array('type' => 'text/html'));
    }

    public function activate() {
        if (!get_option('sac_coupons')) {
            update_option('sac_coupons', json_encode(array(
                array('code' => 'WELCOME10', 'afflink' => 'https://example-affiliate.com/?ref=wp', 'desc' => '10% Off First Purchase', 'expires' => '2026-06-30')
            )));
        }
    }
}

SmartAffiliateCoupons::get_instance();

// Log tracks for free version (premium has dashboard)
add_action('init', function() {
    if (isset($_GET['sac'])) {
        $track = sanitize_text_field($_GET['sac']);
        error_log('SAC Track: ' . $track . ' from ' . $_SERVER['REMOTE_ADDR']);
        // Premium: save to DB
    }
});

// Prevent direct access
if (!defined('SAC_PLUGIN_FILE')) define('SAC_PLUGIN_FILE', __FILE__);
?>