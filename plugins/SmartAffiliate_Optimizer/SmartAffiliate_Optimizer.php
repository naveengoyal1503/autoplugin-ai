/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Optimizer.php
*/
<?php
/**
 * Plugin Name: SmartAffiliate Optimizer
 * Description: Automatically convert product mentions to affiliate links with analytics
 * Version: 1.0.0
 * Author: Affiliate Tools
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliate {
    private $option_prefix = 'smart_affiliate_';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'process_affiliate_links'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_get_affiliate_stats', array($this, 'get_affiliate_stats'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'SmartAffiliate Optimizer',
            'SmartAffiliate',
            'manage_options',
            'smart-affiliate',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            80
        );
        add_submenu_page(
            'smart-affiliate',
            'Settings',
            'Settings',
            'manage_options',
            'smart-affiliate-settings',
            array($this, 'render_settings')
        );
        add_submenu_page(
            'smart-affiliate',
            'Analytics',
            'Analytics',
            'manage_options',
            'smart-affiliate-analytics',
            array($this, 'render_analytics')
        );
    }

    public function register_settings() {
        register_setting('smart_affiliate_settings', $this->option_prefix . 'api_key');
        register_setting('smart_affiliate_settings', $this->option_prefix . 'amazon_id');
        register_setting('smart_affiliate_settings', $this->option_prefix . 'keywords');
        register_setting('smart_affiliate_settings', $this->option_prefix . 'enabled');
    }

    public function process_affiliate_links($content) {
        if (!is_singular('post') || is_admin()) {
            return $content;
        }

        if (!get_option($this->option_prefix . 'enabled')) {
            return $content;
        }

        $keywords = explode(',', get_option($this->option_prefix . 'keywords', ''));
        $keywords = array_map('trim', array_filter($keywords));

        foreach ($keywords as $keyword) {
            if (empty($keyword)) continue;

            $pattern = '/\b' . preg_quote($keyword, '/') . '\b(?!<\/a>|<\/h|<\/strong>)/i';
            $affiliate_link = $this->generate_affiliate_link($keyword);
            $replacement = '<a href="' . esc_url($affiliate_link) . '" class="smart-affiliate-link" data-keyword="' . esc_attr($keyword) . '">' . $keyword . '</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }

        return $content;
    }

    private function generate_affiliate_link($keyword) {
        $amazon_id = get_option($this->option_prefix . 'amazon_id', '');
        if (empty($amazon_id)) {
            return '#';
        }
        return 'https://amazon.com/s?k=' . urlencode($keyword) . '&tag=' . $amazon_id;
    }

    public function enqueue_frontend_scripts() {
        if (is_singular('post')) {
            wp_enqueue_script('smart-affiliate-tracking', plugin_dir_url(__FILE__) . 'js/tracking.js', array('jquery'), '1.0.0', true);
            wp_localize_script('smart-affiliate-tracking', 'smartAffiliateData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'post_id' => get_the_ID()
            ));
        }
    }

    public function get_affiliate_stats() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $stats = $wpdb->get_results("SELECT keyword, clicks, conversions FROM {$wpdb->prefix}smart_affiliate_stats ORDER BY clicks DESC LIMIT 10");
        wp_send_json_success($stats);
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Optimizer Dashboard</h1>
            <div class="dashboard-widgets-wrap">
                <div class="postbox">
                    <h2 class="hndle">Quick Stats</h2>
                    <div class="inside">
                        <p><strong>Total Affiliate Links:</strong> <span id="total-links">0</span></p>
                        <p><strong>This Month Revenue:</strong> <span id="monthly-revenue">$0</span></p>
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'get_affiliate_stats' },
                    success: function(response) {
                        if (response.success) {
                            $('#total-links').text(response.data.length);
                        }
                    }
                });
            });
        </script>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="amazon_id">Amazon Affiliate ID</label></th>
                        <td><input type="text" name="<?php echo $this->option_prefix; ?>amazon_id" id="amazon_id" value="<?php echo esc_attr(get_option($this->option_prefix . 'amazon_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="keywords">Keywords to Monetize</label></th>
                        <td><textarea name="<?php echo $this->option_prefix; ?>keywords" id="keywords" class="large-text code" rows="5"><?php echo esc_attr(get_option($this->option_prefix . 'keywords')); ?></textarea><p class="description">Enter keywords separated by commas</p></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="enabled">Enable Plugin</label></th>
                        <td><input type="checkbox" name="<?php echo $this->option_prefix; ?>enabled" id="enabled" value="1" <?php checked(get_option($this->option_prefix . 'enabled')); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_analytics() {
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Analytics</h1>
            <div id="analytics-container" style="margin-top: 20px;">
                <p>Loading analytics...</p>
            </div>
        </div>
        <?php
    }
}

// Initialize plugin
if (is_admin()) {
    new SmartAffiliate();
}

register_activation_hook(__FILE__, function() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}smart_affiliate_stats (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        keyword varchar(255) NOT NULL,
        clicks int DEFAULT 0,
        conversions int DEFAULT 0,
        revenue decimal(10, 2) DEFAULT 0,
        date_tracked datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

?>