/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost your commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-js', plugin_dir_url(__FILE__) . 'coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-css', plugin_dir_url(__FILE__) . 'coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_settings', 'affiliate_coupon_options');
        add_settings_section('main_section', 'Coupon Settings', null, 'affiliate_coupon');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate_coupon', 'main_section');
    }

    public function coupons_field() {
        $options = get_option('affiliate_coupon_options', array());
        $coupons = isset($options['coupons']) ? $options['coupons'] : array(
            array('name' => 'Sample Deal', 'code' => 'SAVE20', 'url' => '#', 'affiliate' => 'your-aff-link', 'description' => '20% off everything')
        );
        ?>
        <div id="coupons-container">
            <?php foreach ($coupons as $index => $coupon): ?>
            <div class="coupon-row">
                <input type="text" name="affiliate_coupon_options[coupons][<?php echo $index; ?>][name]" value="<?php echo esc_attr($coupon['name']); ?>" placeholder="Coupon Name" />
                <input type="text" name="affiliate_coupon_options[coupons][<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" placeholder="Code" />
                <input type="url" name="affiliate_coupon_options[coupons][<?php echo $index; ?>][url]" value="<?php echo esc_attr($coupon['url']); ?>" placeholder="Redeem URL" />
                <input type="url" name="affiliate_coupon_options[coupons][<?php echo $index; ?>][affiliate]" value="<?php echo esc_attr($coupon['affiliate']); ?>" placeholder="Affiliate Link" />
                <textarea name="affiliate_coupon_options[coupons][<?php echo $index; ?>][description]" placeholder="Description"><?php echo esc_textarea($coupon['description']); ?></textarea>
                <button type="button" class="remove-coupon">Remove</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-coupon">Add Coupon</button>
        <script>
        jQuery(document).ready(function($) {
            var index = <?php echo count($coupons); ?>;
            $('#add-coupon').click(function() {
                $('#coupons-container').append(
                    '<div class="coupon-row">' +
                    '<input type="text" name="affiliate_coupon_options[coupons][" + index + "][name]" placeholder="Coupon Name" />' +
                    '<input type="text" name="affiliate_coupon_options[coupons][" + index + "][code]" placeholder="Code" />' +
                    '<input type="url" name="affiliate_coupon_options[coupons][" + index + "][url]" placeholder="Redeem URL" />' +
                    '<input type="url" name="affiliate_coupon_options[coupons][" + index + "][affiliate]" placeholder="Affiliate Link" />' +
                    '<textarea name="affiliate_coupon_options[coupons][" + index + "][description]" placeholder="Description"></textarea>' +
                    '<button type="button" class="remove-coupon">Remove</button>' +
                    '</div>'
                );
                index++;
            });
            $(document).on('click', '.remove-coupon', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_settings');
                do_settings_sections('affiliate_coupon');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, click tracking, analytics dashboard, and custom designs for $49/year!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $options = get_option('affiliate_coupon_options', array());
        $coupons = isset($options['coupons']) ? $options['coupons'] : array();
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $track_url = add_query_arg('ref', 'affvault', $coupon['affiliate']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-track="<?php echo esc_attr($track_url); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <a href="#" class="coupon-btn" data-url="<?php echo esc_url($track_url); ?>">Get Deal (Affiliate)</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('affiliate_coupon_options')) {
            update_option('affiliate_coupon_options', array('coupons' => array(
                array('name' => 'Sample Deal', 'code' => 'SAVE20', 'url' => '#', 'affiliate' => 'https://example.com/aff', 'description' => '20% off')
            )));
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; max-width: 400px; }
.coupon-code { font-size: 2em; background: #fff; padding: 10px; margin: 10px 0; font-weight: bold; border: 1px solid #ddd; }
.coupon-btn { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; }
.coupon-btn:hover { background: #005a87; }
.pro-notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; border-radius: 4px; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) { $('.coupon-btn').click(function(e) { e.preventDefault(); window.open($(this).data('url'), '_blank'); ga('send', 'event', 'Coupon', 'Click', '<?php echo esc_js(get_query_var('coupon_name', 'vault')); ?>'); }); });</script>
<?php });