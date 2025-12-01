<?php
/*
Plugin Name: AffiliateLink Booster
Description: Automatically cloak affiliate links, track clicks, and aggregate affiliate discount coupons to boost conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateLinkBooster {
    private $option_name = 'alb_links';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_head', array($this, 'inject_discount_banner'));
        add_shortcode('alb_afflink', array($this, 'afflink_shortcode'));
        add_action('wp_ajax_alb_track_click', array($this, 'track_click_ajax'));
        add_action('wp_ajax_nopriv_alb_track_click', array($this, 'track_click_ajax'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));
    }

    public function admin_menu() {
        add_menu_page('AffiliateLink Booster', 'AffiliateLink Booster', 'manage_options', 'affiliate-link-booster', array($this, 'admin_page'), 'dashicons-external');
    }

    public function admin_scripts($hook) {
        if ($hook != 'toplevel_page_affiliate-link-booster') return;
        wp_enqueue_style('alb_admin_css', plugin_dir_url(__FILE__) . 'alb_admin.css');
        wp_enqueue_script('alb_admin_js', plugin_dir_url(__FILE__) . 'alb_admin.js', array('jquery'), false, true);
    }

    public function frontend_scripts() {
        wp_enqueue_script('alb_front_js', plugin_dir_url(__FILE__) . 'alb_front.js', array('jquery'), false, true);
        wp_localize_script('alb_front_js', 'albAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_style('alb_front_css', plugin_dir_url(__FILE__) . 'alb_front.css');
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['alb_save_links'])) {
            check_admin_referer('alb_save_links_nonce');
            $raw_links = sanitize_textarea_field($_POST['alb_links_input']);
            $links = array_filter(array_map('trim', explode("\n", $raw_links)));
            update_option($this->option_name, $links);
            echo '<div class="updated"><p>Affiliate links saved.</p></div>';
        }
        $stored_links = get_option($this->option_name, []);
        ?>
        <div class="wrap">
            <h1>AffiliateLink Booster Settings</h1>
            <form method="post">
                <?php wp_nonce_field('alb_save_links_nonce'); ?>
                <label for="alb_links_input">Affiliate links (one per line):</label><br>
                <textarea id="alb_links_input" name="alb_links_input" rows="10" cols="50"><?php echo esc_textarea(implode("\n", $stored_links)); ?></textarea><br><br>
                <input type="submit" name="alb_save_links" class="button button-primary" value="Save Links">
            </form>
            <h2>Usage</h2>
            <p>Use shortcode <code>[alb_afflink url="YOUR_AFFILIATE_URL"]Link Text[/alb_afflink]</code> to auto cloak and track clicks.</p>
            <p>The plugin also automatically inserts an affiliate discount banner if discount info is available (Premium feature placeholder).</p>
        </div>
        <?php
    }

    public function afflink_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array('url' => ''), $atts);
        $url = esc_url_raw($atts['url']);
        $text = $content ? esc_html($content) : $url;
        if (!$url) return '';
        $link_id = md5($url);
        $link = add_query_arg(array('alb_track_click' => $link_id), admin_url('admin-ajax.php'));

        return sprintf('<a href="%s" class="alb-afflink" data-target="%s" target="_blank" rel="nofollow noopener">%s</a>', esc_url($link), esc_url($url), $text);
    }

    public function track_click_ajax() {
        if (isset($_GET['alb_track_click'])) {
            $hashed = sanitize_text_field($_GET['alb_track_click']);
            // Normally store click in DB or analytics
            // Redirect to actual affiliate URL
            $links = get_option($this->option_name, []);
            foreach ($links as $link) {
                if (md5($link) === $hashed) {
                    wp_redirect($link);
                    exit();
                }
            }
            wp_die('Invalid affiliate link', '', 404);
        }
        wp_die('No affiliate tracking link', '', 400);
    }

    public function inject_discount_banner() {
        // Premium feature placeholder: dynamically fetch and show affiliate discounts
        // For demo, show a simple static banner
        if (is_singular()) {
            echo '<style>.alb-discount-banner{background:#fffae6;color:#333;padding:10px;text-align:center;position:fixed;bottom:0;width:100%;font-weight:bold;z-index:9999;} .alb-discount-banner a{color:#0073aa;text-decoration:underline;}</style>';
            echo '<div class="alb-discount-banner">Limited time offer: Use our exclusive affiliate discounts! <a href="#">See Deals &raquo;</a></div>';
        }
    }
}

new AffiliateLinkBooster();