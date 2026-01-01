/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function activate() {
        add_option('ecp_api_key', '');
        add_option('ecp_coupons', array());
    }

    public function admin_menu() {
        add_menu_page(
            __('Exclusive Coupons', 'exclusive-coupons-pro'),
            __('Coupons Pro', 'exclusive-coupons-pro'),
            'manage_options',
            'exclusive-coupons-pro',
            array($this, 'admin_page'),
            'dashicons-coupon',
            30
        );
        add_submenu_page(
            'exclusive-coupons-pro',
            __('Analytics', 'exclusive-coupons-pro'),
            __('Analytics', 'exclusive-coupons-pro'),
            'manage_options',
            'ecp-analytics',
            array($this, 'analytics_page')
        );
    }

    public function admin_init() {
        if (isset($_POST['ecp_save_coupon'])) {
            $coupons = get_option('ecp_coupons', array());
            $id = sanitize_text_field($_POST['coupon_id']);
            $coupons[$id] = array(
                'code' => sanitize_text_field($_POST['coupon_code']),
                'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
                'description' => sanitize_textarea_field($_POST['description']),
                'expires' => sanitize_text_field($_POST['expires']),
                'uses' => intval($_POST['uses']),
                'max_uses' => intval($_POST['max_uses'])
            );
            update_option('ecp_coupons', $coupons);
        }
        if (isset($_POST['ecp_delete_coupon'])) {
            $coupons = get_option('ecp_coupons', array());
            $id = sanitize_text_field($_POST['coupon_id']);
            unset($coupons[$id]);
            update_option('ecp_coupons', $coupons);
        }
    }

    public function admin_page() {
        $coupons = get_option('ecp_coupons', array());
        $id = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : uniqid();
        $coupon = isset($coupons[$id]) ? $coupons[$id] : array();
        ?>
        <div class="wrap">
            <h1><?php _e('Manage Exclusive Coupons', 'exclusive-coupons-pro'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Coupon ID', 'exclusive-coupons-pro'); ?></th>
                        <td><input type="text" name="coupon_id" value="<?php echo esc_attr($id); ?>" readonly /></td>
                    </tr>
                    <tr>
                        <th><?php _e('Coupon Code', 'exclusive-coupons-pro'); ?></th>
                        <td><input type="text" name="coupon_code" value="<?php echo esc_attr($coupon['code'] ?? ''); ?>" required /></td>
                    </tr>
                    <tr>
                        <th><?php _e('Affiliate Link', 'exclusive-coupons-pro'); ?></th>
                        <td><input type="url" name="affiliate_link" value="<?php echo esc_attr($coupon['affiliate_link'] ?? ''); ?>" required /></td>
                    </tr>
                    <tr>
                        <th><?php _e('Description', 'exclusive-coupons-pro'); ?></th>
                        <td><textarea name="description" rows="4" cols="50"><?php echo esc_textarea($coupon['description'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><?php _e('Expires (YYYY-MM-DD)', 'exclusive-coupons-pro'); ?></th>
                        <td><input type="date" name="expires" value="<?php echo esc_attr($coupon['expires'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php _e('Current Uses', 'exclusive-coupons-pro'); ?></th>
                        <td><input type="number" name="uses" value="<?php echo intval($coupon['uses'] ?? 0); ?>" readonly /></td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Uses', 'exclusive-coupons-pro'); ?></th>
                        <td><input type="number" name="max_uses" value="<?php echo intval($coupon['max_uses'] ?? 0); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(__('Save Coupon', 'exclusive-coupons-pro'), 'primary', 'ecp_save_coupon'); ?>
            </form>
            <h2><?php _e('Existing Coupons', 'exclusive-coupons-pro'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Link</th>
                        <th>Expires</th>
                        <th>Uses</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $cid => $c): ?>
                    <tr>
                        <td><?php echo esc_html($cid); ?></td>
                        <td><?php echo esc_html($c['code']); ?></td>
                        <td><?php echo esc_html($c['affiliate_link']); ?></td>
                        <td><?php echo esc_html($c['expires']); ?></td>
                        <td><?php echo intval($c['uses']); ?>/<?php echo intval($c['max_uses']); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=exclusive-coupons-pro&edit=' . $cid); ?>">Edit</a> |
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="coupon_id" value="<?php echo esc_attr($cid); ?>">
                                <?php submit_button(__('Delete', 'exclusive-coupons-pro'), 'delete', 'ecp_delete_coupon', false, array('style' => 'width:auto;')); ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function analytics_page() {
        global $wpdb;
        $stats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ecp_clicks ORDER BY date DESC LIMIT 50");
        ?>
        <div class="wrap">
            <h1><?php _e('Coupon Analytics', 'exclusive-coupons-pro'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Coupon ID</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $stat): ?>
                    <tr>
                        <td><?php echo esc_html($stat->date); ?></td>
                        <td><?php echo esc_html($stat->coupon_id); ?></td>
                        <td><?php echo esc_html($stat->ip); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('ecp_coupons', array());
        $id = sanitize_text_field($atts['id']);
        if (!isset($coupons[$id])) {
            return '<p>Coupon not found.</p>';
        }
        $coupon = $coupons[$id];
        $expired = !empty($coupon['expires']) && strtotime($coupon['expires']) < current_time('timestamp');
        $maxed = $coupon['max_uses'] > 0 && ($coupon['uses'] ?? 0) >= $coupon['max_uses'];
        if ($expired || $maxed) {
            return '<p class="ecp-expired">Coupon expired or max uses reached.</p>';
        }
        ob_start();
        ?>
        <div class="ecp-coupon" data-id="<?php echo esc_attr($id); ?>">
            <h3><?php echo esc_html($coupon['code']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <a href="#" class="ecp-use-coupon button">Use Coupon</a>
        </div>
        <?php
        return ob_get_clean();
    }
}

ExclusiveCouponsPro::get_instance();

// Track clicks
function ecp_track_click() {
    if (!isset($_POST['ecp_coupon_id'])) {
        wp_die();
    }
    $id = sanitize_text_field($_POST['ecp_coupon_id']);
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'ecp_clicks',
        array(
            'coupon_id' => $id,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'date' => current_time('mysql')
        ),
        array('%s', '%s', '%s')
    );
    $coupons = get_option('ecp_coupons', array());
    if (isset($coupons[$id])) {
        $coupons[$id]['uses'] = intval($coupons[$id]['uses'] ?? 0) + 1;
        update_option('ecp_coupons', $coupons);
    }
    $coupon = $coupons[$id];
    wp_redirect($coupon['affiliate_link']);
    exit;
}
add_action('wp_ajax_ecp_track', 'ecp_track_click');
add_action('wp_ajax_nopriv_ecp_track', 'ecp_track_click');

// Create table on activation
global $ecp_db_version;
$ecp_db_version = '1.0';
function ecp_install() {
    global $wpdb;
    global $ecp_db_version;
    $table_name = $wpdb->prefix . 'ecp_clicks';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        coupon_id varchar(50) NOT NULL,
        ip varchar(45) NOT NULL,
        date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    add_option('ecp_db_version', $ecp_db_version);
}
register_activation_hook(__FILE__, 'ecp_install');

// Pro upsell notice
function ecp_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Unlock unlimited coupons, advanced analytics, and integrations. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
}
add_action('admin_notices', 'ecp_pro_notice');

?>