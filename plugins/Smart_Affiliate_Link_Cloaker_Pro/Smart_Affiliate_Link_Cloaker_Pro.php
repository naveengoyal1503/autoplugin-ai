/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloaks, tracks, and optimizes affiliate links to boost conversions. Free core with Pro upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCloaker {
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
        add_shortcode('sac_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-cloaker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Cloaker', 'SAC Pro', 'manage_options', 'smart-affiliate-cloaker', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sac_settings', 'sac_options');
        add_settings_section('sac_main', 'Main Settings', null, 'sac');
        add_settings_field('sac_prefix', 'Cloak Prefix', array($this, 'prefix_field'), 'sac', 'sac_main');
        add_settings_field('sac_api_key', 'Pro API Key (for upgrades)', array($this, 'api_key_field'), 'sac', 'sac_main');
    }

    public function prefix_field() {
        $options = get_option('sac_options', array('prefix' => 'go'));
        echo '<input type="text" name="sac_options[prefix]" value="' . esc_attr($options['prefix']) . '" />';
    }

    public function api_key_field() {
        $options = get_option('sac_options', array());
        echo '<input type="text" name="sac_options[api_key]" value="' . esc_attr($options['api_key']) . '" placeholder="Enter Pro key for analytics" />';
        echo '<p class="description">Get Pro key at <a href="https://example.com/pro" target="_blank">example.com/pro</a></p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sac_settings');
                do_settings_sections('sac');
                submit_button();
                ?>
            </form>
            <h2>Pro Features</h2>
            <p>Upgrade for analytics, A/B testing, and more: <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function shortcode_link($atts) {
        $atts = shortcodes_atts(array(
            'url' => '',
            'text' => 'Click Here',
        ), $atts);

        $options = get_option('sac_options', array('prefix' => 'go'));
        $slug = sanitize_title($atts['text']);
        $cloak_url = home_url('/' . $options['prefix'] . '/' . $slug . '/');

        // Save mapping
        $mappings = get_option('sac_mappings', array());
        $mappings[$cloak_url] = $atts['url'];
        update_option('sac_mappings', $mappings);

        // Track click
        $this->track_click($cloak_url);

        return '<a href="' . esc_url($cloak_url) . '" class="sac-link">' . esc_html($atts['text']) . '</a>';
    }

    public function track_click($cloak_url) {
        $clicks = get_option('sac_clicks', array());
        $clicks[$cloak_url] = isset($clicks[$cloak_url]) ? $clicks[$cloak_url] + 1 : 1;
        update_option('sac_clicks', $clicks);
    }

    public function activate() {
        if (!get_option('sac_options')) {
            add_option('sac_options', array('prefix' => 'go'));
        }
    }
}

// Rewrite rules
add_action('init', function() {
    $options = get_option('sac_options', array('prefix' => 'go'));
    add_rewrite_rule(
        $options['prefix'] . '/([^/]+)/?',
        'index.php?sac_redirect=$matches[1]',
        'top'
    );
});

add_filter('query_vars', function($vars) {
    $vars[] = 'sac_redirect';
    return $vars;
});

add_action('template_redirect', function() {
    $redirect = get_query_var('sac_redirect');
    if ($redirect) {
        $mappings = get_option('sac_mappings', array());
        $prefix = get_option('sac_options', array('prefix' => 'go'))['prefix'];
        $cloak_url = home_url('/' . $prefix . '/' . $redirect . '/');
        if (isset($mappings[$cloak_url])) {
            // Pro analytics check
            $options = get_option('sac_options');
            if (!empty($options['api_key'])) {
                // Simulate Pro call
                wp_remote_post('https://example.com/api/track', array(
                    'body' => array('key' => $options['api_key'], 'url' => $cloak_url)
                ));
            }
            wp_redirect($mappings[$cloak_url], 301);
            exit;
        }
    }
});

// Admin dashboard widget for stats
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget('sac_stats', 'Affiliate Link Stats', 'sac_dashboard_widget');
});

function sac_dashboard_widget() {
    $clicks = get_option('sac_clicks', array());
    if (empty($clicks)) {
        echo '<p>No clicks yet. Use [sac_link url="https://aff.link" text="Buy Now"] shortcode.</p>';
    } else {
        echo '<ul>';
        foreach ($clicks as $url => $count) {
            echo '<li>' . esc_html($url) . ': ' . $count . ' clicks</li>';
        }
        echo '</ul>';
    }
    echo '<p><strong>Pro:</strong> Advanced analytics at <a href="https://example.com/pro">example.com/pro</a></p>';
}

SmartAffiliateCloaker::get_instance();

// JS for frontend (inline for single file)
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sac-link').on('click', function(e) {
            var $this = $(this);
            $.post(sac_ajax.ajax_url, {
                action: 'sac_track',
                nonce: sac_ajax.nonce,
                url: $this.attr('href')
            });
        });
    });
    </script>
    <?php
});

add_action('wp_ajax_sac_track', function() {
    check_ajax_referer('sac_nonce', 'nonce');
    error_log('SAC Track: ' . $_POST['url']);
    wp_die();
});