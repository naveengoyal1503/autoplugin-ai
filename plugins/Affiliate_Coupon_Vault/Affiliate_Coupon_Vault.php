/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create and manage affiliate coupon vaults to drive conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('coupon_vault', array($this, 'coupon_vault_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_cpt();
    }

    private function create_cpt() {
        $args = array(
            'public' => true,
            'label'  => 'Coupons',
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart',
            'rewrite' => array('slug' => 'coupon'),
            'show_in_rest' => true,
        );
        register_post_type('coupon', $args);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('coupon-vault-css', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=coupon', 'Coupon Settings', 'Settings', 'manage_options', 'coupon-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['save'])) {
            update_option('acv_affiliate_id', sanitize_text_field($_POST['affiliate_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliate_id = get_option('acv_affiliate_id', '');
        ?>
        <div class="wrap">
            <h1>Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Tracking ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="save" class="button-primary" value="Save Settings" /></p>
            </form>
        </div>
        <?php
    }

    public function coupon_vault_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 10), $atts);
        $coupons = get_posts(array(
            'post_type' => 'coupon',
            'posts_per_page' => intval($atts['count']),
            'post_status' => 'publish',
        ));

        $output = '<div class="coupon-vault">';
        $affiliate_id = get_option('acv_affiliate_id', '');
        foreach ($coupons as $coupon) {
            $code = get_post_meta($coupon->ID, 'coupon_code', true);
            $link = get_post_meta($coupon->ID, 'affiliate_link', true);
            $expiry = get_post_meta($coupon->ID, 'expiry_date', true);
            $tracked_link = $link . (strpos($link, '?') ? '&' : '?') . 'ref=' . $affiliate_id;
            $output .= '<div class="coupon-item">';
            $output .= '<h3>' . esc_html($coupon->post_title) . '</h3>';
            $output .= '<p>' . apply_filters('the_content', $coupon->post_content) . '</p>';
            $output .= '<div class="coupon-code">Code: <strong>' . esc_html($code) . '</strong></div>';
            if ($expiry && strtotime($expiry) < current_time('timestamp')) {
                $output .= '<span class="expired">Expired</span>';
            } else {
                $output .= '<a href="' . esc_url($tracked_link) . '" class="coupon-btn" target="_blank">Get Deal</a>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        $this->create_cpt();
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Add meta boxes
function acv_add_meta_boxes() {
    add_meta_box('coupon_details', 'Coupon Details', 'acv_coupon_meta_box', 'coupon');
}
add_action('add_meta_boxes', 'acv_add_meta_boxes');

function acv_coupon_meta_box($post) {
    wp_nonce_field('acv_meta_box', 'acv_meta_nonce');
    $code = get_post_meta($post->ID, 'coupon_code', true);
    $link = get_post_meta($post->ID, 'affiliate_link', true);
    $expiry = get_post_meta($post->ID, 'expiry_date', true);
    echo '<p><label>Coupon Code: <input type="text" name="coupon_code" value="' . esc_attr($code) . '" style="width:100%;"></label></p>';
    echo '<p><label>Affiliate Link: <input type="url" name="affiliate_link" value="' . esc_attr($link) . '" style="width:100%;"></label></p>';
    echo '<p><label>Expiry Date: <input type="date" name="expiry_date" value="' . esc_attr($expiry) . '"></label></p>';
}

function acv_save_meta($post_id) {
    if (!isset($_POST['acv_meta_nonce']) || !wp_verify_nonce($_POST['acv_meta_nonce'], 'acv_meta_box')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, 'coupon_code', sanitize_text_field($_POST['coupon_code']));
    update_post_meta($post_id, 'affiliate_link', esc_url_raw($_POST['affiliate_link']));
    update_post_meta($post_id, 'expiry_date', sanitize_text_field($_POST['expiry_date']));
}
add_action('save_post', 'acv_save_meta');

// Basic CSS
/* Add to style.css or inline */
/*
.coupon-vault { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.coupon-item { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
.coupon-code { background: #f0f0f0; padding: 10px; margin: 10px 0; }
.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
.coupon-btn:hover { background: #005a87; }
.expired { color: red; font-weight: bold; }
*/