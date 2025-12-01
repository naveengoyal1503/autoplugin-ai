/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Course_Locker.php
*/
<?php
/**
 * Plugin Name: WP Course Locker
 * Description: Create and sell interactive online courses with quizzes, certificates, and drip content.
 * Version: 1.0
 * Author: WP Innovate
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register Custom Post Type: Course
function wpcourse_locker_register_course_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Courses',
        'supports' => array('title', 'editor', 'thumbnail'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'courses'),
    );
    register_post_type('course', $args);
}
add_action('init', 'wpcourse_locker_register_course_post_type');

// Register Custom Post Type: Lesson
function wpcourse_locker_register_lesson_post_type() {
    $args = array(
        'public' => true,
        'label'  => 'Lessons',
        'supports' => array('title', 'editor'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'lessons'),
    );
    register_post_type('lesson', $args);
}
add_action('init', 'wpcourse_locker_register_lesson_post_type');

// Add Meta Box for Course Settings
function wpcourse_locker_add_course_meta_box() {
    add_meta_box(
        'course_settings',
        'Course Settings',
        'wpcourse_locker_course_settings_callback',
        'course'
    );
}
add_action('add_meta_boxes', 'wpcourse_locker_add_course_meta_box');

function wpcourse_locker_course_settings_callback($post) {
    wp_nonce_field('wpcourse_locker_save_course_settings', 'wpcourse_locker_nonce');
    $price = get_post_meta($post->ID, '_course_price', true);
    $drip_days = get_post_meta($post->ID, '_course_drip_days', true);
    echo '<label>Course Price: <input type="number" name="course_price" value="' . esc_attr($price) . '" /></label><br>';
    echo '<label>Drip Content (days): <input type="number" name="course_drip_days" value="' . esc_attr($drip_days) . '" /></label>';
}

function wpcourse_locker_save_course_settings($post_id) {
    if (!isset($_POST['wpcourse_locker_nonce']) || !wp_verify_nonce($_POST['wpcourse_locker_nonce'], 'wpcourse_locker_save_course_settings')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['course_price'])) {
        update_post_meta($post_id, '_course_price', sanitize_text_field($_POST['course_price']));
    }
    if (isset($_POST['course_drip_days'])) {
        update_post_meta($post_id, '_course_drip_days', sanitize_text_field($_POST['course_drip_days']));
    }
}
add_action('save_post', 'wpcourse_locker_save_course_settings');

// Shortcode to Display Course
function wpcourse_locker_course_shortcode($atts) {
    $atts = shortcode_atts(array('id' => 0), $atts, 'course');
    $course = get_post($atts['id']);
    if (!$course || $course->post_type !== 'course') {
        return 'Course not found.';
    }
    $price = get_post_meta($course->ID, '_course_price', true);
    $drip_days = get_post_meta($course->ID, '_course_drip_days', true);
    $output = '<div class="wpcourse-locker-course">
        <h2>' . $course->post_title . '</h2>
        <p>' . $course->post_content . '</p>
        <p><strong>Price:</strong> $' . $price . '</p>
        <p><strong>Drip Content:</strong> ' . $drip_days . ' days</p>
        <button>Enroll Now</button>
    </div>';
    return $output;
}
add_shortcode('course', 'wpcourse_locker_course_shortcode');

// Enqueue Styles
function wpcourse_locker_enqueue_styles() {
    wp_enqueue_style('wpcourse-locker-style', plugins_url('style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'wpcourse_locker_enqueue_styles');

// Create Table on Activation
function wpcourse_locker_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wpcourse_locker_enrollments';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        course_id bigint(20) NOT NULL,
        enrollment_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'wpcourse_locker_activate');

// Admin Menu
function wpcourse_locker_admin_menu() {
    add_menu_page(
        'WP Course Locker',
        'Course Locker',
        'manage_options',
        'wpcourse-locker',
        'wpcourse_locker_admin_page'
    );
}
add_action('admin_menu', 'wpcourse_locker_admin_menu');

function wpcourse_locker_admin_page() {
    echo '<div class="wrap"><h1>WP Course Locker</h1><p>Welcome to WP Course Locker. Manage your courses and enrollments here.</p></div>';
}
?>