/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak affiliate links, track clicks, and boost earnings with analytics. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloakerPro {
    private static $instance = null;
    public $slug = 'smart-affiliate-cloaker-pro';

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_salcp_track_click', array($this, 'track_click'));
        add_shortcode('salcp_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('salcp_license_key')) {
            // Premium features unlocked
            $this->premium = true;
        } else {
            $this->premium = false;
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salcp-script', plugin_dir_url(__FILE__) . 'salcp.js', array('jquery'), '1.0.0', true);
        wp_localize_script('salcp-script', 'salcp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Cloaker Pro', 'Affiliate Cloaker', 'manage_options', $this->slug, array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['salcp_license'])) {
            update_option('salcp_license_key', sanitize_text_field($_POST['salcp_license']));
            echo '<div class="notice notice-success"><p>License activated! Premium features unlocked.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Pro</h1>
            <?php if (!$this->premium): ?>
            <form method="post">
                <p>Enter license key for premium features: <input type="text" name="salcp_license" placeholder="Premium License Key" /></p>
                <p><input type="submit" class="button-primary" value="Activate Premium" /></p>
            </form>
            <p><strong>Upgrade to Pro:</strong> $4.99/month at <a href="https://example.com/pricing" target="_blank">example.com/pricing</a> for analytics, A/B testing & more!</p>
            <?php else: ?>
            <p><strong>Premium Active!</strong> View stats below.</p>
            <?php endif; ?>
            <h2>Create Cloaked Link</h2>
            <form id="salcp-create-link">
                <p>Affiliate URL: <input type="url" id="salcp-affurl" required /></p>
                <p>Link Text: <input type="text" id="salcp-text" value="Click Here" /></p>
                <p><input type="submit" class="button-primary" value="Generate Shortcode" /></p>
            </form>
            <div id="salcp-shortcode"></div>
            <?php
            if ($this->premium) {
                global $wpdb;
                $stats = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}salcp_clicks ORDER BY date DESC LIMIT 10");
                echo '<h2>Recent Clicks</h2><ul>';
                foreach ($stats as $stat) {
                    echo '<li>' . esc_html($stat->link_id) . ': ' . $stat->clicks . ' clicks on ' . $stat->date . '</li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#salcp-create-link').submit(function(e) {
                e.preventDefault();
                var affurl = $('#salcp-affurl').val();
                var text = $('#salcp-text').val();
                var id = 'link_' + Math.random().toString(36).substr(2, 9);
                $('#salcp-shortcode').html('<p>Use: <code>[salcp_link id="' + id + '" text="' + text + '"] </code></p><p>Tracks to: ' + affurl + '</p>');
                localStorage.setItem(id, affurl);
            });
        });
        </script>
        <?php
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('id' => '', 'text' => 'Click Here'), $atts);
        $affurl = get_option('salcp_' . $atts['id'], '') ?: (isset($_COOKIE['salcp_' . $atts['id']]) ? $_COOKIE['salcp_' . $atts['id']] : '');
        if (!$affurl) {
            $affurl = localStorage ? '' : ''; // Simplified
        }
        $link_id = $atts['id'];
        $onclick = "salcpTrackClick('$link_id');";
        return '<a href="#" onclick="$onclick return false;" data-affurl="$affurl" data-id="$link_id">' . esc_html($atts['text']) . '</a>';
    }

    public function track_click() {
        $link_id = sanitize_text_field($_POST['link_id']);
        global $wpdb;
        $table = $wpdb->prefix . 'salcp_clicks';
        $wpdb->insert($table, array('link_id' => $link_id, 'date' => current_time('mysql'), 'clicks' => 1), array('%s', '%s', '%d'));
        $affurl = get_option('salcp_' . $link_id, $_POST['affurl']);
        if ($affurl) {
            wp_redirect($affurl);
            exit;
        }
    }

    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'salcp_clicks';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(50) NOT NULL,
            date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            clicks int DEFAULT 1 NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

SmartAffiliateCloakerPro::get_instance();

// Frontend JS
add_action('wp_footer', function() { ?>
<script>
function salcpTrackClick(id) {
    jQuery.post(salcp_ajax.ajax_url, {action: 'salcp_track_click', link_id: id}, function() {
        var affurl = jQuery('[data-id="' + id + '"]').data('affurl') || localStorage.getItem(id);
        if (affurl) window.location = affurl;
    });
}
</script>
<?php });

// Premium nag
function salcp_admin_notice() {
    if (!get_option('salcp_license_key')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Smart Affiliate Cloaker Pro</strong> premium: Advanced analytics & more for $4.99/mo at <a href="https://example.com/pricing" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'salcp_admin_notice');
?>