/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Manage affiliate coupons with tracking, custom codes, and expiration. Perfect for monetizing blogs.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_coupon_post_type();
    }

    public function create_coupon_post_type() {
        register_post_type('affiliate_coupon', array(
            'labels' => array(
                'name' => 'Affiliate Coupons',
                'singular_name' => 'Coupon'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=affiliate_coupon', 'Coupon Settings', 'Settings', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_tracking_id', sanitize_text_field($_POST['tracking_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $tracking_id = get_option('acv_tracking_id', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Tracking ID</th>
                        <td><input type="text" name="tracking_id" value="<?php echo esc_attr($tracking_id); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="acv_save" class="button-primary" value="Save Changes" /></p>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'category' => ''
        ), $atts);

        $args = array(
            'post_type' => 'affiliate_coupon',
            'posts_per_page' => $atts['count'],
            'post_status' => 'publish'
        );
        if ($atts['category']) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'coupon_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                )
            );
        }

        $coupons = get_posts($args);
        $output = '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $aff_link = get_post_meta($coupon->ID, 'affiliate_link', true);
            $code = get_post_meta($coupon->ID, 'coupon_code', true);
            $expires = get_post_meta($coupon->ID, 'expires', true);
            $tracking_id = get_option('acv_tracking_id', '');
            $tracked_link = $aff_link . (strpos($aff_link, '?') ? '&' : '?') . 'ref=' . $tracking_id;

            if ($expires && strtotime($expires) < current_time('timestamp')) {
                continue; // Skip expired
            }

            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . get_the_title($coupon->ID) . '</h3>';
            $output .= '<p>' . get_the_excerpt($coupon->ID) . '</p>';
            $output .= '<div class="coupon-code">Code: <strong>' . $code . '</strong></div>';
            $output .= '<a href="' . esc_url($tracked_link) . '" class="coupon-btn" target="_blank">Get Deal</a>';
            if ($expires) {
                $output .= '<small>Expires: ' . date('M j, Y', strtotime($expires)) . '</small>';
            }
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        $this->create_coupon_post_type();
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Add meta boxes
function acv_add_meta_boxes() {
    add_meta_box('acv_coupon_details', 'Coupon Details', 'acv_coupon_meta_box', 'affiliate_coupon');
}
add_action('add_meta_boxes', 'acv_add_meta_boxes');

function acv_coupon_meta_box($post) {
    wp_nonce_field('acv_meta_box', 'acv_meta_box_nonce');
    $link = get_post_meta($post->ID, 'affiliate_link', true);
    $code = get_post_meta($post->ID, 'coupon_code', true);
    $expires = get_post_meta($post->ID, 'expires', true);
    ?>
    <p>
        <label>Affiliate Link: <input type="url" name="affiliate_link" value="<?php echo esc_attr($link); ?>" style="width:100%;" /></label>
    </p>
    <p>
        <label>Coupon Code: <input type="text" name="coupon_code" value="<?php echo esc_attr($code); ?>" style="width:100%;" /></label>
    </p>
    <p>
        <label>Expires: <input type="date" name="expires" value="<?php echo esc_attr($expires); ?>" /></label>
    </p>
    <?php
}

function acv_save_meta($post_id) {
    if (!isset($_POST['acv_meta_box_nonce']) || !wp_verify_nonce($_POST['acv_meta_box_nonce'], 'acv_meta_box')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $fields = array('affiliate_link', 'coupon_code', 'expires');
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'acv_save_meta');

// Minimal CSS
$css = '.acv-coupons { display: grid; gap: 20px; } .acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; } .coupon-code { background: #f9f9f9; padding: 10px; margin: 10px 0; } .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; } .coupon-btn:hover { background: #005a87; }';
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $css);

// Minimal JS
$js = 'jQuery(document).ready(function($) { $(".coupon-btn").on("click", function() { $(this).text("Copied! Redirecting..."); }); });';
file_put_contents(plugin_dir_path(__FILE__) . 'script.js', $js);