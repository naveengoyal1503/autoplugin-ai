<?php
/*
Plugin Name: AutoAffiliate Manager
Description: Auto-converts product mentions into affiliate links with performance tracking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AutoAffiliate_Manager.php
*/

class AutoAffiliateManager {
    private $affiliate_networks;
    private $tracked_links_option = 'aam_tracked_links';

    public function __construct() {
        $this->affiliate_networks = array(
            'amazon' => 'https://amazon.com/dp/',
            // Add other networks as needed
        );
        add_filter('the_content', array($this, 'auto_convert_affiliate_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aam_track_click', array($this, 'track_click')); // logged in
        add_action('wp_ajax_nopriv_aam_track_click', array($this, 'track_click'));// logged out

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        if (!get_option($this->tracked_links_option)) {
            add_option($this->tracked_links_option, array());
        }
    }

    public function deactivate() {
        // Optionally cleanup
    }

    // Scan content and replace product mentions with affiliate links
    public function auto_convert_affiliate_links($content) {
        // Simple example: scan for ASINs like B08XYZ1234 in content and replace with affiliate link
        $pattern = '/\bB[A-Z0-9]{9}\b/';
        $content = preg_replace_callback($pattern, function($matches) {
            $asin = $matches;
            $link = esc_url($this->affiliate_networks['amazon'] . $asin . '?tag=yourtag-20');
            // Track the link added
            $this->add_tracked_link($link);
            return '<a href="' . $link . '" class="aam-affiliate-link" target="_blank" rel="nofollow noopener">' . $asin . '</a>';
        }, $content);

        return $content;
    }

    private function add_tracked_link($url) {
        $tracked = get_option($this->tracked_links_option, array());
        if (!in_array($url, $tracked)) {
            $tracked[] = $url;
            update_option($this->tracked_links_option, $tracked);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aam-script', plugin_dir_url(__FILE__) . 'aam-script.js', array('jquery'), '1.0', true);
        wp_localize_script('aam-script', 'aam_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function track_click() {
        if (!isset($_POST['url'])) {
            wp_send_json_error('Missing URL');
        }
        $url = sanitize_text_field($_POST['url']);

        $clicks = get_option('aam_clicks', array());
        if (!isset($clicks[$url])) {
            $clicks[$url] = 0;
        }
        $clicks[$url]++;
        update_option('aam_clicks', $clicks);

        wp_send_json_success(array('clicks' => $clicks[$url]));
    }

}

new AutoAffiliateManager();

// JS injected inline since only single file allowed
add_action('wp_footer', function(){
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('a.aam-affiliate-link').on('click', function(e){
            var url = $(this).attr('href');
            $.post(aam_ajax.ajax_url, {
                action: 'aam_track_click',
                url: url
            });
        });
    });
    </script>
    <?php
});