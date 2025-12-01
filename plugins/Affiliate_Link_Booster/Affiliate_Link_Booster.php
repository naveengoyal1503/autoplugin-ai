/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Link Booster
 * Description: Auto-optimize affiliate links with contextual CTAs, coupon insertions, and click tracking.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateLinkBooster {
    private $version = '1.0';
    private $option_name = 'alb_click_counts';

    public function __construct() {
        add_filter('the_content', array($this, 'insert_affiliate_ctas'));
        add_action('wp_ajax_alb_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_alb_track_click', array($this, 'ajax_track_click'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        if (false === get_option($this->option_name)) {
            add_option($this->option_name, array());
        }
    }

    public function enqueue_scripts() {
        if (!is_single()) return;
        wp_enqueue_script('alb-js', plugin_dir_url(__FILE__) . 'alb-script.js', array('jquery'), $this->version, true);
        wp_localize_script('alb-js', 'alb_ajax_obj', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function insert_affiliate_ctas($content) {
        // Pattern to find affiliate links (simple example: links containing "affid")
        $pattern = '/<a[^>]+href=["\']([^"\']*affid=[^"\']*)["\'][^>]*>(.*?)<\/a>/i';

        $replacement = function ($matches) {
            $url = esc_url($matches[1]);
            $text = $matches[2];

            $cta = '<button class="alb-cta" data-url="' . esc_attr($url) . '">Grab Deal</button>';
            // Inject CTA button after the link
            return $matches . ' ' . $cta;
        };

        $new_content = preg_replace_callback($pattern, $replacement, $content);

        return $new_content ? $new_content : $content;
    }

    public function ajax_track_click() {
        if (!isset($_POST['url'])) {
            wp_send_json_error('Missing URL');
            wp_die();
        }

        $url = esc_url_raw($_POST['url']);
        $counts = get_option($this->option_name, array());

        if (isset($counts[$url])) {
            $counts[$url]++;
        } else {
            $counts[$url] = 1;
        }

        update_option($this->option_name, $counts);
        wp_send_json_success(array('clicks' => $counts[$url]));
        wp_die();
    }
}

new AffiliateLinkBooster();

// Minimal inline JS injected for demo (normally separate file 'alb-script.js')
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.alb-cta').forEach(function(button) {
            button.addEventListener('click', function() {
                var url = button.getAttribute('data-url');
                jQuery.post(alb_ajax_obj.ajaxurl, { action: 'alb_track_click', url: url })
                    .done(function(response) {
                        if (response.success) {
                            window.open(url, '_blank');
                        } else {
                            alert('Could not track click. Opening link...');
                            window.open(url, '_blank');
                        }
                    }).fail(function() {
                        window.open(url, '_blank');
                    });
            });
        });
    });
    </script>
    <?php
});
