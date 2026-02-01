/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with smart popups, progress bars, and PayPal integration.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_shortcode('sdb_donate', array($this, 'donate_shortcode'));
    }

    public function init() {
        if (get_option('sdb_settings')) {
            // Settings loaded
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb-style.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdb_submit'])) {
            update_option('sdb_settings', $_POST['sdb_settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = get_option('sdb_settings', array('goal' => 1000, 'amounts' => '5,10,25,50', 'paypal_email' => '', 'show_popup' => '1', 'pro' => '0'));
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Donation Goal</th>
                        <td><input type="number" name="sdb_settings[goal]" value="<?php echo esc_attr($settings['goal']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Suggested Amounts</th>
                        <td><input type="text" name="sdb_settings[amounts]" value="<?php echo esc_attr($settings['amounts']); ?>" placeholder="5,10,25,50" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdb_settings[paypal_email]" value="<?php echo esc_attr($settings['paypal_email']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Show Exit-Intent Popup</th>
                        <td><input type="checkbox" name="sdb_settings[show_popup]" <?php checked($settings['show_popup'], '1'); ?> value="1" /></td>
                    </tr>
                </table>
                <?php if (!$settings['pro']): ?>
                <p><strong>Upgrade to Pro for recurring donations and analytics!</strong></p>
                <?php endif; ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donate_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $settings = get_option('sdb_settings');
        ob_start();
        ?>
        <div id="sdb-donate-form-<?php echo esc_attr($atts['id']); ?>" class="sdb-donate-form">
            <h3>Support Us!</h3>
            <div class="sdb-progress" style="background: #eee; height: 20px; margin-bottom: 10px;">
                <div class="sdb-progress-bar" style="width: 0%; background: #28a745; height: 100%;"></div>
            </div>
            <p>Goal: $<?php echo $settings['goal']; ?></p>
            <div class="sdb-amounts">
                <?php foreach (explode(',', $settings['amounts']) as $amt): $amt = trim($amt); ?>
                <button class="sdb-amount-btn" data-amount="<?php echo $amt; ?>">$<?php echo $amt; ?></button>
                <?php endforeach; ?>
            </div>
            <input type="number" id="sdb-custom-amount-<?php echo esc_attr($atts['id']); ?>" placeholder="Custom amount" />
            <button id="sdb-pay-<?php echo esc_attr($atts['id']); ?>" class="button sdb-pay-btn">Donate via PayPal</button>
        </div>
        <script>
        jQuery(function($){
            var formId = '#sdb-donate-form-<?php echo esc_attr($atts['id']); ?>';
            $('.sdb-amount-btn', formId).click(function(){ $('#sdb-custom-amount-<?php echo esc_attr($atts['id']); ?>').val($(this).data('amount')); });
            $('#sdb-pay-<?php echo esc_attr($atts['id']); ?>').click(function(){
                var amount = $('#sdb-custom-amount-<?php echo esc_attr($atts['id']); ?>').val();
                if(amount > 0){
                    window.location = 'https://www.paypal.com/donate/?hosted_button_id=TEST&amount=' + amount; // Replace with your PayPal button ID
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        // Log donation or update progress (Pro feature)
        wp_die('Donation processed');
    }
}

SmartDonationBooster::get_instance();

// Create JS file content (base64 or inline, but for single file, add as string)
function sdb_add_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        var settings = <?php echo json_encode(get_option('sdb_settings')); ?>;
        // Exit-intent popup
        if(settings.show_popup){
            $(document).on('mouseleave', function(e) {
                if(e.clientY < 0) {
                    $('body').append('<div id="sdb-popup" class="sdb-popup"><div class="sdb-popup-content">[sdb_donate]</div><button onclick="jQuery(\'#sdb-popup\').remove();">Close</button></div>');
                }
            });
        }
        // Update progress bar (dummy)
        $('.sdb-progress-bar').css('width', '60%');
    });
    </script>
    <style>
    .sdb-donate-form { border: 1px solid #ddd; padding: 20px; max-width: 300px; margin: 20px auto; background: #f9f9f9; }
    .sdb-amount-btn { margin: 5px; padding: 10px; background: #007cba; color: white; border: none; cursor: pointer; }
    .sdb-pay-btn { width: 100%; padding: 12px; background: #28a745; color: white; border: none; cursor: pointer; }
    .sdb-popup { position: fixed; top: 20%; left: 20%; width: 60%; height: 60%; background: white; border: 2px solid #007cba; z-index: 9999; }
    .sdb-popup-content { padding: 20px; }
    </style>
    <?php
}
add_action('wp_footer', 'sdb_add_js');

// Activation hook
register_activation_hook(__FILE__, function() {
    add_option('sdb_settings', array('goal' => 1000, 'amounts' => '5,10,25,50', 'paypal_email' => '', 'show_popup' => '1'));
});