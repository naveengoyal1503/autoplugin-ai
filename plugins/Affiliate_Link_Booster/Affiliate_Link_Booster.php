<?php
/*
Plugin Name: Affiliate Link Booster
Plugin URI: https://example.com/affiliate-link-booster
Description: Automatically inserts and optimizes affiliate links in your posts, with analytics and click tracking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Booster.php
License: GPL2
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class AffiliateLinkBooster {
    private $option_name = 'alb_affiliate_links';
    private $clicks_option = 'alb_click_data';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_action('wp_ajax_alb_record_click', array($this, 'record_click'));
        add_action('wp_ajax_nopriv_alb_record_click', array($this, 'record_click'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function admin_menu() {
        add_options_page('Affiliate Link Booster', 'Affiliate Link Booster', 'manage_options', 'affiliate-link-booster', array($this, 'settings_page'));
    }

    public function settings_init() {
        register_setting('alb_settings', $this->option_name, array($this, 'sanitize_affiliate_links'));
        add_settings_section('alb_section', 'Affiliate Links Settings', null, 'affiliate-link-booster');
        add_settings_field('alb_field_links', 'Affiliate Keywords and URLs', array($this, 'affiliate_links_field'), 'affiliate-link-booster', 'alb_section');
    }

    public function sanitize_affiliate_links($input) {
        $clean = array();
        $lines = explode("\n", $input);
        foreach ($lines as $line) {
            $parts = array_map('trim', explode(',', $line));
            if (count($parts) === 2 && filter_var($parts[1], FILTER_VALIDATE_URL)) {
                $clean[$parts] = esc_url_raw($parts[1]);
            }
        }
        return $clean;
    }

    public function affiliate_links_field() {
        $links = get_option($this->option_name, array());
        $text = '';
        foreach ($links as $keyword => $url) {
            $text .= esc_html($keyword) . ', ' . esc_url($url) . "\n";
        }
        echo '<textarea name="' . esc_attr($this->option_name) . '" rows="10" cols="50" placeholder="keyword, https://affiliate-link.com"></textarea>';
        echo '<br><small>Enter one keyword and URL per line, separated by a comma.</small>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Link Booster Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('alb_settings');
                do_settings_sections('affiliate-link-booster');
                submit_button();
                ?>
            </form>
            <h2>Click Analytics</h2>
            <?php $this->render_click_stats(); ?>
        </div>
        <?php
    }

    private function render_click_stats() {
        $click_data = get_option($this->clicks_option, array());
        if (empty($click_data)) {
            echo '<p>No clicks recorded yet.</p>';
            return;
        }
        echo '<table style="border-collapse: collapse; width: 100%;">';
        echo '<thead><tr><th style="border:1px solid #ddd;padding:8px;">Keyword</th><th style="border:1px solid #ddd;padding:8px;">Clicks</th></tr></thead><tbody>';
        foreach ($click_data as $keyword => $count) {
            echo '<tr><td style="border:1px solid #ddd;padding:8px;">' . esc_html($keyword) . '</td><td style="border:1px solid #ddd;padding:8px;">' . intval($count) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public function insert_affiliate_links($content) {
        $affiliate_links = get_option($this->option_name, array());
        if (empty($affiliate_links)) return $content;

        foreach ($affiliate_links as $keyword => $url) {
            $escaped_keyword = preg_quote($keyword, '/');
            $pattern = '/(?<!<a[^>]*?>)\b(' . $escaped_keyword . ')\b(?![^<]*?<\/a>)/i';

            $replacement = '<a href="' . esc_url($url) . '" class="alb-affiliate-link" data-keyword="' . esc_attr(strtolower($keyword)) . '" target="_blank" rel="nofollow noopener noreferrer">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        return $content;
    }

    public function enqueue_scripts() {
        if (!is_singular()) return;
        wp_enqueue_script('alb_script', plugin_dir_url(__FILE__) . 'alb-script.js', array('jquery'), '1.0', true);
        wp_localize_script('alb_script', 'alb_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alb_nonce')
        ));
    }

    public function record_click() {
        check_ajax_referer('alb_nonce', 'security');
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        if (empty($keyword)) wp_send_json_error('No keyword');

        $click_data = get_option($this->clicks_option, array());
        if (!isset($click_data[$keyword])) {
            $click_data[$keyword] = 0;
        }
        $click_data[$keyword]++;
        update_option($this->clicks_option, $click_data);

        wp_send_json_success();
    }
}

new AffiliateLinkBooster();

// JavaScript in same PHP file for single-file plugin (echoed inline) to keep one file
add_action('wp_footer', function() {
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.alb-affiliate-link').forEach(function(el) {
            el.addEventListener('click', function(e) {
                var keyword = el.getAttribute('data-keyword');
                if (!keyword) return;

                var data = new FormData();
                data.append('action', 'alb_record_click');
                data.append('security', '<?php echo wp_create_nonce('alb_nonce'); ?>');
                data.append('keyword', keyword);

                navigator.sendBeacon('<?php echo admin_url('admin-ajax.php'); ?>', data);
            });
        });
    });
    </script>
    <?php
});
