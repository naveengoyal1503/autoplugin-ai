<?php
/*
Plugin Name: ContentValueBooster
Plugin URI: https://contentvaluebooster.com
Description: AI-powered content analyzer that identifies monetization opportunities and suggests revenue strategies
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentValueBooster.php
License: GPL v2 or later
Text Domain: content-value-booster
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CVB_VERSION', '1.0.0');
define('CVB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CVB_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentValueBooster {
    private static $instance = null;
    private $db_version = '1.0';
    private $option_name = 'cvb_db_version';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('wp_ajax_cvb_analyze_post', array($this, 'ajax_analyze_post'));
        add_action('wp_ajax_cvb_get_recommendations', array($this, 'ajax_get_recommendations'));
        add_action('wp_ajax_cvb_get_stats', array($this, 'ajax_get_stats'));
        
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    public function activate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'cvb_post_analysis';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            traffic_score int(3),
            engagement_score int(3),
            monetization_potential varchar(50),
            recommended_strategy varchar(100),
            analysis_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option($this->option_name, $this->db_version);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'Content Value Booster',
            'CVB Dashboard',
            'manage_options',
            'content-value-booster',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            65
        );

        add_submenu_page(
            'content-value-booster',
            'Monetization Strategies',
            'Strategies',
            'manage_options',
            'cvb-strategies',
            array($this, 'render_strategies')
        );

        add_submenu_page(
            'content-value-booster',
            'Settings',
            'Settings',
            'manage_options',
            'cvb-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'content-value-booster') === false) {
            return;
        }

        wp_enqueue_style('cvb-admin-style', CVB_PLUGIN_URL . 'assets/admin-style.css', array(), CVB_VERSION);
        wp_enqueue_script('cvb-admin-script', CVB_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), CVB_VERSION, true);
        wp_localize_script('cvb-admin-script', 'cvbAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cvb-nonce')
        ));
    }

    public function render_dashboard() {
        $top_posts = $this->get_top_posts();
        ?>
        <div class="wrap">
            <h1>Content Value Booster Dashboard</h1>
            <div class="cvb-container">
                <div class="cvb-stats-container">
                    <div class="cvb-stat-card">
                        <h3>Total Posts Analyzed</h3>
                        <p class="cvb-stat-number"><?php echo count($top_posts); ?></p>
                    </div>
                    <div class="cvb-stat-card">
                        <h3>Avg Monetization Score</h3>
                        <p class="cvb-stat-number"><?php echo $this->get_average_monetization_score(); ?></p>
                    </div>
                    <div class="cvb-stat-card">
                        <h3>Revenue Potential</h3>
                        <p class="cvb-stat-number">$<?php echo $this->estimate_revenue_potential(); ?></p>
                    </div>
                </div>
                <div class="cvb-posts-table">
                    <h2>Top Posts for Monetization</h2>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Post Title</th>
                                <th>Potential</th>
                                <th>Recommended Strategy</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_posts as $post): ?>
                                <tr>
                                    <td><?php echo esc_html($post->post_title); ?></td>
                                    <td><span class="cvb-badge"><?php echo esc_html($this->get_post_potential($post->ID)); ?></span></td>
                                    <td><?php echo esc_html($this->get_recommendation($post->ID)); ?></td>
                                    <td><button class="button cvb-analyze-btn" data-post-id="<?php echo $post->ID; ?>">Analyze</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_strategies() {
        ?>
        <div class="wrap">
            <h1>Monetization Strategies</h1>
            <div class="cvb-strategies">
                <div class="cvb-strategy-card">
                    <h2>Display Advertising</h2>
                    <p>Use Google AdSense or Mediavine to display contextual ads. Best for high-traffic sites with general audiences.</p>
                    <button class="button button-primary cvb-strategy-select" data-strategy="ads">Learn More</button>
                </div>
                <div class="cvb-strategy-card">
                    <h2>Affiliate Marketing</h2>
                    <p>Recommend products and earn commissions. Ideal for niche blogs with product-focused content.</p>
                    <button class="button button-primary cvb-strategy-select" data-strategy="affiliate">Learn More</button>
                </div>
                <div class="cvb-strategy-card">
                    <h2>Memberships & Subscriptions</h2>
                    <p>Offer exclusive content to paying members. Great for building recurring revenue streams.</p>
                    <button class="button button-primary cvb-strategy-select" data-strategy="membership">Learn More</button>
                </div>
                <div class="cvb-strategy-card">
                    <h2>Digital Products</h2>
                    <p>Sell e-books, courses, or templates. Perfect for leveraging your expertise into scalable products.</p>
                    <button class="button button-primary cvb-strategy-select" data-strategy="digital">Learn More</button>
                </div>
                <div class="cvb-strategy-card">
                    <h2>Sponsored Content</h2>
                    <p>Partner with brands for sponsored posts. Requires significant traffic and audience alignment.</p>
                    <button class="button button-primary cvb-strategy-select" data-strategy="sponsored">Learn More</button>
                </div>
                <div class="cvb-strategy-card">
                    <h2>Services & Consulting</h2>
                    <p>Offer freelance services or consulting. Leverage your blog authority to attract high-value clients.</p>
                    <button class="button button-primary cvb-strategy-select" data-strategy="services">Learn More</button>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>CVB Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('cvb_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="cvb_adsense_enabled">Enable AdSense Integration</label></th>
                        <td>
                            <input type="checkbox" name="cvb_adsense_enabled" id="cvb_adsense_enabled" value="1" <?php checked(get_option('cvb_adsense_enabled'), 1); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cvb_affiliate_enabled">Enable Affiliate Tracking</label></th>
                        <td>
                            <input type="checkbox" name="cvb_affiliate_enabled" id="cvb_affiliate_enabled" value="1" <?php checked(get_option('cvb_affiliate_enabled'), 1); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cvb_analysis_frequency">Analysis Frequency</label></th>
                        <td>
                            <select name="cvb_analysis_frequency" id="cvb_analysis_frequency">
                                <option value="daily" <?php selected(get_option('cvb_analysis_frequency'), 'daily'); ?>>Daily</option>
                                <option value="weekly" <?php selected(get_option('cvb_analysis_frequency'), 'weekly'); ?>>Weekly</option>
                                <option value="monthly" <?php selected(get_option('cvb_analysis_frequency'), 'monthly'); ?>>Monthly</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget('cvb_dashboard_widget', 'Content Value Booster', array($this, 'render_dashboard_widget'));
    }

    public function render_dashboard_widget() {
        $top_post = $this->get_top_posts(1);
        if (!empty($top_post)) {
            $post = $top_post[0];
            echo '<p><strong>' . esc_html($post->post_title) . '</strong></p>';
            echo '<p>Monetization Potential: <strong>' . esc_html($this->get_post_potential($post->ID)) . '</strong></p>';
            echo '<p>Recommended Strategy: <strong>' . esc_html($this->get_recommendation($post->ID)) . '</strong></p>';
            echo '<a href="' . get_edit_post_link($post->ID) . '" class="button">Edit Post</a>';
        }
    }

    public function ajax_analyze_post() {
        check_ajax_referer('cvb-nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $analysis = $this->analyze_post($post);
        wp_send_json_success($analysis);
    }

    public function ajax_get_recommendations() {
        check_ajax_referer('cvb-nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $recommendations = $this->generate_recommendations();
        wp_send_json_success($recommendations);
    }

    public function ajax_get_stats() {
        check_ajax_referer('cvb-nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $stats = array(
            'total_posts' => $this->count_analyzed_posts(),
            'avg_score' => $this->get_average_monetization_score(),
            'estimated_revenue' => $this->estimate_revenue_potential()
        );
        wp_send_json_success($stats);
    }

    private function analyze_post($post) {
        $word_count = str_word_count(strip_tags($post->post_content));
        $comment_count = $post->comment_count;
        $categories = wp_get_post_categories($post->ID);
        
        $traffic_score = min(100, max(0, $word_count / 10));
        $engagement_score = min(100, $comment_count * 5);
        $overall_score = round(($traffic_score + $engagement_score) / 2);

        $strategy = $this->determine_strategy($post);
        $potential = $this->calculate_potential($overall_score);

        return array(
            'post_id' => $post->ID,
            'traffic_score' => $traffic_score,
            'engagement_score' => $engagement_score,
            'overall_score' => $overall_score,
            'recommended_strategy' => $strategy,
            'monetization_potential' => $potential
        );
    }

    private function determine_strategy($post) {
        $categories = wp_get_post_categories($post->ID);
        $content = strtolower($post->post_content);
        
        if (strpos($content, 'product') !== false || strpos($content, 'review') !== false) {
            return 'Affiliate Marketing';
        } elseif (strpos($content, 'tutorial') !== false || strpos($content, 'guide') !== false) {
            return 'Digital Products';
        } else {
            return 'Display Ads';
        }
    }

    private function calculate_potential($score) {
        if ($score >= 80) return 'High';
        if ($score >= 50) return 'Medium';
        return 'Low';
    }

    private function get_top_posts($limit = 10) {
        $args = array(
            'posts_per_page' => $limit,
            'post_type' => 'post',
            'post_status' => 'publish',
            'orderby' => 'comment_count',
            'order' => 'DESC'
        );
        return get_posts($args);
    }

    private function get_post_potential($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cvb_post_analysis';
        $result = $wpdb->get_var($wpdb->prepare("SELECT monetization_potential FROM $table WHERE post_id = %d ORDER BY analysis_date DESC LIMIT 1", $post_id));
        return $result ?: 'Not Analyzed';
    }

    private function get_recommendation($post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'cvb_post_analysis';
        $result = $wpdb->get_var($wpdb->prepare("SELECT recommended_strategy FROM $table WHERE post_id = %d ORDER BY analysis_date DESC LIMIT 1", $post_id));
        return $result ?: 'Run Analysis';
    }

    private function get_average_monetization_score() {
        global $wpdb;
        $table = $wpdb->prefix . 'cvb_post_analysis';
        $avg = $wpdb->get_var("SELECT AVG(engagement_score + traffic_score) / 2 FROM $table");
        return intval($avg) ?: 0;
    }

    private function estimate_revenue_potential() {
        $avg_score = $this->get_average_monetization_score();
        return round($avg_score * 2.5);
    }

    private function count_analyzed_posts() {
        global $wpdb;
        $table = $wpdb->prefix . 'cvb_post_analysis';
        return $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM $table");
    }

    private function generate_recommendations() {
        return array(
            'optimize_meta' => 'Update post meta descriptions to improve click-through rates',
            'internal_links' => 'Add internal linking to high-value products in affiliate content',
            'update_content' => 'Refresh older posts with latest trends to boost engagement',
            'cta_placement' => 'Add clear calls-to-action for affiliate links and products',
            'email_list' => 'Build email list to notify subscribers of high-value content'
        );
    }

    public function add_action_links($links) {
        $settings_link = '<a href="admin.php?page=content-value-booster">Dashboard</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize plugin
ContentValueBooster::get_instance();

// Create admin stylesheet and JavaScript files placeholder
if (!file_exists(CVB_PLUGIN_DIR . 'assets')) {
    mkdir(CVB_PLUGIN_DIR . 'assets');
}
?>
