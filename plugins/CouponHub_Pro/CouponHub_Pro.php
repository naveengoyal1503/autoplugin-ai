/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=CouponHub_Pro.php
*/
<?php
/**
 * Plugin Name: CouponHub Pro
 * Description: Aggregates and displays coupons and deals with affiliate links in a customizable widget and shortcode.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class CouponHubPro {
    private $coupons_option = 'couponhubpro_coupons';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('couponhubpro', [$this, 'render_coupons_shortcode']);
        add_action('widgets_init', function() {
            register_widget('CouponHubPro_Widget');
        });
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('couponhubpro-style', plugin_dir_url(__FILE__) . 'couponhubpro-style.css');
    }

    public function admin_menu() {
        add_menu_page('CouponHub Pro', 'CouponHub Pro', 'manage_options', 'couponhubpro', [$this, 'settings_page'], 'dashicons-tickets-alt');
    }

    public function register_settings() {
        register_setting('couponhubpro_group', $this->coupons_option);
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $coupons = get_option($this->coupons_option, []);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('couponhubpro_save', 'couponhubpro_nonce')) {
            $input = $_POST['coupons'] ?? [];
            $clean_coupons = [];
            foreach ($input as $c) {
                if (!empty($c['title']) && !empty($c['code']) && !empty($c['url'])) {
                    $clean_coupons[] = [
                        'title' => sanitize_text_field($c['title']),
                        'code' => sanitize_text_field($c['code']),
                        'url' => esc_url_raw($c['url']),
                        'description' => sanitize_textarea_field($c['description'] ?? '')
                    ];
                }
            }
            update_option($this->coupons_option, $clean_coupons);
            echo '<div class="updated notice"><p>Coupons saved.</p></div>';
            $coupons = $clean_coupons;
        }

        ?>
        <div class="wrap">
            <h1>CouponHub Pro - Manage Coupons</h1>
            <form method="post">
                <?php wp_nonce_field('couponhubpro_save', 'couponhubpro_nonce'); ?>
                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Code</th>
                            <th>Affiliate URL</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody id="couponhubpro-table-body">
                        <?php
                        if (empty($coupons)) {
                            $coupons = [['title' => '', 'code' => '', 'url' => '', 'description' => '']];
                        }
                        foreach ($coupons as $index => $coupon): ?>
                        <tr>
                            <td><input type="text" name="coupons[<?php echo $index ?>][title]" value="<?php echo esc_attr($coupon['title']); ?>" required></td>
                            <td><input type="text" name="coupons[<?php echo $index ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" required></td>
                            <td><input type="url" name="coupons[<?php echo $index ?>][url]" value="<?php echo esc_attr($coupon['url']); ?>" required></td>
                            <td><textarea name="coupons[<?php echo $index ?>][description]" rows="2"><?php echo esc_textarea($coupon['description']); ?></textarea></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="couponhubpro-add-row">Add Coupon</button></p>
                <p><input type="submit" class="button-primary" value="Save Coupons"></p>
            </form>
        </div>
        <script>
        document.getElementById('couponhubpro-add-row').addEventListener('click', function() {
            var tbody = document.getElementById('couponhubpro-table-body');
            var rowCount = tbody.children.length;
            var newRow = document.createElement('tr');
            newRow.innerHTML = '
                <td><input type="text" name="coupons[' + rowCount + '][title]" required></td>
                <td><input type="text" name="coupons[' + rowCount + '][code]" required></td>
                <td><input type="url" name="coupons[' + rowCount + '][url]" required></td>
                <td><textarea name="coupons[' + rowCount + '][description]" rows="2"></textarea></td>
            '.trim();
            tbody.appendChild(newRow);
        });
        </script>
        <?php
    }

    public function render_coupons_shortcode($atts) {
        $coupons = get_option($this->coupons_option, []);
        if (empty($coupons)) {
            return '<p>No coupons available at the moment. Please check back later.</p>';
        }
        ob_start();
        ?>
        <div class="couponhubpro-coupons">
            <?php foreach ($coupons as $coupon): ?>
                <div class="couponhubpro-coupon">
                    <h3><?php echo esc_html($coupon['title']); ?></h3>
                    <?php if (!empty($coupon['description'])): ?>
                        <p class="couponhubpro-description"><?php echo esc_html($coupon['description']); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url($coupon['url']); ?>" target="_blank" rel="nofollow noopener noreferrer" class="couponhubpro-link" onclick="navigator.clipboard.writeText('<?php echo esc_js($coupon['code']); ?>'); alert('Coupon code copied: <?php echo esc_js($coupon['code']); ?>'); return true;">Use Coupon: <strong><?php echo esc_html($coupon['code']); ?></strong></a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

class CouponHubPro_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'couponhubpro_widget',
            'CouponHub Pro Widget',
            ['description' => 'Display coupons and deals from CouponHub Pro']
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        echo do_shortcode('[couponhubpro]');
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Current Deals';
        ?>
        <p>
          <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                 name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                 value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }
}

new CouponHubPro();
