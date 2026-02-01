/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Easily add customizable donation buttons, progress bars, and payment options to monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donations-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartDonationsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_shortcode('smart_donation_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-donations-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=TEST_CLIENT_ID&currency=USD', array(), '1.0', true);
        wp_enqueue_style('smart-donations', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function admin_menu() {
        add_options_page('Smart Donations', 'Smart Donations', 'manage_options', 'smart-donations', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sd_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('sd_goal_amount', floatval($_POST['goal_amount']));
            update_option('sd_goal_title', sanitize_text_field($_POST['goal_title']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option('sd_paypal_email', '');
        $goal_amount = get_option('sd_goal_amount', 1000);
        $goal_title = get_option('sd_goal_title', 'Monthly Goal');
        ?>
        <div class="wrap">
            <h1>Smart Donations Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal_email); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Goal Amount</th>
                        <td><input type="number" name="goal_amount" value="<?php echo esc_attr($goal_amount); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Goal Title</th>
                        <td><input type="text" name="goal_title" value="<?php echo esc_attr($goal_title); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_donations';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            donor_email varchar(100) NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now'
        ), $atts);
        $paypal_email = get_option('sd_paypal_email');
        if (!$paypal_email) {
            return '<p>Please set up PayPal email in settings.</p>';
        }
        ob_start();
        ?>
        <div id="smart-donation" class="sd-button" data-amount="<?php echo esc_attr($atts['amount']); ?>" data-email="<?php echo esc_attr($paypal_email); ?>">
            <button><?php echo esc_html($atts['label']); ?></button>
        </div>
        <script>
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '<?php echo esc_js($atts['amount']); ?>'
                        }
                    }]
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'action=sd_record_donation&amount=<?php echo esc_js($atts['amount']); ?>&order=' + details.id
                    });
                    alert('Thank you for your donation!');
                });
            }
        }).render('#smart-donation');
        </script>
        <?php
        return ob_get_clean();
    }

    public function goal_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_donations';
        $total = $wpdb->get_var("SELECT SUM(amount) FROM $table_name");
        $total = $total ? floatval($total) : 0;
        $goal = get_option('sd_goal_amount', 1000);
        $percent = min(100, ($total / $goal) * 100);
        $goal_title = get_option('sd_goal_title', 'Goal');
        ob_start();
        ?>
        <div class="sd-goal">
            <h3><?php echo esc_html($goal_title); ?></h3>
            <p>Raised: $<?php echo number_format($total, 2); ?> / $<?php echo number_format($goal, 2); ?></p>
            <div class="sd-progress">
                <div class="sd-bar" style="width: <?php echo $percent; ?>%;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

new SmartDonationsPro();

add_action('wp_ajax_sd_record_donation', 'sd_record_donation');
add_action('wp_ajax_nopriv_sd_record_donation', 'sd_record_donation');

function sd_record_donation() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'smart_donations';
    $wpdb->insert($table_name, array(
        'amount' => floatval($_POST['amount']),
        'donor_email' => 'paypal',
    ));
    wp_die();
}

/* CSS - Inline for single file */
function sd_add_styles() {
    echo '<style>
    .sd-button { margin: 20px 0; }
    .sd-button button { background: #007cba; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    .sd-button button:hover { background: #005a87; }
    .sd-goal { background: #f9f9f9; padding: 20px; border-radius: 10px; margin: 20px 0; }
    .sd-progress { background: #ddd; height: 20px; border-radius: 10px; overflow: hidden; }
    .sd-bar { height: 100%; background: #28a745; transition: width 0.3s; }
    </style>';
}
add_action('wp_head', 'sd_add_styles');