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

if (!defined('ABSPATH')) {
    exit;
}

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
        add_shortcode('donation_goal', array($this, 'donation_goal_shortcode'));
        add_action('wp_ajax_sdb_donate', array($this, 'ajax_donate'));
        add_action('wp_ajax_nopriv_sdb_donate', array($this, 'ajax_donate'));
    }

    public function init() {
        if (get_option('sdb_paypal_email')) {
            // Plugin active
        }
        register_setting('sdb_options', 'sdb_options', array($this, 'sanitize_settings'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function donation_goal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => 1000,
            'current' => 250,
            'label' => 'Help reach our goal!',
            'button_text' => 'Donate Now'
        ), $atts);

        $percent = min(100, ($atts['current'] / $atts['goal']) * 100);
        $paypal_email = get_option('sdb_paypal_email');

        ob_start();
        ?>
        <div class="sdb-container">
            <h3><?php echo esc_html($atts['label']); ?></h3>
            <div class="sdb-progress">
                <div class="sdb-bar" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p class="sdb-amount">$<?php echo $atts['current']; ?> / $<?php echo $atts['goal']; ?> (<?php echo round($percent); ?>%)</p>
            <?php if ($paypal_email): ?>
            <form action="https://www.paypal.com/donate" method="post" target="_top">
                <input type="hidden" name="hosted_button_id" value="<?php echo esc_attr(get_option('sdb_button_id', '')); ?>" />
                <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" />
                <img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" />
            </form>
            <?php else: ?>
            <p><a href="<?php echo admin_url('options-general.php?page=sdb-settings'); ?>" class="button">Setup PayPal</a></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_donate() {
        check_ajax_referer('sdb_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        // Log donation or integrate with service
        wp_die(json_encode(array('success' => true, 'message' => 'Thank you for your donation!')));
    }

    public function sanitize_settings($input) {
        $input['paypal_email'] = sanitize_email($input['paypal_email']);
        $input['button_id'] = sanitize_text_field($input['button_id']);
        return $input;
    }
}

// Admin menu
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', function() {
            ?>
            <div class="wrap">
                <h1>Smart Donation Booster Settings</h1>
                <form method="post" action="options.php">
                    <?php settings_fields('sdb_options'); ?>
                    <?php do_settings_sections('sdb_options'); ?>
                    <table class="form-table">
                        <tr>
                            <th>PayPal Email</th>
                            <td><input type="email" name="sdb_options[paypal_email]" value="<?php echo esc_attr(get_option('sdb_paypal_email')); ?>" /></td>
                        </tr>
                        <tr>
                            <th>PayPal Button ID</th>
                            <td><input type="text" name="sdb_options[button_id]" value="<?php echo esc_attr(get_option('sdb_button_id')); ?>" /> <p>Create at <a href="https://www.paypal.com/buttons" target="_blank">PayPal Buttons</a></p></td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
                <p><strong>Upgrade to Pro:</strong> Multiple goals, custom themes, analytics - <a href="https://example.com/pro">Get Pro ($29/year)</a></p>
            </div>
            <?php
        });
    });
}

SmartDonationBooster::get_instance();

// Inline CSS and JS for single file
add_action('wp_head', function() {
    echo '<style>
    .sdb-container { max-width: 400px; margin: 20px 0; text-align: center; }
    .sdb-progress { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdb-bar { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s; }
    .sdb-amount { font-weight: bold; color: #333; }
    </style>';
});

// Minimal JS
add_action('wp_footer', function() {
    if (!wp_script_is('sdb-script', 'enqueued')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.sdb-donate-btn').on('click', function(e) {
                e.preventDefault();
                var amount = prompt('Enter donation amount:');
                if (amount) {
                    $.post(sdb_ajax.ajax_url, {
                        action: 'sdb_donate',
                        nonce: sdb_ajax.nonce,
                        amount: amount
                    }, function(resp) {
                        alert(resp.message);
                    });
                }
            });
        });
        </script>
        <?php
    }
});