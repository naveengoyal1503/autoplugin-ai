<?php
/*
Plugin Name: AffiliateCoupon Booster
Plugin URI: https://example.com/affiliatecouponbooster
Description: Create and manage a coupon aggregator with affiliate links and user-submitted deals to increase affiliate marketing conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateCoupon_Booster.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponBooster {
    private static $instance = null;
    private $post_type = 'acb_coupon';

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('add_meta_boxes', array($this, 'add_coupon_meta_boxes'));
        add_action('save_post', array($this, 'save_coupon_meta')); 
        add_shortcode('affiliatecoupon_form', array($this, 'render_coupon_submission_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('template_include', array($this, 'custom_coupon_archive_template'));
        add_shortcode('affiliatecoupon_list', array($this, 'render_coupon_list'));
        add_action('wp_ajax_acb_submit_coupon', array($this, 'handle_coupon_submission'));
        add_action('wp_ajax_nopriv_acb_submit_coupon', array($this, 'handle_coupon_submission'));
    }

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
            'menu_name' => 'Affiliate Coupons'
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'coupons'),
            'supports' => array('title', 'editor', 'author'),
            'capability_type' => 'post',
            'show_in_rest' => true
        );
        register_post_type($this->post_type, $args);
    }

    public function add_coupon_meta_boxes() {
        add_meta_box('acb_coupon_details', 'Coupon Details', array($this, 'coupon_meta_box_html'), $this->post_type, 'normal', 'high');
    }

    public function coupon_meta_box_html($post) {
        wp_nonce_field('acb_coupon_nonce', 'acb_coupon_nonce_field');
        $affiliate_url = get_post_meta($post->ID, '_acb_affiliate_url', true);
        $code = get_post_meta($post->ID, '_acb_coupon_code', true);
        $expiry = get_post_meta($post->ID, '_acb_expiry_date', true);
        echo '<p><label for="acb_affiliate_url">Affiliate URL (required):</label><br><input type="url" name="acb_affiliate_url" id="acb_affiliate_url" value="' . esc_attr($affiliate_url) . '" style="width:100%;" required></p>';
        echo '<p><label for="acb_coupon_code">Coupon Code (optional):</label><br><input type="text" name="acb_coupon_code" id="acb_coupon_code" value="' . esc_attr($code) . '" style="width:100%;"></p>';
        echo '<p><label for="acb_expiry_date">Expiry Date (optional):</label><br><input type="date" name="acb_expiry_date" id="acb_expiry_date" value="' . esc_attr($expiry) . '"></p>';
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['acb_coupon_nonce_field']) || !wp_verify_nonce($_POST['acb_coupon_nonce_field'], 'acb_coupon_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (isset($_POST['post_type']) && $this->post_type == $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) return;
        } else {
            return;
        }

        if (isset($_POST['acb_affiliate_url'])) {
            $url = esc_url_raw($_POST['acb_affiliate_url']);
            update_post_meta($post_id, '_acb_affiliate_url', $url);
        }
        if (isset($_POST['acb_coupon_code'])) {
            $code = sanitize_text_field($_POST['acb_coupon_code']);
            update_post_meta($post_id, '_acb_coupon_code', $code);
        }
        if (isset($_POST['acb_expiry_date'])) {
            $expiry = sanitize_text_field($_POST['acb_expiry_date']);
            update_post_meta($post_id, '_acb_expiry_date', $expiry);
        }
    }

    public function enqueue_assets() {
        wp_enqueue_style('acb-style', plugin_dir_url(__FILE__) . 'acb-style.css');
        wp_enqueue_script('acb-js', plugin_dir_url(__FILE__) . 'acb-script.js', array('jquery'), null, true);
        wp_localize_script('acb-js', 'ACBData', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acb_nonce')));
    }

    public function render_coupon_submission_form() {
        ob_start();
        ?>
        <form id="acb-coupon-form" method="post" action="#">
            <p><label for="acb_title">Coupon Title<span style="color:red;">*</span>:</label><br>
            <input type="text" id="acb_title" name="acb_title" required style="width:100%;"></p>
            <p><label for="acb_description">Description<span style="color:red;">*</span>:</label><br>
            <textarea id="acb_description" name="acb_description" rows="4" required style="width:100%;"></textarea></p>
            <p><label for="acb_affiliate_url">Affiliate URL<span style="color:red;">*</span>:</label><br>
            <input type="url" id="acb_affiliate_url" name="acb_affiliate_url" required style="width:100%;"></p>
            <p><label for="acb_coupon_code">Coupon Code (optional):</label><br>
            <input type="text" id="acb_coupon_code" name="acb_coupon_code" style="width:100%;"></p>
            <p><label for="acb_expiry_date">Expiry Date (optional):</label><br>
            <input type="date" id="acb_expiry_date" name="acb_expiry_date"></p>
            <input type="hidden" name="action" value="acb_submit_coupon">
            <button type="submit">Submit Coupon</button>
            <div id="acb-form-message"></div>
        </form>
        <?php
        return ob_get_clean();
    }

    public function handle_coupon_submission() {
        check_ajax_referer('acb_nonce', 'nonce');

        $title = isset($_POST['acb_title']) ? sanitize_text_field($_POST['acb_title']) : '';
        $description = isset($_POST['acb_description']) ? sanitize_textarea_field($_POST['acb_description']) : '';
        $affiliate_url = isset($_POST['acb_affiliate_url']) ? esc_url_raw($_POST['acb_affiliate_url']) : '';
        $coupon_code = isset($_POST['acb_coupon_code']) ? sanitize_text_field($_POST['acb_coupon_code']) : '';
        $expiry_date = isset($_POST['acb_expiry_date']) ? sanitize_text_field($_POST['acb_expiry_date']) : '';

        if (empty($title) || empty($description) || empty($affiliate_url)) {
            wp_send_json_error('Please fill in all required fields.');
        }

        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_type' => $this->post_type,
            'post_status' => 'pending' // Admin reviews before publishing
        );

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            wp_send_json_error('Error inserting coupon.');
        }

        update_post_meta($post_id, '_acb_affiliate_url', $affiliate_url);
        if ($coupon_code !== '') {
            update_post_meta($post_id, '_acb_coupon_code', $coupon_code);
        }
        if ($expiry_date !== '') {
            update_post_meta($post_id, '_acb_expiry_date', $expiry_date);
        }

        wp_send_json_success('Thank you! Your coupon has been submitted for review.');
    }

    public function custom_coupon_archive_template($template) {
        if (is_post_type_archive($this->post_type)) {
            $custom_template = plugin_dir_path(__FILE__) . 'templates/archive-coupons.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        return $template;
    }

    public function render_coupon_list($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts);
        $query = new WP_Query(array(
            'post_type' => $this->post_type,
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['count'])
        ));

        ob_start();
        if ($query->have_posts()) {
            echo '<ul class="acb-coupon-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $affiliate_url = get_post_meta(get_the_ID(), '_acb_affiliate_url', true);
                $coupon_code = get_post_meta(get_the_ID(), '_acb_coupon_code', true);
                $expiry_date = get_post_meta(get_the_ID(), '_acb_expiry_date', true);

                echo '<li>'; 
                echo '<h3><a href="' . esc_url($affiliate_url) . '" target="_blank" rel="nofollow noopener noreferrer">' . get_the_title() . '</a></h3>';
                the_excerpt();
                if ($coupon_code) {
                    echo '<p><strong>Coupon Code:</strong> ' . esc_html($coupon_code) . '</p>';
                }
                if ($expiry_date) {
                    echo '<p><em>Expires on: ' . esc_html($expiry_date) . '</em></p>';
                }
                echo '</li>';
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p>No coupons found.</p>';
        }
        return ob_get_clean();
    }
}

AffiliateCouponBooster::get_instance();

// Basic CSS to keep plugin self-contained
add_action('wp_head', function() {
    echo '<style>.acb-coupon-list {list-style:none; padding-left:0;} .acb-coupon-list li {margin-bottom:20px; border-bottom:1px solid #ddd; padding-bottom:15px;}</style>';
});

// Basic JS for AJAX form submission
add_action('wp_footer', function() {
    if (is_page() || is_single()) {
        ?>
        <script>
        jQuery(document).ready(function($){
            $('#acb-coupon-form').on('submit', function(e){
                e.preventDefault();
                var form = $(this);
                var data = form.serialize();
                $('#acb-form-message').html('Submitting...');
                $.post(ACBData.ajax_url, data + '&nonce=' + ACBData.nonce, function(response){
                    if(response.success){
                        $('#acb-form-message').html('<span style="color:green;">'+response.data+'</span>');
                        form.reset();
                    } else {
                        $('#acb-form-message').html('<span style="color:red;">'+response.data+'</span>');
                    }
                });
            });
        });
        </script>
        <?php
    }
});
