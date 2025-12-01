/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Manager Pro
 * Plugin URI: https://example.com/affiliatelink-manager-pro
 * Description: Manage, track, and optimize affiliate links with advanced analytics and automated link cloaking.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

define('ALMP_VERSION', '1.0.0');
define('ALMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALMP_PLUGIN_URL', plugin_dir_url(__FILE__));

class AffiliateLinkManagerPro {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliatelink', array($this, 'shortcode_handler'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'AffiliateLink Manager Pro',
            'Affiliate Links',
            'manage_options',
            'affiliatelink-manager-pro',
            array($this, 'plugin_settings_page'),
            'dashicons-admin-links',
            60
        );
    }

    public function settings_init() {
        register_setting('almp_settings', 'almp_settings');

        add_settings_section(
            'almp_plugin_section',
            'Affiliate Link Settings',
            null,
            'almp_settings'
        );

        add_settings_field(
            'almp_tracking_code',
            'Tracking Code',
            array($this, 'tracking_code_render'),
            'almp_settings',
            'almp_plugin_section'
        );
    }

    public function tracking_code_render() {
        $options = get_option('almp_settings');
        ?>
        <input type='text' name='almp_settings[almp_tracking_code]' value='<?php echo $options['almp_tracking_code']; ?>'>
        <p class='description'>Enter your affiliate tracking code (e.g., ?ref=yourid).</p>
        <?php
    }

    public function plugin_settings_page() {
        ?>
        <div class="wrap">
            <h1>AffiliateLink Manager Pro</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('almp_settings');
                do_settings_sections('almp_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('almp-script', ALMP_PLUGIN_URL . 'assets/js/script.js', array('jquery'), ALMP_VERSION, true);
        wp_localize_script('almp-script', 'almp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Click here',
            'class' => '',
            'rel' => 'nofollow'
        ), $atts, 'affiliatelink');

        $options = get_option('almp_settings');
        $tracking_code = isset($options['almp_tracking_code']) ? $options['almp_tracking_code'] : '';

        $url = esc_url($atts['url']);
        if ($tracking_code) {
            $url = add_query_arg('ref', $tracking_code, $url);
        }

        $class = esc_attr($atts['class']);
        $rel = esc_attr($atts['rel']);
        $text = esc_html($atts['text']);

        return "<a href='$url' class='$class' rel='$rel' target='_blank'>$text</a>";
    }
}

new AffiliateLinkManagerPro();

// Create table on plugin activation
register_activation_hook(__FILE__, 'almp_create_table');
function almp_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'almp_links';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        url varchar(512) NOT NULL,
        clicks mediumint(9) NOT NULL DEFAULT 0,
        created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Track clicks
add_action('wp_ajax_nopriv_almp_track_click', 'almp_track_click');
add_action('wp_ajax_almp_track_click', 'almp_track_click');
function almp_track_click() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'almp_links';
    $link_id = intval($_POST['link_id']);
    $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $link_id));
    wp_die();
}

// Add click tracking to links (simplified for demo)
add_filter('the_content', 'almp_add_click_tracking');
function almp_add_click_tracking($content) {
    // This is a simplified example. In practice, you would parse and modify affiliate links.
    return $content;
}
