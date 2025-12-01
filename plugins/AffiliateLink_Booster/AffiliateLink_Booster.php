<?php
/*
Plugin Name: AffiliateLink Booster
Description: Automatically converts keywords into affiliate links and provides analytics.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateLinkBooster {
    private $plugin_name = 'affiliate_link_booster';
    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'convert_affiliate_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_alb_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_alb_track_click', array($this, 'track_click'));
    }

    public function add_admin_menu() {
        add_options_page('AffiliateLink Booster Settings', 'AffiliateLink Booster', 'manage_options', $this->plugin_name, array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting($this->plugin_name, 'alb_keywords');
        register_setting($this->plugin_name, 'alb_affiliate_links');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AffiliateLink Booster Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields($this->plugin_name); ?>
                <?php do_settings_sections($this->plugin_name); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Keywords (comma-separated)</th>
                        <td><input type="text" name="alb_keywords" value="<?php echo esc_attr(get_option('alb_keywords', '')); ?>" style="width: 50%;" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Affiliate Links (comma-separated, matches keywords order)</th>
                        <td><input type="text" name="alb_affiliate_links" value="<?php echo esc_attr(get_option('alb_affiliate_links', '')); ?>" style="width: 70%;" /></td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
            <h2>Click Analytics (Basic)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Keyword</th><th>Clicks</th></tr></thead>
                <tbody>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'alb_clicks';
                $keywords = explode(',', get_option('alb_keywords', ''));
                foreach ($keywords as $keyword) {
                    $keyword = trim($keyword);
                    if (!$keyword) continue;
                    $cnt = $wpdb->get_var($wpdb->prepare("SELECT clicks FROM $table_name WHERE keyword = %s", $keyword));
                    echo '<tr><td>' . esc_html($keyword) . '</td><td>' . intval($cnt) . '</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function convert_affiliate_links($content) {
        $keywords = array_map('trim', explode(',', get_option('alb_keywords', '')));
        $links = array_map('trim', explode(',', get_option('alb_affiliate_links', '')));

        if (count($keywords) !== count($links) || empty($keywords) || empty($links)) {
            return $content;
        }

        foreach ($keywords as $idx => $keyword) {
            if (!$keyword) continue;
            $link = esc_url($links[$idx]);
            $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/i';
            $replacement = '<a href="' . $link . '" class="alb-affiliate-link" data-keyword="' . esc_attr($keyword) . '" target="_blank" rel="nofollow noopener noreferrer">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        return $content;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('alb-tracking', plugin_dir_url(__FILE__) . 'alb-tracking.js', array('jquery'), '1.0', true);
        wp_localize_script('alb-tracking', 'ALBAjax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function track_click() {
        if (isset($_POST['keyword'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'alb_clicks';
            $keyword = sanitize_text_field($_POST['keyword']);

            $clicked = $wpdb->get_var($wpdb->prepare("SELECT clicks FROM $table_name WHERE keyword = %s", $keyword));

            if ($clicked === null) {
                $wpdb->insert($table_name, array('keyword' => $keyword, 'clicks' => 1), array('%s', '%d'));
            } else {
                $wpdb->update($table_name, array('clicks' => $clicked + 1), array('keyword' => $keyword), array('%d'), array('%s'));
            }
            wp_send_json_success();
        }
        wp_send_json_error();
    }
}

function alb_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'alb_clicks';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        keyword varchar(255) NOT NULL,
        clicks int(11) DEFAULT 0 NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY keyword (keyword)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'alb_install');

new AffiliateLinkBooster();

// The JavaScript file 'alb-tracking.js' is not external; insert inline script here for single-file use:
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.alb-affiliate-link').on('click', function() {
            var keyword = $(this).data('keyword');
            $.post(ALBAjax.ajax_url, {action: 'alb_track_click', keyword: keyword});
        });
    });
    </script>
    <?php
});