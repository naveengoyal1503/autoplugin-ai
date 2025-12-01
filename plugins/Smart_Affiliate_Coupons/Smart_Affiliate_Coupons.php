<?php
/*
Plugin Name: Smart Affiliate Coupons
Description: Automatically fetches and displays personalized affiliate coupons and deals to increase conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartAffiliateCoupons {
    private $coupons = [];

    public function __construct() {
        add_shortcode('smart_aff_coupons', [$this, 'render_coupons']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        // Schedule hourly fetch if not scheduled
        if (!wp_next_scheduled('sac_fetch_coupons_hook')) {
            wp_schedule_event(time(), 'hourly', 'sac_fetch_coupons_hook');
        }
        add_action('sac_fetch_coupons_hook', [$this, 'fetch_coupons']);
    }

    public function enqueue_styles() {
        wp_enqueue_style('sac-style', plugins_url('style.css', __FILE__));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'Smart Affiliate Coupons', 'manage_options', 'smart-affiliate-coupons', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('sac_options_group', 'sac_affiliate_id');
        register_setting('sac_options_group', 'sac_feed_url');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sac_options_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate ID</th>
                        <td><input type="text" name="sac_affiliate_id" value="<?php echo esc_attr(get_option('sac_affiliate_id')); ?>" placeholder="Your Affiliate ID" style="width:300px;"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Coupon Feed URL</th>
                        <td><input type="url" name="sac_feed_url" value="<?php echo esc_attr(get_option('sac_feed_url')); ?>" placeholder="https://example.com/coupons-feed.json" style="width:100%; max-width:600px;"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use the shortcode <code>[smart_aff_coupons]</code> to display coupons on any post or page.</p>
        </div>
        <?php
    }

    public function fetch_coupons() {
        $feed_url = get_option('sac_feed_url');
        if (!$feed_url) return;
        $response = wp_remote_get($feed_url, ['timeout'=>10]);

        if (is_wp_error($response)) return;
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($data['coupons'])) return;

        // Filter and store coupons transient, attach affiliate ID
        $coupons = [];
        $affiliate_id = get_option('sac_affiliate_id');
        foreach ($data['coupons'] as $coupon) {
            if (!empty($coupon['code']) && !empty($coupon['link'])) {
                $link = $coupon['link'];
                if ($affiliate_id && strpos($link, $affiliate_id) === false) {
                    $link = add_query_arg('aff_id', $affiliate_id, $link);
                }
                $coupons[] = [
                    'title' => sanitize_text_field($coupon['title'] ?? 'Deal'),
                    'code' => sanitize_text_field($coupon['code']),
                    'link' => esc_url_raw($link),
                    'description' => sanitize_text_field($coupon['description'] ?? ''),
                    'expiry' => sanitize_text_field($coupon['expiry'] ?? '')
                ];
            }
        }

        set_transient('sac_coupons_cached', $coupons, HOUR_IN_SECONDS);
    }

    public function render_coupons() {
        $coupons = get_transient('sac_coupons_cached');
        if (!$coupons) {
            $this->fetch_coupons();
            $coupons = get_transient('sac_coupons_cached');
        }

        if (empty($coupons)) return '<p>No coupons available at the moment.</p>';

        $output = '<div class="sac-coupons-list">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="sac-coupon">';
            $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
            if ($coupon['description']) {
                $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            }
            $output .= '<p><strong>Code: </strong><code>' . esc_html($coupon['code']) . '</code></p>';
            if ($coupon['expiry']) {
                $output .= '<p><em>Expires: ' . esc_html($coupon['expiry']) . '</em></p>';
            }
            $output .= '<p><a href="' . esc_url($coupon['link']) . '" target="_blank" rel="nofollow noopener" class="sac-btn">Use Coupon</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }
}

new SmartAffiliateCoupons();

// Basic style for coupons
add_action('wp_head', function(){
    echo '<style>.sac-coupons-list {display:flex; flex-wrap: wrap; gap: 20px;}
    .sac-coupon {border:1px solid #ddd; background:#f9f9f9; padding:15px; border-radius:6px; max-width:300px; flex:1 1 30%;}
    .sac-coupon h3 {margin-top:0; font-size:1.1em;}
    .sac-btn {display:inline-block; background:#0073aa; color:#fff; padding:8px 12px; border-radius:3px; text-decoration:none;}
    .sac-btn:hover {background:#005177;}</style>';
});