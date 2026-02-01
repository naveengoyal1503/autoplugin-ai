/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak affiliate links, track clicks, and boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloaker {
    private static $instance = null;
    public $slug = 'smart-affiliate-cloaker';

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_shortcode('sac_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_pro') !== 'yes') {
            add_action('wp_footer', array($this, 'free_nag'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-js', plugin_dir_url(__FILE__) . 'sac.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', $this->slug, array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting($this->slug, 'sac_settings');
        add_settings_section('sac_main', 'Settings', null, $this->slug);
        add_settings_field('sac_links', 'Affiliate Links', array($this, 'links_field'), $this->slug, 'sac_main');
    }

    public function links_field() {
        $settings = get_option('sac_settings', array());
        echo '<textarea name="sac_settings[links]" rows="10" cols="50">' . esc_textarea($settings['links'] ?? '') . '</textarea>';
        echo '<p>Format: keyword|affiliate_url (one per line)</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields($this->slug); do_settings_sections($this->slug); submit_button(); ?>
            </form>
            <?php if (get_option('sac_pro') !== 'yes') : ?>
            <div class="notice notice-info"><p><strong>Go Pro!</strong> Unlock analytics, A/B testing & more for $9/mo. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('keyword' => ''), $atts);
        return $this->cloak_link($atts['keyword']);
    }

    private function cloak_link($keyword) {
        $settings = get_option('sac_settings', array());
        $links = explode("\n", $settings['links'] ?? '');
        foreach ($links as $line) {
            list($k, $url) = explode('|', trim($line), 2);
            if (strtolower(trim($k)) === strtolower($keyword)) {
                $id = 'sac_' . md5($url);
                $clicks = get_option('sac_clicks_' . $id, 0) + 0;
                if (get_option('sac_pro') === 'yes') {
                    $clicks++; update_option('sac_clicks_' . $id, $clicks);
                }
                return '<a href="' . esc_url(admin_url('admin-ajax.php?action=sac_redirect&url=' . urlencode($url))) . '" class="sac-link" data-id="' . $id . '">' . $keyword . '</a>';
            }
        }
        return $keyword;
    }

    public function activate() {
        add_option('sac_settings', array());
    }

    public function free_nag() {
        echo '<div style="position:fixed;bottom:20px;right:20px;background:#0073aa;color:white;padding:10px;border-radius:5px;z-index:9999;"><strong>Pro Tip:</strong> Upgrade to Pro for click tracking! <a href="' . admin_url('options-general.php?page=' . $this->slug) . '" style="color:#fff;">Learn More</a></div>';
    }
}

add_action('wp_ajax_sac_redirect', function() {
    $url = $_GET['url'] ?? '';
    if ($url) {
        wp_redirect(esc_url_raw($url));
        exit;
    }
});

add_action('wp_ajax_nopriv_sac_redirect', function() {
    $url = $_GET['url'] ?? '';
    if ($url) {
        wp_redirect(esc_url_raw($url));
        exit;
    }
});

// Dummy JS file content (base64 or inline, but for single file, inline)
function sac_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sac-link').click(function() {
            var id = $(this).data('id');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'sac_track', id: id});
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sac_inline_js');

SmartAffiliateCloaker::get_instance();