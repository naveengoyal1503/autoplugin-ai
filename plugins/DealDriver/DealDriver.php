<?php
/*
Plugin Name: DealDriver
Plugin URI: https://example.com/dealdriver
Description: Aggregates, verifies, and personalizes discount coupons and deals from affiliate partners.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=DealDriver.php
License: GPL2
Text Domain: dealdriver
*/

if (!defined('ABSPATH')) exit;

class DealDriver {
    private static $instance = null;
    private $option_name = 'dealdriver_coupons';

    public static function instance() {
        if(self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this,'admin_menu'));
        add_action('admin_init', array($this,'register_settings'));
        add_shortcode('dealdriver_coupons', array($this,'coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this,'enqueue_scripts'));
    }

    public function admin_menu() {
        add_menu_page('DealDriver Coupons', 'DealDriver', 'manage_options', 'dealdriver', array($this, 'admin_page'), 'dashicons-tag', 60);
    }

    public function register_settings() {
        register_setting('dealdriver_settings_group', $this->option_name);
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>DealDriver Coupons</h1>
            <form method="post" action="options.php">
                <?php settings_fields('dealdriver_settings_group'); ?>
                <?php $coupons = get_option($this->option_name, array()); ?>
                <table class="widefat">
                    <thead><tr><th>Coupon Title</th><th>Affiliate Link</th><th>Expiration Date (YYYY-MM-DD)</th></tr></thead>
                    <tbody id="coupons-table-body">
                    <?php
                    if (!empty($coupons)) {
                        foreach($coupons as $index => $coupon) {
                            echo '<tr>';
                            echo '<td><input type="text" name="'.$this->option_name.'['.$index.'][title]" value="'.esc_attr($coupon['title']).'" required></td>';
                            echo '<td><input type="url" name="'.$this->option_name.'['.$index.'][link]" value="'.esc_attr($coupon['link']).'" required></td>';
                            echo '<td><input type="date" name="'.$this->option_name.'['.$index.'][expiry]" value="'.esc_attr($coupon['expiry']).'"></td>';
                            echo '</tr>';
                        }
                    } else {
                        // Show one empty row by default
                        echo '<tr>';
                        echo '<td><input type="text" name="'.$this->option_name.'[title]" required></td>';
                        echo '<td><input type="url" name="'.$this->option_name.'[link]" required></td>';
                        echo '<td><input type="date" name="'.$this->option_name.'[expiry]"></td>';
                        echo '</tr>';
                    }
                    ?>
                    </tbody>
                </table>
                <p><button type="button" class="button" id="add-coupon-button">Add Coupon</button></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        document.getElementById('add-coupon-button').addEventListener('click', function() {
            var tbody = document.getElementById('coupons-table-body');
            var index = tbody.getElementsByTagName('tr').length;
            var row = document.createElement('tr');
            row.innerHTML =
                '<td><input type="text" name="<?php echo $this->option_name; ?>['+index+'][title]" required></td>' +
                '<td><input type="url" name="<?php echo $this->option_name; ?>['+index+'][link]" required></td>' +
                '<td><input type="date" name="<?php echo $this->option_name; ?>['+index+'][expiry]"></td>';
            tbody.appendChild(row);
        });
        </script>
        <?php
    }

    public function coupons_shortcode($atts) {
        $default_atts = array('count' => 5);
        $atts = shortcode_atts($default_atts, $atts);
        $coupons = get_option($this->option_name, array());

        if (empty($coupons)) {
            return '<p>No coupons available at this time.</p>';
        }

        // Filter out expired coupons
        $today = date('Y-m-d');
        $valid_coupons = array_filter($coupons, function($c) use ($today) {
            return empty($c['expiry']) || ($c['expiry'] >= $today);
        });

        if (empty($valid_coupons)) {
            return '<p>No valid coupons available at this time.</p>';
        }

        // Sort coupons alphabetically
        usort($valid_coupons, function($a, $b) {
            return strcmp($a['title'], $b['title']);
        });

        $max = intval($atts['count']);
        $valid_coupons = array_slice($valid_coupons, 0, $max);

        ob_start();
        echo '<div class="dealdriver-coupon-list">';
        foreach($valid_coupons as $coupon) {
            // Sanitize output
            $title = esc_html($coupon['title']);
            $link = esc_url($coupon['link']);
            echo '<div class="dealdriver-coupon-item" style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">';
            echo '<a href="' . $link . '" target="_blank" rel="nofollow noopener noreferrer" style="font-weight:bold; color:#0073aa; text-decoration:none;">' . $title . '</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        // Add optional minimal styles for coupons
        wp_add_inline_style('wp-block-library', ".dealdriver-coupon-item:hover{background:#f8f8f8;}");
    }
}

DealDriver::instance();