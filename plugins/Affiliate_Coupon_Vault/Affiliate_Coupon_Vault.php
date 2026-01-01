/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Generate exclusive affiliate coupons with tracking, auto-expiration, and Gutenberg blocks for easy display.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('init', array($this, 'register_post_type'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_assets'));
        add_action('init', array($this, 'register_block_type'));
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        wp_register_script('acv-block', plugin_dir_url(__FILE__) . 'block.js', array('wp-blocks', 'wp-element', 'wp-editor'), '1.0.0', true);
        wp_register_style('acv-block', plugin_dir_url(__FILE__) . 'block.css', array('wp-edit-blocks'), '1.0.0');
    }

    public function enqueue_scripts() {
        if (has_block('acv/coupon-display')) {
            wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'frontend.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'frontend.css', array(), '1.0.0');
        }
    }

    public function register_post_type() {
        register_post_type('acv_coupon', array(
            'labels' => array(
                'name' => 'Coupons',
                'singular_name' => 'Coupon'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart',
            'show_in_rest' => true
        ));
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=acv_coupon', 'Coupon Settings', 'Settings', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_tracking_id', sanitize_text_field($_POST['tracking_id']));
            echo '<div class="notice notice-success"><p>Saved!</p></div>';
        }
        $tracking_id = get_option('acv_tracking_id', '');
        ?>
        <div class="wrap">
            <h1>Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Global Tracking ID</th>
                        <td><input type="text" name="tracking_id" value="<?php echo esc_attr($tracking_id); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="acv_save" class="button-primary" value="Save Changes" /></p>
            </form>
        </div>
        <?php
    }

    public function enqueue_block_assets() {
        wp_enqueue_script(
            'acv-block',
            plugin_dir_url(__FILE__) . 'block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-data'),
            '1.0.0',
            true
        );
    }

    public function register_block_type() {
        register_block_type('acv/coupon-display', array(
            'editor_script' => 'acv-block',
            'style' => 'acv-block',
            'render_callback' => array($this, 'render_coupon_block'),
            'attributes' => array(
                'couponId' => array(
                    'type' => 'string',
                    'default' => ''
                )
            )
        ));
    }

    public function render_coupon_block($attributes) {
        if (empty($attributes['couponId'])) return '';
        $post = get_post($attributes['couponId']);
        if (!$post || $post->post_type !== 'acv_coupon') return '';

        $aff_link = get_post_meta($post->ID, 'aff_link', true);
        $code = get_post_meta($post->ID, 'coupon_code', true);
        $expiry = get_post_meta($post->ID, 'expiry_date', true);
        $tracking_id = get_post_meta($post->ID, 'tracking_id', true) ?: get_option('acv_tracking_id', '');

        $link = $tracking_id ? $aff_link . '?ref=' . $tracking_id : $aff_link;
        $expired = $expiry && strtotime($expiry) < current_time('timestamp');

        ob_start();
        ?>
        <div class="acv-coupon <?php echo $expired ? 'expired' : ''; ?>">
            <h3><?php echo esc_html($post->post_title); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($code); ?></code></p>
            <?php if ($expiry): ?>
            <p><strong>Expires:</strong> <?php echo esc_html($expiry); ?></p>
            <?php endif; ?>
            <?php if (!$expired): ?>
            <a href="<?php echo esc_url($link); ?>" class="button acv-btn" target="_blank">Redeem Now (<?php echo $tracking_id ? 'Tracked' : 'Direct'; ?>)</a>
            <?php else: ?>
            <p class="expired">Coupon Expired</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Add meta boxes
function acv_add_meta_boxes() {
    add_meta_box('acv_coupon_details', 'Coupon Details', 'acv_coupon_meta_box_callback', 'acv_coupon', 'normal', 'high');
}
add_action('add_meta_boxes', 'acv_add_meta_boxes');

function acv_coupon_meta_box_callback($post) {
    wp_nonce_field('acv_meta_nonce', 'acv_nonce');
    $aff_link = get_post_meta($post->ID, 'aff_link', true);
    $code = get_post_meta($post->ID, 'coupon_code', true);
    $expiry = get_post_meta($post->ID, 'expiry_date', true);
    $tracking_id = get_post_meta($post->ID, 'tracking_id', true);
    ?>
    <p>
        <label><strong>Affiliate Link:</strong></label><br>
        <input type="url" name="aff_link" value="<?php echo esc_attr($aff_link); ?>" style="width:100%;" />
    </p>
    <p>
        <label><strong>Coupon Code:</strong></label><br>
        <input type="text" name="coupon_code" value="<?php echo esc_attr($code); ?>" style="width:100%;" />
    </p>
    <p>
        <label><strong>Expiry Date (YYYY-MM-DD):</strong></label><br>
        <input type="date" name="expiry_date" value="<?php echo esc_attr($expiry); ?>" style="width:100%;" />
    </p>
    <p>
        <label><strong>Tracking ID:</strong></label><br>
        <input type="text" name="tracking_id" value="<?php echo esc_attr($tracking_id); ?>" style="width:100%;" placeholder="Uses global if empty" />
    </p>
    <?php
}

function acv_save_meta($post_id) {
    if (!isset($_POST['acv_nonce']) || !wp_verify_nonce($_POST['acv_nonce'], 'acv_meta_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'aff_link', sanitize_url($_POST['aff_link'] ?? ''));
    update_post_meta($post_id, 'coupon_code', sanitize_text_field($_POST['coupon_code'] ?? ''));
    update_post_meta($post_id, 'expiry_date', sanitize_text_field($_POST['expiry_date'] ?? ''));
    update_post_meta($post_id, 'tracking_id', sanitize_text_field($_POST['tracking_id'] ?? ''));
}
add_action('save_post', 'acv_save_meta');

// Frontend styles
/*
.acv-coupon { border: 2px solid #0073aa; padding: 20px; border-radius: 8px; background: #f9f9f9; }
.acv-coupon h3 { color: #0073aa; }
.acv-coupon code { background: #eee; padding: 5px 10px; border-radius: 4px; }
.acv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
.acv-btn:hover { background: #005a87; }
.acv-coupon.expired { opacity: 0.6; border-color: #d63638; }
.acv-coupon.expired .acv-btn { display: none; }
.acv-coupon.expired::after { content: 'EXPIRED'; color: #d63638; font-weight: bold; }
*/

// Note: Add block.js, frontend.js, block.css, frontend.css as empty files or implement basic JS for block editor.
// For single-file, styles are commented above. Pro version would add click tracking via AJAX.

AffiliateCouponVault::get_instance();
