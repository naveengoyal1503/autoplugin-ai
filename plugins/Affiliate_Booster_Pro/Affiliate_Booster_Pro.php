/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Plugin URI: https://example.com/affiliate-booster-pro
 * Description: Create and manage customizable affiliate programs with tiered commissions and real-time analytics.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class AffiliateBoosterPro {
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_referral_link', array($this, 'referral_link_shortcode'));
        add_action('init', array($this, 'track_referral'));
        add_action('wp_footer', array($this, 'inject_tracking_script'));
    }

    // Add menu to admin
    public function add_admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', 'affiliate-booster-pro', array($this, 'settings_page'), 'dashicons-networking');
    }

    // Register settings
    public function register_settings() {
        register_setting('affiliate_booster_group', 'abp_commission_rate');
        register_setting('affiliate_booster_group', 'abp_tracking_cookie_lifetime');
        register_setting('affiliate_booster_group', 'abp_premium_mode');
    }

    // Settings page HTML
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Booster Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_booster_group');
                do_settings_sections('affiliate_booster_group');
                ?>
                <table class="form-table">
                    <tr valign="top">
                    <th scope="row">Default Commission Rate (%)</th>
                    <td><input type="number" name="abp_commission_rate" min="1" max="100" value="<?php echo esc_attr(get_option('abp_commission_rate', 10)); ?>" /></td>
                    </tr>
                    <tr valign="top">
                    <th scope="row">Referral Cookie Lifetime (days)</th>
                    <td><input type="number" name="abp_tracking_cookie_lifetime" min="1" max="365" value="<?php echo esc_attr(get_option('abp_tracking_cookie_lifetime', 30)); ?>" /></td>
                    </tr>
                    <tr valign="top">
                    <th scope="row">Premium Mode Active</th>
                    <td><input type="checkbox" name="abp_premium_mode" value="1" <?php checked(1, get_option('abp_premium_mode', 0)); ?> /> Enable advanced features</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Affiliate Dashboard Shortcode</h2>
            <p>Use the shortcode <code>[affiliate_referral_link]</code> on any page to display your referral link for logged-in affiliates.</p>
        </div>
        <?php
    }

    // Generate referral link shortcode
    public function referral_link_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>Please log in to access your referral link.</p>';
        }

        $user_id = get_current_user_id();
        $ref_code = 'ref' . $user_id;
        $ref_url = add_query_arg('ref', $ref_code, home_url('/'));

        return '<p>Your affiliate referral link:</p><input type="text" readonly style="width:100%;" value="' . esc_url($ref_url) . '" onclick="this.select();" />';
    }

    // Track referral in cookie
    public function track_referral() {
        if (isset($_GET['ref'])) {
            $ref_id = sanitize_text_field($_GET['ref']);

            if (strpos($ref_id, 'ref') === 0) {
                $user_id = intval(substr($ref_id, 3));
                if ($user_id > 0 && get_userdata($user_id)) {
                    $days = intval(get_option('abp_tracking_cookie_lifetime', 30));
                    setcookie('abp_referral', $user_id, time() + ($days * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
                    $_COOKIE['abp_referral'] = $user_id;
                }
            }
        }
    }

    // Inject tracking script placeholder (for premium features to be extended)
    public function inject_tracking_script() {
        if (get_option('abp_premium_mode')) {
            ?>
            <script>
            // Future premium advanced tracking can go here
            console.log('Affiliate Booster Pro Premium: Advanced tracking enabled.');
            </script>
            <?php
        }
    }

    // Placeholder for future commission calculation/integration
}

// Initialize plugin
AffiliateBoosterPro::instance();
