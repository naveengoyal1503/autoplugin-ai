/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and manages personalized affiliate coupon codes to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCoupons {
    private static $instance = null;
    public $coupons = [];

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('sac_coupon', [$this, 'coupon_shortcode']);
        add_action('wp_ajax_generate_coupon', [$this, 'ajax_generate_coupon']);
        add_action('wp_ajax_nopriv_generate_coupon', [$this, 'ajax_generate_coupon']);
    }

    public function activate() {
        add_option('sac_api_key', '');
        add_option('sac_coupons_limit', 10);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function init() {
        $this->coupons = get_option('sac_coupons', []);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac.js', ['jquery'], '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Coupons', 'manage_options', 'sac-coupons', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_api_key', sanitize_text_field($_POST['api_key']));
            update_option('sac_coupons_limit', intval($_POST['coupons_limit']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('sac_api_key');
        $limit = get_option('sac_coupons_limit');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Max Coupons (Free: 10)</th>
                        <td><input type="number" name="coupons_limit" value="<?php echo $limit; ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="sac_save" class="button-primary" value="Save Settings" /></p>
            </form>
            <h2>Your Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Code</th><th>Affiliate Link</th><th>Uses</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($this->coupons as $code => $data): ?>
                    <tr>
                        <td><?php echo esc_html($code); ?></td>
                        <td><?php echo esc_html($data['link']); ?></td>
                        <td><?php echo intval($data['uses']); ?></td>
                        <td><a href="#" class="sac-delete" data-code="<?php echo $code; ?>">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.sac-delete').click(function(e) {
                e.preventDefault();
                var code = $(this).data('code');
                $.post(ajaxurl, {action: 'delete_coupon', code: code}, function() {
                    location.reload();
                });
            });
        });
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['id' => ''], $atts);
        ob_start();
        ?>
        <div id="sac-coupon" class="sac-box">
            <p>Click to get your exclusive coupon!</p>
            <button id="sac-generate">Generate Coupon</button>
            <div id="sac-result"></div>
        </div>
        <script>
        jQuery('#sac-generate').click(function() {
            jQuery.post(sac_ajax.ajaxurl, {action: 'generate_coupon'}, function(data) {
                jQuery('#sac-result').html(data);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        $limit = get_option('sac_coupons_limit', 10);
        if (count($this->coupons) >= $limit && $limit > 0) {
            wp_die('Pro upgrade required for more coupons.');
        }
        $code = substr(wp_generate_password(8, false), 0, 8);
        $link = 'https://youraffiliateprogram.com/?coupon=' . $code; // Replace with dynamic affiliate link
        $this->coupons[$code] = ['link' => $link, 'uses' => 0];
        update_option('sac_coupons', $this->coupons);
        echo '<p>Your coupon: <strong>' . $code . '</strong></p><p><a href="' . $link . '" target="_blank">Use Coupon</a></p>';
        wp_die();
    }

    public function track_coupon_use() {
        // Simplified tracking - in pro version, integrate with analytics
        if (isset($_GET['sac_coupon'])) {
            $code = sanitize_text_field($_GET['sac_coupon']);
            if (isset($this->coupons[$code])) {
                $this->coupons[$code]['uses']++;
                update_option('sac_coupons', $this->coupons);
            }
        }
    }
}

SmartAffiliateCoupons::get_instance();

// Track coupon uses
add_action('init', function() {
    $sac = SmartAffiliateCoupons::get_instance();
    $sac->track_coupon_use();
});

// AJAX delete
add_action('wp_ajax_delete_coupon', function() {
    $sac = SmartAffiliateCoupons::get_instance();
    $code = sanitize_text_field($_POST['code']);
    unset($sac->coupons[$code]);
    update_option('sac_coupons', $sac->coupons);
    wp_die('Deleted');
});

// Add CSS
add_action('wp_head', function() {
    echo '<style>.sac-box { border: 1px solid #ddd; padding: 20px; margin: 20px 0; text-align: center; } #sac-generate { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }</style>';
});