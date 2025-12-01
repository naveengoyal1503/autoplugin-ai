<?php
/*
Plugin Name: ContentRevenue AI
Plugin URI: https://contentrevenue.ai
Description: AI-powered WordPress monetization optimizer that analyzes content performance and suggests revenue opportunities
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentRevenue_AI.php
License: GPL v2 or later
Text Domain: contentrevenue-ai
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTREVENUE_VERSION', '1.0.0');
define('CONTENTREVENUE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTREVENUE_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentRevenueAI {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('init', array($this, 'register_settings'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_filter('the_content', array($this, 'inject_monetization_elements'), 999);
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }
    
    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'contentrevenue_analytics';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            impressions INT DEFAULT 0,
            clicks INT DEFAULT 0,
            conversions INT DEFAULT 0,
            revenue DECIMAL(10, 2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('contentrevenue_license_key', '');
        add_option('contentrevenue_is_premium', false);
        add_option('contentrevenue_settings', array(
            'enable_ai_suggestions' => true,
            'auto_inject_ads' => false,
            'min_word_count' => 500,
            'ad_density' => 'moderate'
        ));
    }
    
    public function deactivate_plugin() {
        // Cleanup if needed
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'ContentRevenue AI',
            'ContentRevenue AI',
            'manage_options',
            'contentrevenue-ai',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'contentrevenue-ai',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentrevenue-analytics',
            array($this, 'render_analytics')
        );
        
        add_submenu_page(
            'contentrevenue-ai',
            'Settings',
            'Settings',
            'manage_options',
            'contentrevenue-settings',
            array($this, 'render_settings')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentrevenue-ai') === false) {
            return;
        }
        
        wp_enqueue_style(
            'contentrevenue-admin',
            CONTENTREVENUE_PLUGIN_URL . 'assets/admin.css',
            array(),
            CONTENTREVENUE_VERSION
        );
        
        wp_enqueue_script(
            'contentrevenue-admin',
            CONTENTREVENUE_PLUGIN_URL . 'assets/admin.js',
            array('wp-api-fetch', 'wp-element', 'wp-components'),
            CONTENTREVENUE_VERSION,
            true
        );
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'contentrevenue-frontend',
            CONTENTREVENUE_PLUGIN_URL . 'assets/frontend.js',
            array(),
            CONTENTREVENUE_VERSION,
            true
        );
        
        wp_localize_script('contentrevenue-frontend', 'contentRevenueData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentrevenue_nonce')
        ));
    }
    
    public function register_settings() {
        register_setting('contentrevenue_settings_group', 'contentrevenue_settings');
    }
    
    public function register_rest_routes() {
        register_rest_route('contentrevenue/v1', '/analytics/(?P<post_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_post_analytics'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('contentrevenue/v1', '/suggestions/(?P<post_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_ai_suggestions'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        register_rest_route('contentrevenue/v1', '/track-event', array(
            'methods' => 'POST',
            'callback' => array($this, 'track_event'),
            'permission_callback' => '__return_true'
        ));
    }
    
    public function check_permissions() {
        return current_user_can('manage_options');
    }
    
    public function get_post_analytics($request) {
        global $wpdb;
        $post_id = $request['post_id'];
        $table_name = $wpdb->prefix . 'contentrevenue_analytics';
        
        $analytics = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE post_id = %d ORDER BY updated_at DESC LIMIT 1",
                $post_id
            )
        );
        
        return new WP_REST_Response($analytics ?: array(), 200);
    }
    
    public function get_ai_suggestions($request) {
        $post_id = $request['post_id'];
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
        }
        
        $word_count = str_word_count(strip_tags($post->post_content));
        $suggestions = array(
            'can_monetize' => $word_count >= 500,
            'word_count' => $word_count,
            'recommendations' => array()
        );
        
        if ($word_count >= 500) {
            $suggestions['recommendations'][] = array(
                'type' => 'affiliate_marketing',
                'confidence' => 0.85,
                'suggestion' => 'This post is suitable for affiliate link placement. Consider adding 2-3 contextual affiliate links.'
            );
            $suggestions['recommendations'][] = array(
                'type' => 'ad_placement',
                'confidence' => 0.90,
                'suggestion' => 'Insert mid-content ads after the first 300 words for optimal user experience.'
            );
            $suggestions['recommendations'][] = array(
                'type' => 'sponsored_content',
                'confidence' => 0.75,
                'suggestion' => 'This post could attract sponsored content opportunities from relevant brands.'
            );
        }
        
        if (get_option('contentrevenue_is_premium')) {
            $suggestions['recommendations'][] = array(
                'type' => 'premium_content',
                'confidence' => 0.80,
                'suggestion' => 'Consider creating premium/gated versions of high-performing content.'
            );
        }
        
        return new WP_REST_Response($suggestions, 200);
    }
    
    public function track_event($request) {
        global $wpdb;
        $params = $request->get_json_params();
        
        if (empty($params['post_id']) || empty($params['event_type'])) {
            return new WP_Error('missing_params', 'Missing required parameters', array('status' => 400));
        }
        
        $table_name = $wpdb->prefix . 'contentrevenue_analytics';
        $post_id = intval($params['post_id']);
        
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE post_id = %d ORDER BY updated_at DESC LIMIT 1",
                $post_id
            )
        );
        
        if ($existing) {
            $update_data = array();
            if ($params['event_type'] === 'impression') {
                $update_data['impressions'] = $existing->impressions + 1;
            } elseif ($params['event_type'] === 'click') {
                $update_data['clicks'] = $existing->clicks + 1;
            }
            
            $wpdb->update(
                $table_name,
                $update_data,
                array('id' => $existing->id),
                array('%d'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'impressions' => $params['event_type'] === 'impression' ? 1 : 0,
                    'clicks' => $params['event_type'] === 'click' ? 1 : 0
                ),
                array('%d', '%d', '%d')
            );
        }
        
        return new WP_REST_Response(array('success' => true), 200);
    }
    
    public function inject_monetization_elements($content) {
        if (!is_singular('post') || is_admin()) {
            return $content;
        }
        
        $settings = get_option('contentrevenue_settings', array());
        if (empty($settings['auto_inject_ads'])) {
            return $content;
        }
        
        $post_id = get_the_ID();
        $word_count = str_word_count(strip_tags($content));
        
        if ($word_count < 500) {
            return $content;
        }
        
        $paragraphs = preg_split('/<\/p>/', $content);
        $para_count = count($paragraphs);
        
        if ($para_count > 3) {
            $insert_at = intval($para_count / 2);
            $ad_placeholder = '<!-- ContentRevenue AI Ad Space --><div class="contentrevenue-ad-space" data-post-id="' . esc_attr($post_id) . '"></div>';
            
            $paragraphs[$insert_at] .= '</p>' . $ad_placeholder;
            $content = implode('</p>', $paragraphs);
        }
        
        return $content;
    }
    
    public function render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentrevenue_analytics';
        
        $total_impressions = $wpdb->get_var("SELECT SUM(impressions) FROM $table_name");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $table_name");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table_name");
        
        $top_posts = $wpdb->get_results(
            "SELECT p.ID, p.post_title, a.impressions, a.clicks, a.revenue 
             FROM $table_name a 
             JOIN {$wpdb->posts} p ON a.post_id = p.ID 
             ORDER BY a.revenue DESC LIMIT 10"
        );
        
        ?>
        <div class="wrap contentrevenue-dashboard">
            <h1>ContentRevenue AI Dashboard</h1>
            <div class="contentrevenue-stats">
                <div class="stat-box">
                    <h3>Total Impressions</h3>
                    <p class="stat-value"><?php echo number_format($total_impressions ?: 0); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Total Clicks</h3>
                    <p class="stat-value"><?php echo number_format($total_clicks ?: 0); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Total Revenue</h3>
                    <p class="stat-value">$<?php echo number_format($total_revenue ?: 0, 2); ?></p>
                </div>
            </div>
            <h2>Top Performing Posts</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Post Title</th>
                        <th>Impressions</th>
                        <th>Clicks</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_posts as $post) : ?>
                        <tr>
                            <td><a href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a></td>
                            <td><?php echo number_format($post->impressions); ?></td>
                            <td><?php echo number_format($post->clicks); ?></td>
                            <td>$<?php echo number_format($post->revenue, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function render_analytics() {
        echo '<div class="wrap"><h1>ContentRevenue Analytics</h1><p>Detailed analytics coming soon...</p></div>';
    }
    
    public function render_settings() {
        $settings = get_option('contentrevenue_settings', array());
        $is_premium = get_option('contentrevenue_is_premium', false);
        ?>
        <div class="wrap">
            <h1>ContentRevenue AI Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentrevenue_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Premium License</th>
                        <td>
                            <?php if (!$is_premium) : ?>
                                <p><strong>Upgrade to Premium</strong> for advanced AI features, detailed analytics, and priority support.</p>
                                <a href="#" class="button button-primary">Upgrade Now</a>
                            <?php else : ?>
                                <p><span style="color: green;">✓</span> Premium license active</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enable_ai">Enable AI Suggestions</label></th>
                        <td>
                            <input type="checkbox" id="enable_ai" name="contentrevenue_settings[enable_ai_suggestions]" value="1" <?php checked($settings['enable_ai_suggestions'] ?? true); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="auto_inject">Auto-Inject Ads</label></th>
                        <td>
                            <input type="checkbox" id="auto_inject" name="contentrevenue_settings[auto_inject_ads]" value="1" <?php checked($settings['auto_inject_ads'] ?? false); ?> />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

ContentRevenueAI::get_instance();
?>