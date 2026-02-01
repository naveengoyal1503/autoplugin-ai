/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with smart, contextual prompts and PayPal integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationBooster {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donate'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donate'));
        add_shortcode('sdb_donate_button', array($this, 'donate_button_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdb_settings') === false) {
            update_option('sdb_settings', array(
                'paypal_email' => '',
                'goal_amount' => 1000,
                'current_amount' => 0,
                'button_text' => 'Support Us!',
                'prompts_enabled' => true,
                'exit_intent' => true
            ));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function donate_button_shortcode($atts) {
        $atts = shortcode_atts(array('amount' => '10'), $atts);
        $settings = get_option('sdb_settings');
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($settings['paypal_email']) . '&item_name=Donation&amount=' . $atts['amount'] . '&currency_code=USD&return=' . urlencode(home_url());
        return '<a href="' . esc_url($paypal_url) . '" class="sdb-paypal-btn" target="_blank">' . esc_html($settings['button_text']) . ' ($' . $atts['amount'] . ')</a>';
    }

    public function handle_donate() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $settings = get_option('sdb_settings');
        $settings['current_amount'] += floatval($_POST['amount']);
        if ($settings['current_amount'] > $settings['goal_amount']) {
            $settings['current_amount'] = $settings['goal_amount'];
        }
        update_option('sdb_settings', $settings);
        wp_send_json_success('Thank you for your donation!');
    }

    public function activate() {
        $this->init();
        flush_rewrite_rules();
    }
}

new SmartDonationBooster();

// Admin settings page
function sdb_admin_menu() {
    add_options_page('Smart Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', 'sdb_settings_page');
}
add_action('admin_menu', 'sdb_admin_menu');

function sdb_settings_page() {
    if (isset($_POST['submit'])) {
        update_option('sdb_settings', array(
            'paypal_email' => sanitize_email($_POST['paypal_email']),
            'goal_amount' => floatval($_POST['goal_amount']),
            'current_amount' => floatval($_POST['current_amount']),
            'button_text' => sanitize_text_field($_POST['button_text']),
            'prompts_enabled' => isset($_POST['prompts_enabled']),
            'exit_intent' => isset($_POST['exit_intent'])
        ));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $settings = get_option('sdb_settings');
    ?>
    <div class="wrap">
        <h1>Smart Donation Booster Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr><th>PayPal Email</th><td><input type="email" name="paypal_email" value="<?php echo esc_attr($settings['paypal_email']); ?>" required /></td></tr>
                <tr><th>Goal Amount</th><td><input type="number" name="goal_amount" value="<?php echo esc_attr($settings['goal_amount']); ?>" step="0.01" /></td></tr>
                <tr><th>Current Amount</th><td><input type="number" name="current_amount" value="<?php echo esc_attr($settings['current_amount']); ?>" step="0.01" /></td></tr>
                <tr><th>Button Text</th><td><input type="text" name="button_text" value="<?php echo esc_attr($settings['button_text']); ?>" /></td></tr>
                <tr><th>Enable Prompts</th><td><input type="checkbox" name="prompts_enabled" <?php checked($settings['prompts_enabled']); ?> /></td></tr>
                <tr><th>Exit Intent</th><td><input type="checkbox" name="exit_intent" <?php checked($settings['exit_intent']); ?> /></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Progress Bar</h2>
        <div class="sdb-progress-container">
            <div class="sdb-progress-bar" style="width: <?php echo min(100, ($settings['current_amount'] / $settings['goal_amount']) * 100); ?>%;"></div>
        </div>
        <p>Goal: $<?php echo $settings['goal_amount']; ?> | Raised: $<?php echo $settings['current_amount']; ?></p>
        <p>Use shortcode: <code>[sdb_donate_button amount="10"]</code></p>
    </div>
    <?php
}

// Frontend donation prompts
function sdb_frontend_scripts() {
    $settings = get_option('sdb_settings');
    if ($settings['prompts_enabled']) {
        echo '<div id="sdb-donation-prompt" style="display:none; position:fixed; bottom:20px; right:20px; background:#007cba; color:white; padding:20px; border-radius:10px; z-index:9999;">
                <p>Love this content? <a href="#" id="sdb-prompt-donate">Donate now!</a></p>
                <button id="sdb-prompt-close">&times;</button>
              </div>';
        echo '<script>document.addEventListener("DOMContentLoaded", function(){ setTimeout(function(){ document.getElementById("sdb-donation-prompt").style.display="block"; }, 10000); });</script>';
    }
}
add_action('wp_footer', 'sdb_frontend_scripts');