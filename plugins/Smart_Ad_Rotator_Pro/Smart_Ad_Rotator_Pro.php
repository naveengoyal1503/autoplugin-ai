/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Ad_Rotator_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Ad Rotator Pro
 * Plugin URI: https://example.com/smart-ad-rotator
 * Description: Intelligently rotates ads to maximize revenue with performance tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-ad-rotator
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAdRotatorPro {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sar_update_stats', array($this, 'update_stats'));
        add_shortcode('sar_ad', array($this, 'ad_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-ad-rotator', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sar-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sar-frontend', 'sar_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sar_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Ad Rotator Pro', 'Ad Rotator', 'manage_options', 'smart-ad-rotator', array($this, 'admin_page'));
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sar_stats';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ad_id varchar(50) NOT NULL,
            impressions int(11) DEFAULT 0,
            clicks int(11) DEFAULT 0,
            ctr decimal(5,2) DEFAULT 0,
            date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        flush_rewrite_rules();
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;
        $is_premium = get_option('sar_premium_key') ? true : false;
        ?>
        <div class="wrap">
            <h1>Smart Ad Rotator Pro</h1>
            <?php if (!$is_premium): ?>
            <div class="notice notice-warning"><p><strong>Upgrade to Pro</strong> for unlimited ad zones, A/B testing & analytics! <a href="#" id="sar-upgrade">Get Premium ($9/mo)</a></p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php
                settings_fields('sar_options');
                do_settings_sections('sar_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th>Ad Zone 1 (Free)</th>
                        <td>
                            <textarea name="sar_ads[zone1][]" rows="3" cols="50" placeholder="AdSense code or HTML"><?php echo esc_textarea(get_option('sar_ads')['zone1'] ?? ''); ?></textarea>
                            <?php if ($is_premium): ?>
                            <textarea name="sar_ads[zone1][]" rows="3" cols="50" placeholder="Second ad..."><?php echo esc_textarea(get_option('sar_ads')['zone1'][1] ?? ''); ?></textarea>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Stats</h2>
            <div id="sar-stats"></div>
        </div>
        <script>fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sar_get_stats&nonce=<?php echo wp_create_nonce('sar_nonce'); ?>').then(r=>r.text()).then(t=>document.getElementById('sar-stats').innerHTML=t);</script>
        <?php
    }

    public function register_settings() {
        register_setting('sar_options', 'sar_ads', array($this, 'sanitize_ads'));
        register_setting('sar_options', 'sar_premium_key');
    }

    public function sanitize_ads($input) {
        return array_map('sanitize_textarea_field', $input);
    }

    public function ad_shortcode($atts) {
        $atts = shortcode_atts(array('zone' => 'zone1'), $atts);
        $ads = get_option('sar_ads', array($atts['zone'] => array('')))[ $atts['zone'] ];
        if (empty($ads)) return '';
        $ad_id = rand(1, 1000);
        $selected = $ads[ array_rand($ads) ];
        return '<div id="sar-ad-' . $ad_id . '" data-adid="' . $ad_id . '">' . $selected . '</div><script>/* SAR Track */</script>';
    }

    public function update_stats() {
        check_ajax_referer('sar_nonce', 'nonce');
        global $wpdb;
        $ad_id = sanitize_text_field($_POST['adid']);
        $action = sanitize_text_field($_POST['action_type']); // 'impression' or 'click'
        $table = $wpdb->prefix . 'sar_stats';
        if ($action === 'impression') {
            $wpdb->query($wpdb->prepare("INSERT INTO $table (ad_id, impressions) VALUES (%s, 1) ON DUPLICATE KEY UPDATE impressions = impressions + 1", $ad_id));
        } elseif ($action === 'click') {
            $wpdb->query($wpdb->prepare("INSERT INTO $table (ad_id, clicks) VALUES (%s, 1) ON DUPLICATE KEY UPDATE clicks = clicks + 1", $ad_id));
        }
        wp_die();
    }

    public function get_stats() {
        check_ajax_referer('sar_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'sar_stats';
        $stats = $wpdb->get_results("SELECT ad_id, SUM(impressions) as imp, SUM(clicks) as clk FROM $table GROUP BY ad_id");
        echo '<table><tr><th>Ad ID</th><th>Impressions</th><th>Clicks</th><th>CTR</th></tr>';
        foreach ($stats as $stat) {
            $ctr = $stat->imp > 0 ? round(($stat->clk / $stat->imp) * 100, 2) : 0;
            echo "<tr><td>{$stat->ad_id}</td><td>{$stat->imp}</td><td>{$stat->clk}</td><td>$ctr%</td></tr>";
        }
        echo '</table>';
        wp_die();
    }
}

// Premium check simulation
function sar_is_premium() {
    return get_option('sar_premium_key') !== false;
}

add_action('admin_init', array(SmartAdRotatorPro::get_instance(), 'register_settings'));
add_action('wp_ajax_sar_get_stats', array(SmartAdRotatorPro::get_instance(), 'get_stats'));

// Init
SmartAdRotatorPro::get_instance();

// Frontend JS (inline for single file)
function sar_add_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sar-ad').each(function() {
            var adId = $(this).data('adid');
            $.post(sar_ajax.ajax_url, {action: 'sar_update_stats', adid: adId, action_type: 'impression', nonce: sar_ajax.nonce});
            $(this).on('click', function() {
                $.post(sar_ajax.ajax_url, {action: 'sar_update_stats', adid: adId, action_type: 'click', nonce: sar_ajax.nonce});
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sar_add_inline_js');