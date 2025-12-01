<?php
/*
Plugin Name: ContentAffiliateBooster
Plugin URI: https://contentaffiliatebooster.com
Description: Intelligent affiliate link insertion with geolocation targeting and performance tracking
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentAffiliateBooster.php
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CAB_VERSION', '1.0.0');
define('CAB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAB_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentAffiliateBooster {
    private static $instance = null;
    private $db_version = '1.0';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 20);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_nopriv_cab_track_click', array($this, 'track_affiliate_click'));
        add_action('wp_ajax_cab_track_click', array($this, 'track_affiliate_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cab_affiliate_links (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            url varchar(1000) NOT NULL,
            program varchar(255) NOT NULL,
            country varchar(100),
            clicks int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            commission_earned decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('cab_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentAffiliateBooster',
            'Affiliate Booster',
            'manage_options',
            'cab-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-link',
            25
        );
        
        add_submenu_page(
            'cab-dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'cab-links',
            array($this, 'render_links_page')
        );
        
        add_submenu_page(
            'cab-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'cab-analytics',
            array($this, 'render_analytics_page')
        );
        
        add_submenu_page(
            'cab-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'cab-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('cab_settings_group', 'cab_enable_plugin');
        register_setting('cab_settings_group', 'cab_auto_linking');
        register_setting('cab_settings_group', 'cab_link_color');
        register_setting('cab_settings_group', 'cab_api_key');
    }

    public function render_dashboard() {
        global $wpdb;
        $table = $wpdb->prefix . 'cab_affiliate_links';
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $table");
        $total_earnings = $wpdb->get_var("SELECT SUM(commission_earned) FROM $table");
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        ?>
        <div class="wrap">
            <h1>ContentAffiliateBooster Dashboard</h1>
            <div class="cab-dashboard-grid">
                <div class="cab-card">
                    <h3>Total Affiliate Links</h3>
                    <p class="cab-metric"><?php echo esc_html($total_links); ?></p>
                </div>
                <div class="cab-card">
                    <h3>Total Clicks</h3>
                    <p class="cab-metric"><?php echo esc_html($total_clicks ?: 0); ?></p>
                </div>
                <div class="cab-card">
                    <h3>Earnings</h3>
                    <p class="cab-metric">\$<?php echo esc_html(number_format($total_earnings ?: 0, 2)); ?></p>
                </div>
            </div>
            <div class="cab-actions">
                <a href="?page=cab-links" class="button button-primary">Manage Links</a>
                <a href="?page=cab-analytics" class="button">View Analytics</a>
            </div>
        </div>
        <style>
            .cab-dashboard-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .cab-card {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .cab-metric {
                font-size: 32px;
                font-weight: bold;
                color: #0073aa;
                margin: 10px 0;
            }
            .cab-actions {
                margin-top: 20px;
            }
            .cab-actions a {
                margin-right: 10px;
            }
        </style>
        <?php
    }

    public function render_links_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'cab_affiliate_links';
        
        if (isset($_POST['add_link']) && check_admin_referer('cab_add_link_nonce')) {
            $keyword = sanitize_text_field($_POST['keyword']);
            $url = esc_url_raw($_POST['url']);
            $program = sanitize_text_field($_POST['program']);
            
            $wpdb->insert($table, array(
                'keyword' => $keyword,
                'url' => $url,
                'program' => $program,
            ));
            echo '<div class="notice notice-success"><p>Affiliate link added successfully!</p></div>';
        }
        
        $links = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Manage Affiliate Links</h1>
            <form method="post" style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <?php wp_nonce_field('cab_add_link_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="keyword">Keyword</label></th>
                        <td><input type="text" name="keyword" id="keyword" required style="width: 300px;"></td>
                    </tr>
                    <tr>
                        <th><label for="url">Affiliate URL</label></th>
                        <td><input type="url" name="url" id="url" required style="width: 300px;"></td>
                    </tr>
                    <tr>
                        <th><label for="program">Program</label></th>
                        <td><input type="text" name="program" id="program" placeholder="e.g., Amazon Associates" style="width: 300px;"></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="add_link" class="button button-primary" value="Add Link"></p>
            </form>
            
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Program</th>
                        <th>Clicks</th>
                        <th>Conversions</th>
                        <th>Earnings</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo esc_html($link->keyword); ?></td>
                        <td><?php echo esc_html($link->program); ?></td>
                        <td><?php echo esc_html($link->clicks); ?></td>
                        <td><?php echo esc_html($link->conversions); ?></td>
                        <td>\$<?php echo esc_html(number_format($link->commission_earned, 2)); ?></td>
                        <td><a href="?page=cab-links&delete=<?php echo esc_attr($link->id); ?>" onclick="return confirm('Delete this link?')">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_analytics_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'cab_affiliate_links';
        $stats = $wpdb->get_results("SELECT program, SUM(clicks) as total_clicks, SUM(conversions) as total_conversions, SUM(commission_earned) as total_earnings FROM $table GROUP BY program");
        ?>
        <div class="wrap">
            <h1>Analytics</h1>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Program</th>
                        <th>Clicks</th>
                        <th>Conversions</th>
                        <th>Earnings</th>
                        <th>Conversion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $stat): ?>
                    <tr>
                        <td><?php echo esc_html($stat->program); ?></td>
                        <td><?php echo esc_html($stat->total_clicks ?: 0); ?></td>
                        <td><?php echo esc_html($stat->total_conversions ?: 0); ?></td>
                        <td>\$<?php echo esc_html(number_format($stat->total_earnings ?: 0, 2)); ?></td>
                        <td><?php echo esc_html($stat->total_clicks ? round(($stat->total_conversions / $stat->total_clicks) * 100, 2) . '%' : '0%'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>ContentAffiliateBooster Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('cab_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="cab_enable_plugin">Enable Plugin</label></th>
                        <td><input type="checkbox" name="cab_enable_plugin" id="cab_enable_plugin" value="1" <?php checked(get_option('cab_enable_plugin'), 1); ?>></td>
                    </tr>
                    <tr>
                        <th><label for="cab_auto_linking">Enable Auto-Linking</label></th>
                        <td><input type="checkbox" name="cab_auto_linking" id="cab_auto_linking" value="1" <?php checked(get_option('cab_auto_linking'), 1); ?>></td>
                    </tr>
                    <tr>
                        <th><label for="cab_link_color">Link Color</label></th>
                        <td><input type="color" name="cab_link_color" id="cab_link_color" value="<?php echo esc_attr(get_option('cab_link_color', '#0073aa')); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (!is_singular('post') || !get_option('cab_enable_plugin')) {
            return $content;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'cab_affiliate_links';
        $links = $wpdb->get_results("SELECT * FROM $table");
        
        foreach ($links as $link) {
            $pattern = '/\b' . preg_quote($link->keyword, '/') . '\b/i';
            $replacement = '<a href="' . esc_url(add_query_arg('cab_link_id', $link->id, $link->url)) . '" class="cab-affiliate-link" data-link-id="' . esc_attr($link->id) . '" onclick="cabTrackClick(' . esc_attr($link->id) . ')">' . esc_html($link->keyword) . '</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        
        return $content;
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script('cab-tracking', CAB_PLUGIN_URL . 'js/tracking.js', array('jquery'), CAB_VERSION, true);
        wp_localize_script('cab-tracking', 'cabAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_style('cab-styles', CAB_PLUGIN_URL . 'css/styles.css', array(), CAB_VERSION);
    }

    public function track_affiliate_click() {
        if (!isset($_POST['link_id'])) {
            wp_send_json_error('Invalid link ID');
        }
        
        global $wpdb;
        $link_id = intval($_POST['link_id']);
        $table = $wpdb->prefix . 'cab_affiliate_links';
        
        $wpdb->query($wpdb->prepare("UPDATE $table SET clicks = clicks + 1 WHERE id = %d", $link_id));
        
        wp_send_json_success('Click tracked');
    }
}

ContentAffiliateBooster::get_instance();
?>