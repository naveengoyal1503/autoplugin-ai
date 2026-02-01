/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Donation Booster
 * Plugin URI: https://example.com/smart-donation-booster
 * Description: Boost donations with customizable buttons, goals, and PayPal integration.
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
        add_shortcode('sdb_donate', array($this, 'donate_shortcode'));
        add_shortcode('sdb_goal', array($this, 'goal_shortcode'));
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
        wp_localize_script('sdb-script', 'sdb_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdb_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donation Booster', 'Donation Booster', 'manage_options', 'sdb-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sdb_settings', 'sdb_options');
        add_settings_section('sdb_main', 'Main Settings', null, 'sdb');
        add_settings_field('sdb_paypal_email', 'PayPal Email', array($this, 'paypal_field'), 'sdb', 'sdb_main');
        add_settings_field('sdb_goal_amount', 'Donation Goal ($)', array($this, 'goal_field'), 'sdb', 'sdb_main');
        add_settings_field('sdb_button_text', 'Button Text', array($this, 'button_text_field'), 'sdb', 'sdb_main');
    }

    public function paypal_field() {
        $options = get_option('sdb_options');
        echo '<input type="email" name="sdb_options[paypal_email]" value="' . esc_attr($options['paypal_email'] ?? '') . '" />';
    }

    public function goal_field() {
        $options = get_option('sdb_options');
        echo '<input type="number" name="sdb_options[goal_amount]" value="' . esc_attr($options['goal_amount'] ?? '100') . '" step="0.01" />';
    }

    public function button_text_field() {
        $options = get_option('sdb_options');
        echo '<input type="text" name="sdb_options[button_text]" value="' . esc_attr($options['button_text'] ?? 'Donate Now') . '" />';
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

    public function donate_shortcode($atts) {
        $options = get_option('sdb_options');
        $amounts = array('5', '10', '25', '50', '100');
        $output = '<div class="sdb-donate-container">';
        $output .= '<h3>Support This Site</h3>';
        foreach ($amounts as $amt) {
            $output .= '<button class="sdb-donate-btn" data-amount="' . $amt . '">' . $options['button_text'] ?? 'Donate $' . $amt . '</button>';
        }
        $output .= '<form class="sdb-paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">';
        $output .= '<input type="hidden" name="cmd" value="_s-xclick">';
        $output .= '<input type="hidden" name="hosted_button_id" value="">';
        $output .= '<input type="hidden" name="business" value="' . esc_attr($options['paypal_email'] ?? '') . '">';
        $output .= '<input type="hidden" name="currency_code" value="USD">';
        $output .= '<input type="hidden" name="amount" id="sdb-amount" value="">';
        $output .= '<input type="submit" value="Pay with PayPal">';
        $output .= '</form></div>';
        return $output;
    }

    public function goal_shortcode($atts) {
        $options = get_option('sdb_options');
        $goal = floatval($options['goal_amount'] ?? 100);
        $donated = get_option('sdb_total_donated', 0);
        $percent = min(100, ($donated / $goal) * 100);
        $output = '<div class="sdb-goal-container">';
        $output .= '<p>Goal: $' . number_format($goal) . ' | Raised: $' . number_format($donated) . ' (' . round($percent) . '%)</p>';
        $output .= '<div class="sdb-progress-bar"><div class="sdb-progress-fill" style="width: ' . $percent . '%;"></div></div>';
        $output .= '</div>';
        return $output;
    }

    public function activate() {
        add_option('sdb_options', array('goal_amount' => 100, 'button_text' => 'Donate Now'));
    }
}

SmartDonationBooster::get_instance();

add_action('wp_ajax_sdb_update_donated', 'sdb_update_donated');
add_action('wp_ajax_nopriv_sdb_update_donated', 'sdb_update_donated');

function sdb_update_donated() {
    check_ajax_referer('sdb_nonce', 'nonce');
    $amount = floatval($_POST['amount']);
    $total = get_option('sdb_total_donated', 0) + $amount;
    update_option('sdb_total_donated', $total);
    wp_send_json_success(array('total' => $total));
}

// Inline scripts and styles
add_action('wp_head', 'sdb_inline_styles');
function sdb_inline_styles() {
    echo '<style>
.sdb-donate-container { text-align: center; padding: 20px; background: #f9f9f9; border-radius: 8px; }
.sdb-donate-btn { background: #007cba; color: white; border: none; padding: 10px 20px; margin: 5px; border-radius: 5px; cursor: pointer; }
.sdb-donate-btn:hover { background: #005a87; }
.sdb-paypal-form { margin-top: 10px; }
.sdb-goal-container { text-align: center; padding: 20px; }
.sdb-progress-bar { background: #ddd; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.sdb-progress-fill { background: #28a745; height: 100%; transition: width 0.3s; }
    </style>';
}

add_action('wp_footer', 'sdb_inline_script');
function sdb_inline_script() {
    ?>
    <script>jQuery(document).ready(function($) {
        $('.sdb-donate-btn').click(function() {
            var amount = $(this).data('amount');
            $('#sdb-amount').val(amount);
            $('.sdb-paypal-form').submit();
        });
    });</script>
    <?php
}