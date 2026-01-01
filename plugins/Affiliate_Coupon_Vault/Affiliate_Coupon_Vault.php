/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals to boost commissions.
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
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
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
        $this->options = get_option('affiliate_coupon_vault_options', array(
            'api_key' => '',
            'coupons' => array(
                array('code' => 'SAVE10', 'description' => '10% off on all products', 'affiliate_link' => ''),
                array('code' => 'WELCOME20', 'description' => '20% off first purchase', 'affiliate_link' => ''),
            ),
            'pro' => false
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons = $this->options['coupons'];
        shuffle($coupons);
        $coupons = array_slice($coupons, 0, intval($atts['limit']));

        $output = '<div class="affiliate-coupon-vault">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="coupon-item">';
            $output .= '<h4>' . esc_html($coupon['code']) . '</h4>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            $output .= '<a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" class="coupon-btn">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';

        if (!$this->options['pro']) {
            $output .= '<p><em>Upgrade to Pro for unlimited coupons and analytics!</em></p>';
        }

        return $output;
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_options');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('affiliate_coupon_vault_options', $_POST['options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $this->load_settings();
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function activate() {
        add_option('affiliate_coupon_vault_options', array());
    }
}

AffiliateCouponVault::get_instance();

// Create CSS file content
$css = ".affiliate-coupon-vault { max-width: 400px; margin: 20px 0; }
.coupon-item { background: #f9f9f9; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.coupon-btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; }
.coupon-btn:hover { background: #005a87; }";
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $css);

// Create JS file content
$js = "jQuery(document).ready(function($) {
    $('.coupon-item').on('click', '.coupon-btn', function() {
        $(this).text('Copied!');
        setTimeout(function() { $('.coupon-btn').text('Get Deal'); }, 2000);
    });
});";
file_put_contents(plugin_dir_path(__FILE__) . 'script.js', $js);

// Create admin-page.php
$admin_page = '<div class="wrap">
<h1>Affiliate Coupon Vault Settings</h1>
<form method="post">
<table class="form-table">
<tr><th>Pro License</th><td><input type="checkbox" name="options[pro]" ' . checked(1, $options['pro'], false) . ' disabled> <em>Pro feature</em></td></tr>
<tr><th>Add Coupon</th><td>
<input type="text" name="options[new_code]" placeholder="Coupon Code"><br>
<input type="text" name="options[new_desc]" placeholder="Description"><br>
<input type="url" name="options[new_link]" placeholder="Affiliate Link"><br>
<input type="submit" name="add_coupon" value="Add Coupon" class="button">
</td></tr>
</table>
' . wp_nonce_field('affiliate_coupon_vault', '_nonce') . '
<input type="submit" name="submit" value="Save Settings" class="button-primary">
</form>
<h2>Your Coupons</h2>
<ul>';
foreach ($options['coupons'] as $c) {
    $admin_page .= '<li>' . $c['code'] . ' - ' . $c['description'] . ' <a href="' . $c['affiliate_link'] . '">' . $c['affiliate_link'] . '</a></li>';
}
$admin_page .= '</ul>
<p>Use shortcode: <code>[affiliate_coupons limit="3"]</code></p>
</div>';
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', $admin_page);

?>