/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Generate exclusive affiliate coupons with tracking, auto-expiration, and revenue stats. Boost conversions with custom promo codes.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        } else {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        }
        wp_register_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_register_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_acv-dashboard' !== $hook) return;
        wp_enqueue_style('acv-style');
        wp_enqueue_script('acv-script');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style');
    }

    public function admin_menu() {
        add_menu_page(
            'Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'acv-dashboard',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;
        $coupons = get_option('acv_coupons', array());
        $stats = get_option('acv_stats', array('uses' => 0, 'revenue' => 0));
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <div id="acv-form">
                <h2>Add New Coupon</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('acv_add_coupon', 'acv_nonce'); ?>
                    <p><label>Affiliate Link: <input type="url" name="aff_link" required style="width:300px;"></label></p>
                    <p><label>Coupon Code: <input type="text" name="code" required style="width:200px;"></label></p>
                    <p><label>Discount (%): <input type="number" name="discount" min="1" max="100" required style="width:100px;"></label></p>
                    <p><label>Expires (days): <input type="number" name="expires" min="1" max="365" value="30" style="width:100px;"></label></p>
                    <p><label>Description: <textarea name="desc" rows="3" cols="50"></textarea></label></p>
                    <?php submit_button('Generate Coupon'); ?>
                </form>
            </div>
            <div id="acv-list">
                <h2>Your Coupons</h2>
                <?php if (empty($coupons)): ?>
                    <p>No coupons yet. Create one above!</p>
                <?php else: foreach ($coupons as $id => $coupon): ?>
                    <div class="acv-coupon-item">
                        <strong><?php echo esc_html($coupon['code']); ?></strong> - <?php echo esc_html($coupon['desc']); ?><br>
                        Uses: <?php echo isset($stats['uses'][$id]) ? $stats['uses'][$id] : 0; ?> | Revenue: $<?php echo isset($stats['revenue'][$id]) ? $stats['revenue'][$id] : 0; ?><br>
                        Expires: <?php echo date('Y-m-d', $coupon['expires']); ?> <a href="#" onclick="deleteCoupon(<?php echo $id; ?>)" style="color:red;">Delete</a>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div id="acv-stats">
                <h2>Stats</h2>
                <p>Total Uses: <?php echo $stats['uses']['total'] ?? 0; ?></p>
                <p>Est. Revenue: $<?php echo $stats['revenue']['total'] ?? 0; ?></p>
                <p><em>Pro: Advanced analytics, A/B testing, API integrations.</em></p>
                <a href="https://example.com/pro" class="button button-primary" target="_blank">Upgrade to Pro</a>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#acv-list').on('click', '.delete', function() {
                if (confirm('Delete?')) location.href = '?page=acv-dashboard&delete=' + $(this).data('id');
            });
        });
        function deleteCoupon(id) {
            if (confirm('Delete coupon?')) {
                window.location = '?page=acv-dashboard&action=delete&id=' + id;
            }
        }
        </script>
        <?php
        $this->handle_admin_post();
    }

    public function handle_admin_post() {
        if (!isset($_POST['acv_nonce']) || !wp_verify_nonce($_POST['acv_nonce'], 'acv_add_coupon')) return;
        if (!current_user_can('manage_options')) return;

        $coupons = get_option('acv_coupons', array());
        $stats = get_option('acv_stats', array('uses' => array('total' => 0), 'revenue' => array('total' => 0)));

        $id = uniqid();
        $coupon = array(
            'aff_link' => sanitize_url($_POST['aff_link']),
            'code' => sanitize_text_field($_POST['code']),
            'discount' => intval($_POST['discount']),
            'desc' => sanitize_textarea_field($_POST['desc']),
            'expires' => time() + (intval($_POST['expires']) * DAY_IN_SECONDS)
        );
        $coupons[$id] = $coupon;
        update_option('acv_coupons', $coupons);

        wp_redirect(admin_url('admin.php?page=acv-dashboard&added=1'));
        exit;

        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            unset($coupons[$_GET['id']]);
            update_option('acv_coupons', $coupons);
            wp_redirect(admin_url('admin.php?page=acv-dashboard'));
            exit;
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('acv_coupons', array());
        if (empty($atts['id']) || !isset($coupons[$atts['id']])) return '';

        $coupon = $coupons[$atts['id']];
        if ($coupon['expires'] < time()) {
            $this->track_use($atts['id'], 0, 'expired');
            return '<div class="acv-expired">Coupon expired!</div>';
        }

        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <div class="acv-code"><?php echo esc_html($coupon['code']); ?></div>
            <div><?php echo $coupon['discount']; ?>% OFF</div>
            <a href="#" class="acv-redeem button">Redeem Now</a>
            <div class="acv-track">Tracking uses for revenue...</div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_use($id, $revenue = 0, $status = 'used') {
        $stats = get_option('acv_stats', array('uses' => array('total' => 0), 'revenue' => array('total' => 0)));
        $stats['uses'][$id] = ($stats['uses'][$id] ?? 0) + 1;
        $stats['uses']['total']++;
        if ($revenue > 0) {
            $stats['revenue'][$id] = ($stats['revenue'][$id] ?? 0) + $revenue;
            $stats['revenue']['total'] += $revenue;
        }
        update_option('acv_stats', $stats);
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

AffiliateCouponVault::get_instance();

// AJAX for tracking
function acv_ajax_track() {
    if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) wp_die();
    $id = sanitize_text_field($_POST['id']);
    $revenue = floatval($_POST['revenue'] ?? 0);
    AffiliateCouponVault::get_instance()->track_use($id, $revenue);
    wp_send_json_success();
}
add_action('wp_ajax_acv_track', 'acv_ajax_track');
add_action('wp_ajax_nopriv_acv_track', 'acv_ajax_track');

// Basic CSS
/* Add to plugin dir as style.css */
/* .acv-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
.acv-code { font-size: 2em; font-weight: bold; color: #0073aa; margin: 10px 0; }
.acv-redeem { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }
.acv-expired { border: 2px solid #dc3232; color: #dc3232; padding: 20px; text-align: center; } */

// Basic JS
/* Add to plugin dir as script.js */
/* jQuery(document).ready(function($) {
    $('.acv-redeem').click(function(e) {
        e.preventDefault();
        var $coupon = $(this).closest('.acv-coupon');
        var id = $coupon.data('id');
        $.post(ajaxurl, {
            action: 'acv_track',
            id: id,
            nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>'
        }, function() {
            window.location = $coupon.find('.acv-track').data('link') || '/';
        });
    });
}); */