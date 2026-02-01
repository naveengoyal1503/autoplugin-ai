/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with smart prompts, progress bars, and analytics.
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

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sdb_donate', array($this, 'ajax_donate'));
        add_shortcode('sdb_donation', array($this, 'donation_shortcode'));
    }

    public function activate() {
        add_option('sdb_settings', array(
            'title' => 'Support Us!',
            'amounts' => '5,10,20,50',
            'goal' => '1000',
            'message' => 'Help us reach our goal!',
            'paypal_email' => ''
        ));
    }

    public function deactivate() {}

    public function init() {
        load_plugin_textdomain('smart-donation-booster', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
        wp_localize_script('sdb-frontend', 'sdb_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Donation Booster Settings', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sdb_settings', $_POST['sdb_settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = get_option('sdb_settings', array());
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Button Title</th>
                        <td><input type="text" name="sdb_settings[title)" value="<?php echo esc_attr($settings['title'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Suggested Amounts (comma-separated)</th>
                        <td><input type="text" name="sdb_settings[amounts]" value="<?php echo esc_attr($settings['amounts'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="sdb_settings[goal]" value="<?php echo esc_attr($settings['goal'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Goal Message</th>
                        <td><textarea name="sdb_settings[message]"><?php echo esc_textarea($settings['message'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdb_settings[paypal_email]" value="<?php echo esc_attr($settings['paypal_email'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <?php wp_nonce_field('sdb_settings_nonce'); ?>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Settings" /></p>
            </form>
            <p><strong>Shortcode:</strong> <code>[sdb_donation]</code> | <strong>Widget:</strong> Add to sidebar via Appearance > Widgets.</p>
            <p><em>Pro Upgrade: Unlimited placements, A/B testing, analytics. <a href="#pro">Get Pro</a></em></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $settings = get_option('sdb_settings', array());
        $amounts = explode(',', str_replace(' ', '', $settings['amounts'] ?? '5,10,20'));
        $goal = intval($settings['goal'] ?? 1000);
        $collected = get_option('sdb_collected', 0);
        $progress = min(100, ($collected / $goal) * 100);

        ob_start();
        ?>
        <div id="sdb-container" class="sdb-donation-widget">
            <h3><?php echo esc_html($settings['title'] ?? 'Support Us!'); ?></h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p class="sdb-goal"><?php echo esc_html($settings['message'] ?? ''); ?> <?php echo $collected; ?> / <?php echo $goal; ?> raised</p>
            <div class="sdb-amounts">
                <?php foreach ($amounts as $amount): $amount = trim($amount); ?>
                    <button class="sdb-amount-btn" data-amount="<?php echo esc_attr($amount); ?>">$<?php echo esc_html($amount); ?></button>
                <?php endforeach; ?>
            </div>
            <div class="sdb-custom-amount">
                <input type="number" id="sdb-custom" placeholder="Custom amount" min="1" />
                <button id="sdb-donate-btn" class="button sdb-donate">Donate Now</button>
            </div>
            <div id="sdb-paypal" style="display:none;">
                <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                    <input type="hidden" name="cmd" value="_s-xclick" />
                    <input type="hidden" name="hosted_button_id" value="" />
                    <input type="hidden" name="amount" id="sdb-paypal-amount" />
                    <input type="hidden" name="business" value="<?php echo esc_attr($settings['paypal_email'] ?? ''); ?>" />
                    <input type="hidden" name="currency_code" value="USD" />
                    <input type="submit" value="Pay with PayPal" class="button" />
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_donate() {
        check_ajax_referer('sdb_nonce', 'nonce');
        if (!isset($_POST['amount']) || !is_numeric($_POST['amount'])) {
            wp_die('Invalid amount');
        }
        $amount = floatval(sanitize_text_field($_POST['amount']));
        $collected = get_option('sdb_collected', 0) + $amount;
        update_option('sdb_collected', $collected);
        wp_send_json_success(array('collected' => $collected));
    }
}

// Widget
class SDB_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('sdb_widget', 'Donation Booster');
    }

    public function widget($args, $instance) {
        echo do_shortcode('[sdb_donation]');
    }

    public function form($instance) {
        echo '<p>Uses global settings. Customize in Settings > Donation Booster.</p>';
    }
}
add_action('widgets_init', function() { register_widget('SDB_Widget'); });

SmartDonationBooster::get_instance();

// Pro Upsell Notice
add_action('admin_notices', function() {
    if (get_option('sdb_dismiss_pro') !== '1' && current_user_can('manage_options')) {
        echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>Smart Donation Booster Pro</strong>: A/B testing, analytics & more! <a href="#pro">Upgrade now</a></p></div>';
    }
});

// Frontend CSS (inline for single file)
add_action('wp_head', function() {
    echo '<style>
.sdb-donation-widget { background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; max-width: 300px; margin: 20px auto; }
.sdb-progress-bar { background: #eee; height: 10px; border-radius: 5px; margin: 10px 0; overflow: hidden; }
.sdb-progress { background: #28a745; height: 100%; transition: width 0.3s; }
.sdb-amounts { margin: 15px 0; }
.sdb-amount-btn { background: #007cba; color: white; border: none; padding: 8px 12px; margin: 2px; border-radius: 4px; cursor: pointer; }
.sdb-amount-btn:hover, .sdb-amount-btn.active { background: #005a87; }
.sdb-custom-amount input { width: 80px; padding: 5px; margin-right: 5px; }
.sdb-donate { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; }
@media (max-width: 768px) { .sdb-donation-widget { margin: 10px; } }
</style>';
});

// Frontend JS (inline)
add_action('wp_footer', function() {
    ?>
    <script>
jQuery(document).ready(function($) {
    $('.sdb-amount-btn').click(function() {
        $('.sdb-amount-btn').removeClass('active');
        $(this).addClass('active');
        $('#sdb-custom').val($(this).data('amount'));
    });

    $('#sdb-donate-btn').click(function() {
        var amount = parseFloat($('#sdb-custom').val()) || 0;
        if (amount <= 0) return alert('Please enter an amount');

        $.post(sdb_ajax.ajaxurl, {
            action: 'sdb_donate',
            amount: amount,
            nonce: sdb_ajax.nonce
        }, function(res) {
            if (res.success) {
                location.href = $('#sdb-paypal form').attr('action') + '?amount=' + amount;
            }
        });
    });
});
    </script>
    <?php
});