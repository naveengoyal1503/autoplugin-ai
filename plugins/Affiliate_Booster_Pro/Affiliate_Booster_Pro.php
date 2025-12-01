/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Description: Manage affiliate links, display smart coupons, and track clicks to increase affiliate revenue.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateBoosterPro {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('affiliate_booster_coupon', [$this, 'coupon_shortcode']);
        add_filter('the_content', [$this, 'replace_affiliate_links']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_abp_track_click', [$this, 'track_click']);
        add_action('wp_ajax_nopriv_abp_track_click', [$this, 'track_click']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'abp_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            url VARCHAR(255) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            ip VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('abp-main-js', plugin_dir_url(__FILE__).'abp-main.js', ['jquery'], '1.0', true);
        wp_localize_script('abp-main-js', 'abp_ajax_obj', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('abp_nonce')
        ]);
        wp_enqueue_style('abp-style', plugin_dir_url(__FILE__) . 'abp-style.css');
    }

    public function admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', 'aff-booster-pro', [$this, 'settings_page'], 'dashicons-external', 60);
    }

    public function register_settings() {
        register_setting('abp_settings_group', 'abp_affiliate_domains');
        register_setting('abp_settings_group', 'abp_coupons');
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="wrap">
            <h1>Affiliate Booster Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('abp_settings_group'); ?>
                <?php do_settings_sections('abp_settings_group'); ?>

                <h2>Affiliate Domains (comma separated)</h2>
                <input type="text" name="abp_affiliate_domains" value="<?php echo esc_attr(get_option('abp_affiliate_domains', '')); ?>" size="50" />

                <h2>Coupons (JSON Array)</h2>
                <p>Format: [{"code":"SAVE10","url":"https://example.com/product?aff=123","desc":"Save 10% on selected items"}, ...]</p>
                <textarea name="abp_coupons" rows="8" cols="80"><?php echo esc_textarea(get_option('abp_coupons', '[]')); ?></textarea>

                <p><input type="submit" class="button button-primary" value="Save Settings" /></p>
            </form>

            <h2>Analytics</h2>
            <?php $this->render_analytics(); ?>
        </div>
        <?php
    }

    private function render_analytics() {
        global $wpdb;
        $table = $wpdb->prefix . 'abp_clicks';
        $results = $wpdb->get_results("SELECT url, COUNT(*) as clicks FROM $table GROUP BY url ORDER BY clicks DESC LIMIT 10", ARRAY_A);

        if (!$results) {
            echo '<p>No clicks tracked yet.</p>';
            return;
        }
        echo '<table class="widefat fixed"><thead><tr><th>Affiliate URL</th><th>Clicks</th></tr></thead><tbody>';
        foreach ($results as $row) {
            echo '<tr><td>' . esc_html($row['url']) . '</td><td>' . intval($row['clicks']) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public function coupon_shortcode() {
        $coupons_json = get_option('abp_coupons', '[]');
        $coupons = json_decode($coupons_json, true);
        if (!$coupons || !is_array($coupons)) return '<p>No coupons available.</p>';

        $output = '<div class="abp-coupon-list">';
        foreach ($coupons as $coupon) {
            $code = esc_html($coupon['code'] ?? 'N/A');
            $url = esc_url($coupon['url'] ?? '#');
            $desc = esc_html($coupon['desc'] ?? '');
            $output .= "<div class='abp-coupon'><a href='$url' class='abp-aff-link' data-url='$url' target='_blank' rel='nofollow noopener'>$code</a><span class='abp-desc'> $desc</span></div>";
        }
        $output .= '</div>';
        return $output;
    }

    public function replace_affiliate_links($content) {
        $domains = get_option('abp_affiliate_domains', '');
        if (!$domains) return $content;
        $domains_arr = array_map('trim', explode(',', $domains));
        if (empty($domains_arr)) return $content;

        $pattern = '/<a\s+(?:[^>]*?\s+)?href=["\']([^"\']+)["\']/i';
        return preg_replace_callback($pattern, function ($matches) use ($domains_arr) {
            $url = $matches[1];
            $parsed = parse_url($url);
            if (!$parsed || empty($parsed['host'])) return $matches;

            foreach ($domains_arr as $domain) {
                if (stripos($parsed['host'], $domain) !== false) {
                    // Append tracking span and class
                    $esc_url = esc_url($url);
                    return str_replace($url, $esc_url, $matches) . " data-url='$esc_url' class='abp-aff-link' rel='nofollow noopener'";
                }
            }
            return $matches;
        }, $content);
    }

    public function track_click() {
        check_ajax_referer('abp_nonce', 'nonce');
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (!$url) wp_send_json_error('URL missing');

        global $wpdb;
        $table = $wpdb->prefix . 'abp_clicks';

        $wpdb->insert($table, [
            'url' => $url,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql')
        ]);

        wp_send_json_success('Click tracked');
    }
}

AffiliateBoosterPro::get_instance();

// JavaScript file output for minimal bundle
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($){
        $('body').on('click', 'a.abp-aff-link', function(e){
            var url = $(this).data('url') || $(this).attr('href');
            $.post(abp_ajax_obj.ajaxurl, {
                action: 'abp_track_click',
                url: url,
                nonce: abp_ajax_obj.nonce
            });
        });
    });
    </script>
    <style>
    .abp-coupon-list { display: flex; flex-wrap: wrap; gap: 10px; margin: 10px 0; }
    .abp-coupon { background: #f7f7f7; border: 1px solid #ddd; border-radius: 4px; padding: 8px 12px; }
    .abp-coupon a { font-weight: bold; color: #0073aa; text-decoration: none; }
    .abp-coupon a:hover { text-decoration: underline; }
    .abp-desc { margin-left: 8px; color: #555; font-size: 0.9em; }
    </style>
    <?php
});
