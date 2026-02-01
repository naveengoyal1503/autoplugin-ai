/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Easily add donation buttons and fundraising goals to monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartDonationsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_shortcode('smart_goal', array($this, 'goal_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('smart_donations_paypal') === false) {
            add_option('smart_donations_paypal', '');
        }
        if (get_option('smart_donations_stripe_pk') === false) {
            add_option('smart_donations_stripe_pk', '');
        }
        if (get_option('smart_donations_goal') === false) {
            add_option('smart_donations_goal', 1000);
        }
        if (get_option('smart_donations_current') === false) {
            add_option('smart_donations_current', 0);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-donations-js', plugin_dir_url(__FILE__) . 'donations.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-donations-css', plugin_dir_url(__FILE__) . 'donations.css', array(), '1.0.0');
        wp_localize_script('smart-donations-js', 'smartDonations', array(
            'stripe_pk' => get_option('smart_donations_stripe_pk'),
            'goal' => get_option('smart_donations_goal'),
            'current' => get_option('smart_donations_current'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donations')
        ));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'currency' => 'USD',
            'label' => 'Donate Now'
        ), $atts);

        $paypal = get_option('smart_donations_paypal');
        $output = '<div class="smart-donation-btn" data-amount="' . esc_attr($atts['amount']) . '" data-currency="' . esc_attr($atts['currency']) . '">';
        $output .= '<button class="donate-btn">' . esc_html($atts['label']) . '</button>';
        if ($paypal) {
            $output .= '<form target="paypal" action="https://www.paypal.com/cgi-bin/webscr" method="post" style="display:none;">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="' . esc_attr($paypal) . '">
                <input type="hidden" name="custom" value="' . esc_attr($atts['amount']) . '">
                <input type="hidden" name="amount" value="' . esc_attr($atts['amount']) . '">
                <input type="submit" class="paypal-submit">
            </form>';
        }
        $output .= '</div>';
        return $output;
    }

    public function goal_shortcode($atts) {
        $goal = get_option('smart_donations_goal');
        $current = get_option('smart_donations_current');
        $progress = ($current / $goal) * 100;
        return '<div class="smart-goal">
            <p>Current: $' . number_format($current) . ' / Goal: $' . number_format($goal) . '</p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: ' . $progress . '%;"></div>
            </div>
        </div>';
    }

    public function activate() {
        add_option('smart_donations_paypal', '');
        add_option('smart_donations_stripe_pk', '');
        add_option('smart_donations_goal', 1000);
        add_option('smart_donations_current', 0);
    }
}

new SmartDonationsPro();

// AJAX for updating donation total
add_action('wp_ajax_update_donation', 'smart_update_donation');
add_action('wp_ajax_nopriv_update_donation', 'smart_update_donation');
function smart_update_donation() {
    check_ajax_referer('smart_donations', 'nonce');
    $amount = floatval($_POST['amount']);
    $current = get_option('smart_donations_current');
    $new_current = $current + $amount;
    update_option('smart_donations_current', $new_current);
    wp_send_json_success(array('current' => $new_current));
}

// Admin menu
add_action('admin_menu', 'smart_donations_admin_menu');
function smart_donations_admin_menu() {
    add_options_page('Smart Donations Settings', 'Smart Donations', 'manage_options', 'smart-donations', 'smart_donations_settings_page');
}

function smart_donations_settings_page() {
    if (isset($_POST['submit'])) {
        update_option('smart_donations_paypal', sanitize_text_field($_POST['paypal']));
        update_option('smart_donations_stripe_pk', sanitize_text_field($_POST['stripe_pk']));
        update_option('smart_donations_goal', intval($_POST['goal']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $paypal = get_option('smart_donations_paypal');
    $stripe_pk = get_option('smart_donations_stripe_pk');
    $goal = get_option('smart_donations_goal');
    ?>
    <div class="wrap">
        <h1>Smart Donations Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>PayPal Button ID</th>
                    <td><input type="text" name="paypal" value="<?php echo esc_attr($paypal); ?>" class="regular-text" placeholder="Your PayPal hosted button ID"></td>
                </tr>
                <tr>
                    <th>Stripe Publishable Key</th>
                    <td><input type="text" name="stripe_pk" value="<?php echo esc_attr($stripe_pk); ?>" class="regular-text" placeholder="pk_live_..."></td>
                </tr>
                <tr>
                    <th>Fundraising Goal ($)</th>
                    <td><input type="number" name="goal" value="<?php echo esc_attr($goal); ?>"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>Shortcodes:</strong></p>
        <ul>
            <li>[smart_donation amount="25" currency="USD" label="Support Us"]</li>
            <li>[smart_goal]</li>
        </ul>
        <p><em>Pro version coming soon with recurring donations and analytics!</em></p>
    </div>
    <?php
}

// Inline CSS and JS for simplicity (self-contained)
add_action('wp_head', 'smart_donations_styles');
function smart_donations_styles() {
    echo '<style>
        .smart-donation-btn { text-align: center; margin: 20px 0; }
        .donate-btn { background: #0073aa; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .donate-btn:hover { background: #005a87; }
        .smart-goal { margin: 20px 0; }
        .progress-bar { background: #ddd; height: 20px; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #28a745; transition: width 0.3s; }
        #stripe-payment-element { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
    </style>';
}

add_action('wp_footer', 'smart_donations_js');
function smart_donations_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.donate-btn').on('click', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $form = $btn.siblings('form');
            if ($form.length) {
                $form.find('.paypal-submit').click();
            } else {
                // Stripe fallback (requires pk set)
                var amount = $btn.closest('.smart-donation-btn').data('amount');
                // Simplified Stripe intent creation would go here in pro
                alert('Pro version required for Stripe. Or update donation total: $' + amount);
                $.post(smartDonations.ajax_url, {
                    action: 'update_donation',
                    amount: amount,
                    nonce: smartDonations.nonce
                }, function(res) {
                    if (res.success) {
                        location.reload();
                    }
                });
            }
        });
    });
    </script>
    <?php
}
