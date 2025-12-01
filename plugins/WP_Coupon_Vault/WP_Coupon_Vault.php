/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: WP Coupon Vault
 * Description: Manage and display exclusive coupons, track usage, and earn affiliate commissions.
 * Version: 1.0
 * Author: Cozmo Labs
 */

define('WP_COUPON_VAULT_VERSION', '1.0');

class WPCouponVault {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_box'));
        add_action('save_post', array($this, 'save_coupon_meta'));
        add_shortcode('coupon_vault', array($this, 'display_coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function register_post_type() {
        register_post_type('wpcv_coupon',
            array(
                'labels' => array(
                    'name' => __('Coupons', 'wp-coupon-vault'),
                    'singular_name' => __('Coupon', 'wp-coupon-vault')
                ),
                'public' => true,
                'has_archive' => true,
                'supports' => array('title', 'editor'),
                'menu_icon' => 'dashicons-tag'
            )
        );
    }

    public function add_coupon_meta_box() {
        add_meta_box(
            'wpcv_coupon_meta',
            'Coupon Details',
            array($this, 'render_coupon_meta_box'),
            'wpcv_coupon',
            'normal',
            'high'
        );
    }

    public function render_coupon_meta_box($post) {
        wp_nonce_field('wpcv_save_coupon_meta', 'wpcv_coupon_nonce');
        $code = get_post_meta($post->ID, '_wpcv_code', true);
        $expiry = get_post_meta($post->ID, '_wpcv_expiry', true);
        $affiliate_link = get_post_meta($post->ID, '_wpcv_affiliate_link', true);
        $brand = get_post_meta($post->ID, '_wpcv_brand', true);
        ?>
        <p>
            <label for="wpcv_code">Coupon Code:</label>
            <input type="text" name="wpcv_code" id="wpcv_code" value="<?php echo esc_attr($code); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="wpcv_expiry">Expiry Date:</label>
            <input type="date" name="wpcv_expiry" id="wpcv_expiry" value="<?php echo esc_attr($expiry); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="wpcv_affiliate_link">Affiliate Link:</label>
            <input type="url" name="wpcv_affiliate_link" id="wpcv_affiliate_link" value="<?php echo esc_attr($affiliate_link); ?>" style="width: 100%;" />
        </p>
        <p>
            <label for="wpcv_brand">Brand:</label>
            <input type="text" name="wpcv_brand" id="wpcv_brand" value="<?php echo esc_attr($brand); ?>" style="width: 100%;" />
        </p>
        <?php
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['wpcv_coupon_nonce']) || !wp_verify_nonce($_POST['wpcv_coupon_nonce'], 'wpcv_save_coupon_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array('wpcv_code', 'wpcv_expiry', 'wpcv_affiliate_link', 'wpcv_brand');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_wpcv_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function display_coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts, 'coupon_vault');
        $args = array(
            'post_type' => 'wpcv_coupon',
            'posts_per_page' => $atts['limit'],
            'meta_query' => array(
                array(
                    'key' => '_wpcv_expiry',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                )
            )
        );
        $coupons = new WP_Query($args);
        ob_start();
        if ($coupons->have_posts()) :
            echo '<div class="wpcv-coupon-list">';
            while ($coupons->have_posts()) : $coupons->the_post();
                $code = get_post_meta(get_the_ID(), '_wpcv_code', true);
                $expiry = get_post_meta(get_the_ID(), '_wpcv_expiry', true);
                $affiliate_link = get_post_meta(get_the_ID(), '_wpcv_affiliate_link', true);
                $brand = get_post_meta(get_the_ID(), '_wpcv_brand', true);
                ?>
                <div class="wpcv-coupon">
                    <h3><?php echo esc_html($brand); ?></h3>
                    <p><strong>Code:</strong> <span class="wpcv-code"><?php echo esc_html($code); ?></span></p>
                    <p><strong>Expires:</strong> <?php echo esc_html($expiry); ?></p>
                    <a href="<?php echo esc_url($affiliate_link); ?>" target="_blank" class="wpcv-claim-btn">Claim Deal</a>
                </div>
                <?php
            endwhile;
            echo '</div>';
        else :
            echo '<p>No active coupons found.</p>';
        endif;
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function enqueue_styles() {
        wp_enqueue_style('wpcv-style', plugin_dir_url(__FILE__) . 'style.css');
    }
}

new WPCouponVault();
?>