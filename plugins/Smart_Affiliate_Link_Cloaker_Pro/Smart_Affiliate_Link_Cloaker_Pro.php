/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak, track, and optimize affiliate links with premium analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloaker {
    private static $instance = null;
    public $options;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        $this->options = get_option('smart_affiliate_options', []);
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_filter('the_content', [$this, 'cloak_links']);
        add_shortcode('afflink', [$this, 'afflink_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-cloaker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', ['jquery'], '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'smart-affiliate-cloaker', [$this, 'settings_page']);
    }

    public function admin_init() {
        register_setting('smart_affiliate_options', 'smart_affiliate_options');
        add_settings_section('sac_main', 'Main Settings', null, 'smart-affiliate-cloaker');
        add_settings_field('sac_api_key', 'Tracking API Key (Premium)', [$this, 'api_key_field'], 'smart-affiliate-cloaker', 'sac_main');
        add_settings_field('sac_premium', 'Enable Premium Features', [$this, 'premium_field'], 'smart-affiliate-cloaker', 'sac_main');
    }

    public function api_key_field() {
        $options = $this->options;
        echo '<input type="text" name="smart_affiliate_options[api_key]" value="' . esc_attr($options['api_key'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your premium API key for analytics. <a href="#" onclick="alert(\'Upgrade to Pro for analytics!\')">Upgrade to Pro</a></p>';
    }

    public function premium_field() {
        $premium = $this->options['premium'] ?? false;
        echo '<input type="checkbox" name="smart_affiliate_options[premium]" value="1" ' . checked(1, $premium, false) . ' /> Enable (Pro only)';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_options');
                do_settings_sections('smart-affiliate-cloaker');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Features:</strong> Click tracking, A/B testing, auto-optimization. <a href="#" onclick="alert(\'Visit example.com/pricing\')">Get Pro</a></p>
        </div>
        <?php
    }

    public function cloak_links($content) {
        if (is_feed() || is_preview()) return $content;
        $pattern = '/https?:\/\/(amzn|clickbank|shareasale|commissionjunction|impact|rakuten|awin|partnerstack|skims|flexoffers|pepperjam|cj|impactradius|linkshare|viglink|skimlinks|amazon|ebay|aliexpress|etsy)(\.[a-z]{2,4}){1,2}(\.[a-z]{2,4})?\S*/i';
        $content = preg_replace_callback($pattern, [$this, 'cloak_callback'], $content);
        return $content;
    }

    private function cloak_callback($matches) {
        $url = $matches;
        $hash = md5($url);
        $slug = 'sac-' . substr($hash, 0, 8);
        $pretty_url = home_url('/go/' . $slug . '/');
        update_option('sac_redirects', (get_option('sac_redirects', []) ?: []) + [$slug => $url]);
        return '<a href="' . esc_url($pretty_url) . '" rel="nofollow noopener" target="_blank" class="sac-link">' . esc_html(parse_url($url, PHP_URL_HOST)) . ' <span>→</span></a>';
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(['url' => ''], $atts);
        if (empty($atts['url'])) return '';
        $hash = md5($atts['url']);
        $slug = 'sac-' . substr($hash, 0, 8);
        $pretty_url = home_url('/go/' . $slug . '/');
        return '<a href="' . esc_url($pretty_url) . '" rel="nofollow noopener" target="_blank" class="sac-link">Click Here</a>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

add_action('init', function() {
    add_rewrite_rule('^go/([^/]+)/?$', 'index.php?sac_slug=$matches[1]', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'sac_slug';
    return $vars;
});

add_action('template_redirect', function() {
    $slug = get_query_var('sac_slug');
    if ($slug) {
        $redirects = get_option('sac_redirects', []);
        $url = $redirects[$slug] ?? home_url('/');
        if ($this->options['premium']) {
            // Simulate premium tracking
            error_log('Premium track: ' . $slug);
        }
        wp_redirect(esc_url_raw($url), 301);
        exit;
    }
});

SmartAffiliateCloaker::get_instance();

// Inline JS for basic tracking
function sac_inline_js() {
    ?>
    <script>document.addEventListener('click', function(e) {if(e.target.classList.contains('sac-link')){console.log('Affiliate click tracked');}});</script>
    <?php
}
add_action('wp_footer', 'sac_inline_js');
