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
        add_action('wp_ajax_sdb_donate', array($this, 'ajax_donate'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'ajax_donate'));
    }

    public function init() {
        if (get_option('sdb_goal_amount')) {
            // Init logic
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
            update_option('sdb_goal_amount', sanitize_text_field($_POST['goal_amount']));
            update_option('sdb_current_amount', sanitize_text_field($_POST['current_amount']));
            update_option('sdb_paypal_email', sanitize_email($_POST['paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $paypal = get_option('sdb_paypal_email', '');
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($goal); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Current Amount</th>
                        <td><input type="number" name="current_amount" value="<?php echo esc_attr($current); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function donation_goal_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $goal = get_option('sdb_goal_amount', 1000);
        $current = get_option('sdb_current_amount', 0);
        $percent = ($goal > 0) ? min(100, ($current / $goal) * 100) : 0;
        $paypal = get_option('sdb_paypal_email', '');

        ob_start();
        ?>
        <div class="sdb-container" id="sdb-<?php echo esc_attr($atts['id']); ?>">
            <h3>Support Us! <?php echo esc_html(get_option('blogname')); ?></h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p class="sdb-amounts">$
                <span id="sdb-current-<?php echo esc_attr($atts['id']); ?>"><?php echo number_format($current, 0); ?></span>
                / $
                <span><?php echo number_format($goal, 0); ?></span>
            </p>
            <?php if ($paypal): ?>
            <a href="https://www.paypal.com/donate?hosted_button_id=TEST&business=<?php echo urlencode($paypal); ?>&item_name=Support <?php echo esc_attr(get_option('blogname')); ?>&amount=10&currency_code=USD" class="sdb-donate-btn" data-amount="10">Donate $10</a>
            <a href="https://www.paypal.com/donate?hosted_button_id=TEST&business=<?php echo urlencode($paypal); ?>&item_name=Support <?php echo esc_attr(get_option('blogname')); ?>&amount=25&currency_code=USD" class="sdb-donate-btn" data-amount="25">Donate $25</a>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.sdb-donate-btn').click(function(e) {
                e.preventDefault();
                var amount = $(this).data('amount');
                $.post(sdb_ajax.ajax_url, {
                    action: 'sdb_donate',
                    amount: amount,
                    nonce: sdb_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#sdb-current-<?php echo esc_js($atts['id']); ?>').text(response.data.new_amount);
                        $('.sdb-progress').css('width', response.data.percent + '%');
                        alert('Thank you for your donation!');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_donate() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        $current = get_option('sdb_current_amount', 0) + $amount;
        $goal = get_option('sdb_goal_amount', 1000);
        $percent = ($goal > 0) ? min(100, ($current / $goal) * 100) : 0;
        update_option('sdb_current_amount', $current);
        wp_send_json_success(array('new_amount' => number_format($current, 0), 'percent' => $percent));
    }
}

SmartDonationBooster::get_instance();

/* Sample CSS - Save as sdb.css in plugin folder */
/*
.sdb-container { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
.sdb-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdb-progress { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.5s; }
.sdb-donate-btn { display: inline-block; margin: 5px; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; }
.sdb-donate-btn:hover { background: #005a87; }
.sdb-amounts { font-size: 1.2em; font-weight: bold; }
*/

/* Sample JS - Save as sdb.js in plugin folder */
/*
// JS is already inlined in shortcode for single-file simplicity
*/