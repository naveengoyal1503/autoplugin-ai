<?php
/*
Plugin Name: Content Monetizer Pro
Description: Multi-strategy content monetization plugin for WordPress.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Content_Monetizer_Pro.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class ContentMonetizerPro {
    private static $instance = null;
    private $plugin_slug = 'content-monetizer-pro';
    private $options;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        add_filter('the_content', array($this, 'inject_affiliate_links'));

        add_shortcode('cmp_subscribe_button', array($this, 'subscribe_button_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_action('wp_ajax_cmp_microtransaction', array($this, 'handle_microtransaction'));
        add_action('wp_ajax_nopriv_cmp_microtransaction', array($this, 'handle_microtransaction'));
    }

    public function add_admin_menu() {
        add_menu_page('Content Monetizer Pro', 'Content Monetizer', 'manage_options', $this->plugin_slug, array($this, 'settings_page'), 'dashicons-chart-line');
    }

    public function register_settings() {
        register_setting($this->plugin_slug.'_settings_group', $this->plugin_slug.'_options');
        add_settings_section('cmp_main_section', 'Main Settings', null, $this->plugin_slug);

        add_settings_field('affiliate_domains', 'Affiliate Domains (comma separated)', array($this, 'affiliate_domains_callback'), $this->plugin_slug, 'cmp_main_section');
        add_settings_field('sponsored_post_keyword', 'Sponsored Content Keyword', array($this, 'sponsored_post_keyword_callback'), $this->plugin_slug, 'cmp_main_section');
        add_settings_field('microtransaction_amount', 'Microtransaction Amount (USD)', array($this, 'microtransaction_amount_callback'), $this->plugin_slug, 'cmp_main_section');
        add_settings_field('subscription_enabled', 'Enable Subscriptions', array($this, 'subscription_enabled_callback'), $this->plugin_slug, 'cmp_main_section');
    }

    public function affiliate_domains_callback() {
        $options = get_option($this->plugin_slug.'_options');
        echo '<input type="text" name="'.$this->plugin_slug.'_options[affiliate_domains]" value="'.esc_attr($options['affiliate_domains'] ?? '').'" style="width: 300px;" placeholder="example.com,shop.com" />';
    }

    public function sponsored_post_keyword_callback() {
        $options = get_option($this->plugin_slug.'_options');
        echo '<input type="text" name="'.$this->plugin_slug.'_options[sponsored_post_keyword]" value="'.esc_attr($options['sponsored_post_keyword'] ?? '').'" style="width: 300px;" placeholder="sponsored" />';
        echo '<p class="description">Posts containing this keyword in title will be marked as sponsored automatically.</p>';
    }

    public function microtransaction_amount_callback() {
        $options = get_option($this->plugin_slug.'_options');
        echo '<input type="number" step="0.01" min="0" name="'.$this->plugin_slug.'_options[microtransaction_amount]" value="'.esc_attr($options['microtransaction_amount'] ?? '0').'" style="width: 100px;" />';
        echo '<p class="description">Users can pay this amount to unlock micro-content.</p>';
    }

    public function subscription_enabled_callback() {
        $options = get_option($this->plugin_slug.'_options');
        $checked = isset($options['subscription_enabled']) && $options['subscription_enabled'] == 1 ? 'checked' : '';
        echo '<input type="checkbox" name="'.$this->plugin_slug.'_options[subscription_enabled]" value="1" '.$checked.' /> Enable subscription features';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Content Monetizer Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->plugin_slug.'_settings_group');
                do_settings_sections($this->plugin_slug);
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Affiliate links in posts will be automatically linked to domains specified in settings.</p>
            <p>Add <code>[cmp_subscribe_button]</code> shortcode to display subscription button.</p>
            <p>Microtransaction unlock can be triggered via AJAX through frontend UI integration.</p>
        </div>
        <?php
    }

    public function inject_affiliate_links($content) {
        $options = get_option($this->plugin_slug.'_options');
        if (empty($options['affiliate_domains'])) return $content;

        $domains = array_map('trim', explode(',', $options['affiliate_domains']));
        if (empty($domains)) return $content;

        foreach ($domains as $domain) {
            // Simple link detection and replacement
            $pattern = '/https?:\/\/(www\.)?'.preg_quote($domain, '/').'[\S]*/i';
            $replacement = '<a href="$0?ref=cmp" target="_blank" rel="nofollow noopener">$0</a>';
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Mark sponsored posts
        if (!empty($options['sponsored_post_keyword']) && is_single()) {
            global $post;
            $keyword = trim($options['sponsored_post_keyword']);
            if (stripos($post->post_title, $keyword) !== false) {
                $content = '<div style="border:2px solid #ffae42;padding:10px;margin-bottom:20px;background:#fff9e6;"><strong>Sponsored Content</strong></div>'.$content;
            }
        }

        return $content;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('cmp_script', plugin_dir_url(__FILE__).'cmp-script.js', array('jquery'), '1.0', true);
        wp_localize_script('cmp_script', 'cmpAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function subscribe_button_shortcode() {
        $options = get_option($this->plugin_slug.'_options');
        if (empty($options['subscription_enabled'])) return '<p>Subscription feature is disabled.</p>';

        if (is_user_logged_in()) {
            return '<button id="cmp-subscribe-btn">Subscribe for Premium Access</button><div id="cmp-subscribe-msg"></div><script>jQuery(document).ready(function($){$("#cmp-subscribe-btn").click(function(){
    $.post(cmpAjax.ajaxurl, {action: "cmp_microtransaction"}, function(response){
        $("#cmp-subscribe-msg").html(response.data.message);
    });
});});</script>';
        } else {
            return '<p>Please log in to subscribe.</p>';
        }
    }

    public function handle_microtransaction() {
        // This is a stub for microtransaction processing
        // In real use, should integrate payment gateway (Stripe, PayPal, etc.)
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to subscribe.'));
        }

        $user_id = get_current_user_id();
        // Simulate successful transaction
        // Ideally store subscription status, expire date, etc.
        update_user_meta($user_id, 'cmp_subscribed', 1);

        wp_send_json_success(array('message' => 'Thank you for subscribing! Premium access granted.'));
    }
}

ContentMonetizerPro::get_instance();
