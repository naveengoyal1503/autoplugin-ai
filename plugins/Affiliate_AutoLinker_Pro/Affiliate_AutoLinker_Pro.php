<?php
/*
Plugin Name: Affiliate AutoLinker Pro
Plugin URI: https://example.com/affiliate-autolinker-pro
Description: Autolinks specified keywords in posts to affiliate URLs with click tracking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_AutoLinker_Pro.php
License: GPL2
Text Domain: affiliate-autolinker-pro
*/

if ( ! defined('ABSPATH') ) {
    exit; // Exit if accessed directly
}

class AffiliateAutoLinkerPro {
    private $option_name = 'aalp_settings';
    private $keywords = array();

    public function __construct() {
        add_filter('the_content', array($this, 'autolink_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aalp_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_aalp_track_click', array($this, 'track_click'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aalp-click-tracker', plugin_dir_url(__FILE__) . 'click-tracker.js', array('jquery'), '1.0', true);
        wp_localize_script('aalp-click-tracker', 'aalpAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aalp_nonce')
        ));
    }

    public function add_admin_menu() {
        add_options_page('Affiliate AutoLinker Pro', 'Affiliate AutoLinker Pro', 'manage_options', 'affiliate-autolinker-pro', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array($this, 'validate_settings'));
        add_settings_section('aalp_main', 'Affiliate Keyword Settings', null, $this->option_name);
        add_settings_field('keywords', 'Keyword to Affiliate URL Mapping', array($this, 'field_keywords'), $this->option_name, 'aalp_main');
    }

    public function validate_settings($input) {
        $output = array();
        if (isset($input['keywords'])) {
            $lines = explode("\n", $input['keywords']);
            foreach($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                $parts = explode(',', $line);
                if(count($parts) == 2) {
                    $keyword = sanitize_text_field(trim($parts));
                    $url = esc_url_raw(trim($parts[1]));
                    if ($keyword && $url) {
                        $output[$keyword] = $url;
                    }
                }
            }
        }
        return array('keywords' => $output);
    }

    public function field_keywords() {
        $options = get_option($this->option_name);
        $keywords = isset($options['keywords']) ? $options['keywords'] : array();
        $text = '';
        foreach ($keywords as $keyword => $url) {
            $text .= $keyword . ', ' . $url . "\n";
        }
        echo '<textarea name="'.$this->option_name.'[keywords]" rows="10" cols="50" placeholder="keyword, https://affiliate-link.com">' . esc_textarea($text) . '</textarea>';
        echo '<p class="description">Enter each keyword and its affiliate URL separated by a comma, one per line.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate AutoLinker Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_name);
                do_settings_sections($this->option_name);
                submit_button();
                ?>
            </form>
            <p>Example entries:<br>
            <code>plugin, https://affiliate.example.com/product123</code><br>
            <code>hosting, https://affiliate.hosting.com/deal456</code></p>
        </div>
        <?php
    }

    public function autolink_content($content) {
        $options = get_option($this->option_name);
        if (empty($options) || empty($options['keywords'])) {
            return $content;
        }
        $replacements = $options['keywords'];

        // Avoid replacing inside links or tags
        foreach ($replacements as $keyword => $url) {
            $pattern = '/(?<![\w>])(' . preg_quote($keyword, '/') . ')(?![\w<])/i';

            // Replace first occurrence only in content
            // Build replacement with click tracking data attribute
            $replacement = '<a href="'.esc_url($url).'" class="aalp-affiliate-link" target="_blank" rel="nofollow noopener noreferrer" data-keyword="'.esc_attr($keyword).'">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        return $content;
    }

    public function track_click() {
        check_ajax_referer('aalp_nonce', 'nonce');
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $ip = $_SERVER['REMOTE_ADDR'];
        $time = current_time('mysql');
        if ($keyword) {
            global $wpdb;
            $table = $wpdb->prefix . 'aalp_clicks';
            $wpdb->insert($table, array(
                'keyword' => $keyword,
                'ip' => $ip,
                'clicked_at' => $time
            ));
            wp_send_json_success();
        } else {
            wp_send_json_error('Invalid keyword');
        }
    }

    public function create_db_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aalp_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            ip varchar(100) NOT NULL,
            clicked_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize plugin
$aalp_instance = new AffiliateAutoLinkerPro();

// Create DB table on activation
register_activation_hook(__FILE__, function() use($aalp_instance) {
    $aalp_instance->create_db_table();
});

// Register and enqueue JS directly inline (click-tracker.js)
add_action('wp_footer', function() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($){
            $('body').on('click', 'a.aalp-affiliate-link', function(e){
                var link = $(this);
                var keyword = link.data('keyword');
                if (!keyword) return;

                $.post(ajaxurl || aalpAjax.ajax_url, {
                    action: 'aalp_track_click',
                    nonce: aalpAjax.nonce,
                    keyword: keyword
                }); // fire and forget
            });
        });
    </script>
    <?php
});
