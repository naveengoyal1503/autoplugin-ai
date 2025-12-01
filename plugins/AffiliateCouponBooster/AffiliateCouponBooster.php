<?php
/*
Plugin Name: AffiliateCouponBooster
Description: Manage and display affiliate coupons & deals with tracking and customizable display.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateCouponBooster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
    private $version = '1.0';
    public function __construct() {
        add_action('init', array($this, 'register_coupon_post_type'));
        add_shortcode('affiliate_coupons', array($this, 'display_coupons_shortcode'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'front_scripts'));
        add_action('save_post', array($this, 'save_coupon_meta'));
        add_filter('manage_coupon_posts_columns', array($this, 'add_coupon_columns'));
        add_action('manage_coupon_posts_custom_column', array($this, 'render_coupon_columns'), 10, 2);
        // Register meta boxes
        add_action('add_meta_boxes', array($this, 'add_coupon_metaboxes'));
        // Track affiliate clicks
        add_action('template_redirect', array($this, 'handle_redirect'));
        // Create custom rewrite for redirect
        add_rewrite_rule('^acb-redirect/([0-9]+)/?', 'index.php?acb_redirect=$matches[1]', 'top');
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('init', array($this, 'flush_rewrites'), 20);
    }

    public function flush_rewrites() {
        flush_rewrite_rules();
    }

    public function query_vars($vars) {
        $vars[] = 'acb_redirect';
        return $vars;
    }

    public function handle_redirect() {
        global $wp_query;
        if (isset($wp_query->query_vars['acb_redirect'])) {
            $coupon_id = intval($wp_query->query_vars['acb_redirect']);
            $url = get_post_meta($coupon_id, '_acb_affiliate_url', true);
            if ($url) {
                // Track click count
                $clicks = intval(get_post_meta($coupon_id, '_acb_clicks', true));
                update_post_meta($coupon_id, '_acb_clicks', $clicks + 1);
                wp_redirect(esc_url_raw($url));
                exit;
            }
        }
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
            'not_found' => 'No Coupons found',
            'not_found_in_trash' => 'No Coupons found in Trash',
            'menu_name' => 'Affiliate Coupons'
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'show_in_menu' => true,
            'supports' => array('title','editor','thumbnail','excerpt'),
            'menu_icon' => 'dashicons-tag',
            'rewrite' => array('slug' => 'affiliate-coupons'),
        );
        register_post_type('coupon', $args);
    }

    public function add_coupon_metaboxes() {
        add_meta_box('acb_coupon_details', 'Coupon Details', array($this, 'render_coupon_metabox'), 'coupon', 'normal', 'high');
    }

    public function render_coupon_metabox($post) {
        wp_nonce_field('acb_coupon_nonce', 'acb_coupon_nonce_field');
        $affiliate_url = get_post_meta($post->ID, '_acb_affiliate_url', true);
        $coupon_code = get_post_meta($post->ID, '_acb_coupon_code', true);
        $expiry_date = get_post_meta($post->ID, '_acb_expiry_date', true);
        ?>
        <p>
            <label for="acb_affiliate_url">Affiliate URL (where users will be redirected):</label><br>
            <input type="url" id="acb_affiliate_url" name="acb_affiliate_url" value="<?php echo esc_attr($affiliate_url); ?>" style="width:100%;" required>
        </p>
        <p>
            <label for="acb_coupon_code">Coupon Code (optional):</label><br>
            <input type="text" id="acb_coupon_code" name="acb_coupon_code" value="<?php echo esc_attr($coupon_code); ?>" style="width:100%;">
        </p>
        <p>
            <label for="acb_expiry_date">Expiry Date (optional):</label><br>
            <input type="date" id="acb_expiry_date" name="acb_expiry_date" value="<?php echo esc_attr($expiry_date); ?>">
        </p>
        <?php
    }

    public function save_coupon_meta($post_id) {
        if (!isset($_POST['acb_coupon_nonce_field']) || !wp_verify_nonce($_POST['acb_coupon_nonce_field'], 'acb_coupon_nonce')) {
            return $post_id;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
        if ('coupon' !== get_post_type($post_id)) return $post_id;
        if (!current_user_can('edit_post', $post_id)) return $post_id;

        if (isset($_POST['acb_affiliate_url'])) {
            update_post_meta($post_id, '_acb_affiliate_url', esc_url_raw($_POST['acb_affiliate_url']));
        }
        if (isset($_POST['acb_coupon_code'])) {
            update_post_meta($post_id, '_acb_coupon_code', sanitize_text_field($_POST['acb_coupon_code']));
        }
        if (isset($_POST['acb_expiry_date'])) {
            update_post_meta($post_id, '_acb_expiry_date', sanitize_text_field($_POST['acb_expiry_date']));
        }
    }

    public function add_coupon_columns($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['coupon_code'] = 'Coupon Code';
                $new_columns['clicks'] = 'Clicks';
                $new_columns['expiry'] = 'Expiry Date';
            }
        }
        return $new_columns;
    }

    public function render_coupon_columns($column, $post_id) {
        switch ($column) {
            case 'coupon_code':
                echo esc_html(get_post_meta($post_id, '_acb_coupon_code', true));
                break;
            case 'clicks':
                echo intval(get_post_meta($post_id, '_acb_clicks', true));
                break;
            case 'expiry':
                $exp = get_post_meta($post_id, '_acb_expiry_date', true);
                echo $exp ? esc_html($exp) : '-';
                break;
        }
    }

    public function admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_style('acb_admin_css', plugin_dir_url(__FILE__) . 'admin.css');
        }
    }

    public function front_scripts() {
        wp_enqueue_style('acb_front_css', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function display_coupons_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 10), $atts, 'affiliate_coupons');
        $args = array(
            'post_type' => 'coupon',
            'posts_per_page' => intval($atts['count']),
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_acb_expiry_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => '_acb_expiry_date',
                    'compare' => 'NOT EXISTS'
                )
            ),
        );
        $coupons = get_posts($args);
        if (!$coupons) {
            return '<p>No coupons available at the moment.</p>';
        }
        ob_start();
        echo '<div class="acb-coupon-list">';
        foreach ($coupons as $coupon) {
            $code = get_post_meta($coupon->ID, '_acb_coupon_code', true);
            $redirect_url = site_url('/acb-redirect/' . $coupon->ID);
            echo '<div class="acb-coupon-item">';
            echo '<h3 class="acb-coupon-title">' . esc_html(get_the_title($coupon)) . '</h3>';
            if ($code) {
                echo '<p class="acb-coupon-code">Coupon Code: <strong>' . esc_html($code) . '</strong></p>';
            }
            echo '<p class="acb-coupon-excerpt">' . esc_html(get_the_excerpt($coupon)) . '</p>';
            echo '<a href="' . esc_url($redirect_url) . '" target="_blank" rel="nofollow noopener" class="acb-coupon-link">Get Deal</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }
}

new AffiliateCouponBooster();
