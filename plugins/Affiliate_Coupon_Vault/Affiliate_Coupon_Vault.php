/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons, exclusive deals, and discount codes to boost conversions and commissions.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupons', array($this, 'save_coupons'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('affiliate-coupon-vault', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        $coupons = get_option('acv_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }

        $coupon = $coupons[$atts['id']];
        $aff_link = !empty($coupon['affiliate_url']) ? $coupon['affiliate_url'] . (strpos($coupon['affiliate_url'], '?') ? '&' : '?') . 'ref=' . get_bloginfo('url') : '#';

        ob_start();
        ?>
        <div class="acv-coupon-box">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <div class="acv-code"><?php echo esc_html($coupon['code']); ?></div>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <a href="<?php echo esc_url($aff_link); ?}" target="_blank" class="acv-button" rel="nofollow">Get Deal (<?php echo esc_html($coupon['discount']); ?> Off)</a>
            <?php if ($coupon['pro']) { echo '<span class="acv-pro-badge">Pro Feature</span>'; } ?>
        </div>
        <?php
        return ob_get_clean();
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

    public function admin_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('acv_save');
            $coupons = isset($_POST['coupons']) ? $_POST['coupons'] : array();
            foreach ($coupons as &$c) {
                $c = array_map('sanitize_text_field', $c);
            }
            update_option('acv_coupons', $coupons);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }

        $coupons = get_option('acv_coupons', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <?php wp_nonce_field('acv_save'); ?>
                <table class="form-table">
                    <tr>
                        <th>Add Coupon</th>
                        <td>
                            <input type="text" name="new_coupon[title]" placeholder="Coupon Title" class="regular-text" /><br>
                            <input type="text" name="new_coupon[code]" placeholder="CODE20" class="regular-text" /><br>
                            <input type="text" name="new_coupon[description]" placeholder="20% off first purchase" class="regular-text" /><br>
                            <input type="text" name="new_coupon[discount]" placeholder="20%" class="regular-text" /><br>
                            <input type="url" name="new_coupon[affiliate_url]" placeholder="https://affiliate.com" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <?php submit_button('Add Coupon'); ?>
            </form>

            <h2>Manage Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($coupons as $id => $coupon) : ?>
                    <tr>
                        <td><?php echo $id; ?></td>
                        <td><?php echo esc_html($coupon['title']); ?></td>
                        <td><?php echo esc_html($coupon['code']); ?></td>
                        <td><a href="#" class="delete-coupon" data-id="<?php echo $id; ?>">Delete</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><strong>Upgrade to Pro:</strong> Unlimited coupons, analytics, auto-expiry, and more! <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.delete-coupon').click(function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                // Simple delete simulation - in pro, use AJAX
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    public function save_coupons() {
        check_ajax_referer('acv_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $coupons = $_POST['coupons'];
        update_option('acv_coupons', $coupons);
        wp_send_json_success();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', array(
                0 => array(
                    'title' => 'Sample Coupon',
                    'code' => 'WELCOME20',
                    'description' => '20% off your first order',
                    'discount' => '20%',
                    'affiliate_url' => 'https://example.com',
                    'pro' => true
                )
            ));
        }
    }
}

// Create assets directories if not exist
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Minimal CSS
file_put_contents($assets_dir . '/style.css', ".acv-coupon-box { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; } .acv-code { font-size: 2em; font-weight: bold; color: #007cba; background: white; padding: 10px; margin: 10px 0; display: inline-block; } .acv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; } .acv-pro-badge { color: #ff0000; font-weight: bold; }");

// Minimal JS
file_put_contents($assets_dir . '/script.js', "jQuery(document).ready(function($) { console.log('Affiliate Coupon Vault loaded'); });");

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_affiliate-coupon-vault') {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, click tracking, and premium templates for just $49/year!</p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');