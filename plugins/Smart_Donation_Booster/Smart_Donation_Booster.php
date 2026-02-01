/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with progress bars, goals, and easy PayPal integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

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
        add_shortcode('donation_button', array($this, 'donation_button_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Create default options on activation
        if (get_option('sdb_goal_amount') === false) {
            update_option('sdb_goal_amount', 1000);
            update_option('sdb_current_amount', 0);
            update_option('sdb_paypal_email', get_option('admin_email'));
            update_option('sdb_goal_title', 'Support Our Work!');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb-style.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function donation_goal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default'
        ), $atts);

        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $percent = ($current / $goal) * 100;

        ob_start();
        ?>
        <div class="sdb-goal-container" id="sdb-<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html(get_option('sdb_goal_title', 'Support Our Work!')); ?></h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p class="sdb-amount">$<?php echo number_format($current); ?> / $<?php echo number_format($goal); ?> (<?php echo round($percent); ?>%)</p>
            <?php echo do_shortcode('[donation_button]'); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function donation_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10'
        ), $atts);

        $paypal_email = get_option('sdb_paypal_email');
        $button_text = get_option('sdb_button_text', 'Donate Now');

        ob_start();
        ?>
        <button class="sdb-donate-btn" data-amount="<?php echo esc_attr($atts['amount']); ?>">
            <?php echo esc_html($button_text); ?> $<?php echo esc_attr($atts['amount']); ?>
        </button>
        <form id="sdb-paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="display:none;">
            <input type="hidden" name="cmd" value="_xclick">
            <input type="hidden" name="business" value="<?php echo esc_attr($paypal_email); ?>">
            <input type="hidden" name="item_name" value="Donation via Smart Donation Booster">
            <input type="hidden" name="amount" id="sdb-amount" value="">
            <input type="hidden" name="currency_code" value="USD">
            <input type="hidden" name="return" value="<?php echo esc_url(home_url()); ?>">
        </form>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// AJAX handler for simulating donations (demo - replace with real payment webhook)
add_action('wp_ajax_sdb_donate', 'sdb_handle_donation');
add_action('wp_ajax_nopriv_sdb_donate', 'sdb_handle_donation');
function sdb_handle_donation() {
    check_ajax_referer('sdb_nonce', 'nonce');

    $amount = floatval($_POST['amount']);
    $current = get_option('sdb_current_amount', 0) + $amount;
    $goal = get_option('sdb_goal_amount', 1000);
    if ($current > $goal) $current = $goal;

    update_option('sdb_current_amount', $current);

    wp_send_json_success(array('current' => $current, 'percent' => ($current / $goal) * 100));
}

// Admin settings page
add_action('admin_menu', 'sdb_admin_menu');
function sdb_admin_menu() {
    add_options_page('Donation Booster Settings', 'Donation Booster', 'manage_options', 'sdb-settings', 'sdb_settings_page');
}

function sdb_settings_page() {
    if (isset($_POST['sdb_submit'])) {
        update_option('sdb_goal_amount', floatval($_POST['goal_amount']));
        update_option('sdb_current_amount', floatval($_POST['current_amount']));
        update_option('sdb_paypal_email', sanitize_email($_POST['paypal_email']));
        update_option('sdb_goal_title', sanitize_text_field($_POST['goal_title']));
        update_option('sdb_button_text', sanitize_text_field($_POST['button_text']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }

    $goal = get_option('sdb_goal_amount', 1000);
    $current = get_option('sdb_current_amount', 0);
    $paypal = get_option('sdb_paypal_email');
    $title = get_option('sdb_goal_title', 'Support Our Work!');
    $btn_text = get_option('sdb_button_text', 'Donate Now');
    ?>
    <div class="wrap">
        <h1>Smart Donation Booster Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Goal Amount</th>
                    <td><input type="number" name="goal_amount" value="<?php echo $goal; ?>" /></td>
                </tr>
                <tr>
                    <th>Current Amount</th>
                    <td><input type="number" name="current_amount" value="<?php echo $current; ?>" step="0.01" /></td>
                </tr>
                <tr>
                    <th>PayPal Email</th>
                    <td><input type="email" name="paypal_email" value="<?php echo $paypal; ?>" /></td>
                </tr>
                <tr>
                    <th>Goal Title</th>
                    <td><input type="text" name="goal_title" value="<?php echo $title; ?>" /></td>
                </tr>
                <tr>
                    <th>Button Text</th>
                    <td><input type="text" name="button_text" value="<?php echo $btn_text; ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p>Use shortcodes: <code>[donation_goal]</code> or <code>[donation_button amount="20"]</code></p>
    </div>
    <?php
}

SmartDonationBooster::get_instance();

// Inline JS and CSS for simplicity (self-contained)
add_action('wp_head', 'sdb_inline_assets');
function sdb_inline_assets() {
    ?>
    <style>
    .sdb-goal-container { background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; max-width: 400px; margin: 20px auto; }
    .sdb-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdb-progress-fill { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s ease; }
    .sdb-donate-btn { background: #007cba; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .sdb-donate-btn:hover { background: #005a87; }
    .sdb-amount { font-size: 18px; font-weight: bold; color: #333; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.sdb-donate-btn').click(function(e) {
            e.preventDefault();
            var amount = $(this).data('amount') || 10;
            $('#sdb-amount').val(amount);
            $('#sdb-paypal-form').submit();
            // Simulate update for demo
            $.post(sdb_ajax.ajax_url, {
                action: 'sdb_donate',
                amount: amount,
                nonce: sdb_ajax.nonce
            }, function(res) {
                if (res.success) {
                    $('.sdb-progress-fill').css('width', res.data.percent + '%');
                    $('.sdb-amount').text('$' + res.data.current.toLocaleString() + ' / $' + <?php echo get_option('sdb_goal_amount'); ?> + ' (' + Math.round(res.data.percent) + '%)');
                }
            });
        });
    });
    </script>
    <?php
}
