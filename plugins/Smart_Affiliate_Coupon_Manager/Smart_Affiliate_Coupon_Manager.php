/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Plugin URI: https://example.com/smart-affiliate-coupon
 * Description: Automatically generates and manages exclusive affiliate coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCouponManager {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sac_coupon_section', array($this, 'coupon_section_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-frontend', plugin_dir_url(__FILE__) . 'sac-frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sac-frontend', plugin_dir_url(__FILE__) . 'sac-frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Manager', 'manage_options', 'sac-manager', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sac_options', 'sac_coupons');
        add_settings_section('sac_main', 'Coupons', null, 'sac');
        add_settings_field('sac_coupons_list', 'Coupons', array($this, 'coupons_field'), 'sac', 'sac_main');
    }

    public function coupons_field() {
        $coupons = get_option('sac_coupons', array());
        echo '<textarea name="sac_coupons" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>JSON array: [{"name":"Coupon1","code":"SAVE10","afflink":"https://aff.link","desc":"10% off"}]</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Manager</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sac_options');
                do_settings_sections('sac');
                submit_button();
                ?>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[sac_coupon_section]</code> to display coupons.</p>
            <?php
            $coupons = get_option('sac_coupons', array());
            if (!empty($coupons)) {
                echo '<h3>Active Coupons:</h3><ul>';
                foreach ($coupons as $coupon) {
                    echo '<li>' . esc_html($coupon['name']) . ' - ' . esc_html($coupon['code']) . '</li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
        <?php
    }

    public function coupon_section_shortcode($atts) {
        $coupons = get_option('sac_coupons', array());
        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=sac-manager') . '">Set up now</a>.</p>';
        }
        ob_start();
        ?>
        <div id="sac-coupons" class="sac-container">
            <h3>Exclusive Coupons</h3>
            <?php foreach ($coupons as $i => $coupon): ?>
                <div class="sac-coupon">
                    <h4><?php echo esc_html($coupon['name']); ?></h4>
                    <p><?php echo esc_html($coupon['desc']); ?></p>
                    <div class="sac-code">Code: <strong><?php echo esc_html($coupon['code']); ?></strong></div>
                    <a href="<?php echo esc_url($coupon['afflink']); ?}" class="sac-button" target="_blank" rel="nofollow">Get Deal <?php echo $i + 1; ?></a>
                </div>
            <?php endforeach; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#sac-coupons .sac-coupon').click(function() {
                $(this).addClass('clicked');
                gtag('event', 'coupon_click', {'coupon': '<?php echo esc_js($coupon['name']); ?>'});
            });
        });
        </script>
        <style>
        .sac-container { max-width: 600px; }
        .sac-coupon { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 8px; cursor: pointer; transition: box-shadow 0.3s; }
        .sac-coupon:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .sac-code { font-size: 18px; color: #e74c3c; margin: 10px 0; }
        .sac-button { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .sac-button:hover { background: #2980b9; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('sac_coupons')) {
            update_option('sac_coupons', array(
                array('name' => 'Sample Coupon', 'code' => 'SAVE20', 'afflink' => '#', 'desc' => '20% off on sample product')
            ));
        }
    }
}

new SmartAffiliateCouponManager();

// Pro upsell notice
function sac_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Coupon Pro</strong> for unlimited coupons, analytics, and auto-generation! <a href="https://example.com/pro">Get Pro</a></p></div>';
}
add_action('admin_notices', 'sac_admin_notice');