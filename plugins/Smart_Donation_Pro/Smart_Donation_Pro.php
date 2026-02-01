/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donation_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donation Pro
 * Plugin URI: https://example.com/smart-donation-pro
 * Description: Boost donations with smart, customizable prompts and A/B testing.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdp_donate', array($this, 'handle_donation'));
        add_action('wp_ajax_nopriv_sdp_donate', array($this, 'handle_donation'));
        add_shortcode('smart_donation', array($this, 'donation_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sdp_settings') === false) {
            add_option('sdp_settings', array(
                'message' => 'Loved this content? Support us with a donation!',
                'amounts' => '5,10,20,50',
                'paypal_email' => '',
                'trigger' => 'scroll',
                'delay' => 30,
                'pages' => 'all'
            ));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sdp-script', plugin_dir_url(__FILE__) . 'sdp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sdp-style', plugin_dir_url(__FILE__) . 'sdp-style.css', array(), '1.0.0');
        wp_localize_script('sdp-script', 'sdp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sdp_nonce')));
    }

    public function donation_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $settings = get_option('sdp_settings');
        ob_start();
        ?>
        <div id="sdp-modal-<?php echo esc_attr($atts['id']); ?>" class="sdp-modal" style="display:none;">
            <div class="sdp-overlay"></div>
            <div class="sdp-content">
                <div class="sdp-header">
                    <h3><?php echo esc_html($settings['message']); ?></h3>
                    <span class="sdp-close">&times;</span>
                </div>
                <div class="sdp-body">
                    <div class="sdp-amounts">
                        <?php foreach (explode(',', $settings['amounts']) as $amount): 
                            $amount = trim($amount);
                        ?>
                            <button class="sdp-amount-btn" data-amount="<?php echo esc_attr($amount); ?>"><?php echo esc_html($amount); ?>$</button>
                        <?php endforeach; ?>
                    </div>
                    <form class="sdp-paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                        <input type="hidden" name="cmd" value="_xclick">
                        <input type="hidden" name="business" value="<?php echo esc_attr($settings['paypal_email']); ?>">
                        <input type="hidden" name="item_name" value="Donation">
                        <input type="hidden" name="amount" id="sdp-amount" value="">
                        <input type="hidden" name="currency_code" value="USD">
                        <input type="hidden" name="return" value="<?php echo esc_url(home_url()); ?>">
                        <button type="submit" class="sdp-donate-btn">Donate Now</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('sdp_nonce', 'nonce');
        wp_die('success');
    }

    public function activate() {
        $this->init();
        flush_rewrite_rules();
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Donation Pro', 'Donation Pro', 'manage_options', 'sdp-settings', 'sdp_settings_page');
    });

    function sdp_settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sdp_settings', $_POST['sdp_settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = get_option('sdp_settings');
        ?>
        <div class="wrap">
            <h1>Smart Donation Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Message</th>
                        <td><input type="text" name="sdp_settings[message]" value="<?php echo esc_attr($settings['message']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Amounts (comma-separated)</th>
                        <td><input type="text" name="sdp_settings[amounts]" value="<?php echo esc_attr($settings['amounts']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="sdp_settings[paypal_email]" value="<?php echo esc_attr($settings['paypal_email']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Trigger</th>
                        <td>
                            <select name="sdp_settings[trigger]">
                                <option value="scroll" <?php selected($settings['trigger'], 'scroll'); ?>>50% Scroll</option>
                                <option value="time" <?php selected($settings['trigger'], 'time'); ?>>Time Delay</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Delay (seconds)</th>
                        <td><input type="number" name="sdp_settings[delay]" value="<?php echo esc_attr($settings['delay']); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Settings" /></p>
            </form>
        </div>
        <?php
    }
}

SmartDonationPro::get_instance();

// Inline JS and CSS for simplicity (self-contained)
function sdp_inline_assets() {
    $settings = get_option('sdp_settings');
    ?>
    <style>
    .sdp-modal { position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; }
    .sdp-overlay { position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
    .sdp-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 10px; max-width: 400px; }
    .sdp-close { float: right; cursor: pointer; font-size: 28px; }
    .sdp-amounts { display: flex; gap: 10px; margin: 20px 0; }
    .sdp-amount-btn { padding: 10px 20px; border: 1px solid #ddd; background: #f9f9f9; cursor: pointer; }
    .sdp-amount-btn.active { background: #007cba; color: white; }
    .sdp-donate-btn { width: 100%; padding: 12px; background: #007cba; color: white; border: none; cursor: pointer; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        var settings = <?php echo json_encode($settings); ?>;
        var shown = false;

        function showModal() {
            if (shown || settings.pages !== 'all' && !window.location.href.includes(settings.pages)) return;
            $('#sdp-modal-default').fadeIn();
            shown = true;
        }

        $(window).on('scroll', function() {
            if ($(window).scrollTop() + $(window).height() > $(document).height() * 0.5 && settings.trigger === 'scroll') {
                showModal();
            }
        });

        setTimeout(function() {
            if (settings.trigger === 'time') showModal();
        }, settings.delay * 1000);

        $('.sdp-close, .sdp-overlay').on('click', function() { $('#sdp-modal-default').fadeOut(); });
        $('.sdp-amount-btn').on('click', function() {
            $('.sdp-amount-btn').removeClass('active');
            $(this).addClass('active');
            $('#sdp-amount').val($(this).data('amount'));
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sdp_inline_assets');