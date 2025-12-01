<?php
/*
Plugin Name: Affiliate Coupon & Deal Booster
Description: Dynamically generates a coupon & deal page linked to affiliate URLs with shortcode and widget support.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon___Deal_Booster.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateCouponDealBooster {

    private $coupons_option = 'acdb_coupons_data';

    public function __construct() {
        add_action('admin_menu', array($this, 'acdb_add_admin_menu'));
        add_action('admin_init', array($this, 'acdb_settings_init'));
        add_shortcode('acdb_coupons', array($this, 'acdb_render_coupons_shortcode'));
        add_action('widgets_init', function() {
            register_widget('ACDB_Coupons_Widget');
        });
        add_action('wp_enqueue_scripts', array($this, 'acdb_enqueue_scripts'));
    }

    public function acdb_enqueue_scripts() {
        wp_enqueue_style('acdb-style', plugin_dir_url(__FILE__) . 'acdb-style.css');
    }

    public function acdb_add_admin_menu() {
        add_options_page('Affiliate Coupon Booster', 'Affiliate Coupon Booster', 'manage_options', 'affiliate_coupon_booster', array($this, 'acdb_options_page'));
    }

    public function acdb_settings_init() {
        register_setting('acdb_settings_group', $this->coupons_option);

        add_settings_section(
            'acdb_settings_section',
            __('Manage Coupons & Deals', 'acdb'),
            function() {
                echo '<p>' . __('Add your affiliate coupons and deals here. Use JSON format. Example: [{"title":"10% off Shoes","code":"SHOES10","url":"https://affiliate-link.com/product"}]', 'acdb') . '</p>';
            },
            'acdb_settings_group'
        );

        add_settings_field(
            'acdb_coupons_field',
            __('Coupons JSON', 'acdb'),
            array($this, 'acdb_coupons_field_render'),
            'acdb_settings_group',
            'acdb_settings_section'
        );
    }

    public function acdb_coupons_field_render() {
        $data = get_option($this->coupons_option, '[]');
        echo '<textarea cols="60" rows="10" name="' . esc_attr($this->coupons_option) . '">' . esc_textarea($data) . '</textarea>';
    }

    public function acdb_options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Coupon & Deal Booster</h2>
            <?php
            settings_fields('acdb_settings_group');
            do_settings_sections('acdb_settings_group');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function acdb_render_coupons_shortcode() {
        $coupons_json = get_option($this->coupons_option, '[]');
        $coupons = json_decode($coupons_json, true);

        if (empty($coupons) || !is_array($coupons)) {
            return '<p>No coupons added yet.</p>';
        }

        $output = '<div class="acdb-coupons-container">';
        foreach ($coupons as $coupon) {
            $title = esc_html($coupon['title'] ?? '');
            $code = esc_html($coupon['code'] ?? '');
            $url = esc_url($coupon['url'] ?? '#');

            $output .= '<div class="acdb-coupon">';
            $output .= '<h3 class="acdb-title">' . $title . '</h3>';
            $output .= '<p>Use code: <strong>' . $code . '</strong></p>';
            $output .= '<p><a class="acdb-button" href="' . $url . '" target="_blank" rel="nofollow noopener">Shop Now</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }
}

// Widget to display coupons
class ACDB_Coupons_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'acdb_coupons_widget',
            'Affiliate Coupons Widget',
            array('description' => __('Displays affiliate coupons and deals', 'acdb'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];

        $title = apply_filters('widget_title', $instance['title'] ?? 'Deals & Coupons');
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $coupons_json = get_option('acdb_coupons_data', '[]');
        $coupons = json_decode($coupons_json, true);

        if (empty($coupons) || !is_array($coupons)) {
            echo '<p>No coupons available.</p>';
        } else {
            echo '<ul class="acdb-widget-list">';
            foreach ($coupons as $coupon) {
                $title = esc_html($coupon['title'] ?? '');
                $url = esc_url($coupon['url'] ?? '#');
                echo '<li><a href="' . $url . '" target="_blank" rel="nofollow noopener">' . $title . '</a></li>';
            }
            echo '</ul>';
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = $instance['title'] ?? __('Deals & Coupons', 'acdb');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Title:'); ?></label> 
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php 
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }
}

// Init plugin
new AffiliateCouponDealBooster();

// Simple CSS injected inline for demonstration
add_action('wp_head', function() {
    echo '<style>
    .acdb-coupons-container { display: flex; flex-wrap: wrap; gap: 15px; }
    .acdb-coupon { background: #f7f7f7; padding: 15px; border: 1px solid #ccc; width: 300px; border-radius: 5px; }
    .acdb-title { font-size: 1.2em; margin-bottom: 8px; }
    .acdb-button { display: inline-block; padding: 8px 12px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 3px; }
    .acdb-button:hover { background: #005177; }
    .acdb-widget-list { list-style-type: disc; padding-left: 20px; margin: 0; }
    </style>';
});
