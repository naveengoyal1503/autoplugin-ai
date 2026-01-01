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
    public function __construct() {
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
        $this->load_settings();
    }

    private function load_settings() {
        $this->settings = get_option('affiliate_coupon_vault_settings', array(
            'coupons' => array(
                array('code' => 'SAVE10', 'description' => '10% off on all products', 'affiliate_link' => 'https://affiliate-link.com/?ref=123', 'image' => ''),
            ),
            'pro' => false
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        if (!isset($this->settings['coupons'][$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $this->settings['coupons'][$atts['id']];
        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-coupon-id="<?php echo esc_attr($atts['id']); ?>">
            <?php if ($coupon['image']): ?>
            <img src="<?php echo esc_url($coupon['image']); ?>" alt="Coupon" style="max-width:100%;">
            <?php endif; ?>
            <h3><?php echo esc_html($coupon['description']); ?></h3>
            <div class="coupon-code">Code: <strong><?php echo esc_html($coupon['code']); ?></strong></div>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?>" target="_blank" class="coupon-button">Get Deal Now</a>
            <p class="pro-notice">Upgrade to Pro for analytics & more!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_group', 'affiliate_coupon_vault_settings');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('affiliate_coupon_vault_settings', $_POST['settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = get_option('affiliate_coupon_vault_settings', array('coupons' => array()));
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('affiliate_coupon_vault_save'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons</th>
                        <td>
                            <div id="coupons-list">
                                <?php foreach ($settings['coupons'] as $i => $coupon): ?>
                                <div class="coupon-item">
                                    <input type="text" name="settings[coupons][<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" placeholder="Coupon Code" />
                                    <input type="text" name="settings[coupons][<?php echo $i; ?>][description]" value="<?php echo esc_attr($coupon['description']); ?>" placeholder="Description" />
                                    <input type="url" name="settings[coupons][<?php echo $i; ?>][affiliate_link]" value="<?php echo esc_attr($coupon['affiliate_link']); ?>" placeholder="Affiliate Link" />
                                    <input type="url" name="settings[coupons][<?php echo $i; ?>][image]" value="<?php echo esc_attr($coupon['image']); ?>" placeholder="Image URL" />
                                    <button type="button" class="button remove-coupon">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-coupon" class="button">Add Coupon</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> <code>[affiliate_coupon_vault id="0"]</code> (replace 0 with coupon index)</p>
            <p><a href="https://example.com/pro" target="_blank">Upgrade to Pro</a> for unlimited coupons, tracking, and integrations.</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let couponIndex = <?php echo count($settings['coupons']); ?>;
            $('#add-coupon').click(function() {
                $('#coupons-list').append(
                    '<div class="coupon-item">' +
                    '<input type="text" name="settings[coupons][" + couponIndex + '][code]" placeholder="Coupon Code" />' +
                    '<input type="text" name="settings[coupons][" + couponIndex + '][description]" placeholder="Description" />' +
                    '<input type="url" name="settings[coupons][" + couponIndex + '][affiliate_link]" placeholder="Affiliate Link" />' +
                    '<input type="url" name="settings[coupons][" + couponIndex + '][image]" placeholder="Image URL" />' +
                    '<button type="button" class="button remove-coupon">Remove</button>' +
                    '</div>'
                );
                couponIndex++;
            });
            $(document).on('click', '.remove-coupon', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <style>
        .coupon-item { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .coupon-item input { width: 20%; margin-right: 5px; }
        </style>
        <?php
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array('coupons' => array()));
    }
}

new AffiliateCouponVault();

/* Pro upsell notice */
function affiliate_coupon_vault_pro_notice() {
    if (!get_option('affiliate_coupon_vault_pro_dismissed')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock unlimited coupons & analytics! <a href="https://example.com/pro">Upgrade Now</a> | <a href="?dismiss_pro=1">Dismiss</a></p></div>';
        });
    }
    if (isset($_GET['dismiss_pro'])) {
        update_option('affiliate_coupon_vault_pro_dismissed', true);
    }
}
add_action('init', 'affiliate_coupon_vault_pro_notice');

/* Frontend CSS */
function affiliate_coupon_vault_styles() {
    echo '<style>
    .affiliate-coupon-vault { background: #fff; border: 2px dashed #007cba; padding: 20px; text-align: center; max-width: 400px; margin: 20px auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .coupon-code { font-size: 24px; margin: 15px 0; }
    .coupon-button { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold; }
    .coupon-button:hover { background: #005a87; }
    .pro-notice { font-size: 12px; color: #666; margin-top: 10px; }
    </style>';
}
add_action('wp_head', 'affiliate_coupon_vault_styles');

/* JS for copy code */
function affiliate_coupon_vault_js() {
    echo '<script>jQuery(document).ready(function($) { $(".coupon-code").each(function() { $(this).append("<button class=\"copy-btn\">Copy</button>"); }); $(".copy-btn").click(function() { navigator.clipboard.writeText($(this).siblings("strong").text()); alert("Copied!"); }); });</script>';
}
add_action('wp_footer', 'affiliate_coupon_vault_js');
