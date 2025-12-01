/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Course_Locker.php
*/
<?php
/**
 * Plugin Name: WP Course Locker
 * Description: Create, manage, and monetize interactive online courses with quizzes, certificates, and drip content.
 * Version: 1.0
 * Author: WP Innovate
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPCourseLocker {

    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('course', array($this, 'course_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function register_post_types() {
        register_post_type('wpcl_course', array(
            'labels' => array(
                'name' => 'Courses',
                'singular_name' => 'Course'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'rewrite' => array('slug' => 'courses')
        ));

        register_post_type('wpcl_lesson', array(
            'labels' => array(
                'name' => 'Lessons',
                'singular_name' => 'Lesson'
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor'),
            'rewrite' => array('slug' => 'lessons')
        ));
    }

    public function admin_menu() {
        add_menu_page('WP Course Locker', 'Course Locker', 'manage_options', 'wp-course-locker', array($this, 'admin_dashboard'));
    }

    public function admin_dashboard() {
        echo '<div class="wrap"><h1>WP Course Locker</h1><p>Welcome to WP Course Locker. Manage your courses and lessons from here.</p></div>';
    }

    public function course_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts, 'course');

        $course = get_post($atts['id']);
        if (!$course || $course->post_type !== 'wpcl_course') {
            return '<p>Course not found.</p>';
        }

        $lessons = new WP_Query(array(
            'post_type' => 'wpcl_lesson',
            'meta_key' => 'course_id',
            'meta_value' => $atts['id']
        ));

        $output = '<div class="wpcl-course">
                    <h2>' . $course->post_title . '</h2>
                    <div>' . $course->post_content . '</div>
                    <h3>Lessons</h3>
                    <ul>';

        while ($lessons->have_posts()) {
            $lessons->the_post();
            $output .= '<li><a href="#" class="wpcl-lesson-link" data-id="' . get_the_ID() . '">' . get_the_title() . '</a></li>';
        }

        $output .= '</ul></div>';

        wp_reset_postdata();

        return $output;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wpcl-script', plugin_dir_url(__FILE__) . 'wpcl-script.js', array('jquery'), '1.0', true);
        wp_enqueue_style('wpcl-style', plugin_dir_url(__FILE__) . 'wpcl-style.css');
    }
}

new WPCourseLocker();

// wpcl-script.js
// jQuery(document).ready(function($) {
//     $('.wpcl-lesson-link').on('click', function(e) {
//         e.preventDefault();
//         var lessonId = $(this).data('id');
//         // AJAX call to load lesson content
//     });
// });

// wpcl-style.css
// .wpcl-course { margin: 20px 0; }
// .wpcl-course h2 { color: #333; }
// .wpcl-course ul { list-style: disc; margin-left: 20px; }
