/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Description: Manage and display exclusive coupons and deals for your audience.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register custom post type for coupons
class WPCouponVault {
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_box'));
        add_action('save_post', array($this, 'save_coupon_meta'));
        add_shortcode('coupon_vault', array($this, 'display_coupon_vault'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function register_coupon_post_type() {
        $args = array(
            'public' => true,
            'label'  => 'Coupons',
            'supports' => array('title', 'editor'),
            'has_archive' => true,
            'rewrite' => array('slug' => 'coupons'),
        );
        register_post_type('coupon', $args);
    }

    public function add_coupon_meta_box() {
        add_meta_box('coupon_details', 'Coupon Details', array($this, 'coupon_meta_box_callback'), 'coupon');
    }

    public function coupon_meta_box_callback($post) {
        wp_nonce_field('save_coupon_meta', 'coupon_nonce');
        $code = get_post_meta($post->ID, '_coupon_code', true);
        $expiry = get_post_meta($post->ID, '_coupon_expiry', true);
        $url = get_post_meta($post->ID, '_coupon_url', true);
        ?>
        <p>
            <label for="coupon_code">Coupon Code:</label>
            <input type="text" id="coupon_code" name="coupon_code" value="<?php echo esc_attr($code); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="coupon_expiry">Expiry Date:</label>
            <input type="date" id="coupon_expiry" name="coupon_expiry" value="<?php echo esc_attr($expiry); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="coupon_url">Affiliate URL:</label>
            <input type="url" id="coupon_url" name="coupon_url" value="<?php echo esc_attr($url); ?>" style="width: 100%;" />
        </p>
        <?php
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['coupon_nonce']) || !wp_verify_nonce($_POST['coupon_nonce'], 'save_coupon_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (isset($_POST['coupon_code'])) {
            update_post_meta($post_id, '_coupon_code', sanitize_text_field($_POST['coupon_code']));
        }
        if (isset($_POST['coupon_expiry'])) {
            update_post_meta($post_id, '_coupon_expiry', sanitize_text_field($_POST['coupon_expiry']));
        }
        if (isset($_POST['coupon_url'])) {
            update_post_meta($post_id, '_coupon_url', esc_url_raw($_POST['coupon_url']));
        }
    }

    public function display_coupon_vault($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts);
        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => $atts['limit'],
            'meta_query' => array(
                array(
                    'key' => '_coupon_expiry',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        );
        $coupons = new WP_Query($args);
        $output = '<div class="coupon-vault">';
        if ($coupons->have_posts()) {
            while ($coupons->have_posts()) {
                $coupons->the_post();
                $code = get_post_meta(get_the_ID(), '_coupon_code', true);
                $url = get_post_meta(get_the_ID(), '_coupon_url', true);
                $output .= '<div class="coupon-item">
                    <h3>' . get_the_title() . '</h3>
                    <p><strong>Code:</strong> ' . esc_html($code) . '</p>
                    <p><a href="' . esc_url($url) . '" target="_blank">Get Deal</a></p>
                </div>';
            }
        } else {
            $output .= '<p>No active coupons found.</p>';
        }
        $output .= '</div>';
        wp_reset_postdata();
        return $output;
    }

    public function add_admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'coupon-vault', array($this, 'admin_page'), 'dashicons-tickets-alt');
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>Coupon Vault</h1><p>Manage your coupons and deals here.</p></div>';
    }
}

new WPCouponVault();
