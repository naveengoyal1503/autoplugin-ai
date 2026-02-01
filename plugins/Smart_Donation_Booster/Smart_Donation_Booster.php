/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with smart, non-intrusive prompts, progress bars, and PayPal integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationBooster {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation'));
        add_shortcode('sdb_donate_button', array($this, 'donate_button_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdb_settings') === false) {
            update_option('sdb_settings', array(
                'goal' => 1000,
                'message' => 'Support our content!',
                'paypal_email' => '',
                'show_on_posts' => 1,
                'show_after_content' => 1
            ));
        }
        add_action('wp_footer', array($this, 'output_donation_widget'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function output_donation_widget() {
        $settings = get_option('sdb_settings');
        if (!$settings['paypal_email']) return;
        $current = get_transient('sdb_donations_total') ?: 0;
        $progress = min(100, ($current / $settings['goal']) * 100);
        ?>
        <div id="sdb-widget" style="display:none;">
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p><?php echo esc_html($settings['message']); ?> <strong>$<?php echo number_format($current); ?></strong> / $<?php echo number_format($settings['goal']); ?></p>
            <form action="https://www.paypal.com/donate" method="post" target="_top">
                <input type="hidden" name="hosted_button_id" value="YOUR_PAYPAL_BUTTON_ID">
                <input type="hidden" name="business" value="<?php echo esc_attr($settings['paypal_email']); ?>">
                <input type="hidden" name="item_name" value="Support Site">
                <input type="hidden" name="currency_code" value="USD">
                <input type="submit" value="Donate Now" class="sdb-donate-btn">
            </form>
            <button id="sdb-dismiss">Dismiss</button>
        </div>
        <?php
    }

    public function donate_button_shortcode($atts) {
        $settings = get_option('sdb_settings');
        if (!$settings['paypal_email']) return '';
        ob_start();
        ?>
        <div class="sdb-inline">
            <form action="https://www.paypal.com/donate" method="post" target="_top">
                <input type="hidden" name="business" value="<?php echo esc_attr($settings['paypal_email']); ?>">
                <input type="hidden" name="item_name" value="Support Content">
                <input type="hidden" name="currency_code" value="USD">
                <input type="submit" value="Buy Me a Coffee" class="sdb-btn">
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = floatval(get_transient('sdb_donations_total') ?: 0);
        $new_total = $current + $amount;
        set_transient('sdb_donations_total', $new_total, WEEK_IN_SECONDS);
        wp_send_json_success(array('new_total' => $new_total));
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Admin settings page
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', 'sdb_settings_page');
    });
}

function sdb_settings_page() {
    if (isset($_POST['submit'])) {
        update_option('sdb_settings', array(
            'goal' => intval($_POST['goal']),
            'message' => sanitize_text_field($_POST['message']),
            'paypal_email' => sanitize_email($_POST['paypal_email']),
            'show_on_posts' => isset($_POST['show_on_posts']),
            'show_after_content' => isset($_POST['show_after_content'])
        ));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $settings = get_option('sdb_settings');
    ?>
    <div class="wrap">
        <h1>Smart Donation Booster Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="paypal_email" value="<?php echo esc_attr($settings['paypal_email']); ?>" required></td>
                </tr>
                <tr>
                    <th>Donation Goal</th>
                    <td><input type="number" name="goal" value="<?php echo esc_attr($settings['goal']); ?>"> USD</td>
                </tr>
                <tr>
                    <th>Message</th>
                    <td><input type="text" name="message" value="<?php echo esc_attr($settings['message']); ?>"></td>
                </tr>
                <tr>
                    <th>Show on Posts</th>
                    <td><input type="checkbox" name="show_on_posts" <?php checked($settings['show_on_posts']); ?>></td>
                </tr>
                <tr>
                    <th>Show after Content</th>
                    <td><input type="checkbox" name="show_after_content" <?php checked($settings['show_after_content']); ?>></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>Pro Features:</strong> Unlimited campaigns, A/B testing, analytics. <a href="#pro">Upgrade to Pro</a></p>
    </div>
    <?php
}

SmartDonationBooster::get_instance();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    #sdb-widget { position: fixed; bottom: 20px; right: 20px; background: #007cba; color: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); max-width: 300px; z-index: 9999; }
    .sdb-progress-bar { background: rgba(255,255,255,0.3); height: 10px; border-radius: 5px; margin-bottom: 10px; overflow: hidden; }
    .sdb-progress { background: #00ff88; height: 100%; transition: width 0.5s; }
    .sdb-donate-btn, .sdb-btn { background: #ff9500; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
    .sdb-donate-btn:hover, .sdb-btn:hover { background: #e68900; }
    #sdb-dismiss { background: none; border: none; color: white; cursor: pointer; float: right; }
    .sdb-inline { text-align: center; margin: 20px 0; }
    </style>';
});

// Sample JS - enqueue actual file in production
add_action('wp_footer', function() {
    ?><script>
    jQuery(document).ready(function($) {
        setTimeout(function() { $('#sdb-widget').fadeIn(); }, 10000);
        $('#sdb-dismiss').click(function() { $('#sdb-widget').fadeOut(); });
    });
    </script><?php
});