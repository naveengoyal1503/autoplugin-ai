/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Easily add customizable donation buttons and progress bars to monetize your WordPress site.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartDonationPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('sdp_donate', array($this, 'donation_shortcode'));
        add_shortcode('sdp_progress', array($this, 'progress_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_paypal_email')) {
            add_filter('script_loader_tag', array($this, 'add_defer_attribute'), 10, 2);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '10',
            'label' => 'Donate Now',
            'currency' => 'USD',
            'goal' => '1000'
        ), $atts);

        $paypal_email = get_option('sdp_paypal_email', '');
        if (!$paypal_email) {
            return '<p>Donation settings not configured. Please set PayPal email in settings.</p>';
        }

        ob_start();
        ?>
        <div class="sdp-donate-container">
            <button class="sdp-donate-btn" data-amount="<?php echo esc_attr($atts['amount']); ?>" data-currency="<?php echo esc_attr($atts['currency']); ?>"><?php echo esc_html($atts['label']); ?></button>
            <div id="sdp-progress-<?php echo uniqid(); ?>" class="sdp-progress" data-goal="<?php echo esc_attr($atts['goal']); ?>"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.sdp-donate-btn').click(function() {
                var amount = $(this).data('amount');
                var currency = $(this).data('currency');
                var form = $('<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">' +
                    '<input type="hidden" name="cmd" value="_s-xclick">' +
                    '<input type="hidden" name="hosted_button_id" value="YOUR_BUTTON_ID">' + // Replace with actual button or use custom
                    '<input type="hidden" name="amount" value="' + amount + '">' +
                    '<input type="hidden" name="currency_code" value="' + currency + '">' +
                    '<input type="hidden" name="business" value="<?php echo esc_js(get_option('sdp_paypal_email')); ?>">' +
                    '</form>');
                $('body').append(form);
                form.submit();
                form.remove();
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function progress_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default', 'goal' => '1000'), $atts);
        $progress = get_option('sdp_progress_' . $atts['id'], 0);
        $percent = ($progress / $atts['goal']) * 100;
        return '<div class="sdp-progress-bar"><div class="sdp-progress-fill" style="width: ' . min($percent, 100) . '%;"></div><span>$' . $progress . ' / $' . $atts['goal'] . '</span></div>';
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        $amount = sanitize_text_field($_POST['amount']);
        $campaign = sanitize_text_field($_POST['campaign']);
        $current = (float) get_option('sdp_progress_' . $campaign, 0);
        update_option('sdp_progress_' . $campaign, $current + (float) $amount);
        wp_send_json_success('Thank you for your donation!');
    }

    public function activate() {
        add_option('sdp_paypal_email', '');
    }

    public function add_defer_attribute($tag, $handle) {
        return str_replace(' src=', ' defer src=', $tag);
    }
}

new SmartDonationPro();

// Admin settings page
add_action('admin_menu', function() {
    add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', function() {
        if (isset($_POST['sdp_paypal_email'])) {
            update_option('sdp_paypal_email', sanitize_email($_POST['sdp_paypal_email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_paypal_email" value="<?php echo esc_attr(get_option('sdp_paypal_email')); ?>" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcodes: <code>[sdp_donate amount="10" label="Buy Me Coffee" goal="1000"]</code> or <code>[sdp_progress id="goal1" goal="1000"]</code></p>
        </div>
        <?php
    });
});

// Inline CSS and JS for self-contained
add_action('wp_head', function() {
    echo '<style>
    .sdp-donate-container { text-align: center; margin: 20px 0; }
    .sdp-donate-btn { background: #007cba; color: white; border: none; padding: 15px 30px; font-size: 16px; border-radius: 5px; cursor: pointer; }
    .sdp-donate-btn:hover { background: #005a87; }
    .sdp-progress-bar { background: #f0f0f0; height: 20px; border-radius: 10px; overflow: hidden; margin: 10px 0; }
    .sdp-progress-fill { height: 100%; background: #007cba; transition: width 0.3s; }
    </style>';
    echo '<script>jQuery(document).ready(function($){ /* AJAX progress update logic */ });</script>';
});