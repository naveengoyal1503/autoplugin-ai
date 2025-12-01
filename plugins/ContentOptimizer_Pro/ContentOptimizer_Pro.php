<?php
/*
Plugin Name: ContentOptimizer Pro
Plugin URI: https://contentoptimizer.pro
Description: AI-powered content optimization for WordPress with SEO, readability, and conversion insights
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentOptimizer_Pro.php
License: GPL v2 or later
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENT_OPTIMIZER_VERSION', '1.0.0');
define('CONTENT_OPTIMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENT_OPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentOptimizer {
    private static $instance = null;
    private $db;
    private $options;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->options = get_option('content_optimizer_options', array());
        $this->initHooks();
        $this->createTables();
    }

    private function initHooks() {
        add_action('admin_menu', array($this, 'addMenuPage'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('rest_api_init', array($this, 'registerRestRoutes'));
        add_action('save_post', array($this, 'analyzeContent'), 10, 2);
        add_action('admin_notices', array($this, 'displayNotices'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function addMenuPage() {
        add_menu_page(
            'ContentOptimizer Pro',
            'ContentOptimizer',
            'manage_options',
            'content-optimizer',
            array($this, 'renderDashboard'),
            'dashicons-chart-bar'
        );
        add_submenu_page(
            'content-optimizer',
            'Analytics',
            'Analytics',
            'manage_options',
            'content-optimizer-analytics',
            array($this, 'renderAnalytics')
        );
        add_submenu_page(
            'content-optimizer',
            'Settings',
            'Settings',
            'manage_options',
            'content-optimizer-settings',
            array($this, 'renderSettings')
        );
    }

    public function enqueueScripts($hook) {
        if (strpos($hook, 'content-optimizer') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1');
            wp_enqueue_style('content-optimizer-style', CONTENT_OPTIMIZER_PLUGIN_URL . 'assets/style.css');
            wp_enqueue_script('content-optimizer-script', CONTENT_OPTIMIZER_PLUGIN_URL . 'assets/script.js', array('jquery'), CONTENT_OPTIMIZER_VERSION);
            wp_localize_script('content-optimizer-script', 'contentOptimizerData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('content-optimizer-nonce'),
                'isPremium' => $this->isPremiumUser()
            ));
        }
    }

    public function registerRestRoutes() {
        register_rest_route('content-optimizer/v1', '/analyze', array(
            'methods' => 'POST',
            'callback' => array($this, 'analyzeContentAPI'),
            'permission_callback' => array($this, 'checkPermission')
        ));
        register_rest_route('content-optimizer/v1', '/suggestions', array(
            'methods' => 'GET',
            'callback' => array($this, 'getSuggestions'),
            'permission_callback' => array($this, 'checkPermission')
        ));
    }

    public function analyzeContent($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ($post->post_type !== 'post' && $post->post_type !== 'page') return;

        $analysis = $this->performAnalysis($post->post_content, $post->post_title);
        $this->saveAnalysis($post_id, $analysis);
    }

    private function performAnalysis($content, $title) {
        $analysis = array(
            'timestamp' => current_time('mysql'),
            'word_count' => str_word_count(wp_strip_all_tags($content)),
            'readability_score' => $this->calculateReadability($content),
            'seo_score' => $this->calculateSEOScore($title, $content),
            'engagement_score' => $this->calculateEngagement($content),
            'recommendations' => $this->generateRecommendations($content, $title)
        );
        return $analysis;
    }

    private function calculateReadability($content) {
        $words = str_word_count(wp_strip_all_tags($content));
        $sentences = substr_count($content, '.') + substr_count($content, '!') + substr_count($content, '?');
        $paragraphs = substr_count($content, '</p>');
        
        if ($sentences == 0 || $words == 0) return 0;
        
        $score = min(100, ($words / $sentences) * 2 + ($paragraphs * 5));
        return round($score);
    }

    private function calculateSEOScore($title, $content) {
        $score = 0;
        $content_lower = strtolower($content);
        $title_lower = strtolower($title);
        
        if (strlen($title) >= 30 && strlen($title) <= 60) $score += 25;
        if (strlen(wp_strip_all_tags($content)) >= 300) $score += 25;
        if (substr_count($content, '<h2>') > 0) $score += 20;
        if (preg_match_all('/!\[.*?\]\(.*?\)/', $content) > 0) $score += 15;
        if (preg_match_all('/https?:\/\/', $content) > 0) $score += 15;
        
        return min(100, $score);
    }

    private function calculateEngagement($content) {
        $score = 0;
        if (preg_match_all('/<strong>|<b>/', $content) > 0) $score += 15;
        if (preg_match_all('/<ul>|<ol>/', $content) > 1) $score += 25;
        if (preg_match_all('/<h[2-6]>/', $content) > 2) $score += 30;
        if (preg_match_all('/\(.*?\)/', $content) > 3) $score += 15;
        if (preg_match_all('/<blockquote>/', $content) > 0) $score += 15;
        
        return min(100, $score);
    }

    private function generateRecommendations($content, $title) {
        $recommendations = array();
        
        if (strlen($title) < 30) {
            $recommendations[] = array('type' => 'title', 'message' => 'Increase title length to 30-60 characters for better SEO');
        }
        if (str_word_count(wp_strip_all_tags($content)) < 300) {
            $recommendations[] = array('type' => 'length', 'message' => 'Expand content to at least 300 words');
        }
        if (substr_count($content, '<h2>') === 0) {
            $recommendations[] = array('type' => 'headers', 'message' => 'Add subheadings (H2) to improve structure');
        }
        if (substr_count($content, '<ul>') + substr_count($content, '<ol>') === 0) {
            $recommendations[] = array('type' => 'lists', 'message' => 'Add bullet or numbered lists for better readability');
        }
        
        return $recommendations;
    }

    private function saveAnalysis($post_id, $analysis) {
        update_post_meta($post_id, '_content_optimizer_analysis', $analysis);
    }

    public function analyzeContentAPI($request) {
        if (!$this->isPremiumUser()) {
            return new WP_Error('premium_required', 'Premium subscription required', array('status' => 403));
        }
        
        $content = $request->get_param('content');
        $title = $request->get_param('title');
        
        if (empty($content) || empty($title)) {
            return new WP_Error('missing_params', 'Content and title are required', array('status' => 400));
        }
        
        $analysis = $this->performAnalysis($content, $title);
        return rest_ensure_response($analysis);
    }

    public function getSuggestions($request) {
        $post_id = $request->get_param('post_id');
        $analysis = get_post_meta($post_id, '_content_optimizer_analysis', true);
        
        if (!$analysis) {
            return new WP_Error('no_analysis', 'No analysis available for this post', array('status' => 404));
        }
        
        return rest_ensure_response($analysis['recommendations']);
    }

    public function checkPermission() {
        return current_user_can('edit_posts');
    }

    public function renderDashboard() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>ContentOptimizer Pro Dashboard</h1>
            <div id="co-dashboard" style="margin-top: 20px;">
                <div class="co-stats">
                    <div class="co-stat-box">
                        <h3>Average SEO Score</h3>
                        <p id="avg-seo" style="font-size: 32px; font-weight: bold;">--</p>
                    </div>
                    <div class="co-stat-box">
                        <h3>Average Readability</h3>
                        <p id="avg-readability" style="font-size: 32px; font-weight: bold;">--</p>
                    </div>
                    <div class="co-stat-box">
                        <h3>Total Articles Analyzed</h3>
                        <p id="total-articles" style="font-size: 32px; font-weight: bold;">--</p>
                    </div>
                </div>
                <div style="margin-top: 30px;">
                    <h2>Recent Analyses</h2>
                    <div id="recent-analyses" style="background: #f5f5f5; padding: 15px; border-radius: 5px;">Loading...</div>
                </div>
            </div>
        </div>
        <?php
    }

    public function renderAnalytics() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>ContentOptimizer Pro Analytics</h1>
            <div id="co-analytics" style="margin-top: 20px;">
                <canvas id="performanceChart" style="max-width: 600px;"></canvas>
            </div>
            <?php if (!$this->isPremiumUser()) { ?>
                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;">
                    <p><strong>Upgrade to Premium</strong> to unlock advanced analytics and detailed performance insights.</p>
                    <a href="#" class="button button-primary">Upgrade Now</a>
                </div>
            <?php } ?>
        </div>
        <?php
    }

    public function renderSettings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        ?>
        <div class="wrap">
            <h1>ContentOptimizer Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('content_optimizer_options'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_key">API Key</label></th>
                        <td>
                            <input type="password" id="api_key" name="content_optimizer_api_key" value="<?php echo esc_attr($this->options['api_key'] ?? ''); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="auto_analyze">Auto-analyze on publish</label></th>
                        <td>
                            <input type="checkbox" id="auto_analyze" name="content_optimizer_auto_analyze" value="1" <?php checked($this->options['auto_analyze'] ?? 0, 1); ?> />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!$this->isPremiumUser()) { ?>
                <div style="margin-top: 30px; padding: 20px; background: #e8f4f8; border-left: 4px solid #0073aa; border-radius: 5px;">
                    <h2>Unlock Premium Features</h2>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li>Unlimited content analysis</li>
                        <li>AI-powered recommendations</li>
                        <li>A/B testing capabilities</li>
                        <li>Advanced analytics dashboard</li>
                        <li>Priority API support</li>
                    </ul>
                    <a href="#" class="button button-primary button-large">Subscribe Now - $9.99/month</a>
                </div>
            <?php } ?>
        </div>
        <?php
    }

    public function displayNotices() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $screen = get_current_screen();
        if ($screen->id === 'post') {
            global $post;
            $analysis = get_post_meta($post->ID, '_content_optimizer_analysis', true);
            if ($analysis) {
                ?>
                <div class="notice notice-info" style="margin: 15px 0;">
                    <p><strong>ContentOptimizer Analysis:</strong> SEO Score: <?php echo $analysis['seo_score']; ?>/100 | Readability: <?php echo $analysis['readability_score']; ?>/100 | Engagement: <?php echo $analysis['engagement_score']; ?>/100</p>
                </div>
                <?php
            }
        }
    }

    private function isPremiumUser() {
        $user_id = get_current_user_id();
        return get_user_meta($user_id, '_content_optimizer_premium', true) === '1';
    }

    private function createTables() {
        $charset_collate = $this->db->get_charset_collate();
        $table_name = $this->db->prefix . 'content_optimizer_analytics';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            seo_score mediumint(3),
            readability_score mediumint(3),
            engagement_score mediumint(3),
            word_count mediumint(5),
            analyzed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->createTables();
        add_option('content_optimizer_activated', current_time('mysql'));
    }

    public function deactivate() {
        delete_option('content_optimizer_activated');
    }
}

ContentOptimizer::getInstance();
?>