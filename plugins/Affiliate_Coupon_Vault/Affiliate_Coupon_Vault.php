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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon Name|Affiliate Link|Discount Code|Image URL");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Coupons (one per line: Name|Affiliate Link|Discount Code|Image URL)</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="acv_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Usage: <code>[affiliate_coupon id="1"]</code> or <code>[affiliate_coupon]</code> for random.</p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics dashboard, auto-rotation. <a href="https://example.com/pro">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'random'), $atts);
        $coupons_str = get_option('acv_coupons', '');
        if (empty($coupons_str)) return 'No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>';

        $coupons = explode("\n", trim($coupons_str));
        if ($atts['id'] === 'random') {
            $coupon = $coupons[array_rand($coupons)];
        } else {
            $coupon = isset($coupons[(int)$atts['id'] - 1]) ? $coupons[(int)$atts['id'] - 1] : $coupons;
        }
        $parts = explode('|', $coupon);
        if (count($parts) < 4) return 'Invalid coupon format.';

        list($name, $link, $code, $image) = $parts;
        $personalized_code = $code . '-' . substr(md5(uniqid()), 0, 5);
        $track_id = uniqid();

        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px solid #0073aa; padding: 20px; border-radius: 10px; max-width: 300px; text-align: center; background: #f9f9f9;">
            <?php if ($image): ?><img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($name); ?>" style="max-width: 100%; height: auto;"><?php endif; ?>
            <h3><?php echo esc_html($name); ?></h3>
            <p><strong>Code: <?php echo esc_html($personalized_code); ?></strong></p>
            <a href="#" class="acv-button button" data-link="<?php echo esc_url($link); ?>" data-track="<?php echo esc_attr($track_id); ?>" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Get Deal Now (Track Affiliate)</a>
            <p style="font-size: 12px; margin-top: 10px;">Exclusive reader discount!</p>
        </div>
        <script>
        jQuery('.acv-button[data-track="<?php echo esc_js($track_id); ?>"]').click(function(e) {
            e.preventDefault();
            var link = jQuery(this).data('link');
            jQuery.post(acv_ajax.ajax_url, {action: 'acv_track_click', link: link}, function() {
                window.open(link, '_blank');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        if (isset($_POST['link'])) {
            $link = sanitize_url($_POST['link']);
            // In Pro: Log to analytics
            error_log('ACV Click: ' . $link);
        }
        wp_die();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "WP Rocket|https://wp-rocket.me/?aff=123|SAVE20|https://example.com/wp-rocket.jpg\nElementor|https://elementor.com/?ref=456|PRO30|https://example.com/elementor.jpg");
        }
    }

    public function deactivate() {
        // Cleanup optional
    }
}

AffiliateCouponVault::get_instance();

// Prevent direct access to JS file content
if (strpos($_SERVER['REQUEST_URI'], 'acv-script.js') !== false) {
    wp_die('');
}
?>