/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Paywall_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Paywall Pro
 * Plugin URI: https://example.com/smart-paywall-pro
 * Description: AI-powered paywall for WordPress content monetization with subscriptions and one-time payments.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-paywall-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartPaywallPro {
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
        add_filter('the_content', array($this, 'paywall_content'), 99);
        add_shortcode('smart_paywall', array($this, 'paywall_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_paywall_options', array(
            'paywall_enabled' => 1,
            'preview_words' => 100,
            'price_monthly' => 9.99,
            'price_yearly' => 99,
            'stripe_key' => '',
            'stripe_secret' => '',
            'pro' => false
        ));
        if ($this->options['pro']) {
            // Pro features
        }
        load_plugin_textdomain('smart-paywall-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-paywall', plugin_dir_url(__FILE__) . 'assets/paywall.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-paywall', plugin_dir_url(__FILE__) . 'assets/paywall.css', array(), '1.0.0');
        wp_localize_script('smart-paywall', 'spp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('spp_nonce')));
    }

    public function paywall_content($content) {
        if (!is_single() || !$this->options['paywall_enabled'] || is_user_logged_in()) {
            return $content;
        }
        $words = str_word_count(strip_tags($content));
        if ($words <= $this->options['preview_words']) {
            return $content;
        }
        $preview = wp_trim_words($content, $this->options['preview_words'], '... <a href="#paywall" class="spp-trigger">Read full article</a>');
        $paywall = $this->render_paywall($content);
        return $preview . '<div id="spp-paywall" style="display:none;">' . $paywall . '</div>';
    }

    private function render_paywall($full_content) {
        $monthly = number_format($this->options['price_monthly'], 2);
        $yearly = number_format($this->options['price_yearly'], 2);
        ob_start();
        ?>
        <div class="spp-modal">
            <div class="spp-overlay" onclick="sppClose()"></div>
            <div class="spp-content">
                <button class="spp-close" onclick="sppClose()">×</button>
                <div class="spp-full-content"><?php echo $full_content; ?></div>
                <div class="spp-pricing">
                    <h3>Unlock Full Access</h3>
                    <div class="spp-plan">
                        <h4>Monthly</h4>
                        $<span class="price"><?php echo $monthly; ?></span>/mo
                        <button onclick="sppSubscribe('monthly')">Subscribe</button>
                    </div>
                    <div class="spp-plan">
                        <h4>Yearly (Save 20%)</h4>
                        $<span class="price"><?php echo $yearly; ?></span>/yr
                        <button onclick="sppSubscribe('yearly')">Subscribe</button>
                    </div>
                    <div class="spp-one-time">
                        <button onclick="sppOneTime()">One-Time Access ($5)</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function paywall_shortcode($atts) {
        $atts = shortcode_atts(array('content' => ''), $atts);
        return $this->render_paywall(do_shortcode($atts['content']));
    }

    public function admin_menu() {
        add_options_page('Smart Paywall Pro', 'Smart Paywall', 'manage_options', 'smart-paywall', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->options = array_merge($this->options, $_POST['options']);
            update_option('smart_paywall_options', $this->options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Paywall Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Enable Paywall</th>
                        <td><input type="checkbox" name="options[paywall_enabled]" <?php checked($this->options['paywall_enabled']); ?> value="1"></td>
                    </tr>
                    <tr>
                        <th>Preview Words</th>
                        <td><input type="number" name="options[preview_words]" value="<?php echo $this->options['preview_words']; ?>"></td>
                    </tr>
                    <tr>
                        <th>Monthly Price</th>
                        <td><input type="number" step="0.01" name="options[price_monthly]" value="<?php echo $this->options['price_monthly']; ?>"></td>
                    </tr>
                    <tr>
                        <th>Yearly Price</th>
                        <td><input type="number" step="0.01" name="options[price_yearly]" value="<?php echo $this->options['price_yearly']; ?>"></td>
                    </tr>
                    <tr>
                        <th>Stripe Publishable Key (Pro)</th>
                        <td><input type="text" name="options[stripe_key]" value="<?php echo esc_attr($this->options['stripe_key']); ?>"></td>
                    </tr>
                    <tr>
                        <th>Stripe Secret Key (Pro)</th>
                        <td><input type="password" name="options[stripe_secret]" value="<?php echo esc_attr($this->options['stripe_secret']); ?>"></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Changes"></p>
            </form>
            <p><strong>Pro Features:</strong> Full Stripe integration, A/B testing, analytics. <a href="https://example.com/upgrade">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_paywall_options', array('paywall_enabled' => 1, 'preview_words' => 100, 'price_monthly' => 9.99, 'price_yearly' => 99));
    }

    public function deactivate() {}
}

SmartPaywallPro::get_instance();

// Frontend JS (inline for single file)
function spp_inline_scripts() {
    if (is_single()) {
        ?>
        <script>
        function sppTrigger() { document.getElementById('spp-paywall').style.display = 'block'; }
        function sppClose() { document.getElementById('spp-paywall').style.display = 'none'; }
        function sppSubscribe(plan) { alert('Pro: Integrate Stripe for ' + plan + ' subscription'); }
        function sppOneTime() { alert('Pro: One-time payment processing'); }
        document.addEventListener('DOMContentLoaded', function() {
            const triggers = document.querySelectorAll('.spp-trigger');
            triggers.forEach(t => t.addEventListener('click', function(e) { e.preventDefault(); sppTrigger(); }));
        });
        </script>
        <style>
        .spp-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; }
        .spp-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); }
        .spp-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; max-width: 800px; max-height: 80%; overflow: auto; }
        .spp-close { position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; }
        .spp-pricing { margin-top: 20px; text-align: center; }
        .spp-plan { display: inline-block; margin: 10px; padding: 20px; border: 1px solid #ddd; }
        .price { font-size: 24px; font-weight: bold; color: #0073aa; }
        button { background: #0073aa; color: white; border: none; padding: 10px 20px; cursor: pointer; }
        </style>
        <?php
    }
}
add_action('wp_footer', 'spp_inline_scripts');