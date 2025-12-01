<?php
/*
Plugin Name: Couponify Pro
Plugin URI: https://example.com/couponify-pro
Description: Create and manage exclusive, user-generated coupon codes with affiliate integration to boost revenue and engagement.
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Couponify_Pro.php
License: GPLv2 or later
Text Domain: couponify-pro
*/

if (!defined('ABSPATH')) exit;

class CouponifyPro {
    private static $instance = null;
    private $db_version = '1.0';

    public static function instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'register_coupon_post_type'));
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_post_couponify_generate', array($this, 'handle_coupon_submission'));
        add_shortcode('couponify_form', array($this, 'render_coupon_form'));
        add_shortcode('couponify_list', array($this, 'render_coupon_list'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function activate() {
        $this->register_coupon_post_type();
        $this->create_db_table();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function register_coupon_post_type() {
        $labels = array(
            'name' => __('Coupons', 'couponify-pro'),
            'singular_name' => __('Coupon', 'couponify-pro'),
            'add_new' => __('Add New Coupon', 'couponify-pro'),
            'add_new_item' => __('Add New Coupon', 'couponify-pro'),
            'edit_item' => __('Edit Coupon', 'couponify-pro'),
            'new_item' => __('New Coupon', 'couponify-pro'),
            'view_item' => __('View Coupon', 'couponify-pro'),
            'search_items' => __('Search Coupons', 'couponify-pro'),
            'not_found' => __('No coupons found', 'couponify-pro'),
            'not_found_in_trash' => __('No coupons found in Trash', 'couponify-pro'),
            'menu_name' => __('Couponify', 'couponify-pro'),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'capability_type' => 'post',
            'supports' => array('title','editor'),
            'has_archive' => false,
            'menu_position' => 20,
        );

        register_post_type('couponify_coupon', $args);
    }

    public function create_db_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'couponify_affiliates';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            coupon_id BIGINT(20) NOT NULL,
            affiliate_link TEXT NOT NULL,
            creator_ip VARCHAR(100) DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY coupon_id (coupon_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('couponify_db_version', $this->db_version);
    }

    public function register_admin_menu() {
        add_menu_page('Couponify', 'Couponify', 'manage_options', 'couponify-settings', array($this, 'admin_settings_page'), 'dashicons-tickets', 65);
    }

    public function admin_settings_page() {
        ?><div class="wrap">
        <h1><?php _e('Couponify Settings', 'couponify-pro'); ?></h1>
        <p><?php _e('Manage your coupons and affiliate links here.', 'couponify-pro'); ?></p>
        <p><a href="edit.php?post_type=couponify_coupon" class="button button-primary"><?php _e('View Coupons', 'couponify-pro'); ?></a></p>
        </div><?php
    }

    public function handle_coupon_submission() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'couponify_nonce')) {
            wp_die(__('Nonce verification failed', 'couponify-pro'));
        }

        if (!isset($_POST['coupon_code']) || !isset($_POST['coupon_description'])) {
            wp_die(__('Required fields missing', 'couponify-pro'));
        }

        $code = sanitize_text_field($_POST['coupon_code']);
        $description = sanitize_textarea_field($_POST['coupon_description']);
        $affiliate_link = esc_url_raw($_POST['affiliate_link'] ?? '');
        $creator_ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // Check for duplicate coupon code
        $existing = get_page_by_title($code, OBJECT, 'couponify_coupon');
        if ($existing) {
            wp_redirect(add_query_arg('couponify_error', 'exists', wp_get_referer()));
            exit;
        }

        $postarr = array(
            'post_title' => $code,
            'post_content' => $description,
            'post_type' => 'couponify_coupon',
            'post_status' => 'publish'
        );

        $post_id = wp_insert_post($postarr);
        if (!$post_id) {
            wp_redirect(add_query_arg('couponify_error', 'failed', wp_get_referer()));
            exit;
        }

        if (!empty($affiliate_link)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'couponify_affiliates';
            $wpdb->insert(
                $table_name,
                array(
                    'coupon_id' => $post_id,
                    'affiliate_link' => $affiliate_link,
                    'creator_ip' => $creator_ip,
                    'created_at' => current_time('mysql', 1),
                ),
                array('%d','%s','%s','%s')
            );
        }

        wp_redirect(add_query_arg('couponify_success', '1', wp_get_referer()));
        exit;
    }

    public function render_coupon_form() {
        ob_start();
        if (isset($_GET['couponify_error'])) {
            $error = sanitize_text_field($_GET['couponify_error']);
            if ($error === 'exists') {
                echo '<div style="color:red;">'.__('Coupon code already exists.', 'couponify-pro').'</div>';
            } elseif ($error === 'failed') {
                echo '<div style="color:red;">'.__('Failed to create coupon.', 'couponify-pro').'</div>';
            }
        }
        if (isset($_GET['couponify_success'])) {
            echo '<div style="color:green;">'.__('Coupon submitted successfully!', 'couponify-pro').'</div>';
        }
        ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="couponify_generate">
            <?php wp_nonce_field('couponify_nonce'); ?>
            <p>
                <label for="coupon_code"><?php _e('Coupon Code', 'couponify-pro'); ?> *</label><br>
                <input type="text" id="coupon_code" name="coupon_code" required maxlength="50" style="width: 100%;">
            </p>
            <p>
                <label for="coupon_description"><?php _e('Coupon Description', 'couponify-pro'); ?> *</label><br>
                <textarea id="coupon_description" name="coupon_description" required rows="4" style="width: 100%;"></textarea>
            </p>
            <p>
                <label for="affiliate_link"><?php _e('Affiliate Link (optional)', 'couponify-pro'); ?></label><br>
                <input type="url" id="affiliate_link" name="affiliate_link" placeholder="https://affiliate.example.com/deal" style="width: 100%;">
            </p>
            <p><input type="submit" value="<?php _e('Submit Coupon', 'couponify-pro'); ?>"></p>
        </form>
        <?php
        return ob_get_clean();
    }

    public function render_coupon_list() {
        ob_start();
        $query = new WP_Query(array(
            'post_type' => 'couponify_coupon',
            'post_status' => 'publish',
            'posts_per_page' => 10,
        ));

        if ($query->have_posts()) {
            echo '<ul class="couponify-coupon-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $code = get_the_title();
                $desc = get_the_content();

                // Get affiliate link
                global $wpdb;
                $table_name = $wpdb->prefix . 'couponify_affiliates';
                $affiliate_link = $wpdb->get_var($wpdb->prepare("SELECT affiliate_link FROM $table_name WHERE coupon_id = %d ORDER BY created_at DESC LIMIT 1", get_the_ID()));

                echo '<li style="margin-bottom:15px;">
                <strong>'.esc_html($code).'</strong><br>
                <em>'.esc_html($desc).'</em><br>';
                if ($affiliate_link) {
                    echo '<a href="'.esc_url($affiliate_link).'" target="_blank">'.__('Shop Now', 'couponify-pro').'</a>';
                }
                echo '</li>';
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p>'.__('No coupons available.', 'couponify-pro').'</p>';
        }
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('couponify-style', plugins_url('couponify-style.css', __FILE__));
    }
}

CouponifyPro::instance();
