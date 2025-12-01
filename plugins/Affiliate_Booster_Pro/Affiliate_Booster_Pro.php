/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Description: Turn your visitors into revenue drivers with affiliate link tracking, payouts, and gamification.
 * Version: 1.0
 * Author: YourName
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateBoosterPro {
    private static $instance = null;
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'track_affiliate_click'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_abp_process_payout', array($this, 'process_payout'));
        add_action('wp_ajax_abp_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_nopriv_abp_get_stats', array($this, 'ajax_get_stats'));
        register_activation_hook(__FILE__, array($this, 'activation'));
    }

    public function activation() {
        global $wpdb;
        $table = $wpdb->prefix . 'abp_affiliates';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NULL,
            affiliate_code VARCHAR(32) NOT NULL UNIQUE,
            clicks BIGINT UNSIGNED DEFAULT 0,
            conversions BIGINT UNSIGNED DEFAULT 0,
            commission DECIMAL(10,2) DEFAULT 0.00
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('abp-main-js', plugin_dir_url(__FILE__) . 'abp-script.js', array('jquery'), '1.0', true);
        wp_localize_script('abp-main-js', 'abpAjax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function register_shortcodes() {
        add_shortcode('affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_shortcode('affiliate_stats', array($this, 'affiliate_stats_shortcode'));
    }

    // Generates an affiliate link with ?ref=affiliate_code
    public function affiliate_link_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        $code = sanitize_text_field($atts['code']);
        if (empty($code)) return '';
        $url = home_url('/?ref=' . rawurlencode($code));
        $text = $content ?: 'Visit';
        return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noreferrer">' . esc_html($text) . '</a>';
    }

    public function affiliate_stats_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to view your affiliate stats.</p>';
        }
        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'abp_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
        if (!$affiliate) {
            return '<p>You do not have an affiliate account.</p>';
        }

        ob_start();
        ?>
        <div id="abp-affiliate-stats">
            <p>Clicks: <span id="abp-clicks"><?php echo intval($affiliate->clicks); ?></span></p>
            <p>Conversions: <span id="abp-conversions"><?php echo intval($affiliate->conversions); ?></span></p>
            <p>Commission: $<span id="abp-commission"><?php echo number_format(floatval($affiliate->commission), 2); ?></span></p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="abp_process_payout">
                <?php wp_nonce_field('abp_payout_nonce'); ?>
                <button type="submit" class="button">Request Payout</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    // Track clicks via URL parameter
    public function track_affiliate_click() {
        if (!isset($_GET['ref'])) return;
        $code = sanitize_text_field($_GET['ref']);
        if (empty($code)) return;

        global $wpdb;
        $table = $wpdb->prefix . 'abp_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE affiliate_code = %s", $code));
        if (!$affiliate) return;

        // Count click, prevent multiple clicks in same session
        if (!isset($_COOKIE['abp_click_' . $code])) {
            $wpdb->query($wpdb->prepare("UPDATE $table SET clicks = clicks + 1 WHERE affiliate_code = %s", $code));
            setcookie('abp_click_' . $code, 1, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
        }

        // Store affiliate code in user session for conversions
        if (!session_id()) session_start();
        $_SESSION['abp_affiliate_code'] = $code;
    }

    // Assume conversion tracked manually, increment conversions and commission
    public static function record_conversion($amount) {
        if (!session_id()) session_start();
        if (!isset($_SESSION['abp_affiliate_code'])) return false;
        $code = sanitize_text_field($_SESSION['abp_affiliate_code']);
        if (empty($code)) return false;

        global $wpdb;
        $table = $wpdb->prefix . 'abp_affiliates';
        $rate = 0.10; // 10% commission rate

        // Increment conversions and add commission
        $commission = $amount * $rate;
        $wpdb->query($wpdb->prepare("UPDATE $table SET conversions = conversions + 1, commission = commission + %f WHERE affiliate_code = %s", $commission, $code));

        // Clear session affiliate code to avoid double counting
        unset($_SESSION['abp_affiliate_code']);
        return true;
    }

    public function admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', 'affiliate-booster-pro', array($this, 'admin_page'), 'dashicons-chart-bar', 76);
    }

    public function admin_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'abp_affiliates';
        $affiliates = $wpdb->get_results("SELECT * FROM $table ORDER BY commission DESC LIMIT 50");
        echo '<div class="wrap"><h1>Affiliate Booster Pro Stats</h1><table class="widefat striped"><thead><tr><th>Affiliate Code</th><th>User ID</th><th>Clicks</th><th>Conversions</th><th>Commission</th></tr></thead><tbody>';
        foreach ($affiliates as $a) {
            echo '<tr>' .
                '<td>' . esc_html($a->affiliate_code) . '</td>' .
                '<td>' . intval($a->user_id) . '</td>' .
                '<td>' . intval($a->clicks) . '</td>' .
                '<td>' . intval($a->conversions) . '</td>' .
                '<td>$' . number_format(floatval($a->commission), 2) . '</td>' .
                '</tr>';
        }
        echo '</tbody></table></div>';
    }

    public function process_payout() {
        if (!current_user_can('edit_posts')) wp_die('Unauthorized');
        check_admin_referer('abp_payout_nonce');

        global $wpdb;
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'abp_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));

        if (!$affiliate || $affiliate->commission <= 0) {
            wp_redirect($_SERVER['HTTP_REFERER']);
            exit;
        }

        // Simulate payout processing (actual integration with payment gateway needed)
        // Reset commission to zero after payout
        $wpdb->query($wpdb->prepare("UPDATE $table SET commission = 0 WHERE user_id = %d", $user_id));

        wp_redirect(add_query_arg('abp_payout', 'success', $_SERVER['HTTP_REFERER']));
        exit;
    }

    public function ajax_get_stats() {
        if (!is_user_logged_in()) wp_send_json_error('Not logged in');
        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'abp_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
        if (!$affiliate) wp_send_json_error('No affiliate account');
        wp_send_json_success(array(
            'clicks' => intval($affiliate->clicks),
            'conversions' => intval($affiliate->conversions),
            'commission' => number_format(floatval($affiliate->commission), 2)
        ));
    }

}

// Initialize plugin
AffiliateBoosterPro::instance();

// Helper to create affiliate account for users automatically on registration
add_action('user_register', function($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'abp_affiliates';
    $code = substr(md5(uniqid('aff_', true)), 0, 8);
    $wpdb->insert($table, array('user_id' => $user_id, 'affiliate_code' => $code));
});

// Example: To record conversion after successful purchase, call:
// AffiliateBoosterPro::record_conversion($order_amount);

