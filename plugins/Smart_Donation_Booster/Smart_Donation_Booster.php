/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with progress bars, goals, and PayPal buttons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationBooster {
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
        add_shortcode('donation_goal', array($this, 'donation_goal_shortcode'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('sdb_paypal_email')) {
            // Plugin is set up
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function donation_goal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '1000',
            'current' => '250',
            'title' => 'Support Our Work',
            'button_text' => 'Donate Now',
            'paypal_email' => get_option('sdb_paypal_email', '')
        ), $atts);

        $percent = ($atts['current'] / $atts['goal']) * 100;
        $remaining = $atts['goal'] - $atts['current'];

        ob_start();
        ?>
        <div class="sdb-container">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p class="sdb-stats">
                Raised: $<span id="sdb-current"><?php echo number_format($atts['current'], 0); ?></span> / $<span><?php echo number_format($atts['goal'], 0); ?></span><br>
                Remaining: $<span id="sdb-remaining"><?php echo number_format($remaining, 0); ?></span>
            </p>
            <?php if ($atts['paypal_email']): ?>
            <form action="https://www.paypal.com/donate" method="post" target="_top" class="sdb-paypal-form">
                <input type="hidden" name="hosted_button_id" value="<?php echo esc_attr(get_option('sdb_paypal_button_id', '')); ?>">
                <input type="hidden" name="no_note" value="1">
                <input type="hidden" name="currency_code" value="USD">
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button">
            </form>
            <button class="sdb-donate-btn" onclick="document.querySelector('.sdb-paypal-form').submit();"> <?php echo esc_html($atts['button_text']); ?></button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = floatval(get_option('sdb_current_donation', 0));
        $new_current = $current + $amount;
        update_option('sdb_current_donation', $new_current);
        wp_send_json_success(array('current' => $new_current));
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Donation Booster Settings', 'Donation Booster', 'manage_options', 'sdb-settings', 'sdb_settings_page');
    });

    function sdb_settings_page() {
        if (isset($_POST['sdb_paypal_email'])) {
            update_option('sdb_paypal_email', sanitize_email($_POST['sdb_paypal_email']));
            update_option('sdb_paypal_button_id', sanitize_text_field($_POST['sdb_paypal_button_id']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sdb_paypal_email', '');
        $button_id = get_option('sdb_paypal_button_id', '');
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdb_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>PayPal Button ID</th>
                        <td><input type="text" name="sdb_paypal_button_id" value="<?php echo esc_attr($button_id); ?>" class="regular-text"><br><small>Create at <a href="https://www.paypal.com/buttons/" target="_blank">PayPal Buttons</a></small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Usage:</strong> Use shortcode <code>[donation_goal goal="1000" current="250"]</code></p>
        </div>
        <?php
    }
}

SmartDonationBooster::get_instance();

// Inline CSS and JS for single file
function sdb_add_inline_assets() {
    ?>
    <style>
    .sdb-container { max-width: 400px; margin: 20px 0; text-align: center; }
    .sdb-progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdb-progress { background: linear-gradient(90deg, #4CAF50, #45a049); height: 100%; transition: width 0.5s ease; }
    .sdb-donate-btn { background: #007cba; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .sdb-donate-btn:hover { background: #005a87; }
    .sdb-stats { font-size: 18px; font-weight: bold; color: #333; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.sdb-donate-btn').on('click', function() {
            // Handled by form submit
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sdb_add_inline_assets');