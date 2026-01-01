/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Plugin URI: https://example.com/smart-affiliate-coupon-manager
 * Description: Automatically generates and manages personalized affiliate coupons with click tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCouponManager {
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
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
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
        add_options_page('Smart Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'sac-manager', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('sac_options', 'sac_coupons');
        add_settings_section('sac_main', 'Coupons', null, 'sac-manager');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Manager</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sac_options');
                do_settings_sections('sac-manager');
                $coupons = get_option('sac_coupons', array());
                ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons</th>
                        <td>
                            <table id="sac-coupons-table" class="wp-list-table widefat fixed striped">
                                <thead><tr><th>Name</th><th>Code</th><th>Affiliate URL</th><th>Discount</th><th>Actions</th></tr></thead>
                                <tbody>
                                <?php foreach ($coupons as $index => $coupon): ?>
                                    <tr>
                                        <td><input type="text" name="sac_coupons[<?php echo $index; ?>][name]" value="<?php echo esc_attr($coupon['name']); ?>" /></td>
                                        <td><input type="text" name="sac_coupons[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" /></td>
                                        <td><input type="url" name="sac_coupons[<?php echo $index; ?>][url]" value="<?php echo esc_attr($coupon['url']); ?>" /></td>
                                        <td><input type="text" name="sac_coupons[<?php echo $index; ?>][discount]" value="<?php echo esc_attr($coupon['discount']); ?>" /></td>
                                        <td><button type="button" class="button button-secondary sac-remove">Remove</button></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p><button type="button" id="sac-add-coupon" class="button button-primary">Add Coupon</button></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-expiration, and A/B testing for $49/year.</p>
        </div>
        <style>
            #sac-coupons-table input { width: 100%; margin-bottom: 5px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            var index = <?php echo count($coupons); ?>;
            $('#sac-add-coupon').click(function() {
                var row = '<tr><td><input type="text" name="sac_coupons[' + index + '][name]" /></td><td><input type="text" name="sac_coupons[' + index + '][code]" /></td><td><input type="url" name="sac_coupons[' + index + '][url]" /></td><td><input type="text" name="sac_coupons[' + index + '][discount]" /></td><td><button type="button" class="button button-secondary sac-remove">Remove</button></td></tr>';
                $('#sac-coupons-table tbody').append(row);
                index++;
            });
            $(document).on('click', '.sac-remove', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('sac_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $track_id = uniqid('sac_');
        $tracked_url = add_query_arg('sac_track', $track_id, $coupon['url']);
        ob_start();
        ?>
        <div class="sac-coupon" data-coupon-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <div class="sac-code">Code: <strong><?php echo esc_html($coupon['code']); ?></strong></div>
            <div class="sac-discount"><?php echo esc_html($coupon['discount']); ?> OFF</div>
            <a href="<?php echo esc_url($tracked_url); ?>" class="sac-button button" target="_blank">Get Deal & Track Click</a>
            <div class="sac-stats">Clicks: <span class="sac-clicks">0</span></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('sac_coupons')) {
            update_option('sac_coupons', array(
                array('name' => 'Sample Coupon', 'code' => 'WELCOME10', 'url' => 'https://example.com/affiliate', 'discount' => '10%')
            ));
        }
    }
}

SmartAffiliateCouponManager::get_instance();

// AJAX for tracking
add_action('wp_ajax_sac_track_click', array(SmartAffiliateCouponManager::get_instance(), 'track_click'));
add_action('wp_ajax_nopriv_sac_track_click', array(SmartAffiliateCouponManager::get_instance(), 'track_click'));

function sac_track_click() {
    if (isset($_POST['coupon_id'])) {
        $coupon_id = intval($_POST['coupon_id']);
        $stats = get_option('sac_stats_' . $coupon_id, 0);
        update_option('sac_stats_' . $coupon_id, $stats + 1);
        wp_die('Tracked');
    }
}

// Frontend JS and CSS (embedded for single file)
function sac_frontend_assets() {
    ?>
    <script>jQuery(document).ready(function($) {
        $('.sac-button').click(function(e) {
            e.preventDefault();
            var url = $(this).attr('href');
            var couponId = $(this).closest('.sac-coupon').data('coupon-id');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'sac_track_click', coupon_id: couponId}, function() {
                window.open(url, '_blank');
            });
            return false;
        });
    });</script>
    <style>
        .sac-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; border-radius: 8px; background: #f9f9f9; }
        .sac-code { font-size: 24px; margin: 10px 0; }
        .sac-discount { color: #00a32a; font-size: 20px; font-weight: bold; }
        .sac-button { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .sac-button:hover { background: #005a87; }
        .sac-stats { margin-top: 10px; font-size: 14px; }
    </style>
    <?php
}
add_action('wp_footer', 'sac_frontend_assets');