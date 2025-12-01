/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Description: Boost your affiliate revenue with automated product displays, income tracking, and optimized calls to action.
 * Version: 1.0.0
 * Author: PluginDev
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateBoosterPro {
    private $version = '1.0.0';
    private $plugin_slug = 'affiliate-booster-pro';

    public function __construct() {
        add_action('init', array($this, 'register_affiliate_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_abp_track_click', array($this, 'track_click_ajax'));
        add_action('wp_ajax_nopriv_abp_track_click', array($this, 'track_click_ajax'));
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_post_abp_save_settings', array($this, 'save_settings'));
    }

    public function enqueue_assets() {
        wp_enqueue_style('abp-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('abp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), $this->version, true);
        wp_localize_script('abp-script', 'abp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function register_affiliate_shortcode() {
        add_shortcode('affiliate_product', array($this, 'affiliate_product_shortcode'));
    }

    public function affiliate_product_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product_name' => 'Product',
            'product_url' => '#',
            'image_url' => '',
            'price' => '',
            'affiliate_id' => '',
        ), $atts, 'affiliate_product');

        $output = '<div class="abp-product">';
        if (!empty($atts['image_url'])) {
            $output .= '<img src="' . esc_url($atts['image_url']) . '" alt="' . esc_attr($atts['product_name']) . '" class="abp-product-image" />';
        }
        $output .= '<h3 class="abp-product-name">' . esc_html($atts['product_name']) . '</h3>';
        if (!empty($atts['price'])) {
            $output .= '<p class="abp-product-price">Price: ' . esc_html($atts['price']) . '</p>';
        }
        $tracking_data = '';
        if (!empty($atts['affiliate_id'])) {
            $tracking_data = ' data-affiliate-id="' . esc_attr($atts['affiliate_id']) . '"';
        }
        $output .= '<a href="' . esc_url($atts['product_url']) . '" class="abp-button" target="_blank" rel="nofollow noopener"' . $tracking_data . '>Buy Now</a>';
        $output .= '</div>';
        return $output;
    }

    public function track_click_ajax() {
        if (!isset($_POST['affiliate_id'])) {
            wp_send_json_error(array('message' => 'Affiliate ID missing'));
            wp_die();
        }
        $affiliate_id = sanitize_text_field($_POST['affiliate_id']);
        $clicks = (int) get_option('abp_clicks_' . $affiliate_id, 0);
        update_option('abp_clicks_' . $affiliate_id, $clicks + 1);
        wp_send_json_success(array('clicks' => $clicks + 1));
        wp_die();
    }

    public function register_admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', $this->plugin_slug, array($this, 'settings_page'), 'dashicons-chart-line');
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $clicks_data = '';
        $options = get_option_names();
        ob_start();
        ?>
        <div class="wrap">
            <h1>Affiliate Booster Pro - Click Stats</h1>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Affiliate ID</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
        <?php
        global $wpdb;
        $keys = $this->get_affiliate_keys();
        if (empty($keys)) {
            echo '<tr><td colspan="2">No click data recorded yet.</td></tr>';
        } else {
            foreach ($keys as $key) {
                $count = get_option($key, 0);
                $id = str_replace('abp_clicks_', '', $key);
                echo '<tr><td>' . esc_html($id) . '</td><td>' . intval($count) . '</td></tr>';
            }
        }
        ?>
                </tbody>
            </table>
            <p>Use the shortcode <code>[affiliate_product]</code> with attributes <code>product_name</code>, <code>product_url</code>, <code>image_url</code>, <code>price</code>, and <code>affiliate_id</code> to display affiliate products.</p>
        </div>
        <?php
        echo ob_get_clean();
    }

    private function get_affiliate_keys() {
        global $wpdb;
        $results = $wpdb->get_col("SELECT option_name FROM " . $wpdb->options . " WHERE option_name LIKE 'abp_clicks_%'");
        return $results;
    }

    public function save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Not allowed');
        }
        // Placeholder for future settings saving
        wp_redirect(admin_url('admin.php?page=' . $this->plugin_slug));
        exit;
    }
}

new AffiliateBoosterPro();

?>

/* style.css */
/* Minimal styling example, just to be self-contained */
/* Add in same file - normally would be separate, but single file required */

// Generated on loading might be better but insufficient for the prompt

// Instead, embed styles and scripts inline in wp_enqueue_scripts

add_action('wp_enqueue_scripts', function() {
    wp_add_inline_style('abp-style', ".abp-product { border: 1px solid #ccc; padding: 15px; margin: 10px 0; text-align: center; max-width: 300px; }
        .abp-product-image { max-width: 100%; height: auto; }
        .abp-product-name { font-size: 1.25em; margin: 0.5em 0; }
        .abp-product-price { color: #008000; font-weight: bold; }
        .abp-button { display: inline-block; background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .abp-button:hover { background: #005177; }");
});

add_action('wp_footer', function() {
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.abp-button').on('click', function(e) {
        var affiliateId = $(this).data('affiliate-id');
        if (affiliateId) {
            $.post(abp_ajax.ajax_url, { action: 'abp_track_click', affiliate_id: affiliateId });
        }
    });
});
</script>
<?php
});