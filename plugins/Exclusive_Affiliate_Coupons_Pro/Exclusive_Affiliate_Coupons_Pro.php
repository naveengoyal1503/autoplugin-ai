/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Affiliate Coupons Pro
 * Plugin URI: https://example.com/affiliate-coupons-pro
 * Description: Automatically generates and displays exclusive, trackable affiliate coupons to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponsPro {
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
        load_plugin_textdomain('affiliate-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupons-pro', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupons-pro', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupons Pro',
            'Affiliate Coupons',
            'manage_options',
            'affiliate-coupons-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupons_options', 'affiliate_coupons_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Affiliate Coupons Pro Settings', 'affiliate-coupons-pro'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupons_options');
                do_settings_sections('affiliate_coupons_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Coupons', 'affiliate-coupons-pro'); ?></th>
                        <td>
                            <textarea name="affiliate_coupons_settings[coupons]" rows="10" cols="50" placeholder='[{"name":"10% Off","code":"AFF10","affiliate_link":"https://example.com","description":"Exclusive discount"}]'><?php echo esc_textarea(get_option('affiliate_coupons_settings')['coupons'] ?? ''); ?></textarea>
                            <p class="description"><?php _e('JSON array of coupons: {"name":"Name","code":"CODE","affiliate_link":"URL","description":"Desc"}', 'affiliate-coupons-pro'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('affiliate_coupons_settings', array('coupons' => '[]'));
        $coupons = json_decode($settings['coupons'], true);
        if (!isset($coupons[$atts['id']])) {
            return '';
        }
        $coupon = $coupons[$atts['id']];
        $unique_code = $coupon['code'] . '-' . uniqid();
        ob_start();
        ?>
        <div class="affiliate-coupon-pro" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <div class="coupon-code"><?php echo esc_html($unique_code); ?></div>
            <a href="<?php echo esc_url($coupon['affiliate_link'] . '?coupon=' . urlencode($unique_code)); ?>" class="coupon-button" target="_blank">Get Deal Now (Affiliate Link)</a>
            <p class="coupon-copy">Click to copy code: <button onclick="copyCoupon(this)">Copy</button></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('affiliate_coupons_settings', array('coupons' => '[]'));
    }
}

AffiliateCouponsPro::get_instance();

/* style.css content (inline for single file) */
function affiliate_coupons_pro_styles() {
    ?>
    <style>
    .affiliate-coupon-pro {
        border: 2px solid #007cba;
        padding: 20px;
        margin: 20px 0;
        border-radius: 10px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        text-align: center;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .affiliate-coupon-pro h3 {
        color: #007cba;
        margin: 0 0 10px;
    }
    .coupon-code {
        font-size: 24px;
        font-weight: bold;
        background: #fff;
        padding: 10px;
        border: 1px dashed #007cba;
        margin: 10px 0;
        word-break: break-all;
    }
    .coupon-button {
        display: inline-block;
        background: #007cba;
        color: white;
        padding: 12px 24px;
        text-decoration: none;
        border-radius: 5px;
        font-weight: bold;
        transition: background 0.3s;
    }
    .coupon-button:hover {
        background: #005a87;
    }
    </style>
    <?php
}
add_action('wp_head', 'affiliate_coupons_pro_styles');

/* script.js content (inline) */
function affiliate_coupons_pro_scripts() {
    ?>
    <script>
    function copyCoupon(button) {
        const code = button.parentElement.parentElement.querySelector('.coupon-code').textContent;
        navigator.clipboard.writeText(code).then(() => {
            button.textContent = 'Copied!';
            setTimeout(() => { button.textContent = 'Copy'; }, 2000);
        });
    }
    </script>
    <?php
}
add_action('wp_footer', 'affiliate_coupons_pro_scripts');