/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak affiliate links, track clicks, and optimize conversions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCloaker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
        add_action('wp_ajax_sac_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sac_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sac_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_nag'));
        }
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-ajax', plugin_dir_url(__FILE__) . 'sac-ajax.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-ajax', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Click Here',
            'id' => 'default'
        ), $atts);

        if (empty($atts['url'])) return 'Invalid link';

        $link_id = sanitize_text_field($atts['id']);
        $pretty_url = add_query_arg('sac', $link_id, home_url('/'));

        return '<a href="' . esc_url($pretty_url) . '" data-sac-url="' . esc_url($atts['url']) . '" class="sac-link">' . esc_html($atts['text']) . '</a>';
    }

    public function track_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        $link_id = sanitize_text_field($_POST['link_id']);
        $aff_url = sanitize_url($_POST['aff_url']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'sac_clicks',
            array(
                'link_id' => $link_id,
                'aff_url' => $aff_url,
                'ip' => $ip,
                'user_agent' => substr($user_agent, 0, 255),
                'clicked_at' => current_time('mysql')
            )
        );

        wp_redirect($aff_url);
        exit;
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sac_clicks';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(50) NOT NULL,
            aff_url text NOT NULL,
            ip varchar(45) NOT NULL,
            user_agent varchar(255) NOT NULL,
            clicked_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        add_option('sac_pro', 'no');
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function pro_nag() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Link Cloaker Pro</strong> for A/B testing and advanced analytics!</p></div>';
    }
}

// AJAX JS file content (embedded)
function sac_ajax_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sac-link').on('click', function(e) {
            e.preventDefault();
            var $this = $(this);
            var aff_url = $this.data('sac-url');
            var link_id = $this.data('link-id') || 'default';

            $.post(sac_ajax.ajaxurl, {
                action: 'sac_track_click',
                nonce: sac_ajax.nonce,
                link_id: link_id,
                aff_url: aff_url
            }, function() {
                window.location = aff_url;
            });
        });
    });
    </script>
    <?php
}

add_action('wp_footer', 'sac_ajax_js');

SmartAffiliateCloaker::get_instance();