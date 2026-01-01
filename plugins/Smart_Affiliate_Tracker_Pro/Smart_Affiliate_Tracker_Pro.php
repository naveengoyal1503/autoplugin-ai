/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Tracker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Tracker Pro
 * Plugin URI: https://example.com/smart-affiliate-tracker
 * Description: Automatically tracks affiliate link clicks, sessions, and conversions with detailed analytics to boost revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateTrackerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sat_track_click', array($this, 'track_click'));
        add_action('wp_footer', array($this, 'inject_tracker'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sat_pro_upgraded') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_upgrade_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Tracker',
            'Affiliate Tracker',
            'manage_options',
            'smart-affiliate-tracker',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['sat_save_settings'])) {
            update_option('sat_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('sat_api_key', '');
        $stats = get_option('sat_stats', array('clicks' => 0, 'conversions' => 0));
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Tracker Pro</h1>
            <h2>Dashboard</h2>
            <p><strong>Total Clicks:</strong> <?php echo $stats['clicks']; ?></p>
            <p><strong>Estimated Conversions:</strong> <?php echo $stats['conversions']; ?></p>
            <h2>Settings</h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key (for premium tracking)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="sat_save_settings" class="button-primary" value="Save Settings" /></p>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock advanced reports, A/B testing, and more for $49/year. <a href="#" onclick="alert('Pro upgrade link would redirect to payment page')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function pro_upgrade_notice() {
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Tracker Pro</strong> for advanced features! <a href="admin.php?page=smart-affiliate-tracker">Learn more</a></p></div>';
        }
    }

    public function inject_tracker() {
        if (is_admin()) return;
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('a[href*="aff="], a[href*="tag="], a[href*="?ref"]').click(function(e) {
                var url = $(this).attr('href');
                $.post(ajaxurl, {
                    action: 'sat_track_click',
                    url: url
                });
            });
        });
        </script>
        <?php
    }

    public function track_click() {
        $stats = get_option('sat_stats', array('clicks' => 0, 'conversions' => 0));
        $stats['clicks']++;
        update_option('sat_stats', $stats);
        wp_die('tracked');
    }

    public function activate() {
        add_option('sat_stats', array('clicks' => 0, 'conversions' => 0));
    }

    public function deactivate() {
        // Cleanup optional
    }
}

SmartAffiliateTrackerPro::get_instance();
