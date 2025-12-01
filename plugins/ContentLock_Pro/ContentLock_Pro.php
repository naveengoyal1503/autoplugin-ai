<?php
/*
Plugin Name: ContentLock Pro
Plugin URI: https://contentlockpro.com
Description: Lock and monetize your WordPress content with subscriptions, memberships, and paywalls
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentLock_Pro.php
License: GPL2
Text Domain: contentlock-pro
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTLOCK_PRO_VERSION', '1.0.0');
define('CONTENTLOCK_PRO_FILE', __FILE__);
define('CONTENTLOCK_PRO_DIR', plugin_dir_path(__FILE__));
define('CONTENTLOCK_PRO_URL', plugin_dir_url(__FILE__));

class ContentLockPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'apply_content_lock'));
        add_shortcode('contentlock_unlock_form', array($this, 'unlock_form_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_contentlock_process_payment', array($this, 'process_payment'));
        register_activation_hook(CONTENTLOCK_PRO_FILE, array($this, 'activate'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentlock_subscriptions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            plan_id mediumint(9) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY plan_id (plan_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function register_post_type() {
        register_post_type('contentlock_plan', array(
            'label' => 'Subscription Plans',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'contentlock-pro',
            'supports' => array('title'),
            'capability_type' => 'post',
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentLock Pro',
            'ContentLock Pro',
            'manage_options',
            'contentlock-pro',
            array($this, 'render_dashboard'),
            'dashicons-lock',
            30
        );
        
        add_submenu_page(
            'contentlock-pro',
            'Settings',
            'Settings',
            'manage_options',
            'contentlock-settings',
            array($this, 'render_settings')
        );
    }

    public function register_settings() {
        register_setting('contentlock_pro_settings', 'contentlock_stripe_key');
        register_setting('contentlock_pro_settings', 'contentlock_stripe_secret');
        register_setting('contentlock_pro_settings', 'contentlock_currency', array('default' => 'USD'));
    }

    public function render_dashboard() {
        echo '<div class="wrap"><h1>ContentLock Pro Dashboard</h1>';
        echo '<p>Manage your content locks and subscription plans.</p>';
        echo '<a href="' . admin_url('post-new.php?post_type=contentlock_plan') . '" class="button button-primary">Create Plan</a>';
        echo '</div>';
    }

    public function render_settings() {
        echo '<div class="wrap"><h1>ContentLock Pro Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('contentlock_pro_settings');
        do_settings_sections('contentlock_pro_settings');
        echo '<table class="form-table"><tr>';
        echo '<th scope="row"><label for="contentlock_stripe_key">Stripe Publishable Key</label></th>';
        echo '<td><input type="text" name="contentlock_stripe_key" id="contentlock_stripe_key" value="' . esc_attr(get_option('contentlock_stripe_key')) . '" class="regular-text"></td>';
        echo '</tr><tr>';
        echo '<th scope="row"><label for="contentlock_stripe_secret">Stripe Secret Key</label></th>';
        echo '<td><input type="password" name="contentlock_stripe_secret" id="contentlock_stripe_secret" value="' . esc_attr(get_option('contentlock_stripe_secret')) . '" class="regular-text"></td>';
        echo '</tr></table>';
        submit_button();
        echo '</form></div>';
    }

    public function apply_content_lock($content) {
        if (is_singular('post') && !current_user_can('edit_posts')) {
            $post_id = get_the_ID();
            $lock_enabled = get_post_meta($post_id, '_contentlock_enabled', true);
            
            if ($lock_enabled) {
                $plan_id = get_post_meta($post_id, '_contentlock_plan_id', true);
                $user_id = get_current_user_id();
                
                if (!$this->user_has_access($user_id, $plan_id)) {
                    $preview = get_post_meta($post_id, '_contentlock_preview', true);
                    $content = wp_kses_post($preview) . $this->get_unlock_form($plan_id);
                }
            }
        }
        return $content;
    }

    private function user_has_access($user_id, $plan_id) {
        if (!$user_id) return false;
        
        global $wpdb;
        $subscription = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}contentlock_subscriptions WHERE user_id = %d AND plan_id = %d AND status = 'active' AND (end_date IS NULL OR end_date > NOW())",
            $user_id,
            $plan_id
        ));
        
        return !empty($subscription);
    }

    private function get_unlock_form($plan_id) {
        $plan = get_post($plan_id);
        $price = get_post_meta($plan_id, '_contentlock_price', true);
        $currency = get_option('contentlock_currency', 'USD');
        
        ob_start();
        echo '<div class="contentlock-unlock-box" style="border: 1px solid #ddd; padding: 20px; margin: 20px 0; background: #f9f9f9;">';
        echo '<h3>Unlock Premium Content</h3>';
        echo '<p>Subscribe to access this content and more.</p>';
        echo '<form class="contentlock-payment-form" data-plan-id="' . intval($plan_id) . '" data-price="' . intval($price * 100) . '" data-currency="' . esc_attr($currency) . '">';
        echo '<button type="submit" class="button button-primary">Subscribe for $' . esc_html($price) . '/' . esc_html(get_post_meta($plan_id, '_contentlock_interval', true)) . '</button>';
        echo '</form></div>';
        return ob_get_clean();
    }

    public function unlock_form_shortcode($atts) {
        $atts = shortcode_atts(array('plan_id' => 0), $atts);
        return $this->get_unlock_form($atts['plan_id']);
    }

    public function process_payment() {
        if (!wp_verify_nonce($_POST['nonce'], 'contentlock_payment')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $plan_id = intval($_POST['plan_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'contentlock_subscriptions',
            array(
                'user_id' => $user_id,
                'plan_id' => $plan_id,
                'status' => 'active',
                'end_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
            ),
            array('%d', '%d', '%s', '%s')
        );
        
        wp_send_json_success('Subscription created');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('contentlock-pro', CONTENTLOCK_PRO_URL . 'js/contentlock.js', array('jquery'), CONTENTLOCK_PRO_VERSION);
        wp_localize_script('contentlock-pro', 'contentlock_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentlock_payment')
        ));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('contentlock-admin', CONTENTLOCK_PRO_URL . 'js/admin.js', array('jquery'), CONTENTLOCK_PRO_VERSION);
    }
}

function contentlock_pro_init() {
    return ContentLockPro::get_instance();
}

contentlock_pro_init();

add_action('add_meta_boxes', function() {
    add_meta_box('contentlock_settings', 'ContentLock Pro', function($post) {
        wp_nonce_field('contentlock_meta', 'contentlock_nonce');
        $enabled = get_post_meta($post->ID, '_contentlock_enabled', true);
        $plan_id = get_post_meta($post->ID, '_contentlock_plan_id', true);
        echo '<label><input type="checkbox" name="contentlock_enabled" value="1" ' . checked($enabled, 1, false) . '> Enable content lock</label>';
        echo '<p><label>Subscription Plan: <select name="contentlock_plan_id"><option value="">Select Plan</option>';
        foreach (get_posts(array('post_type' => 'contentlock_plan', 'numberposts' => -1)) as $p) {
            echo '<option value="' . $p->ID . '" ' . selected($plan_id, $p->ID, false) . '>' . $p->post_title . '</option>';
        }
        echo '</select></label></p>';
        echo '<p><label>Preview Text (before unlock):<br><textarea name="contentlock_preview" rows="4" style="width:100%;">' . esc_textarea(get_post_meta($post->ID, '_contentlock_preview', true)) . '</textarea></label></p>';
    }, 'post', 'normal');
});

add_action('save_post', function($post_id) {
    if (!isset($_POST['contentlock_nonce']) || !wp_verify_nonce($_POST['contentlock_nonce'], 'contentlock_meta')) return;
    update_post_meta($post_id, '_contentlock_enabled', isset($_POST['contentlock_enabled']) ? 1 : 0);
    update_post_meta($post_id, '_contentlock_plan_id', intval($_POST['contentlock_plan_id']));
    update_post_meta($post_id, '_contentlock_preview', sanitize_textarea_field($_POST['contentlock_preview']));
});

add_action('save_post_contentlock_plan', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['contentlock_plan_nonce'])) {
        update_post_meta($post_id, '_contentlock_price', floatval($_POST['contentlock_price']));
        update_post_meta($post_id, '_contentlock_interval', sanitize_text_field($_POST['contentlock_interval']));
    }
});

add_action('edit_form_advanced', function($post) {
    if ($post->post_type === 'contentlock_plan') {
        wp_nonce_field('contentlock_plan_meta', 'contentlock_plan_nonce');
        echo '<div class="inside" style="padding:10px;">';
        echo '<p><label>Price: \$<input type="number" name="contentlock_price" value="' . esc_attr(get_post_meta($post->ID, '_contentlock_price', true)) . '" step="0.01"></label></p>';
        echo '<p><label>Interval: <select name="contentlock_interval"><option value="month" ' . selected(get_post_meta($post->ID, '_contentlock_interval', true), 'month', false) . '>Month</option><option value="year" ' . selected(get_post_meta($post->ID, '_contentlock_interval', true), 'year', false) . '>Year</option></select></label></p>';
        echo '</div>';
    }
});
?>