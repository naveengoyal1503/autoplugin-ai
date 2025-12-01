<?php
/*
Plugin Name: Affiliate Coupon Vault
Description: Manage and display affiliate coupons with shortcode and widget support.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/

if (!defined('ABSPATH')) { exit; }

class AffiliateCouponVault {
    private static $instance = null;
    private $coupons_option_key = 'acv_coupons_data';

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('acv_coupons', array($this, 'shortcode_display_coupons'));
        add_action('widgets_init', function() {
            register_widget('ACV_Coupon_Widget');
        });
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css');
    }

    public function admin_menu() {
        add_menu_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'), 'dashicons-tickets-alt');
    }

    public function settings_init() {
        register_setting('acv_settings_group', $this->coupons_option_key, array($this, 'sanitize_coupons'));
    }

    public function sanitize_coupons($input) {
        $sanitized = array();
        if (is_array($input)) {
            foreach ($input as $coupon) {
                if (!empty($coupon['title']) && !empty($coupon['code']) && !empty($coupon['link'])) {
                    $sanitized[] = array(
                        'title' => sanitize_text_field($coupon['title']),
                        'code' => sanitize_text_field($coupon['code']),
                        'link' => esc_url_raw($coupon['link']),
                        'description' => sanitize_textarea_field($coupon['description'])
                    );
                }
            }
        }
        return $sanitized;
    }

    public function admin_page() {
        $coupons = get_option($this->coupons_option_key, array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings_group'); ?>
                <table class="form-table" id="acv-coupons-table">
                    <thead>
                        <tr><th>Title</th><th>Code</th><th>Affiliate Link</th><th>Description</th><th>Remove</th></tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($coupons) {
                        foreach ($coupons as $index => $coupon) {
                            ?>
                            <tr>
                                <td><input type="text" name="<?php echo esc_attr($this->coupons_option_key); ?>[<?php echo $index; ?>][title]" value="<?php echo esc_attr($coupon['title']); ?>" required></td>
                                <td><input type="text" name="<?php echo esc_attr($this->coupons_option_key); ?>[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" required></td>
                                <td><input type="url" name="<?php echo esc_attr($this->coupons_option_key); ?>[<?php echo $index; ?>][link]" value="<?php echo esc_attr($coupon['link']); ?>" required></td>
                                <td><textarea name="<?php echo esc_attr($this->coupons_option_key); ?>[<?php echo $index; ?>][description]" rows="2"><?php echo esc_textarea($coupon['description']); ?></textarea></td>
                                <td><button type="button" class="button acv-remove-row">Remove</button></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="5">No coupons added yet.</td></tr>';
                    }
                    ?>
                    </tbody>
                </table>
                <p><button type="button" class="button button-primary" id="acv-add-coupon">Add Coupon</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        (function() {
            const tableBody = document.querySelector('#acv-coupons-table tbody');
            const addBtn = document.getElementById('acv-add-coupon');
            addBtn.addEventListener('click', function() {
                const count = tableBody.children.length;
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td><input type="text" name="<?php echo esc_js($this->coupons_option_key); ?>[${count}][title]" required></td>
                    <td><input type="text" name="<?php echo esc_js($this->coupons_option_key); ?>[${count}][code]" required></td>
                    <td><input type="url" name="<?php echo esc_js($this->coupons_option_key); ?>[${count}][link]" required></td>
                    <td><textarea name="<?php echo esc_js($this->coupons_option_key); ?>[${count}][description]" rows="2"></textarea></td>
                    <td><button type="button" class="button acv-remove-row">Remove</button></td>
                `;
                tableBody.appendChild(newRow);
            });

            tableBody.addEventListener('click', function(e) {
                if(e.target && e.target.classList.contains('acv-remove-row')) {
                    e.target.closest('tr').remove();
                }
            });
        })();
        </script>
        <style>
        #acv-coupons-table input, #acv-coupons-table textarea { width: 100%; }
        </style>
        <?php
    }

    public function shortcode_display_coupons($atts) {
        $coupons = get_option($this->coupons_option_key, array());
        if (!$coupons) {
            return '<p>No coupons available at the moment. Check back soon!</p>';
        }
        $output = '<div class="acv-coupon-list">';
        foreach ($coupons as $coupon) {
            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
            if (!empty($coupon['description'])) {
                $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            }
            $output .= '<p><strong>Code: </strong><span class="acv-code" style="font-family: monospace;">' . esc_html($coupon['code']) . '</span></p>';
            $output .= '<p><a class="acv-link button button-primary" href="' . esc_url($coupon['link']) . '" target="_blank" rel="nofollow noopener">Redeem Offer</a></p>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }
}

class ACV_Coupon_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'acv_coupon_widget',
            __('Affiliate Coupon Vault', 'acv'),
            array('description' => __('Display affiliate coupons in a widget', 'acv'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        echo do_shortcode('[acv_coupons]');
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Coupons', 'acv');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_attr_e('Title:', 'acv'); ?></label>
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

AffiliateCouponVault::get_instance();