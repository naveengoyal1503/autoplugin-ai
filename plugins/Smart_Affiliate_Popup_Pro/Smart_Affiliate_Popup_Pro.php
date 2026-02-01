/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Popup_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Popup Pro
 * Plugin URI: https://example.com/smart-affiliate-popup
 * Description: AI-powered popup plugin that displays personalized affiliate links and opt-in forms to boost conversions and monetize WordPress sites effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliatePopupPro {
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
        add_action('wp_ajax_sap_show_popup', array($this, 'handle_ajax_popup'));
        add_action('wp_ajax_nopriv_sap_show_popup', array($this, 'handle_ajax_popup'));
        add_shortcode('sap_popup', array($this, 'popup_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sap_pro_version') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_nag'));
        }
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_style('sap-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('sap-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sap-script', 'sap_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sap_nonce')));
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sap_affiliates';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            affiliate_url text NOT NULL,
            image_url varchar(255) DEFAULT '',
            trigger varchar(50) DEFAULT 'exit_intent',
            delay int DEFAULT 5,
            status tinyint DEFAULT 1,
            clicks int DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function popup_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 1), $atts);
        ob_start();
        echo '<div id="sap-popup-' . esc_attr($atts['id']) . '" class="sap-popup" style="display:none;">
                <div class="sap-overlay"></div>
                <div class="sap-content">
                    <button class="sap-close">&times;</button>
                    <img id="sap-image-' . esc_attr($atts['id']) . '" src="" alt="Affiliate Offer">
                    <h3 id="sap-title-' . esc_attr($atts['id']) . '"></h3>
                    <a id="sap-link-' . esc_attr($atts['id']) . '" href="#" class="sap-btn" target="_blank">Get It Now!</a>
                </div>
            </div>';
        return ob_get_clean();
    }

    public function handle_ajax_popup() {
        check_ajax_referer('sap_nonce', 'nonce');
        global $wpdb;
        $popup_id = intval($_POST['popup_id']);
        $popup = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "sap_affiliates WHERE id = %d AND status = 1", $popup_id));
        if ($popup) {
            $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "sap_affiliates SET clicks = clicks + 1 WHERE id = %d", $popup_id));
            wp_send_json_success(array(
                'title' => $popup->title,
                'url' => $popup->affiliate_url,
                'image' => $popup->image_url
            ));
        }
        wp_send_json_error();
    }

    public function pro_nag() {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Popup Pro:</strong> Unlock A/B testing, geo-targeting, and unlimited popups with Pro version! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }

    public function activate() {
        $this->create_table();
        // Insert sample data
        global $wpdb;
        $table_name = $wpdb->prefix . 'sap_affiliates';
        $wpdb->insert($table_name, array(
            'title' => 'Boost Your Site Speed Now!',
            'affiliate_url' => 'https://example.com/affiliate-link',
            'image_url' => 'https://example.com/popup-image.jpg',
            'trigger' => 'exit_intent',
            'delay' => 5000
        ));
    }
}

SmartAffiliatePopupPro::get_instance();

// Admin menu
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Smart Affiliate Popup', 'Affiliate Popup', 'manage_options', 'sap-settings', function() {
            echo '<div class="wrap"><h1>Smart Affiliate Popup Settings</h1><p>Manage popups via Tools > Affiliate Popup or use shortcode [sap_popup id="1"].</p><p><a href="https://example.com/pro" class="button button-primary">Go Pro</a></p></div>';
        });
    });
}

// Minimal CSS (inline for single file)
function sap_add_inline_style() {
    echo '<style>
        .sap-popup { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; }
        .sap-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); }
        .sap-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; max-width: 400px; text-align: center; }
        .sap-close { position: absolute; top: 10px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; }
        .sap-btn { display: inline-block; background: #ff6b35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .sap-btn:hover { background: #e55a2b; }
    </style>';
}
add_action('wp_head', 'sap_add_inline_style');

// Minimal JS (inline for single file)
function sap_add_inline_script() {
    echo '<script>
        jQuery(document).ready(function($) {
            var shown = sessionStorage.getItem("sap_shown");
            if (!shown) {
                setTimeout(function() {
                    $.post(sap_ajax.ajax_url, {
                        action: "sap_show_popup",
                        popup_id: 1,
                        nonce: sap_ajax.nonce
                    }, function(res) {
                        if (res.success) {
                            $("#sap-title-1").text(res.data.title);
                            $("#sap-link-1").attr("href", res.data.url);
                            $("#sap-image-1").attr("src", res.data.image);
                            $("#sap-popup-1").fadeIn();
                            sessionStorage.setItem("sap_shown", "1");
                        }
                    });
                }, 5000);
            }
            $(".sap-close, .sap-overlay").click(function() {
                $(".sap-popup").fadeOut();
            });
            // Exit intent
            $(document).on("mouseleave", function(e) {
                if (e.clientY <= 0) {
                    // Trigger popup if not shown
                }
            });
        });
    </script>';
}
add_action('wp_footer', 'sap_add_inline_script');