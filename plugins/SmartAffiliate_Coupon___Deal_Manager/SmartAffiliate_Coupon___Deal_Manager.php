<?php
/*
Plugin Name: SmartAffiliate Coupon & Deal Manager
Description: Manage affiliate coupons and deals with expiration, geo-targeting, and dynamic affiliate links for higher conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Coupon___Deal_Manager.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartAffiliateCoupons {

    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_boxes'));
        add_action('save_post_coupon', array($this, 'save_coupon_meta'));        
        add_shortcode('smartaffiliate_coupon', array($this, 'render_coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_styles'));

        add_action('template_redirect', array($this, 'handle_redirect'));
    }

    // Register Custom Post Type for Coupons
    public function register_coupon_post_type() {
        $labels = array(
            'name' => 'Coupons',
            'singular_name' => 'Coupon',
            'add_new' => 'Add New Coupon',
            'add_new_item' => 'Add New Coupon',
            'edit_item' => 'Edit Coupon',
            'new_item' => 'New Coupon',
            'view_item' => 'View Coupon',
            'search_items' => 'Search Coupons',
            'not_found' => 'No coupons found',
            'not_found_in_trash' => 'No coupons found in Trash',
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'coupons'),
            'supports' => array('title', 'editor'),
            'show_in_rest' => true
        );

        register_post_type('coupon', $args);
    }

    // Add Meta Boxes
    public function add_coupon_meta_boxes() {
        add_meta_box('coupon_details', 'Coupon Details', array($this, 'coupon_meta_box_html'), 'coupon', 'normal', 'high');
    }

    public function coupon_meta_box_html($post) {
        wp_nonce_field('save_coupon_meta', 'coupon_meta_nonce');

        $affiliate_link = get_post_meta($post->ID, '_affiliate_link', true);
        $expiry_date = get_post_meta($post->ID, '_expiry_date', true);
        $geo_target = get_post_meta($post->ID, '_geo_target', true);

        echo '<p><label for="affiliate_link">Affiliate URL:</label><br />';
        echo '<input type="url" id="affiliate_link" name="affiliate_link" value="' . esc_attr($affiliate_link) . '" style="width:100%;" required/></p>';

        echo '<p><label for="expiry_date">Expiry Date (UTC, optional):</label><br />';
        echo '<input type="datetime-local" id="expiry_date" name="expiry_date" value="' . esc_attr($expiry_date) . '" style="width:100%;"/></p>';

        echo '<p><label for="geo_target">Geo Targeting (Comma-separated country codes, e.g. US,CA,GB; leave empty for all):</label><br />';
        echo '<input type="text" id="geo_target" name="geo_target" value="' . esc_attr($geo_target) . '" style="width:100%;"/></p>';

        echo '<p><strong>How to use:</strong> Insert shortcode [smartaffiliate_coupon id="' . $post->ID .'" text="Use Coupon"] to display coupon button.</p>';
    }

    // Save Meta Data
    public function save_coupon_meta($post_id) {
        if (!isset($_POST['coupon_meta_nonce']) || !wp_verify_nonce($_POST['coupon_meta_nonce'], 'save_coupon_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['affiliate_link'])) {
            update_post_meta($post_id, '_affiliate_link', esc_url_raw($_POST['affiliate_link']));
        }
        if (isset($_POST['expiry_date'])) {
            $date = sanitize_text_field($_POST['expiry_date']);
            update_post_meta($post_id, '_expiry_date', $date);
        }
        if (isset($_POST['geo_target'])) {
            $geo = sanitize_text_field($_POST['geo_target']);
            update_post_meta($post_id, '_geo_target', $geo);
        }
    }

    // Enqueue styles
    public function enqueue_scripts_styles() {
        wp_register_style('smartaff_coupon_style', plugins_url('smartaff_coupon_style.css', __FILE__));
        wp_enqueue_style('smartaff_coupon_style');
    }

    // Shortcode rendering
    public function render_coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'text' => 'Get Coupon'
        ), $atts, 'smartaffiliate_coupon');

        $post_id = intval($atts['id']);
        if (!$post_id || get_post_type($post_id) !== 'coupon') {
            return '<p>Invalid coupon ID.</p>';
        }

        $expiry = get_post_meta($post_id, '_expiry_date', true);
        $geo_target = get_post_meta($post_id, '_geo_target', true);

        // Check expiry
        if ($expiry) {
            $expiry_ts = strtotime($expiry . ' UTC');
            if (time() > $expiry_ts) {
                return '<p>This coupon has expired.</p>';
            }
        }

        // Geo-targeting
        if ($geo_target) {
            $allowed_countries = array_map('trim', explode(',', strtoupper($geo_target)));
            $user_ip = $this->get_user_ip();
            $country = $this->get_country_by_ip($user_ip);
            if (!in_array($country, $allowed_countries)) {
                return '<p>This coupon is not available in your region.</p>';
            }
        }

        $link = esc_url(home_url('/smartaff_redirect/?coupon_id=' . $post_id));
        $text = esc_html($atts['text']);

        return '<a class="smartaff-coupon-btn" href="' . $link . '" target="_blank" rel="nofollow noopener">' . $text . '</a>';
    }

    // Redirect handler for affiliate link and tracking
    public function handle_redirect() {
        if (isset($_GET['coupon_id'])) {
            $post_id = intval($_GET['coupon_id']);
            if ($post_id && get_post_type($post_id) === 'coupon') {
                $affiliate_link = get_post_meta($post_id, '_affiliate_link', true);
                if ($affiliate_link) {
                    // Track clicks (simple example)
                    $clicks = (int) get_post_meta($post_id, '_clicks', true);
                    update_post_meta($post_id, '_clicks', $clicks + 1);

                    // Append UTM param if not present
                    if (strpos($affiliate_link, 'utm_source=') === false) {
                        $delimiter = (strpos($affiliate_link, '?') === false) ? '?' : '&';
                        $affiliate_link .= $delimiter . 'utm_source=smartaffiliate&utm_campaign=coupon' . $post_id;
                    }

                    wp_redirect($affiliate_link);
                    exit;
                }
            }
        }
    }

    // Utility: Get user IP
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return sanitize_text_field(trim($ip_list));
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return '0.0.0.0';
    }

    // Utility: Simplified geo IP lookup (uses free API)
    private function get_country_by_ip($ip) {
        static $cache = array();
        if (isset($cache[$ip])) {
            return $cache[$ip];
        }
        $country = 'XX';
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                if ($body) {
                    $country = trim(strtoupper($body));
                }
            }
        }
        $cache[$ip] = $country;
        return $country;
    }

}

new SmartAffiliateCoupons();
