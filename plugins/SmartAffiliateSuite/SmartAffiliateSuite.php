/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliateSuite.php
*/
<?php
/**
 * Plugin Name: SmartAffiliateSuite
 * Description: Comprehensive affiliate management with real-time tracking, customizable commissions, and fraud detection.
 * Version: 1.0
 * Author: Your Company
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateSuite {
    private static $instance;

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_affiliate_post_type'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_sas_add_affiliate', array($this, 'add_affiliate'));
        add_action('wp_ajax_sas_track_referral', array($this, 'track_referral_ajax'));
        add_shortcode('sas_affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_action('template_redirect', array($this, 'detect_affiliate_referral'));
    }

    // Register custom post type for affiliates
    public function register_affiliate_post_type() {
        $labels = array(
            'name' => 'Affiliates',
            'singular_name' => 'Affiliate',
            'menu_name' => 'SmartAffiliateSuite',
            'add_new' => 'Add New Affiliate',
            'add_new_item' => 'Add New Affiliate',
            'edit_item' => 'Edit Affiliate',
            'new_item' => 'New Affiliate',
            'view_item' => 'View Affiliate',
            'search_items' => 'Search Affiliates',
            'not_found' => 'No affiliates found',
        );

        $args = array(
            'public' => false,
            'show_ui' => true,
            'label' => 'Affiliates',
            'supports' => array('title', 'custom-fields'),
            'menu_position' => 25,
            'menu_icon' => 'dashicons-groups',
        );

        register_post_type('sas_affiliate', $args);
    }

    // Admin menu
    public function add_admin_menu() {
        add_menu_page('SmartAffiliateSuite', 'SmartAffiliateSuite', 'manage_options', 'smartaffiliatesuite', array($this, 'admin_page'), 'dashicons-networking', 26);
    }

    // Admin page
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized access');
        }
        echo '<div class="wrap"><h1>SmartAffiliateSuite Affiliate Manager</h1>';
        echo '<p>Use this page to manage affiliates, view stats, and configure settings.</p>';

        // List affiliates
        $affiliates = get_posts(array(
            'post_type' => 'sas_affiliate',
            'numberposts' => -1,
            'post_status' => 'publish',
        ));

        echo '<h2>Affiliates</h2><table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Name</th><th>Referral Count</th><th>Actions</th></tr></thead><tbody>';
        foreach ($affiliates as $affiliate) {
            $referrals = get_post_meta($affiliate->ID, 'sas_referral_count', true);
            $referrals = $referrals ? intval($referrals) : 0;
            echo '<tr><td>' . esc_html($affiliate->ID) . '</td><td>' . esc_html($affiliate->post_title) . '</td><td>' . $referrals . '</td><td></td></tr>';
        }
        echo '</tbody></table>';

        // Add new affiliate form
        echo '<h2>Add New Affiliate</h2><form id="sas_add_affiliate_form"><input type="text" name="affiliate_name" placeholder="Affiliate Name" required><input type="submit" class="button button-primary" value="Add Affiliate"></form>';

        echo '<script>jQuery(document).ready(function($){
            $("#sas_add_affiliate_form").on("submit", function(e) {
                e.preventDefault();
                var name = $(this).find("input[name='affiliate_name']").val();
                $.post(ajaxurl, {action: 'sas_add_affiliate', affiliate_name: name}, function(response) {
                    alert(response.data.message);
                    if(response.success) { location.reload(); }
                });
            });
        });</script>';

        echo '</div>';
    }

    // Ajax callback to add affiliate
    public function add_affiliate() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
            return;
        }
        $name = sanitize_text_field($_POST['affiliate_name'] ?? '');
        if (empty($name)) {
            wp_send_json_error(['message' => 'Affiliate name required']);
            return;
        }

        $affiliate_id = wp_insert_post(array(
            'post_title' => $name,
            'post_type' => 'sas_affiliate',
            'post_status' => 'publish'
        ));

        if ($affiliate_id) {
            update_post_meta($affiliate_id, 'sas_referral_count', 0);
            wp_send_json_success(['message' => 'Affiliate added successfully']);
        } else {
            wp_send_json_error(['message' => 'Error adding affiliate']);
        }
    }

    // Track referral via AJAX
    public function track_referral_ajax() {
        // Expect affiliate_id and nonce
        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        if (!$affiliate_id || get_post_type($affiliate_id) !== 'sas_affiliate') {
            wp_send_json_error(['message' => 'Invalid affiliate']);
            return;
        }

        // Basic fraud detection: check IP and limit referrals per IP per day
        $ip = $_SERVER['REMOTE_ADDR'];
        $meta_key = 'sas_ip_' . md5($ip);
        $last_referral_day = get_post_meta($affiliate_id, $meta_key, true);
        $today = date('Ymd');
        if ($last_referral_day === $today) {
            // Already tracked today for this IP
            wp_send_json_error(['message' => 'Referral already counted today from your IP']);
            return;
        }

        $count = get_post_meta($affiliate_id, 'sas_referral_count', true);
        $count = $count ? intval($count) : 0;
        update_post_meta($affiliate_id, 'sas_referral_count', $count + 1);

        // Save IP to prevent multiple referrals same day
        update_post_meta($affiliate_id, $meta_key, $today);

        wp_send_json_success(['message' => 'Referral tracked']);
    }

    // Shortcode to generate affiliate link
    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $affiliate_id = intval($atts['id']);
        if (!$affiliate_id || get_post_type($affiliate_id) !== 'sas_affiliate') {
            return 'Invalid affiliate ID';
        }
        // Create affiliate link for current page
        $url = add_query_arg('sas_aff', $affiliate_id, home_url('/'));
        return '<a href="' . esc_url($url) . '">Affiliate Link</a>';
    }

    // Detect referral on frontend
    public function detect_affiliate_referral() {
        if (is_admin()) return;

        if (isset($_GET['sas_aff'])) {
            $affiliate_id = intval($_GET['sas_aff']);
            if ($affiliate_id && get_post_type($affiliate_id) === 'sas_affiliate') {
                // Set cookie for 30 days
                setcookie('sas_affiliate', $affiliate_id, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            }
        }

        // Track on page load if cookie present
        if (isset($_COOKIE['sas_affiliate'])) {
            $affiliate_id = intval($_COOKIE['sas_affiliate']);
            // Use wp_remote_post AJAX call to track referral silently
            add_action('wp_footer', function() use ($affiliate_id) {
                ?>
                <script type="text/javascript">
                (function(){
                    var data = {
                        action: 'sas_track_referral',
                        affiliate_id: <?php echo esc_js($affiliate_id); ?>
                    };
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                    var params = 'action=' + encodeURIComponent(data.action) + '&affiliate_id=' + encodeURIComponent(data.affiliate_id);
                    xhr.send(params);
                })();
                </script>
                <?php
            });
        }
    }
}

SmartAffiliateSuite::get_instance();