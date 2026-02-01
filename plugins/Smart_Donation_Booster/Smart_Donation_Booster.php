/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with smart prompts, progress bars, and PayPal integration.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationBooster {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation'));
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
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => get_option('sdb_goal'),
            'prompt' => 'Support our work!'
        ), $atts);

        $current = get_option('sdb_current', 0);
        $progress = ($current / $atts['goal']) * 100;

        ob_start();
        ?>
        <div class="sdb-container">
            <h3><?php echo esc_html($atts['prompt']); ?></h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>$<?php echo $current; ?> / $<?php echo $atts['goal']; ?> raised</p>
            <form class="sdb-form">
                <input type="number" name="amount" placeholder="Enter amount" min="1" step="1" required>
                <button type="button" class="sdb-paypal-btn">Donate with PayPal</button>
                <p class="sdb-suggestion">Suggested: $5, $10, $25</p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        if ($amount > 0) {
            $current = get_option('sdb_current', 0) + $amount;
            update_option('sdb_current', $current);
            wp_send_json_success(array('new_amount' => $current));
        }
        wp_send_json_error();
    }
}

new SmartDonationBooster();

/* Dummy JS and CSS - In production, save as separate files */
function sdb_add_inline_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sdb-paypal-btn').click(function() {
            var amount = $(this).siblings('input[name="amount"]').val();
            if (amount) {
                // Simulate PayPal redirect (use real PayPal JS SDK in pro)
                alert('Redirecting to PayPal for $' + amount + '... (Demo)');
                $.post(sdb_ajax.ajax_url, {
                    action: 'sdb_donate',
                    amount: amount,
                    nonce: sdb_ajax.nonce
                }, function(res) {
                    if (res.success) {
                        location.reload();
                    }
                });
            }
        });
    });
    </script>
    <style>
    .sdb-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; }
    .sdb-progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdb-progress { background: #4CAF50; height: 100%; transition: width 0.3s; }
    .sdb-form input { padding: 10px; margin: 10px; width: 120px; }
    .sdb-paypal-btn { background: #0070ba; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; }
    .sdb-paypal-btn:hover { background: #005ea6; }
    .sdb-suggestion { font-size: 0.9em; color: #666; }
    </style>
    <?php
}
add_action('wp_footer', 'sdb_add_inline_scripts');