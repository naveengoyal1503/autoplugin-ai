<?php
/*
Plugin Name: Affiliate Deal Booster
Description: Display customizable coupon widgets linked to affiliate programs to increase conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    public function __construct() {
        add_shortcode('affiliate_deal', array($this, 'render_deal_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        wp_register_style('adb_styles', plugin_dir_url(__FILE__) . 'adb_styles.css');
        wp_enqueue_style('adb_styles');
        wp_register_script('adb_script', plugin_dir_url(__FILE__) . 'adb_script.js', array('jquery'), null, true);
        wp_enqueue_script('adb_script');
    }

    public function render_deal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Special Deal',
            'coupon' => '',
            'deal_url' => '',
            'description' => '',
            'expiry' => '',
            'button_text' => 'Get Deal'
        ), $atts);

        $expiry_notice = '';
        if (!empty($atts['expiry'])) {
            $expiry_time = strtotime($atts['expiry']);
            if ($expiry_time && time() > $expiry_time) {
                return '<div class="adb-expired">This deal has expired.</div>';
            } elseif ($expiry_time) {
                $expiry_notice = '<div class="adb-expiry">Expires on ' . esc_html(date('F j, Y', $expiry_time)) . '</div>';
            }
        }

        $coupon_html = '';
        if (!empty($atts['coupon'])) {
            $coupon_html = '<div class="adb-coupon">Coupon: <strong>' . esc_html($atts['coupon']) . '</strong></div>';
        }

        $escaped_url = esc_url($atts['deal_url']);
        $escaped_button = sanitize_text_field($atts['button_text']);
        $description_html = !empty($atts['description']) ? '<div class="adb-description">' . esc_html($atts['description']) . '</div>' : '';

        ob_start();
        ?>
        <div class="adb-widget">
            <div class="adb-title"><?php echo esc_html($atts['title']); ?></div>
            <?php echo $description_html; ?>
            <?php echo $coupon_html; ?>
            <?php echo $expiry_notice; ?>
            <a href="<?php echo $escaped_url; ?>" target="_blank" rel="nofollow noopener" class="adb-button"><?php echo $escaped_button; ?></a>
        </div>
        <?php
        return ob_get_clean();
    }
}

new AffiliateDealBooster();

// Inline CSS
add_action('wp_head', function(){ ?>
<style>
.adb-widget { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; max-width: 320px; font-family: Arial, sans-serif; margin: 10px 0; border-radius: 5px; }
.adb-title { font-size: 18px; font-weight: bold; margin-bottom: 8px; color: #2c3e50; }
.adb-description { font-size: 14px; color: #34495e; margin-bottom: 10px; }
.adb-coupon { background: #e74c3c; color: #fff; display: inline-block; padding: 5px 10px; font-weight: bold; border-radius: 3px; margin-bottom: 10px; }
.adb-button { display: inline-block; background: #27ae60; color: #fff; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; transition: background 0.3s ease; }
.adb-button:hover { background: #2ecc71; }
.adb-expiry { font-size: 12px; color: #7f8c8d; margin-top: 5px; }
.adb-expired { color: #c0392b; font-weight: bold; font-size: 14px; }
</style>
<?php });
