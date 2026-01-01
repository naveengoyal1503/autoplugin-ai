/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate, manage, and track exclusive affiliate coupons to increase conversions and monetize your site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_save_coupon', array($this, 'save_coupon'));
        }
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'ecp.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page('Exclusive Coupons', 'Coupons Pro', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_coupon'])) {
            $this->save_coupon();
        }
        $coupons = $this->get_coupons();
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            code varchar(50) NOT NULL,
            affiliate_url text NOT NULL,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            expiry date NULL,
            created timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function get_coupons() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "exclusive_coupons");
    }

    public function save_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'exclusive_coupons';
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'code' => strtoupper(sanitize_text_field($_POST['code'])),
            'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
            'max_uses' => intval($_POST['max_uses']),
            'expiry' => !empty($_POST['expiry']) ? $_POST['expiry'] : null
        );
        $wpdb->insert($table, $data);
        wp_redirect(admin_url('admin.php?page=exclusive-coupons'));
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "exclusive_coupons WHERE id = %d", $atts['id']));
        if (!$coupon) return '';

        $used = $coupon->uses;
        $max = $coupon->max_uses;
        $expired = $coupon->expiry && strtotime($coupon->expiry) < current_time('timestamp');

        ob_start();
        ?>
        <div class="ecp-coupon" data-id="<?php echo $coupon->id; ?>">
            <h3><?php echo esc_html($coupon->name); ?></h3>
            <div class="ecp-code"><?php echo esc_html($coupon->code); ?></div>
            <?php if ($max > 0 && $used >= $max) : ?>
                <p class="ecp-exhausted">Coupon uses exhausted!</p>
            <?php elseif ($expired) : ?>
                <p class="ecp-expired">Coupon expired!</p>
            <?php else : ?>
                <p>Uses left: <?php echo $max > 0 ? $max - $used : 'Unlimited'; ?></p>
                <a href="<?php echo esc_url(add_query_arg('coupon', $coupon->code, $coupon->affiliate_url)); ?>" class="ecp-button" target="_blank">Redeem Now</a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        $this->create_table();
    }
}

new ExclusiveCouponsPro();

// Inline JS and CSS for self-contained plugin
add_action('wp_head', 'ecp_inline_scripts');
function ecp_inline_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.ecp-coupon .ecp-button').on('click', function() {
            var id = $(this).closest('.ecp-coupon').data('id');
            $.post(ajaxurl, {action: 'track_coupon_use', id: id});
        });
    });
    </script>
    <style>
    .ecp-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; border-radius: 8px; background: #f9f9f9; }
    .ecp-code { font-size: 24px; font-weight: bold; color: #0073aa; margin: 10px 0; }
    .ecp-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    .ecp-button:hover { background: #005a87; }
    .ecp-exhausted, .ecp-expired { color: red; font-weight: bold; }
    </style>
    <?php
}

add_action('wp_ajax_track_coupon_use', 'track_coupon_use');
function track_coupon_use() {
    global $wpdb;
    $id = intval($_POST['id']);
    $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "exclusive_coupons SET uses = uses + 1 WHERE id = %d", $id));
    wp_die();
}

// Admin page template
add_action('admin_footer', 'ecp_admin_template');
function ecp_admin_template() {
    if (isset($_GET['page']) && $_GET['page'] === 'exclusive-coupons') {
        ?>
        <div id="ecp-admin-template" style="display:none;">
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_coupon">
                <?php wp_nonce_field('save_coupon'); ?>
                <table class="form-table">
                    <tr><th>Name</th><td><input type="text" name="name" required></td></tr>
                    <tr><th>Code</th><td><input type="text" name="code" required></td></tr>
                    <tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:100%;" required></td></tr>
                    <tr><th>Max Uses</th><td><input type="number" name="max_uses" value="0"></td></tr>
                    <tr><th>Expiry</th><td><input type="date" name="expiry"></td></tr>
                </table>
                <p><input type="submit" value="Add Coupon" class="button-primary"></p>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#ecp-admin-template form').on('submit', function(e) {
                // Auto-generate code if empty
                if (!$('input[name="code"]').val()) {
                    $('input[name="code"]').val(Math.random().toString(36).substr(2,8).toUpperCase());
                }
            });
        });
        </script>
        <?php
    }
}

// Display coupons in admin
add_action('admin_notices', 'ecp_admin_page_content');
function ecp_admin_page_content() {
    if (isset($_GET['page']) && $_GET['page'] === 'exclusive-coupons') {
        $plugin = new ExclusiveCouponsPro();
        $coupons = $plugin->get_coupons();
        echo '<div class="wrap"><h1>Exclusive Coupons Pro</h1>';
        echo '<div id="ecp-admin-template"></div>'; // Trigger template
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Name</th><th>Code</th><th>Uses</th><th>Max Uses</th><th>Expiry</th></tr></thead><tbody>';
        foreach ($coupons as $c) {
            echo '<tr><td>' . $c->id . '</td><td>' . esc_html($c->name) . '</td><td>' . esc_html($c->code) . '</td><td>' . $c->uses . '</td><td>' . $c->max_uses . '</td><td>' . ($c->expiry ?: 'No') . '</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
