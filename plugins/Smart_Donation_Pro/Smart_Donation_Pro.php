/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with customizable buttons, progress bars, and goal trackers.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('sdp_donate', array($this, 'donate_shortcode'));
        add_shortcode('sdp_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_goal_amount') === false) {
            update_option('sdp_goal_amount', 1000);
            update_option('sdp_current_amount', 0);
            update_option('sdp_donation_message', 'Support our work!');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'button_text' => 'Donate Now',
            'message' => get_option('sdp_donation_message', 'Support us!')
        ), $atts);

        ob_start();
        ?>
        <div class="sdp-donate-container">
            <p><?php echo esc_html($atts['message']); ?></p>
            <input type="number" id="sdp-amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1" step="0.01">
            <button id="sdp-donate-btn" class="sdp-button"><?php echo esc_html($atts['button_text']); ?></button>
            <p id="sdp-message"></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function goal_shortcode($atts) {
        $goal = get_option('sdp_goal_amount', 1000);
        $current = get_option('sdp_current_amount', 0);
        $percent = min(100, ($current / $goal) * 100);

        ob_start();
        ?>
        <div class="sdp-goal-container">
            <h3>Donation Goal: $<span id="sdp-current"><?php echo number_format($current, 2); ?></span> / $<span id="sdp-goal"><?php echo number_format($goal, 2); ?></span></h3>
            <div class="sdp-progress-bar">
                <div class="sdp-progress" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p><?php echo round($percent); ?>% achieved</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        if ($amount > 0) {
            $current = get_option('sdp_current_amount', 0) + $amount;
            update_option('sdp_current_amount', $current);
            wp_send_json_success(array('message' => 'Thank you for your $' . number_format($amount, 2) . ' donation! Total: $' . number_format($current, 2)));
        } else {
            wp_send_json_error('Invalid amount');
        }
    }

    public function activate() {
        $this->init();
    }
}

new SmartDonationPro();

// Inline scripts and styles for single file
add_action('wp_head', function() {
    echo '<script>
jQuery(document).ready(function($) {
    $(".sdp-donate-btn").click(function() {
        var amount = $("#sdp-amount").val();
        $.post(sdp_ajax.ajax_url, {
            action: "sdp_donate",
            amount: amount,
            nonce: sdp_ajax.nonce
        }, function(res) {
            if (res.success) {
                $("#sdp-message").html("<strong style=\'color:green\'>" + res.data.message + "</strong>");
                $("#sdp-current").text("' . get_option('sdp_current_amount', 0) + '".replace(/\\d(?=(?:\\d{3})+(?!\\d))/g, "$&,");
            } else {
                $("#sdp-message").html("<strong style=\'color:red\'>" + res.data + "</strong>");
            }
        });
    });
});
</script>';

    echo '<style>
.sdp-donate-container { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.sdp-button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
.sdp-button:hover { background: #005a87; }
#sdp-amount { padding: 8px; margin-right: 10px; border: 1px solid #ccc; border-radius: 4px; width: 100px; }
.sdp-goal-container { text-align: center; padding: 20px; }
.sdp-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdp-progress { background: #28a745; height: 100%; transition: width 0.5s; }
</style>';
});