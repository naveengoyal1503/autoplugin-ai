/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Manage affiliate coupons with tracking, custom codes, and shortcodes for easy display.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_cpt();
    }

    public function create_cpt() {
        $args = array(
            'public' => true,
            'label'  => 'Coupons',
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart',
            'rewrite' => array('slug' => 'coupons'),
            'show_in_rest' => true,
        );
        register_post_type('coupon', $args);
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=coupon', 'Coupon Settings', 'Settings', 'manage_options', 'coupon-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['save'])) {
            update_option('acv_tracking_id', sanitize_text_field($_POST['tracking_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $tracking_id = get_option('acv_tracking_id', '');
        ?>
        <div class="wrap">
            <h1>Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Global Tracking ID</th>
                        <td><input type="text" name="tracking_id" value="<?php echo esc_attr($tracking_id); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="save" class="button-primary" value="Save Changes" /></p>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => 10,
        ), $atts);

        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish',
        );
        if ($atts['category']) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'coupon_category',
                    'field'    => 'slug',
                    'terms'    => $atts['category'],
                ),
            );
        }

        $query = new WP_Query($args);
        if (!$query->have_posts()) {
            return '<p>No coupons available.</p>';
        }

        $output = '<div class="acv-coupons">';
        while ($query->have_posts()) {
            $query->the_post();
            $aff_link = get_post_meta(get_the_ID(), 'affiliate_link', true);
            $code = get_post_meta(get_the_ID(), 'coupon_code', true);
            $expires = get_post_meta(get_the_ID(), 'expires', true);
            $tracking_id = get_post_meta(get_the_ID(), 'tracking_id', true) ?: get_option('acv_tracking_id', '');
            $tracked_link = $tracking_id ? $aff_link . '?ref=' . $tracking_id : $aff_link;

            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . get_the_title() . '</h3>';
            $output .= '<p>' . get_the_excerpt() . '</p>';
            if ($code) {
                $output .= '<div class="coupon-code">Code: <strong>' . esc_html($code) . '</strong></div>';
            }
            if ($expires && strtotime($expires) > current_time('timestamp')) {
                $output .= '<div class="expires">Expires: ' . date('M j, Y', strtotime($expires)) . '</div>';
            } else {
                $output .= '<div class="expired">Expired</div>';
            }
            $output .= '<a href="' . esc_url($tracked_link) . '" class="coupon-btn" target="_blank">Get Deal</a>';
            $output .= '</div>';
        }
        wp_reset_postdata();
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        $this->create_cpt();
        flush_rewrite_rules();
        // Add custom fields
        add_meta_box('coupon_meta', 'Coupon Details', function($post) {
            wp_nonce_field('coupon_meta', 'coupon_meta_nonce');
            $link = get_post_meta($post->ID, 'affiliate_link', true);
            $code = get_post_meta($post->ID, 'coupon_code', true);
            $expires = get_post_meta($post->ID, 'expires', true);
            $track = get_post_meta($post->ID, 'tracking_id', true);
            echo '<p><label>Affiliate Link:</label><br><input type="url" name="aff_link" value="' . esc_attr($link) . '" style="width:100%;"></p>';
            echo '<p><label>Coupon Code:</label><br><input type="text" name="coupon_code" value="' . esc_attr($code) . '" style="width:100%;"></p>';
            echo '<p><label>Expires:</label><br><input type="date" name="expires" value="' . esc_attr($expires) . '"></p>';
            echo '<p><label>Tracking ID:</label><br><input type="text" name="tracking_id" value="' . esc_attr($track) . '" style="width:100%;"></p>';
        }, 'coupon', 'normal');
        add_action('save_post', function($post_id) {
            if (!isset($_POST['coupon_meta_nonce']) || !wp_verify_nonce($_POST['coupon_meta_nonce'], 'coupon_meta')) return;
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if (!current_user_can('edit_post', $post_id)) return;

            update_post_meta($post_id, 'affiliate_link', sanitize_url($_POST['aff_link']));
            update_post_meta($post_id, 'coupon_code', sanitize_text_field($_POST['coupon_code']));
            update_post_meta($post_id, 'expires', sanitize_text_field($_POST['expires']));
            update_post_meta($post_id, 'tracking_id', sanitize_text_field($_POST['tracking_id']));
        });
    }
}

new AffiliateCouponVault();

// Basic CSS
/* Add to style.css or enqueue */
/*
.acv-coupons { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
.coupon-code { background: #f0f0f0; padding: 10px; font-size: 1.2em; }
.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.coupon-btn:hover { background: #005a87; }
.expired { color: red; font-weight: bold; }
*/
?>