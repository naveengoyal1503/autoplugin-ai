/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Affiliate Coupons Pro
 * Plugin URI: https://example.com/custom-affiliate-coupons
 * Description: Create, manage, and track personalized affiliate coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CustomAffiliateCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('cac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('cac-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('cac-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page('Coupons', 'Coupons', 'manage_options', 'cac-coupons', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('cac_options', 'cac_coupons', array($this, 'sanitize_coupons'));
    }

    public function sanitize_coupons($input) {
        return is_array($input) ? array_map('sanitize_text_field', $input) : array();
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('cac_coupons', $_POST['cac_coupons']);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('cac_coupons', array());
        ?>
        <div class="wrap">
            <h1>Manage Affiliate Coupons</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Affiliate URL</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($coupons as $index => $coupon): ?>
                    <tr>
                        <td><input type="text" name="cac_coupons[<?php echo $index; ?>][name]" value="<?php echo esc_attr($coupon['name']); ?>" /></td>
                        <td><input type="text" name="cac_coupons[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" /></td>
                        <td><input type="url" name="cac_coupons[<?php echo $index; ?>][url]" value="<?php echo esc_attr($coupon['url']); ?>" /></td>
                        <td><a href="#" onclick="return deleteRow(this)">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <p><input type="button" class="button" value="Add New" onclick="addRow()" /></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        function addRow() {
            const table = document.querySelector('.form-table');
            const row = table.insertRow();
            row.innerHTML = `<td><input type="text" name="cac_coupons[${table.rows.length - 1}][name]" /></td><td><input type="text" name="cac_coupons[${table.rows.length - 1}][code]" /></td><td><input type="url" name="cac_coupons[${table.rows.length - 1}][url]" /></td><td><a href="#" onclick="return deleteRow(this)">Delete</a></td>`;
        }
        function deleteRow(link) {
            if (confirm('Delete this coupon?')) {
                link.closest('tr').remove();
            }
            return false;
        }
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('cac_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $track_id = uniqid('cac_');
        $tracked_url = add_query_arg('cac_track', $track_id, $coupon['url']);
        ob_start();
        ?>
        <div class="cac-coupon" data-track="<?php echo esc_attr($track_id); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($tracked_url); ?>" class="coupon-button" target="_blank">Get Deal & Track</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('cac_coupons')) {
            update_option('cac_coupons', array());
        }
    }
}

new CustomAffiliateCouponsPro();

// Pro notice
function cac_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Custom Affiliate Coupons Pro</strong> for analytics and unlimited coupons! <a href="https://example.com/pro" target="_blank">Learn More</a></p></div>';
}
add_action('admin_notices', 'cac_pro_notice');

// Minimal CSS
/*
.cac-coupon { border: 2px dashed #0073aa; padding: 20px; text-align: center; margin: 20px 0; background: #f9f9f9; }
.coupon-code { font-size: 2em; font-weight: bold; color: #0073aa; margin: 10px 0; }
.coupon-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.coupon-button:hover { background: #005a87; }
*/

// Minimal JS
/*
(function($) {
    $(document).on('click', '.coupon-button', function(e) {
        const track = $(this).closest('.cac-coupon').data('track');
        // Send tracking beacon in pro version
        console.log('Tracking coupon:', track);
    });
})(jQuery);
*/
?>