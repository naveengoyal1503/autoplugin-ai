/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_AutoLink_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate AutoLink Pro
 * Plugin URI: https://example.com/affiliate-autolink-pro
 * Description: Automatically converts keywords in your posts to affiliate links from multiple networks.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateAutoLinkPro {
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
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'auto_link_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (!session_id()) {
            session_start();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aalp-script', plugin_dir_url(__FILE__) . 'aalp.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate AutoLink Pro', 'AutoLink Pro', 'manage_options', 'aalp-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('aalp_settings', 'aalp_options');
        add_settings_section('aalp_main', 'Main Settings', null, 'aalp');
        add_settings_field('keywords', 'Keywords (keyword=url, one per line)', array($this, 'keywords_field'), 'aalp', 'aalp_main');
        add_settings_field('pro_version', 'Upgrade to Pro', array($this, 'pro_field'), 'aalp', 'aalp_main');
    }

    public function keywords_field() {
        $options = get_option('aalp_options', array());
        $keywords = isset($options['keywords']) ? $options['keywords'] : "amazon=https://amazon.com\nclickbank=https://clickbank.com";
        echo '<textarea name="aalp_options[keywords]" rows="10" cols="50">' . esc_textarea($keywords) . '</textarea>';
        echo '<p class="description">Free version limited to 5 keywords. Format: keyword=affiliate_url</p>';
    }

    public function pro_field() {
        echo '<a href="https://example.com/pro" class="button button-primary">Upgrade to Pro ($49/year)</a>';
        echo '<p>Unlimited keywords, analytics, more networks.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate AutoLink Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('aalp_settings');
                do_settings_sections('aalp');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function auto_link_content($content) {
        if (is_feed() || is_admin()) {
            return $content;
        }
        $options = get_option('aalp_options', array());
        $keywords_raw = isset($options['keywords']) ? $options['keywords'] : '';
        $keywords = explode("\n", trim($keywords_raw));
        $keywords = array_slice($keywords, 0, 5); // Free limit
        $links = array();
        foreach ($keywords as $line) {
            $parts = explode('=', trim($line), 2);
            if (count($parts) === 2) {
                $links[trim($parts)] = trim($parts[1]);
            }
        }
        foreach ($links as $keyword => $url) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $link = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">' . $keyword . '</a>';
            $content = preg_replace($pattern, $link, $content, 1);
        }
        return $content;
    }

    public function activate() {
        add_option('aalp_options', array('keywords' => "amazon=https://amazon.com\nclickbank=https://clickbank.com"));
    }

    public function deactivate() {}
}

AffiliateAutoLinkPro::get_instance();

// Inline JS
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.aalp-notice').fadeOut(5000);
    });
    </script>
    <?php
});

// Pro upsell notice
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_aalp-settings') {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Pro</strong> for unlimited links & analytics! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
});