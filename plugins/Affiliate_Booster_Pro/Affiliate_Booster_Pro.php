/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Description: Boost affiliate revenue by tracking clicks, auto-inserting coupons, and displaying personalized deals.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateBoosterPro {
    private static $instance = null;
    private $option_name = 'abp_affiliate_links';

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    private function init_hooks() {
        add_shortcode('abp_coupon', array($this, 'render_coupon_shortcode'));
        add_filter('the_content', array($this, 'auto_insert_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_abp_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_abp_track_click', array($this, 'ajax_track_click'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('abp-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0', true);
        wp_localize_script('abp-tracker', 'abp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function ajax_track_click() {
        if (!isset($_POST['link']) || !wp_verify_nonce($_POST['nonce'], 'abp_nonce')) {
            wp_send_json_error('Invalid request');
        }
        $link = esc_url_raw($_POST['link']);
        $clicks = get_option('abp_clicks_' . md5($link), 0);
        update_option('abp_clicks_' . md5($link), $clicks + 1);
        wp_send_json_success();
    }

    public function render_coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => '',
            'description' => '',
            'url' => ''
        ), $atts, 'abp_coupon');

        $code = sanitize_text_field($atts['code']);
        $description = sanitize_text_field($atts['description']);
        $url = esc_url($atts['url']);

        ob_start();
        ?>
        <div class="abp-coupon-block" style="border:1px solid #ccc;padding:10px;margin:10px 0;background:#f9f9f9;">
            <strong>Coupon Code:</strong> <span class="abp-coupon-code" style="font-family:monospace;background:#eee;padding:2px 6px;cursor:pointer;" onclick="navigator.clipboard.writeText('<?php echo esc_js($code); ?>');alert('Coupon code copied!');"><?php echo esc_html($code); ?></span><br/>
            <em><?php echo esc_html($description); ?></em><br/>
            <?php if ($url): ?>
                <a href="<?php echo esc_url($url); ?>" class="abp-track-link" target="_blank" rel="nofollow noopener">Shop Now</a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function auto_insert_deals($content) {
        if (!is_singular('post')) return $content;

        $coupons = get_option($this->option_name, array());
        if (empty($coupons)) return $content;

        $coupon = $coupons[array_rand($coupons)];

        $coupon_html = $this->render_coupon_shortcode(array(
            'code' => $coupon['code'],
            'description' => $coupon['description'],
            'url' => $coupon['url']
        ));

        // Insert coupon block before last paragraph
        $paragraphs = explode('</p>', $content);
        if (count($paragraphs) < 2) {
            return $content . $coupon_html;
        } else {
            $last_paragraph = array_pop($paragraphs);
            $content = implode('</p>', $paragraphs) . $coupon_html . '</p>' . $last_paragraph;
            return $content;
        }
    }

    public function admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', 'affiliate-booster-pro', array($this, 'settings_page'), 'dashicons-admin-links');
    }

    public function register_settings() {
        register_setting('abp_settings_group', $this->option_name, array($this, 'sanitize_coupons'));
    }

    public function sanitize_coupons($input) {
        if (!is_array($input)) return array();
        $clean = array();
        foreach ($input as $coupon) {
            if (empty($coupon['code'])) continue;
            $clean[] = array(
                'code' => sanitize_text_field($coupon['code']),
                'description' => sanitize_text_field($coupon['description']),
                'url' => esc_url_raw($coupon['url'])
            );
        }
        return $clean;
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Booster Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('abp_settings_group'); ?>
                <?php $coupons = get_option($this->option_name, array()); ?>

                <table class="widefat fixed" cellspacing="0">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Coupon Code</th>
                            <th style="width: 50%;">Description</th>
                            <th style="width: 25%;">Affiliate URL</th>
                        </tr>
                    </thead>
                    <tbody id="abp-coupons-body">
                        <?php if (empty($coupons)) : ?>
                            <tr>
                                <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[code]" value="" /></td>
                                <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[description]" value="" /></td>
                                <td><input type="url" name="<?php echo esc_attr($this->option_name); ?>[url]" value="" /></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($coupons as $i => $c) : ?>
                                <tr>
                                    <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $i; ?>][code]" value="<?php echo esc_attr($c['code']); ?>" required/></td>
                                    <td><input type="text" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $i; ?>][description]" value="<?php echo esc_attr($c['description']); ?>" /></td>
                                    <td><input type="url" name="<?php echo esc_attr($this->option_name); ?>[<?php echo $i; ?>][url]" value="<?php echo esc_attr($c['url']); ?>" /></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p><button type="button" class="button" id="abp-add-coupon">Add Coupon</button></p>

                <?php submit_button(); ?>
            </form>
        </div>

        <script>
            (function($){
                $('#abp-add-coupon').on('click', function(){
                    var count = $('#abp-coupons-body tr').length;
                    var row = '<tr>' +
                        '<td><input type="text" name="<?php echo esc_js($this->option_name); ?>['+count+'][code]" required></td>' +
                        '<td><input type="text" name="<?php echo esc_js($this->option_name); ?>['+count+'][description]"></td>' +
                        '<td><input type="url" name="<?php echo esc_js($this->option_name); ?>['+count+'][url]"></td>' +
                    '</tr>';
                    $('#abp-coupons-body').append(row);
                });
            })(jQuery);
        </script>
        <?php
    }
}

AffiliateBoosterPro::instance();

// tracker.js content embedded inline for single file
add_action('wp_footer', function(){ ?>
<script>
(function($){
    $(document).ready(function(){
        $('.abp-track-link').on('click', function(e){
            var href = $(this).attr('href');
            $.post(abp_ajax.ajax_url, {
                action: 'abp_track_click',
                link: href,
                nonce: '<?php echo wp_create_nonce('abp_nonce'); ?>'
            });
        });
    });
})(jQuery);
</script>
<?php });
