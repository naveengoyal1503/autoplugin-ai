/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Pro
 * Plugin URI: https://example.com/smart-affiliate-coupon-pro
 * Description: Automatically generates and displays targeted affiliate coupons, deal trackers, and promotional banners to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCouponPro {
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
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        add_shortcode('sac_deals', array($this, 'deals_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sac-style', plugins_url('style.css', __FILE__), array(), '1.0.0');
        wp_enqueue_script('sac-script', plugins_url('script.js', __FILE__), array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Coupon Pro Settings',
            'SAC Pro',
            'manage_options',
            'sac-pro',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['sac_submit'])) {
            update_option('sac_api_key', sanitize_text_field($_POST['sac_api_key']));
            update_option('sac_affiliate_ids', sanitize_textarea_field($_POST['sac_affiliate_ids']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('sac_api_key', '');
        $affiliate_ids = get_option('sac_affiliate_ids', '');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate API Key</th>
                        <td><input type="text" name="sac_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Network IDs (one per line)</th>
                        <td><textarea name="sac_affiliate_ids" rows="5" class="large-text"><?php echo esc_textarea($affiliate_ids); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-rotation, and premium integrations for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default',
            'type' => 'coupon'
        ), $atts);

        $coupons = $this->get_sample_coupons();
        $coupon = $coupons[array_rand($coupons)];

        ob_start();
        ?>
        <div class="sac-coupon sac-<?php echo esc_attr($atts['type']); ?>" data-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="sac-header">
                <span class="sac-discount"><?php echo esc_html($coupon['discount']); ?></span>
                <span class="sac-code"><?php echo esc_html($coupon['code']); ?></span>
            </div>
            <div class="sac-description"><?php echo esc_html($coupon['description']); ?></div>
            <a href="<?php echo esc_url($coupon['link']); ?}" class="sac-button" target="_blank">Get Deal <?php echo $this->is_pro() ? '' : '(Pro Feature)'; ?></a>
            <?php if (!$this->is_pro()) { ?>
            <div class="sac-upgrade">Upgrade to Pro for live tracking!</div>
            <?php } ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function deals_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 3), $atts);
        $deals = $this->get_sample_coupons();
        shuffle($deals);
        $deals = array_slice($deals, 0, intval($atts['limit']));

        ob_start();
        echo '<div class="sac-deals-grid">';
        foreach ($deals as $deal) {
            echo '<div class="sac-deal">';
            echo '<h4>' . esc_html($deal['title']) . '</h4>';
            echo '<p>' . esc_html($deal['description']) . '</p>';
            echo '<a href="' . esc_url($deal['link']) . '" class="sac-deal-link" target="_blank">Shop Now</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    private function get_sample_coupons() {
        return array(
            array(
                'title' => 'Bluehost Hosting',
                'discount' => '70% OFF',
                'code' => 'BLUE70',
                'description' => 'First year hosting for just $2.95/mo with free domain.',
                'link' => 'https://example.com/aff/bluehost'
            ),
            array(
                'title' => 'Elementor Pro',
                'discount' => '50% OFF',
                'code' => 'ELEM50',
                'description' => 'Premium WordPress page builder discount.',
                'link' => 'https://example.com/aff/elementor'
            ),
            array(
                'title' => 'WP Rocket',
                'discount' => '40% OFF',
                'code' => 'ROCKET40',
                'description' => 'Top caching plugin for speed optimization.',
                'link' => 'https://example.com/aff/wprocket'
            )
        );
    }

    private function is_pro() {
        return false; // Check license in pro version
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateCouponPro::get_instance();

// Inline styles and scripts for single file

function sac_inline_styles() {
    ?>
    <style>
    .sac-coupon { border: 2px solid #28a745; border-radius: 10px; padding: 20px; margin: 20px 0; background: #f8f9fa; text-align: center; max-width: 400px; }
    .sac-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .sac-discount { font-size: 24px; font-weight: bold; color: #28a745; }
    .sac-code { background: #ffc107; padding: 5px 10px; border-radius: 5px; font-family: monospace; }
    .sac-button { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .sac-button:hover { background: #005a87; }
    .sac-upgrade { font-size: 12px; color: #dc3545; margin-top: 10px; }
    .sac-deals-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
    .sac-deal { border: 1px solid #ddd; padding: 15px; border-radius: 8px; }
    .sac-deal-link { color: #28a745; text-decoration: none; font-weight: bold; }
    </style>
    <?php
}
add_action('wp_head', 'sac_inline_styles');

/*
End of plugin
*/
?>