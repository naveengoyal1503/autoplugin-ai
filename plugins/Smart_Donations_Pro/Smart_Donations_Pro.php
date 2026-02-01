/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Boost your WordPress site revenue with easy donation buttons, progress goals, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-donations-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
    }

    public function init() {
        $this->options = get_option('sdp_options', array(
            'goal' => 1000,
            'title' => 'Support Us!',
            'button_text' => 'Donate Now',
            'amounts' => '5,10,25,50',
            'paypal_email' => ''
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Donations Pro', 'Donations', 'manage_options', 'sdp-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sdp_options', $_POST['sdp_options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = $this->options;
        ?>
        <div class="wrap">
            <h1>Smart Donations Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Donation Goal</th>
                        <td><input type="number" name="sdp_options[goal]" value="<?php echo esc_attr($options['goal']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Title</th>
                        <td><input type="text" name="sdp_options[title]" value="<?php echo esc_attr($options['title']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Button Text</th>
                        <td><input type="text" name="sdp_options[button_text]" value="<?php echo esc_attr($options['button_text']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Suggested Amounts (comma-separated)</th>
                        <td><input type="text" name="sdp_options[amounts]" value="<?php echo esc_attr($options['amounts']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_options[paypal_email]" value="<?php echo esc_attr($options['paypal_email']); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[smart_donation]</code></p>
        </div>
        <?php
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts, 'smart_donation');
        $options = $this->options;
        $amounts = explode(',', $options['amounts']);
        $progress = get_option('sdp_total_donated', 0);

        ob_start();
        ?>
        <div id="sdp-container" class="sdp-widget">
            <h3><?php echo esc_html($options['title']); ?></h3>
            <div class="sdp-progress">
                <div class="sdp-progress-bar" style="width: <?php echo min(100, ($progress / $options['goal']) * 100); ?>%;"></div>
            </div>
            <p>$<?php echo number_format($progress); ?> / $<?php echo number_format($options['goal']); ?> raised</p>
            <div class="sdp-buttons">
                <?php foreach ($amounts as $amount) : $amount = trim($amount); ?>
                    <button class="sdp-amount-btn" data-amount="<?php echo esc_attr($amount); ?>">$<?php echo esc_html($amount); ?></button>
                <?php endforeach; ?>
                <input type="number" id="sdp-custom-amount" placeholder="Custom amount" />
                <button id="sdp-donate-btn" class="sdp-donate-btn"><?php echo esc_html($options['button_text']); ?></button>
            </div>
            <div id="sdp-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = floatval($_POST['amount']);
        if ($amount <= 0) {
            wp_die('Invalid amount');
        }

        $total = get_option('sdp_total_donated', 0) + $amount;
        update_option('sdp_total_donated', $total);

        $paypal_email = $this->options['paypal_email'];
        if ($paypal_email) {
            $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode($paypal_email) . '&amount=' . $amount . '&item_name=Donation to site';
            wp_send_json_success(array('redirect' => $paypal_url));
        } else {
            wp_send_json_success('Thank you for your donation of $' . $amount . '! Total raised: $' . number_format($total));
        }
    }
}

new SmartDonationsPro();

// Inline styles
add_action('wp_head', function() {
    echo '<style>
    .sdp-widget { max-width: 400px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
    .sdp-progress { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdp-progress-bar { height: 100%; background: #4CAF50; transition: width 0.3s; }
    .sdp-buttons { display: flex; flex-wrap: wrap; gap: 10px; }
    .sdp-amount-btn, .sdp-donate-btn { padding: 10px 15px; border: none; background: #0073aa; color: white; border-radius: 5px; cursor: pointer; }
    .sdp-amount-btn:hover, .sdp-donate-btn:hover { background: #005a87; }
    #sdp-custom-amount { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    #sdp-message { margin-top: 10px; padding: 10px; background: #d4edda; border-radius: 5px; }
    </style>';
});

// Minimal JS
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sdp-amount-btn').click(function() {
            $('#sdp-custom-amount').val($(this).data('amount'));
        });
        $('#sdp-donate-btn').click(function() {
            var amount = parseFloat($('#sdp-custom-amount').val()) || 0;
            if (amount > 0) {
                $.post(sdp_ajax.ajax_url, {
                    action: 'sdp_donate',
                    amount: amount,
                    nonce: sdp_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            $('#sdp-message').html(response.data).show();
                            location.reload();
                        }
                    }
                });
            }
        });
    });
    </script>
    <?php
});