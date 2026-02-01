/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Tip_Jar_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Tip Jar Pro
 * Plugin URI: https://example.com/smart-tip-jar
 * Description: Add customizable tip jars to your WordPress site to collect tips and donations easily.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-tip-jar
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartTipJar {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_stj_donate', array($this, 'handle_donation'));
        add_shortcode('smart_tip_jar', array($this, 'tip_jar_shortcode'));
    }

    public function init() {
        if (get_option('stj_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
        register_setting('stj_settings', 'stj_options');
        register_setting('stj_settings', 'stj_pro');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('stj-script', plugin_dir_url(__FILE__) . 'tip-jar.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('stj-style', plugin_dir_url(__FILE__) . 'tip-jar.css', array(), '1.0.0');
        wp_localize_script('stj-script', 'stj_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('stj_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Tip Jar', 'Tip Jar', 'manage_options', 'smart-tip-jar', array($this, 'settings_page'));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Tip Jar Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('stj_settings'); ?>
                <?php do_settings_sections('stj_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Tip Jar</th>
                        <td><input type="checkbox" name="stj_options[enabled]" value="1" <?php checked(1, get_option('stj_options')['enabled'] ?? 0); ?> /></td>
                    </tr>
                    <tr>
                        <th>Jar Title</th>
                        <td><input type="text" name="stj_options[title]" value="<?php echo esc_attr(get_option('stj_options')['title'] ?? 'Buy Me a Coffee'); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Amounts (comma separated)</th>
                        <td><input type="text" name="stj_options[amounts]" value="<?php echo esc_attr(get_option('stj_options')['amounts'] ?? '3,5,10,20'); ?>" /></td>
                    </tr>
                    <tr>
                        <th>PayPal Email</th>
                        <td><input type="email" name="stj_options[paypal]" value="<?php echo esc_attr(get_option('stj_options')['paypal'] ?? ''); ?>" /></td>
                    </tr>
                    <?php if (get_option('stj_pro') !== 'yes') : ?>
                    <tr>
                        <th>Pro Features</th>
                        <td><a href="#" class="button button-primary">Upgrade to Pro ($29/year)</a> - Unlimited jars, analytics, themes</td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Smart Tip Jar Pro:</strong> Unlock unlimited jars and analytics for $29/year! <a href="options-general.php?page=smart-tip-jar">Upgrade now</a></p></div>';
    }

    public function tip_jar_shortcode($atts) {
        $options = get_option('stj_options', array());
        if (empty($options['enabled'])) return '';

        $amounts = explode(',', $options['amounts'] ?? '3,5,10');
        $amounts = array_map('trim', $amounts);
        $paypal = $options['paypal'] ?? '';
        $title = $options['title'] ?? 'Support this site!';

        ob_start();
        ?>
        <div id="stj-jar" class="stj-jar">
            <h3><?php echo esc_html($title); ?></h3>
            <div class="stj-amounts">
                <?php foreach ($amounts as $amount) : ?>
                    <button class="stj-amount" data-amount="<?php echo esc_attr($amount); ?>">$<?php echo esc_html($amount); ?></button>
                <?php endforeach; ?>
                <input type="number" class="stj-custom" placeholder="Custom" />
            </div>
            <?php if ($paypal) : ?>
            <form action="https://www.paypal.com/donate" method="post" target="_blank">
                <input type="hidden" name="business" value="<?php echo esc_attr($paypal); ?>">
                <input type="hidden" name="item_name" value="Tip">
                <input type="hidden" name="amount" id="stj-amount" value="">
                <input type="hidden" name="currency_code" value="USD">
                <input type="submit" class="button stj-donate-btn" value="Donate via PayPal">
            </form>
            <?php else : ?>
            <p class="stj-setup">Setup PayPal in settings to enable donations.</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_donation() {
        check_ajax_referer('stj_nonce', 'nonce');
        // Log donation for pro analytics
        if (get_option('stj_pro') === 'yes') {
            $amount = sanitize_text_field($_POST['amount']);
            error_log('STJ Donation: ' . $amount);
        }
        wp_die();
    }
}

new SmartTipJar();

// Inline CSS and JS for self-contained plugin

function stj_add_inline_assets() {
    ?>
    <style>
    .stj-jar { border: 2px solid #007cba; padding: 20px; border-radius: 10px; text-align: center; max-width: 300px; margin: 20px auto; background: #f9f9f9; }
    .stj-jar h3 { margin: 0 0 15px; color: #007cba; }
    .stj-amounts { margin-bottom: 15px; }
    .stj-amount, .stj-custom { margin: 5px; padding: 10px; background: white; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; }
    .stj-amount:hover { background: #007cba; color: white; }
    .stj-donate-btn { background: #ffc107; color: #000; padding: 12px 24px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
    .stj-donate-btn:hover { background: #ffb300; }
    .stj-setup { color: #666; font-style: italic; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.stj-amount, .stj-custom').on('click change', function() {
            var amt = $(this).val() || $(this).data('amount');
            $('#stj-amount').val(amt);
        });
    });
    </script>
    <?php
}

add_action('wp_footer', 'stj_add_inline_assets');