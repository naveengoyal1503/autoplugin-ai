/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with progress bars, PayPal buttons, and goal tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donation-booster
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
        add_action('wp_ajax_sdb_donate', array($this, 'handle_donation'));
        add_shortcode('sdb_donation', array($this, 'donation_shortcode'));
        add_shortcode('sdb_progress', array($this, 'progress_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-donation-booster', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->options = get_option('sdb_options', array(
            'paypal_email' => '',
            'goal_amount' => 1000,
            'current_amount' => 0,
            'title' => 'Support Our Work',
            'description' => 'Your donation helps us create more content!',
            'button_text' => 'Donate Now',
            'pro' => false
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdb-script', plugin_dir_url(__FILE__) . 'sdb-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdb-style', plugin_dir_url(__FILE__) . 'sdb-style.css', array(), '1.0.0');
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->options = array(
                'paypal_email' => sanitize_email($_POST['paypal_email']),
                'goal_amount' => floatval($_POST['goal_amount']),
                'current_amount' => floatval($_POST['current_amount']),
                'title' => sanitize_text_field($_POST['title']),
                'description' => sanitize_textarea_field($_POST['description']),
                'button_text' => sanitize_text_field($_POST['button_text'])
            );
            update_option('sdb_options', $this->options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr><th>PayPal Email</th><td><input type="email" name="paypal_email" value="<?php echo esc_attr($this->options['paypal_email']); ?>" /></td></tr>
                    <tr><th>Goal Amount</th><td><input type="number" step="0.01" name="goal_amount" value="<?php echo esc_attr($this->options['goal_amount']); ?>" /></td></tr>
                    <tr><th>Current Amount</th><td><input type="number" step="0.01" name="current_amount" value="<?php echo esc_attr($this->options['current_amount']); ?>" /></td></tr>
                    <tr><th>Title</th><td><input type="text" name="title" value="<?php echo esc_attr($this->options['title']); ?>" /></td></tr>
                    <tr><th>Description</th><td><textarea name="description"><?php echo esc_textarea($this->options['description']); ?></textarea></td></tr>
                    <tr><th>Button Text</th><td><input type="text" name="button_text" value="<?php echo esc_attr($this->options['button_text']); ?>" /></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcodes:</strong> <code>[sdb_donation]</code> <code>[sdb_progress]</code></p>
            <?php if (!$this->options['pro']) : ?>
            <p><a href="#" style="color: #0073aa;">Upgrade to Pro for analytics & more ($29/year)</a></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts);
        $paypal_url = 'https://www.paypal.com/donate?hosted_button_id=DUMMY&email=' . urlencode($this->options['paypal_email']);
        ob_start();
        ?>
        <div class="sdb-donation">
            <h3><?php echo esc_html($this->options['title']); ?></h3>
            <p><?php echo esc_html($this->options['description']); ?></p>
            <a href="<?php echo esc_url($paypal_url); ?>" class="sdb-button" target="_blank"><?php echo esc_html($this->options['button_text']); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function progress_shortcode($atts) {
        $progress = min(100, ($this->options['current_amount'] / $this->options['goal_amount']) * 100);
        ob_start();
        ?>
        <div class="sdb-progress">
            <div class="sdb-progress-bar" style="width: <?php echo $progress; ?>%;"></div>
            <span><?php echo number_format_i18n($this->options['current_amount']); ?> / <?php echo number_format_i18n($this->options['goal_amount']); ?> (<?php echo round($progress); ?>%)</span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdb_nonce', 'nonce');
        // Simulate donation update (in Pro, integrate real webhook)
        $amount = floatval($_POST['amount']);
        $this->options['current_amount'] += $amount;
        update_option('sdb_options', $this->options);
        wp_send_json_success(array('new_amount' => $this->options['current_amount'], 'progress' => min(100, ($this->options['current_amount'] / $this->options['goal_amount']) * 100)));
    }

    public function activate() {
        add_option('sdb_options', array(
            'paypal_email' => get_option('admin_email'),
            'goal_amount' => 1000,
            'current_amount' => 0,
            'title' => 'Support Our Work',
            'description' => 'Your donation helps us create more content!',
            'button_text' => 'Donate Now',
            'pro' => false
        ));
    }
}

SmartDonationBooster::get_instance();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
    .sdb-donation { text-align: center; padding: 20px; border: 2px dashed #0073aa; margin: 20px 0; background: #f9f9f9; }
    .sdb-button { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .sdb-button:hover { background: #005a87; }
    .sdb-progress { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdb-progress-bar { height: 100%; background: #0073aa; transition: width 0.3s; }
    .sdb-progress span { display: block; text-align: center; margin-top: 5px; }
    </style>';
});

// Note: Create empty sdb-script.js and sdb-style.css files in plugin folder for enqueue to work without errors.