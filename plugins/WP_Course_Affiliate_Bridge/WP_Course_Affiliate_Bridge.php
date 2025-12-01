/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Course_Affiliate_Bridge.php
*/
<?php
/**
 * Plugin Name: WP Course Affiliate Bridge
 * Plugin URI: https://example.com/wp-course-affiliate-bridge
 * Description: Sell courses and run an affiliate program for them.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPCourseAffiliateBridge {

    public function __construct() {
        add_action('init', array($this, 'init_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function init_plugin() {
        // Register custom post type for courses
        register_post_type('course', array(
            'labels' => array(
                'name' => 'Courses',
                'singular_name' => 'Course'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail')
        ));

        // Register custom post type for affiliates
        register_post_type('affiliate', array(
            'labels' => array(
                'name' => 'Affiliates',
                'singular_name' => 'Affiliate'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor')
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Course Affiliate Bridge',
            'Course Affiliate Bridge',
            'manage_options',
            'wp-course-affiliate-bridge',
            array($this, 'plugin_settings_page')
        );
    }

    public function plugin_settings_page() {
        echo '<div class="wrap"><h1>WP Course Affiliate Bridge</h1><p>Manage your courses and affiliates here.</p></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-course-affiliate-bridge', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function shortcode_course_list() {
        $courses = get_posts(array('post_type' => 'course', 'numberposts' => -1));
        $output = '<ul class="course-list">';
        foreach ($courses as $course) {
            $output .= '<li><a href="' . get_permalink($course->ID) . '">' . $course->post_title . '</a></li>';
        }
        $output .= '</ul>';
        return $output;
    }

    public function shortcode_affiliate_signup() {
        if ($_POST['affiliate_submit']) {
            $affiliate = array(
                'post_title' => sanitize_text_field($_POST['name']),
                'post_content' => sanitize_text_field($_POST['email']),
                'post_type' => 'affiliate',
                'post_status' => 'publish'
            );
            wp_insert_post($affiliate);
            return '<p>Thank you for signing up as an affiliate!</p>';
        }
        return '<form method="post">
                    <input type="text" name="name" placeholder="Your Name" required>
                    <input type="email" name="email" placeholder="Your Email" required>
                    <input type="submit" name="affiliate_submit" value="Sign Up">
                </form>';
    }
}

new WPCourseAffiliateBridge();

// Shortcodes
add_shortcode('course_list', array('WPCourseAffiliateBridge', 'shortcode_course_list'));
add_shortcode('affiliate_signup', array('WPCourseAffiliateBridge', 'shortcode_affiliate_signup'));
?>