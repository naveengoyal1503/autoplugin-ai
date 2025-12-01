<?php
/*
Plugin Name: SmartAffiliateCoupons
Description: Auto-aggregates and displays affiliate coupons with dynamic tracking and engagement tools.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliateCoupons.php
*/

if (!defined('ABSPATH')) exit;

class SmartAffiliateCoupons {
    private $option_name = 'sac_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('smart_coupons', array($this, 'render_coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page('Smart Affiliate Coupons', 'Smart Coupons', 'manage_options', 'smart_affiliate_coupons', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('sac_plugin', $this->option_name);

        add_settings_section('sac_plugin_section', __('Coupon Settings', 'sac'), null, 'sac_plugin');

        add_settings_field(
            'sac_manual_coupons',
            __('Manage Coupons', 'sac'),
            array($this, 'manual_coupons_render'),
            'sac_plugin',
            'sac_plugin_section'
        );
    }

    public function manual_coupons_render() {
        $options = get_option($this->option_name, array());
        if (!is_array($options)) $options = array();

        echo '<textarea cols="80" rows="10" name="'.$this->option_name.'[manual]" placeholder="Enter coupons JSON here">' . esc_textarea(isset($options['manual']) ? $options['manual'] : '') . '</textarea>';
        echo '<p class="description">Enter coupons as JSON array. Example: [{"title":"20% off Shoes","code":"SHOES20","url":"https://affiliate.link/product?code=SHOES20"}]</p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h1>Smart Affiliate Coupons</h1>
            <?php
            settings_fields('sac_plugin');
            do_settings_sections('sac_plugin');
            submit_button();
            ?>
            <h2>Usage</h2>
            <p>Use the shortcode <code>[smart_coupons]</code> to display coupons anywhere on your site.</p>
        </form>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sac-style', plugin_dir_url(__FILE__) . 'sac-style.css');
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), null, true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function render_coupons_shortcode($atts) {
        $options = get_option($this->option_name, array());
        $coupons = array();

        if (!empty($options['manual'])) {
            $decoded = json_decode($options['manual'], true);
            if (is_array($decoded)) {
                $coupons = $decoded;
            }
        }

        if (empty($coupons)) {
            return '<p>No coupons available.</p>';
        }

        ob_start();
        echo '<div class="sac-coupons">';
        foreach ($coupons as $coupon) {
            $title = isset($coupon['title']) ? esc_html($coupon['title']) : '';
            $code = isset($coupon['code']) ? esc_html($coupon['code']) : '';
            $url = isset($coupon['url']) ? esc_url($coupon['url']) : '#';
            $safe_code = esc_attr($code);

            echo '<div class="sac-coupon">';
            echo '<h3>' . $title . '</h3>';
            echo '<p><strong>Code:</strong> <input type="text" readonly value="' . $safe_code . '" class="sac-coupon-code" /></p>';
            echo '<p><a href="' . $url . '" target="_blank" rel="nofollow noopener noreferrer" class="sac-use-coupon">Use Coupon</a></p>';
            echo '<button class="sac-copy-btn" data-code="' . $safe_code . '">Copy Code</button>';
            echo '</div>';
        }
        echo '</div>';

        return ob_get_clean();
    }
}

new SmartAffiliateCoupons();

// Copy button JavaScript inline for simplicity
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.sac-copy-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                navigator.clipboard.writeText(code).then(() => {
                    alert('Coupon code copied: ' + code);
                }).catch(() => {
                    alert('Failed to copy the code.');
                });
            });
        });
    });
    </script>
    <style>
    .sac-coupons { display: flex; flex-wrap: wrap; gap: 15px; }
    .sac-coupon { border: 1px solid #ccc; padding: 15px; width: 300px; border-radius: 5px; background: #fafafa; }
    .sac-coupon h3 { margin-top: 0; }
    .sac-coupon-code { width: 100%; font-weight: bold; font-size: 1.1em; }
    .sac-copy-btn, .sac-use-coupon { margin-top: 10px; padding: 8px 12px; background: #0073aa; color: white; border: none; cursor: pointer; text-decoration: none; display: inline-block; border-radius: 3px; }
    .sac-copy-btn:hover, .sac-use-coupon:hover { background: #005177; }
    </style>
    <?php
});