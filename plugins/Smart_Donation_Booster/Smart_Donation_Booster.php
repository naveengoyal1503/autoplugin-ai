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
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdb_donate', array($this, 'ajax_donate'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'ajax_donate'));
        add_shortcode('sdb_donation', array($this, 'donation_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdb_goal') === false) {
            update_option('sdb_goal', 1000);
        }
        if (get_option('sdb_current') === false) {
            update_option('sdb_current', 0);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => get_option('sdb_goal'),
            'current' => get_option('sdb_current'),
            'paypal' => get_option('sdb_paypal_email', 'your-paypal@example.com'),
        ), $atts);

        $percent = ($atts['current'] / $atts['goal']) * 100;
        ob_start();
        ?>
        <div class="sdb-container">
            <h3>Support Us! Current Goal: $<span id="sdb-current"><?php echo $atts['current']; ?></span> / $<span id="sdb-goal"><?php echo $atts['goal']; ?></span></h3>
            <div class="sdb-progress" style="width: 100%; background: #f0f0f0; border-radius: 10px;">
                <div class="sdb-bar" style="width: <?php echo $percent; ?>%; height: 30px; background: linear-gradient(90deg, #4CAF50, #45a049); border-radius: 10px; transition: width 0.5s;"></div>
            </div>
            <p><?php echo round($percent, 1); ?>% achieved! <button class="sdb-donate-btn" data-amount="5">$5</button>
            <button class="sdb-donate-btn" data-amount="10">$10</button>
            <button class="sdb-donate-btn" data-amount="25">$25</button>
            <button class="sdb-donate-btn custom">Custom</button></p>
            <div id="sdb-custom-amount" style="display:none;">
                $<input type="number" id="sdb-amount" min="1" value="10"> <button class="sdb-donate-btn" id="sdb-pay">Donate via PayPal</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_donate() {
        if (!wp_verify_nonce($_POST['nonce'], 'sdb_nonce')) {
            wp_die('Security check failed');
        }
        $amount = floatval($_POST['amount']);
        $current = get_option('sdb_current', 0) + $amount;
        $goal = get_option('sdb_goal', 1000);
        if ($current > $goal) $current = $goal;
        update_option('sdb_current', $current);
        wp_send_json_success(array('current' => $current));
    }

    public function activate() {
        add_option('sdb_goal', 1000);
        add_option('sdb_current', 0);
        add_option('sdb_paypal_email', 'your-paypal@example.com');
    }
}

SmartDonationBooster::get_instance();

// Admin page
add_action('admin_menu', function() {
    add_options_page('Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', 'sdb_admin_page');
});

function sdb_admin_page() {
    if (isset($_POST['sdb_goal'])) {
        update_option('sdb_goal', intval($_POST['sdb_goal']));
        update_option('sdb_current', floatval($_POST['sdb_current']));
        update_option('sdb_paypal_email', sanitize_email($_POST['sdb_paypal_email']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $goal = get_option('sdb_goal', 1000);
    $current = get_option('sdb_current', 0);
    $paypal = get_option('sdb_paypal_email', 'your-paypal@example.com');
    echo '<div class="wrap"><h1>Smart Donation Booster Settings</h1>
    <form method="post">
        <table class="form-table">
            <tr><th>Goal Amount</th><td><input type="number" name="sdb_goal" value="' . $goal . '" /></td></tr>
            <tr><th>Current Amount</th><td><input type="number" step="0.01" name="sdb_current" value="' . $current . '" /></td></tr>
            <tr><th>PayPal Email</th><td><input type="email" name="sdb_paypal_email" value="' . $paypal . '" /></td></tr>
        </table>
        <p>Use shortcode: <code>[sdb_donation]</code></p>
        ' . wp_nonce_field('sdb_save', '_wpnonce') . '<input type="submit" class="button-primary" value="Save" />
    </form></div>';
}

// Inline JS and CSS for self-contained
add_action('wp_head', function() {
    echo '<script>
jQuery(document).ready(function($) {
    $(".sdb-donate-btn").not("#sdb-pay").click(function() {
        var amount = $(this).data("amount");
        $("#sdb-amount").val(amount);
    });
    $(".custom").click(function() {
        $("#sdb-custom-amount").toggle();
    });
    $("#sdb-pay").click(function() {
        var amount = $("#sdb-amount").val();
        if (amount > 0) {
            var paypal = "' . get_option('sdb_paypal_email') . '";
            window.open("https://www.paypal.com/donate?hosted_button_id=REPLACE_WITH_YOURS&amount=" + amount, "_blank");
        }
    });
    $(".sdb-donate-btn[data-amount]").click(function() {
        var amount = $(this).data("amount");
        $.post(sdb_ajax.ajaxurl, {
            action: "sdb_donate",
            amount: amount,
            nonce: "' . wp_create_nonce('sdb_nonce') . '"
        }, function(res) {
            if (res.success) {
                $("#sdb-current").text(res.data.current);
                var percent = (res.data.current / $("#sdb-goal").text()) * 100;
                $(".sdb-bar").css("width", percent + "%");
                alert("Thank you for your " + amount + " donation!");
            }
        });
    });
});
</script>
<style>
.sdb-container { max-width: 500px; margin: 20px 0; padding: 20px; border: 2px solid #4CAF50; border-radius: 15px; background: #f9f9f9; text-align: center; }
.sdb-donate-btn { background: #4CAF50; color: white; border: none; padding: 10px 20px; margin: 5px; border-radius: 5px; cursor: pointer; }
.sdb-donate-btn:hover { background: #45a049; }
#wpadminbar .sdb-container { display: none; }
</style>';
});