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
    exit; // Exit if accessed directly.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('donation_goal', array($this, 'donation_goal_shortcode'));
        add_shortcode('donation_button', array($this, 'donation_button_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Donation Booster Settings', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sdb_settings', 'sdb_options');
        add_settings_section('sdb_main', 'Main Settings', null, 'sdb');
        add_settings_field('goal_amount', 'Donation Goal ($)', array($this, 'goal_amount_field'), 'sdb', 'sdb_main');
        add_settings_field('paypal_email', 'PayPal Email', array($this, 'paypal_email_field'), 'sdb', 'sdb_main');
        add_settings_field('current_amount', 'Current Amount ($)', array($this, 'current_amount_field'), 'sdb', 'sdb_main');
    }

    public function goal_amount_field() {
        $options = get_option('sdb_options');
        echo '<input type="number" name="sdb_options[goal_amount]" value="' . esc_attr($options['goal_amount'] ?? 1000) . '" />';
    }

    public function paypal_email_field() {
        $options = get_option('sdb_options');
        echo '<input type="email" name="sdb_options[paypal_email]" value="' . esc_attr($options['paypal_email'] ?? '') . '" />';
    }

    public function current_amount_field() {
        $options = get_option('sdb_options');
        echo '<input type="number" step="0.01" name="sdb_options[current_amount]" value="' . esc_attr($options['current_amount'] ?? 0) . '" />';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sdb_settings');
                do_settings_sections('sdb');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function donation_goal_shortcode($atts) {
        $options = get_option('sdb_options');
        $goal = floatval($options['goal_amount'] ?? 1000);
        $current = floatval($options['current_amount'] ?? 0);
        $percent = min(100, ($current / $goal) * 100);
        ob_start();
        ?>
        <div class="sdb-goal-container">
            <h3>Help Us Reach Our Goal!</h3>
            <div class="sdb-progress-bar">
                <div class="sdb-progress" style="width: <?php echo $percent; ?>%;"></div>
            </div>
            <p>$<?php echo number_format($current); ?> / $<?php echo number_format($goal); ?> (<?php echo round($percent); ?>%)</p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function donation_button_shortcode($atts) {
        $options = get_option('sdb_options');
        $paypal_email = $options['paypal_email'] ?? '';
        if (empty($paypal_email)) return 'PayPal email not set in settings.';
        $atts = shortcode_atts(array('amount' => '10'), $atts);
        $amount = floatval($atts['amount']);
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) . '&amount=' . $amount . '&item_name=Donation&currency_code=USD&return=' . urlencode(home_url());
        ob_start();
        ?>
        <a href="<?php echo esc_url($paypal_url); ?>" class="sdb-donate-btn" target="_blank">Donate $<?php echo $amount; ?></a>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sdb_options', array(
            'goal_amount' => 1000,
            'current_amount' => 0,
            'paypal_email' => ''
        ));
    }
}

SmartDonationBooster::get_instance();

// Inline JS and CSS for self-contained plugin
add_action('wp_head', function() {
    $options = get_option('sdb_options');
    $current = floatval($options['current_amount'] ?? 0);
    ?>
    <style>
    .sdb-goal-container { background: #f9f9f9; padding: 20px; border-radius: 8px; text-align: center; max-width: 400px; margin: 20px auto; }
    .sdb-progress-bar { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdb-progress { height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049); transition: width 0.3s ease; }
    .sdb-donate-btn { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .sdb-donate-btn:hover { background: #005a87; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.sdb-donate-btn').click(function() {
            // Optional: Track clicks with analytics
            console.log('Donation button clicked');
        });
    });
    </script>
    <?php
});

// Simulate donation update (in real plugin, use AJAX form)
add_action('init', function() {
    if (isset($_GET['sdb_update']) && current_user_can('manage_options')) {
        $amount = floatval($_GET['amount'] ?? 0);
        $options = get_option('sdb_options');
        $options['current_amount'] = floatval($options['current_amount'] ?? 0) + $amount;
        update_option('sdb_options', $options);
        wp_redirect(remove_query_arg(array('sdb_update', 'amount')));
        exit;
    }
});