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
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('donation_goal', array($this, 'donation_goal_shortcode'));
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('sdb_goal_amount') === false) {
            update_option('sdb_goal_amount', 1000);
        }
        if (get_option('sdb_current_amount') === false) {
            update_option('sdb_current_amount', 0);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Donation Booster Settings', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sdb_submit'])) {
            update_option('sdb_goal_amount', floatval($_POST['goal_amount']));
            update_option('sdb_current_amount', floatval($_POST['current_amount']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo $goal; ?>" step="0.01" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="current_amount" value="<?php echo $current; ?>" step="0.01" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[donation_goal]</code></p>
        </div>
        <?php
    }

    public function donation_goal_shortcode($atts) {
        $atts = shortcode_atts(array('paypal_email' => get_option('sdb_paypal_email', '')), $atts);
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $percent = ($current / $goal) * 100;
        ob_start();
        ?>
        <div id="sdb-container" class="sdb-progress-container">
            <h3>Support Us! Goal: $<span id="sdb-goal"><?php echo number_format($goal, 2); ?></span></h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress-fill" style="width: <?php echo min(100, $percent); ?>%;"></div>
            </div>
            <p>Current: $<span id="sdb-current"><?php echo number_format($current, 2); ?></span> (<?php echo round($percent); ?>%)</p>
            <form id="sdb-donate-form" class="sdb-donate-form">
                <input type="number" id="sdb-amount" placeholder="Enter amount" step="0.01" min="1" required>
                <button type="submit">Donate via PayPal</button>
                <input type="hidden" name="action" value="sdb_donate">
                <?php wp_nonce_field('sdb_nonce', 'sdb_nonce'); ?>
            </form>
            <p id="sdb-message"></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#sdb-donate-form').on('submit', function(e) {
                e.preventDefault();
                var amount = $('#sdb-amount').val();
                $.post(sdb_ajax.ajax_url, {
                    action: 'sdb_donate',
                    amount: amount,
                    nonce: sdb_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#sdb-current').text(response.data.current);
                        $('.sdb-progress-fill').css('width', response.data.percent + '%');
                        $('#sdb-message').html('<strong>Thank you! Redirecting to PayPal...</strong>');
                        setTimeout(function() {
                            window.location.href = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=<?php echo $atts['paypal_email']; ?>&amount=' + amount + '&item_name=Donation';
                        }, 1500);
                    } else {
                        $('#sdb-message').html('<em>Error: ' + response.data + '</em>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        if (!current_user_can('read')) {
            wp_send_json_error('Unauthorized');
        }
        $amount = floatval($_POST['amount']);
        if ($amount < 1) {
            wp_send_json_error('Minimum $1');
        }
        $current = get_option('sdb_current_amount', 0) + $amount;
        $goal = get_option('sdb_goal_amount', 1000);
        update_option('sdb_current_amount', $current);
        $percent = min(100, ($current / $goal) * 100);
        wp_send_json_success(array('current' => number_format($current, 2), 'percent' => $percent));
    }
}

SmartDonationBooster::get_instance();

/* CSS and JS files would be enqueued, but for single-file, inline them */
function sdb_inline_assets() {
    ?>
    <style>
    #sdb-container { max-width: 400px; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
    .sdb-progress-bar { width: 100%; height: 20px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdb-progress-fill { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.5s; }
    .sdb-donate-form { display: flex; gap: 10px; margin-top: 15px; }
    #sdb-amount { flex: 1; padding: 8px; }
    #sdb-donate-form button { padding: 8px 16px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer; }
    #sdb-donate-form button:hover { background: #005a87; }
    #sdb-message { margin-top: 10px; font-weight: bold; }
    </style>
    <script>jQuery(document).ready(function($){/* JS already in shortcode */});</script>
    <?php
}
add_action('wp_head', 'sdb_inline_assets');