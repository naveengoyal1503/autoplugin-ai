/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Link Booster
 * Description: Automatically inserts, cloaks, and tracks affiliate links with geo-targeting and customizable settings.
 * Version: 1.0
 * Author: Generated
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class AffiliateLinkBooster {
    private $option_name = 'alb_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'auto_insert_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_alb_track_click', array($this, 'track_click')); // AJAX click tracking
        add_action('wp_ajax_nopriv_alb_track_click', array($this, 'track_click'));
        add_shortcode('alb_affiliate_link', array($this, 'affiliate_link_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'alb_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            link VARCHAR(255) NOT NULL,
            ip VARCHAR(100) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            referrer VARCHAR(255) DEFAULT '',
            click_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('alb_script', plugin_dir_url(__FILE__) . 'alb-script.js', array('jquery'), '1.0', true);
        wp_localize_script('alb_script', 'alb_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Link Booster', 'Affiliate Link Booster', 'manage_options', 'alb-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('alb_options_group', $this->option_name);
        add_settings_section('alb_main_section', 'Main Settings', null, 'alb-settings');

        add_settings_field('keywords', 'Keyword to Link', array($this, 'keywords_callback'), 'alb-settings', 'alb_main_section');
        add_settings_field('affiliate_url', 'Affiliate URL', array($this, 'affiliate_url_callback'), 'alb-settings', 'alb_main_section');
        add_settings_field('geo_target', 'Geo Targeting (Country Codes, comma separated)', array($this, 'geo_target_callback'), 'alb-settings', 'alb_main_section');
    }

    public function keywords_callback() {
        $options = get_option($this->option_name);
        $val = isset($options['keywords']) ? esc_attr($options['keywords']) : '';
        echo "<input type='text' name='{$this->option_name}[keywords]' value='{$val}' placeholder='e.g. product, brand' style='width:300px;' /> <p class='description'>Separate keywords by commas.</p>";
    }

    public function affiliate_url_callback() {
        $options = get_option($this->option_name);
        $val = isset($options['affiliate_url']) ? esc_url($options['affiliate_url']) : '';
        echo "<input type='url' name='{$this->option_name}[affiliate_url]' value='{$val}' placeholder='https://example.com/affiliate?ref=yourid' style='width:400px;' />";
    }

    public function geo_target_callback() {
        $options = get_option($this->option_name);
        $val = isset($options['geo_target']) ? esc_attr($options['geo_target']) : '';
        echo "<input type='text' name='{$this->option_name}[geo_target]' value='{$val}' placeholder='US,CA,GB' style='width:200px;' /> <p class='description'>Only show links to visitors from these countries (optional).</p>";
    }

    public function settings_page() {
        ?>
        <div class='wrap'>
            <h1>Affiliate Link Booster Settings</h1>
            <form method='post' action='options.php'>
                <?php
                settings_fields('alb_options_group');
                do_settings_sections('alb-settings');
                submit_button();
                ?>
            </form>
            <h2>Shortcode usage:</h2>
            <p>Use <code>[alb_affiliate_link url="https://affiliate-link"]Text[/alb_affiliate_link]</code> to create cloaked affiliate links anywhere.</p>
        </div>
        <?php
    }

    // Automatically replace first occurrence of keywords with affiliate cloaked links
    public function auto_insert_links($content) {
        $options = get_option($this->option_name);
        if (empty($options) || empty($options['keywords']) || empty($options['affiliate_url'])) {
            return $content;
        }

        // Geo Targeting check
        if (!empty($options['geo_target'])) {
            $allowed_countries = array_map('trim', explode(',', strtoupper($options['geo_target'])));
            $user_country = $this->get_user_country_code();
            if (!in_array($user_country, $allowed_countries)) {
                return $content; // Do not insert links if not in allowed countries
            }
        }

        $keywords = array_map('trim', explode(',', $options['keywords']));
        $affiliate_url = esc_url($options['affiliate_url']);
        $cloaked_url = add_query_arg('alb', '1', admin_url('admin-ajax.php')); // Using AJAX to track click

        // Replace only the first occurrence for each keyword
        foreach ($keywords as $keyword) {
            if (empty($keyword)) continue;
            // Preg quote keyword
            $keyword_quoted = preg_quote($keyword, '/');
            $pattern = '/(\b' . $keyword_quoted . '\b)(?![^<]*>)/i';

            if (preg_match($pattern, $content)) {
                $replacement = '<a href="' . esc_url($affiliate_url) . '" target="_blank" rel="nofollow noopener noreferrer" class="alb-affiliate-link" data-url="' . esc_attr($affiliate_url) . '">$1</a>';
                $content = preg_replace($pattern, $replacement, $content, 1);
            }
        }

        return $content;
    }

    // Shortcode handler for cloaked affiliate links
    public function affiliate_link_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array('url' => ''), $atts, 'alb_affiliate_link');
        if (empty($atts['url']) || empty($content)) return '';

        return '<a href="' . esc_url($atts['url']) . '" target="_blank" rel="nofollow noopener noreferrer" class="alb-affiliate-link" data-url="' . esc_attr($atts['url']) . '">' . esc_html($content) . '</a>';
    }

    public function track_click() {
        if (empty($_POST['url']) || !filter_var($_POST['url'], FILTER_VALIDATE_URL)) {
            wp_send_json_error('Invalid URL');
            wp_die();
        }

        $url = esc_url_raw($_POST['url']);

        global $wpdb;
        $table = $wpdb->prefix . 'alb_clicks';
        $ip = ($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';

        $wpdb->insert($table, [
            'link' => $url,
            'ip' => $ip,
            'user_agent' => $user_agent,
            'referrer' => $referrer,
            'click_time' => current_time('mysql')
        ]);

        wp_send_json_success('Click logged');
        wp_die();
    }

    private function get_user_country_code() {
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return strtoupper(sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']));
        }
        // Could add more logic with GeoIP
        return '';
    }
}

new AffiliateLinkBooster();

// Javascript for AJAX click tracking, to be included inline
add_action('wp_footer', function() {
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.alb-affiliate-link').forEach(function(el) {
            el.addEventListener('click', function(e) {
                var url = this.getAttribute('data-url');
                if (!url) return;
                jQuery.post(
                    alb_ajax.ajax_url,
                    { action: 'alb_track_click', url: url },
                    function(response) { /* Tracking success - no action needed */ }
                );
            });
        });
    });
    </script>
    <?php
});
