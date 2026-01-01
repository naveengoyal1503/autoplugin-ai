/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons to boost conversions and earnings.
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
        if (is_admin()) {
            return;
        }
        $this->load_textdomain();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'code' => '',
            'discount' => '10%',
            'link' => '',
            'expires' => ''
        ), $atts);

        $options = get_option('affiliate_coupon_vault_options', array());
        $is_pro = isset($options['pro_license']) && $options['pro_license'] === 'valid';

        if (!$is_pro && $this->count_coupons() >= 3) {
            return '<p>Upgrade to Pro for unlimited coupons!</p>';
        }

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>" data-link="<?php echo esc_url($atts['link']); ?>">
            <div class="coupon-code"><?php echo esc_html($atts['code']); ?></div>
            <div class="coupon-discount"><?php echo esc_html($atts['discount']); ?> OFF</div>
            <?php if ($atts['expires']) : ?>
            <div class="coupon-expires">Expires: <?php echo esc_html($atts['expires']); ?></div>
            <?php endif; ?>
            <a href="#" class="coupon-copy" data-clipboard-text="<?php echo esc_attr($atts['code']); ?>">Copy Code</a>
            <a href="<?php echo esc_url($atts['link']); ?}" class="coupon-button" target="_blank">Shop Now & Save</a>
            <div class="coupon-track">Tracking clicks...</div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function count_coupons() {
        global $post;
        if (!$post) return 0;
        preg_match_all('/\[affiliate_coupon[^\]]*\]/', $post->post_content, $matches);
        return count($matches);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_options');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('affiliate_coupon_vault_options'); ?>
                <?php do_settings_sections('affiliate_coupon_vault_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="affiliate_coupon_vault_options[pro_license]" value="<?php echo esc_attr(get_option('affiliate_coupon_vault_options')['pro_license'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> <a href="https://example.com/pro" target="_blank">Get unlimited coupons for $49/year</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('affiliate_coupon_vault_options', array());
    }

    private function load_textdomain() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

// Assets (base64 encoded for single file)
$css = '<style>.affiliate-coupon-vault{display:flex;flex-direction:column;align-items:center;padding:20px;border:2px dashed #007cba;background:#f9f9f9;max-width:300px;margin:20px auto;}.coupon-code{font-size:2em;font-weight:bold;color:#007cba;background:#fff;padding:10px;border-radius:5px;}.coupon-discount{font-size:1.2em;color:#28a745;}.coupon-expires{font-size:0.9em;color:#6c757d;}.coupon-copy,.coupon-button{display:inline-block;padding:10px 20px;margin:5px;background:#007cba;color:#fff;text-decoration:none;border-radius:5px;}.coupon-copy:hover,.coupon-button:hover{background:#005a87;}.coupon-track{font-size:0.8em;color:#6c757d;}</style>'; // Simplified CSS
$js = '<script>jQuery(document).ready(function($){$(".coupon-copy").click(function(e){e.preventDefault();var code=$(this).data("clipboard-text");navigator.clipboard.writeText(code).then(function(){$(this).text("Copied!");setTimeout(()=>{$(this).text("Copy Code");},2000);},function(){prompt("Copy this code:",code);});var vault=$(this).closest(".affiliate-coupon-vault");$.post("'+admin_url('admin-ajax.php')+'",{action:"track_coupon_click",affiliate:vault.data("affiliate"),link:vault.data("link")});});});</script>'; // Simplified JS

add_action('wp_head', function() { echo $css . $js; });

// AJAX tracking
add_action('wp_ajax_track_coupon_click', 'acv_track_click');
add_action('wp_ajax_nopriv_track_coupon_click', 'acv_track_click');
function acv_track_click() {
    // Log click (Pro feature simulates tracking)
    error_log('Coupon click: ' . $_POST['affiliate']);
    wp_die();
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id == 'settings_page_affiliate-coupon-vault') return;
    echo '<div class="notice notice-info"><p>Unlock <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons and analytics! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade now</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');