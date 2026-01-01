/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Generate exclusive affiliate coupons with tracking and SEO optimization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCoupons {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_coupon', array($this, 'save_coupon'));
        add_action('wp_ajax_delete_coupon', array($this, 'delete_coupon'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->coupons = get_option('sac_coupons', array());
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-js', plugin_dir_url(__FILE__) . 'sac.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sac-css', plugin_dir_url(__FILE__) . 'sac.css', array(), '1.0.0');
        wp_localize_script('sac-js', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupons', 'Coupons', 'manage_options', 'sac-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_submit'])) {
            check_admin_referer('sac_save');
            $this->save_coupon();
        }
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function save_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $id = sanitize_text_field($_POST['id'] ?? '');
        $coupon = array(
            'id' => $id ?: uniqid(),
            'title' => sanitize_text_field($_POST['title']),
            'code' => sanitize_text_field($_POST['code']),
            'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
            'description' => sanitize_textarea_field($_POST['description']),
            'expires' => sanitize_text_field($_POST['expires']),
            'uses' => intval($_POST['uses']),
        );
        $this->coupons[$coupon['id']] = $coupon;
        update_option('sac_coupons', $this->coupons);
        wp_send_json_success('Coupon saved');
    }

    public function delete_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $id = sanitize_text_field($_POST['id']);
        unset($this->coupons[$id]);
        update_option('sac_coupons', $this->coupons);
        wp_send_json_success('Coupon deleted');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        if (!$atts['id'] || !isset($this->coupons[$atts['id']])) {
            return '';
        }
        $coupon = $this->coupons[$atts['id']];
        if ($coupon['expires'] && strtotime($coupon['expires']) < current_time('timestamp')) {
            return '<div class="sac-expired">Coupon expired</div>';
        }
        $track_link = add_query_arg('sac', $coupon['id'], $coupon['affiliate_link']);
        ob_start();
        ?>
        <div class="sac-coupon" data-id="<?php echo esc_attr($coupon['id']); ?>">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><strong>Code:</strong> <span class="sac-code"><?php echo esc_html($coupon['code']); ?></span></p>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <a href="<?php echo esc_url($track_link); ?}" class="sac-button" target="_blank">Get Deal (Affiliate)</a>
            <?php if ($coupon['expires']): ?>
            <p>Expires: <?php echo esc_html($coupon['expires']); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('sac_coupons')) {
            update_option('sac_coupons', array());
        }
    }
}

new SmartAffiliateCoupons();

// Pro notice
function sac_pro_notice() {
    if (!get_option('sac_pro_activated')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Coupons Pro</strong> for unlimited coupons and analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'sac_pro_notice');

// Minimal CSS
$css = '.sac-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }.sac-code { font-size: 1.2em; color: #d63638; font-weight: bold; }.sac-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }.sac-expired { color: red; font-weight: bold; }';
file_put_contents(plugin_dir_path(__FILE__) . 'sac.css', $css);

// Minimal JS
$js = "jQuery(document).ready(function($) { $('.sac-copy').click(function() { var code = $(this).prev('.sac-code').text(); navigator.clipboard.writeText(code); $(this).text('Copied!'); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'sac.js', "<script>" . $js . "</script>");

// Admin page template
$admin_page = '<div class="wrap"><h1>Affiliate Coupons</h1><form method="post"><h2>Add/Edit Coupon</h2><input type="hidden" name="id" id="coupon-id"><table class="form-table"><tr><th>Title</th><td><input type="text" name="title" id="title" class="regular-text"></td></tr><tr><th>Code</th><td><input type="text" name="code" id="code"></td></tr><tr><th>Affiliate Link</th><td><input type="url" name="affiliate_link" id="affiliate_link" class="regular-text"></td></tr><tr><th>Description</th><td><textarea name="description" id="description" rows="3" class="large-text"></textarea></td></tr><tr><th>Expires (YYYY-MM-DD)</th><td><input type="date" name="expires" id="expires"></td></tr><tr><th>Max Uses</th><td><input type="number" name="uses" id="uses"></td></tr></table><?php wp_nonce_field("sac_save"); ?><p><input type="submit" name="sac_submit" value="Save Coupon" class="button-primary"></p></form><h2>Coupons List</h2><div id="coupons-list">' . json_encode(get_option('sac_coupons', array())) . '</div><script>/* Load coupons list here */</script></div>';
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', "<?php echo '$admin_page'; ?>");