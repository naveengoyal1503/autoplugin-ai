/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, personalized coupon codes for your WordPress site visitors, boosting affiliate conversions and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $options = get_option('exclusive_coupons_options', array('affiliates' => array()));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('exclusive-coupons', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('exclusive-coupons', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('exclusive_coupons_options', $_POST['options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = get_option('exclusive_coupons_options', array('affiliates' => array()));
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Offers</th>
                        <td>
                            <div id="affiliates">
                                <?php foreach ($options['affiliates'] as $index => $aff): ?>
                                    <div class="affiliate-row">
                                        <input type="text" name="options[affiliates][<?php echo $index; ?>][name]" value="<?php echo esc_attr($aff['name']); ?>" placeholder="Brand Name" />
                                        <input type="text" name="options[affiliates][<?php echo $index; ?>][code]" value="<?php echo esc_attr($aff['code']); ?>" placeholder="Coupon Code" />
                                        <input type="url" name="options[affiliates][<?php echo $index; ?>][link]" value="<?php echo esc_attr($aff['link']); ?>" placeholder="Affiliate Link" />
                                        <button type="button" class="button remove-aff">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-affiliate" class="button">Add Affiliate</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var index = <?php echo count($options['affiliates']); ?>;
            $('#add-affiliate').click(function() {
                $('#affiliates').append(
                    '<div class="affiliate-row">' +
                    '<input type="text" name="options[affiliates][" + index + "][name]" placeholder="Brand Name" />' +
                    '<input type="text" name="options[affiliates][" + index + "][code]" placeholder="Coupon Code" />' +
                    '<input type="url" name="options[affiliates][" + index + "][link]" placeholder="Affiliate Link" />' +
                    '<button type="button" class="button remove-aff">Remove</button>' +
                    '</div>'
                );
                index++;
            });
            $(document).on('click', '.remove-aff', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $options = get_option('exclusive_coupons_options', array('affiliates' => array()));
        $id = intval($atts['id']);
        if (!isset($options['affiliates'][$id])) {
            return 'Invalid coupon ID.';
        }
        $aff = $options['affiliates'][$id];
        $unique_code = $aff['code'] . '-' . wp_generate_uuid4();
        $visitor_ip = $_SERVER['REMOTE_ADDR'];
        set_transient('coupon_' . md5($visitor_ip . $id), $unique_code, HOUR_IN_SECONDS);

        ob_start();
        ?>
        <div class="exclusive-coupon" data-id="<?php echo $id; ?>">
            <h3><?php echo esc_html($aff['name']); ?> Exclusive Deal!</h3>
            <p>Your personal coupon: <strong id="coupon-code"><?php echo esc_html($unique_code); ?></strong></p>
            <a href="<?php echo esc_url($aff['link']) . '?coupon=' . urlencode($unique_code); ?>" class="button exclusive-btn" target="_blank">Redeem Now (Affiliate Link)</a>
            <p class="coupon-note">Generated exclusively for you. Valid for 1 hour.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('exclusive_coupons_options', array('affiliates' => array(
            array('name' => 'Sample Brand', 'code' => 'SAVE20', 'link' => 'https://example.com')
        )));
    }
}

ExclusiveCouponsPro::get_instance();

// Premium notice
function exclusive_coupons_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>Exclusive Coupons Pro Premium</strong>: Unlimited coupons, analytics, email capture & more! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'exclusive_coupons_pro_notice');

// Assets would be created separately: script.js and style.css
// For demo, inline styles
/*
.assets/style.css:
.exclusive-coupon { border: 2px solid #0073aa; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; }
.exclusive-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.affiliate-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
.assets/script.js:
jQuery(document).ready(function($) {
    $('.exclusive-coupon').each(function() {
        var $this = $(this);
        setTimeout(function() {
            $this.fadeOut(function() {
                $this.html('<p>Coupon expired. <a href="' + window.location.href + '">Generate new</a></p>').fadeIn();
            });
        }, 3600000); // 1 hour
    });
});
*/