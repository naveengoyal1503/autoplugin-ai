/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Generate exclusive affiliate coupons with expiration, usage limits, and analytics to maximize affiliate commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Exclusive_Coupons {
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
        $this->create_table();
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            affiliate_url text NOT NULL,
            description text,
            max_uses int DEFAULT 1,
            current_uses int DEFAULT 0,
            expires datetime DEFAULT NULL,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons',
            'manage_options',
            'wp-exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['add_coupon'])) {
            $this->add_coupon();
        }
        if (isset($_GET['delete'])) {
            $this->delete_coupon($_GET['delete']);
        }
        $this->list_coupons();
    }

    private function add_coupon() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $code = sanitize_text_field($_POST['code']);
        $url = esc_url_raw($_POST['url']);
        $desc = sanitize_textarea_field($_POST['description']);
        $max_uses = intval($_POST['max_uses']);
        $expires = !empty($_POST['expires']) ? sanitize_text_field($_POST['expires']) : null;

        $wpdb->insert(
            $table_name,
            array(
                'code' => $code,
                'affiliate_url' => $url,
                'description' => $desc,
                'max_uses' => $max_uses,
                'expires' => $expires
            )
        );
    }

    private function delete_coupon($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $wpdb->delete($table_name, array('id' => intval($id)), array('%d'));
    }

    private function list_coupons() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created DESC");
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="code" required placeholder="SAVE20" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate URL</th>
                        <td><input type="url" name="url" style="width: 400px;" required /></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="description" rows="3" style="width: 400px;"></textarea></td>
                    </tr>
                    <tr>
                        <th>Max Uses</th>
                        <td><input type="number" name="max_uses" value="1" min="1" /></td>
                    </tr>
                    <tr>
                        <th>Expires (YYYY-MM-DD HH:MM)</th>
                        <td><input type="datetime-local" name="expires" /></td>
                    </tr>
                </table>
                <?php submit_button('Add Coupon'); ?>
            </form>
            <h2>Active Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Uses / Max</th>
                        <th>Expires</th>
                        <th>Shortcode</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><?php echo esc_html($coupon->code); ?></td>
                        <td><?php echo esc_html($coupon->description); ?></td>
                        <td><?php echo intval($coupon->current_uses) . ' / ' . intval($coupon->max_uses); ?></td>
                        <td><?php echo $coupon->expires ? date('Y-m-d H:i', strtotime($coupon->expires)) : 'Never'; ?></td>
                        <td><code>[exclusive_coupon id="<?php echo $coupon->id; ?>"]</code></td>
                        <td><a href="?page=wp-exclusive-coupons&delete=<?php echo $coupon->id; ?>" onclick="return confirm('Delete?')">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-exclusive-coupons', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $id = intval($atts['id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$coupon) {
            return '<p>Coupon not found.</p>';
        }

        $expired = $coupon->expires && strtotime($coupon->expires) < current_time('timestamp');
        $maxed = $coupon->current_uses >= $coupon->max_uses;

        ob_start();
        ?>
        <div class="exclusive-coupon" id="coupon-<?php echo $coupon->id; ?>">
            <?php if ($expired || $maxed): ?>
                <p style="color: red;">Coupon expired or max uses reached! <a href="<?php echo esc_url($coupon->affiliate_url); ?>" target="_blank">Shop anyway</a></p>
            <?php else: ?>
                <h3>Exclusive Deal: <strong><?php echo esc_html($coupon->code); ?></strong></h3>
                <p><?php echo esc_html($coupon->description); ?></p>
                <a href="<?php echo esc_url(add_query_arg('coupon_code', $coupon->code, $coupon->affiliate_url)); ?>" class="coupon-button" target="_blank">Redeem Now (<?php echo $coupon->max_uses - $coupon->current_uses; ?> left)</a>
                <p class="coupon-uses">Used: <?php echo $coupon->current_uses; ?> / <?php echo $coupon->max_uses; ?></p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#coupon-<?php echo $coupon->id; ?> .coupon-button').click(function() {
                var btn = $(this);
                btn.text('Redeemed! Thanks!').addClass('redeemed');
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'track_coupon_use',
                    id: <?php echo $coupon->id; ?>
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

WP_Exclusive_Coupons::get_instance();

add_action('wp_ajax_track_coupon_use', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'coupon_nonce')) {
        wp_die();
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'exclusive_coupons';
    $id = intval($_POST['id']);
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET current_uses = current_uses + 1 WHERE id = %d", $id));
    wp_die();
});

// Premium upsell notice
function wp_exclusive_coupons_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>WP Exclusive Coupons Pro:</strong> Unlock unlimited coupons, analytics dashboard, custom branding, and email capture for <a href="https://example.com/premium" target="_blank">$49/year</a>.</p></div>';
}
add_action('admin_notices', 'wp_exclusive_coupons_notice');

// Minimal CSS
add_action('wp_head', function() {
    echo '<style>.exclusive-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }.coupon-button { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }.coupon-button.redeemed { background: #46b450; }.coupon-uses { font-size: 0.9em; color: #666; }</style>';
});