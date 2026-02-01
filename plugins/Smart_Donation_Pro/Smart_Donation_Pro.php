/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with customizable buttons, goals, and progress bars.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_smart_donation_submit', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_smart_donation_submit', array($this, 'handle_donation'));
    }

    public function init() {
        if (get_option('smart_donation_enabled', 'yes') !== 'yes') {
            return;
        }
        // Auto-insert in posts if enabled
        if (get_option('smart_donation_auto_insert_footer', 'no') === 'yes') {
            add_filter('the_content', array($this, 'auto_insert_donation'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-donation', plugin_dir_url(__FILE__) . 'smart-donation.js', array('jquery'), '1.0.0', true);
        wp_localize_script('smart-donation', 'smartDonationAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smart_donation_nonce')
        ));
        wp_enqueue_style('smart-donation', plugin_dir_url(__FILE__) . 'smart-donation.css', array(), '1.0.0');
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'goal' => '100',
            'title' => 'Support This Site',
            'button_text' => 'Donate Now',
            'amounts' => '5,10,25,50',
            'paypal_email' => get_option('smart_donation_paypal_email', ''),
        ), $atts);

        $amounts = explode(',', $atts['amounts']);
        $progress = $this->get_donation_progress($atts['goal']);

        ob_start();
        ?>
        <div class="smart-donation-widget" data-goal="<?php echo esc_attr($atts['goal']); ?>" data-paypal="<?php echo esc_attr($atts['paypal_email']); ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="donation-progress">
                <div class="progress-bar" style="width: <?php echo $progress; ?>%;"></div>
            </div>
            <p>Goal: $<span class="current"><?php echo $this->get_current_donations($atts['goal']); ?></span> / $<?php echo esc_html($atts['goal']); ?></p>
            <div class="donation-amounts">
                <?php foreach ($amounts as $amount): 
                    $amount = trim($amount);
                ?>
                <button class="amount-btn" data-amount="<?php echo esc_attr($amount); ?>">$<?php echo esc_html($amount); ?></button>
                <?php endforeach; ?>
            </div>
            <button class="donate-btn" id="donate-btn"><?php echo esc_html($atts['button_text']); ?></button>
            <div class="donation-form hidden">
                <input type="number" id="custom-amount" placeholder="Custom amount" min="1">
                <input type="email" id="donor-email" placeholder="Your email">
                <button id="submit-donation">Send to PayPal</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_donation_progress($goal) {
        $current = get_option('smart_donation_total', 0);
        return min(100, ($current / $goal) * 100);
    }

    private function get_current_donations($goal) {
        return get_option('smart_donation_total', 0);
    }

    public function auto_insert_donation($content) {
        if (is_single() && in_the_loop() && is_main_query()) {
            $content .= do_shortcode('[smart_donation]');
        }
        return $content;
    }

    public function handle_donation() {
        check_ajax_referer('smart_donation_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $email = sanitize_email($_POST['email']);

        if ($amount > 0 && $email) {
            $current = (float) get_option('smart_donation_total', 0);
            update_option('smart_donation_total', $current + (float)$amount);

            $paypal_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . get_option('smart_donation_paypal_email') . '&amount=' . $amount . '&item_name=Donation&email=' . $email;
            wp_send_json_success(array('redirect' => $paypal_url));
        }
        wp_send_json_error();
    }
}

SmartDonationPro::get_instance();

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'smart-donation', 'smart_donation_admin_page');
    });

    function smart_donation_admin_page() {
        if (isset($_POST['paypal_email'])) {
            update_option('smart_donation_paypal_email', sanitize_email($_POST['paypal_email']));
            update_option('smart_donation_auto_insert_footer', sanitize_text_field($_POST['auto_insert']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal = get_option('smart_donation_paypal_email', '');
        $auto = get_option('smart_donation_auto_insert_footer', 'no');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="paypal_email" value="<?php echo esc_attr($paypal); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Auto-insert in post footer</th>
                        <td><input type="checkbox" name="auto_insert" value="yes" <?php checked($auto, 'yes'); ?>></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Current total donations: $<strong><?php echo get_option('smart_donation_total', 0); ?></strong></p>
            <p>Use shortcode: <code>[smart_donation goal="100" title="Support Us"]</code></p>
        </div>
        <?php
    }
}

// Inline styles and scripts for self-contained
add_action('wp_head', function() {
    echo '<style>
.smart-donation-widget { border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
.donation-progress { background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
.progress-bar { height: 100%; background: #28a745; transition: width 0.3s; }
.donation-amounts { margin: 10px 0; }
.amount-btn { margin: 5px; padding: 10px 15px; background: #007cba; color: white; border: none; border-radius: 5px; cursor: pointer; }
.amount-btn:hover { background: #005a87; }
.donate-btn { background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
.donation-form { margin-top: 15px; }
.donation-form input { width: 100%; padding: 10px; margin: 5px 0; box-sizing: border-box; }
.donation-form.hidden { display: none; }
@media (max-width: 768px) { .smart-donation-widget { margin: 10px; } }
    </style>';
});

add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.amount-btn, #donate-btn').click(function() {
            $(this).closest('.smart-donation-widget').find('.donation-form').toggleClass('hidden');
            if ($(this).hasClass('amount-btn')) {
                $('#custom-amount').val($(this).data('amount'));
            }
        });

        $('.smart-donation-widget').on('click', '#submit-donation', function(e) {
            e.preventDefault();
            var $widget = $(this).closest('.smart-donation-widget');
            var amount = $('#custom-amount', $widget).val();
            var email = $('#donor-email', $widget).val();
            var paypal = $widget.data('paypal');

            $.post(smartDonationAjax.ajaxurl, {
                action: 'smart_donation_submit',
                amount: amount,
                email: email,
                nonce: smartDonationAjax.nonce
            }, function(response) {
                if (response.success) {
                    window.location = response.data.redirect;
                } else {
                    alert('Error processing donation.');
                }
            });
        });
    });
    </script>
    <?php
});