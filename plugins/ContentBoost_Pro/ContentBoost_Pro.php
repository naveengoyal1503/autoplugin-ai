<?php
/*
Plugin Name: ContentBoost Pro
Plugin URI: https://contentboostpro.com
Description: AI-powered content performance analytics and optimization for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Pro.php
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOOST_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentBoostPro {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendAssets'));
        add_action('rest_api_init', array($this, 'registerRestRoutes'));
        add_action('plugins_loaded', array($this, 'initPlugin'));
        register_activation_hook(__FILE__, array($this, 'activatePlugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivatePlugin'));
    }

    public function initPlugin() {
        $this->createTables();
        $this->setDefaultOptions();
    }

    public function activatePlugin() {
        $this->createTables();
        $this->setDefaultOptions();
    }

    public function deactivatePlugin() {
        // Cleanup on deactivation
    }

    private function createTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentboost_analytics';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                post_id bigint(20) NOT NULL,
                seo_score int(3),
                readability_score int(3),
                engagement_score int(3),
                word_count int(5),
                reading_time int(3),
                last_analyzed datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY post_id (post_id)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    private function setDefaultOptions() {
        $defaults = array(
            'contentboost_license_key' => '',
            'contentboost_is_premium' => false,
            'contentboost_api_key' => '',
            'contentboost_enable_auto_analysis' => true
        );

        foreach ($defaults as $key => $value) {
            if (!get_option($key)) {
                add_option($key, $value);
            }
        }
    }

    public function addAdminMenu() {
        add_menu_page(
            'ContentBoost Pro',
            'ContentBoost',
            'manage_options',
            'contentboost-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-chart-bar',
            25
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentboost-dashboard',
            array($this, 'renderDashboard')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-settings',
            array($this, 'renderSettings')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Upgrade to Pro',
            'Upgrade to Pro',
            'manage_options',
            'contentboost-pricing',
            array($this, 'renderPricing')
        );
    }

    public function enqueueAdminAssets() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'contentboost') !== false) {
            wp_enqueue_style('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'assets/admin.css', array(), CONTENTBOOST_VERSION);
            wp_enqueue_script('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'assets/admin.js', array('jquery'), CONTENTBOOST_VERSION, true);
            wp_localize_script('contentboost-admin', 'ContentBoostData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentboost_nonce'),
                'isPremium' => get_option('contentboost_is_premium')
            ));
        }
    }

    public function enqueueFrontendAssets() {
        // Enqueue frontend assets if needed
    }

    public function registerRestRoutes() {
        register_rest_route('contentboost/v1', '/analyze', array(
            'methods' => 'POST',
            'callback' => array($this, 'analyzePostContent'),
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));

        register_rest_route('contentboost/v1', '/analytics/(?P<post_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'getPostAnalytics'),
            'permission_callback' => function() {
                return current_user_can('read');
            }
        ));

        register_rest_route('contentboost/v1', '/activate-license', array(
            'methods' => 'POST',
            'callback' => array($this, 'activateLicense'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }

    public function analyzePostContent($request) {
        $params = $request->get_json_params();
        $post_id = intval($params['post_id'] ?? 0);

        if (!$post_id) {
            return new WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
        }

        $post = get_post($post_id);
        if (!$post || ($post->post_type !== 'post' && $post->post_type !== 'page')) {
            return new WP_Error('post_not_found', 'Post not found', array('status' => 404));
        }

        $content = $post->post_content;
        $title = $post->post_title;

        $analytics = $this->calculateAnalytics($content, $title);
        $this->saveAnalytics($post_id, $analytics);

        return rest_ensure_response($analytics);
    }

    private function calculateAnalytics($content, $title) {
        $word_count = str_word_count(strip_tags($content));
        $reading_time = ceil($word_count / 200);
        $sentence_count = substr_count($content, '.') + substr_count($content, '!') + substr_count($content, '?');

        // Basic SEO Score (0-100)
        $seo_score = 50;
        $seo_score += !empty($title) ? 15 : 0;
        $seo_score += strlen($title) >= 30 && strlen($title) <= 60 ? 10 : 0;
        $seo_score += $word_count >= 300 ? 15 : 0;
        $seo_score += strpos($content, $title) !== false ? 10 : 0;
        $seo_score = min(100, $seo_score);

        // Readability Score (0-100)
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        $readability_score = 80;
        $readability_score -= ($avg_sentence_length > 20) ? 10 : 0;
        $readability_score -= ($word_count < 300) ? 15 : 0;
        $readability_score = max(0, min(100, $readability_score));

        // Engagement Score (0-100)
        $heading_count = substr_count($content, '<h');
        $image_count = substr_count($content, '<img');
        $link_count = substr_count($content, '<a href');
        $engagement_score = 50;
        $engagement_score += ($heading_count >= 3) ? 15 : 0;
        $engagement_score += ($image_count >= 1) ? 15 : 0;
        $engagement_score += ($link_count >= 3) ? 20 : 0;
        $engagement_score = min(100, $engagement_score);

        return array(
            'seo_score' => intval($seo_score),
            'readability_score' => intval($readability_score),
            'engagement_score' => intval($engagement_score),
            'word_count' => $word_count,
            'reading_time' => $reading_time,
            'recommendations' => $this->generateRecommendations($seo_score, $readability_score, $engagement_score, $content)
        );
    }

    private function generateRecommendations($seo, $readability, $engagement, $content) {
        $recommendations = array();

        if ($seo < 70) {
            $recommendations[] = 'Improve SEO score: Add more keywords to your title and optimize meta descriptions.';
        }
        if ($readability < 70) {
            $recommendations[] = 'Improve readability: Break your content into shorter paragraphs and simpler sentences.';
        }
        if ($engagement < 70) {
            $recommendations[] = 'Boost engagement: Add more headings, images, and internal links to your content.';
        }

        return count($recommendations) > 0 ? $recommendations : array('Great content! Your post meets all quality standards.');
    }

    private function saveAnalytics($post_id, $analytics) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_analytics';

        $wpdb->replace(
            $table_name,
            array(
                'post_id' => $post_id,
                'seo_score' => $analytics['seo_score'],
                'readability_score' => $analytics['readability_score'],
                'engagement_score' => $analytics['engagement_score'],
                'word_count' => $analytics['word_count'],
                'reading_time' => $analytics['reading_time'],
                'last_analyzed' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%s')
        );
    }

    public function getPostAnalytics($request) {
        global $wpdb;
        $post_id = intval($request['post_id']);
        $table_name = $wpdb->prefix . 'contentboost_analytics';

        $analytics = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d",
            $post_id
        ), ARRAY_A);

        if (!$analytics) {
            return new WP_Error('not_found', 'No analytics found for this post', array('status' => 404));
        }

        return rest_ensure_response($analytics);
    }

    public function activateLicense($request) {
        $params = $request->get_json_params();
        $license_key = sanitize_text_field($params['license_key'] ?? '');

        if (empty($license_key)) {
            return new WP_Error('invalid_license', 'License key is required', array('status' => 400));
        }

        // Simulate license validation (in production, verify with remote server)
        if (strlen($license_key) === 32 && ctype_alnum($license_key)) {
            update_option('contentboost_license_key', $license_key);
            update_option('contentboost_is_premium', true);
            return rest_ensure_response(array('success' => true, 'message' => 'License activated successfully'));
        }

        return new WP_Error('invalid_license', 'Invalid license key', array('status' => 400));
    }

    public function renderDashboard() {
        $is_premium = get_option('contentboost_is_premium');
        ?>
        <div class="wrap contentboost-dashboard">
            <h1>ContentBoost Pro Dashboard</h1>
            <div class="contentboost-container">
                <div class="contentboost-card">
                    <h2>Analyze Your Content</h2>
                    <p>Select a post to analyze its SEO, readability, and engagement scores.</p>
                    <div id="contentboost-post-selector">
                        <?php
                        $args = array(
                            'post_type' => array('post', 'page'),
                            'posts_per_page' => 10,
                            'orderby' => 'date',
                            'order' => 'DESC'
                        );
                        $posts = get_posts($args);
                        ?>
                        <select id="contentboost-post-list">
                            <option value="">Select a post...</option>
                            <?php foreach ($posts as $post) : ?>
                                <option value="<?php echo esc_attr($post->ID); ?>">
                                    <?php echo esc_html($post->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="button button-primary" id="contentboost-analyze-btn">Analyze</button>
                    </div>
                    <div id="contentboost-results" style="margin-top: 20px; display: none;">
                        <div class="contentboost-scores">
                            <div class="score-box">
                                <strong>SEO Score:</strong> <span id="seo-score">-</span>/100
                            </div>
                            <div class="score-box">
                                <strong>Readability:</strong> <span id="readability-score">-</span>/100
                            </div>
                            <div class="score-box">
                                <strong>Engagement:</strong> <span id="engagement-score">-</span>/100
                            </div>
                            <div class="score-box">
                                <strong>Reading Time:</strong> <span id="reading-time">-</span> min
                            </div>
                        </div>
                        <div id="contentboost-recommendations">
                            <h3>Recommendations:</h3>
                            <ul id="recommendations-list"></ul>
                        </div>
                    </div>
                </div>

                <?php if (!$is_premium) : ?>
                    <div class="contentboost-card contentboost-upgrade">
                        <h2>Upgrade to Premium</h2>
                        <p>Get access to advanced features and unlimited analysis.</p>
                        <ul>
                            <li>✓ Unlimited post analysis</li>
                            <li>✓ AI-powered recommendations</li>
                            <li>✓ Bulk optimization</li>
                            <li>✓ Priority support</li>
                        </ul>
                        <a href="<?php echo admin_url('admin.php?page=contentboost-pricing'); ?>" class="button button-primary">Upgrade Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function renderSettings() {
        ?>
        <div class="wrap">
            <h1>ContentBoost Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('contentboost_settings');
                do_settings_sections('contentboost_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="auto_analysis">Enable Auto-Analysis</label></th>
                        <td>
                            <input type="checkbox" id="auto_analysis" name="contentboost_enable_auto_analysis" value="1" <?php checked(get_option('contentboost_enable_auto_analysis'), 1); ?> />
                            <p class="description">Automatically analyze posts when they are published or updated.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function renderPricing() {
        $is_premium = get_option('contentboost_is_premium');
        ?>
        <div class="wrap">
            <h1>Upgrade to ContentBoost Pro</h1>
            <div class="contentboost-pricing">
                <div class="pricing-card">
                    <h2>Free</h2>
                    <p class="price">$0</p>
                    <ul>
                        <li>✓ Basic content analysis</li>
                        <li>✓ SEO score</li>
                        <li>✓ Limited to 5 posts/month</li>
                    </ul>
                    <button class="button" disabled>Current Plan</button>
                </div>
                <div class="pricing-card featured">
                    <h2>Pro</h2>
                    <p class="price">$9.99<span>/month</span></p>
                    <ul>
                        <li>✓ Unlimited analysis</li>
                        <li>✓ Advanced recommendations</li>
                        <li>✓ AI-powered insights</li>
                        <li>✓ Priority support</li>
                    </ul>
                    <?php if (!$is_premium) : ?>
                        <button class="button button-primary" id="contentboost-upgrade-btn">Upgrade Now</button>
                    <?php else : ?>
                        <button class="button" disabled>Active</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$is_premium) : ?>
                <div class="license-activation" style="margin-top: 40px;">
                    <h2>Already have a license?</h2>
                    <input type="text" id="license-key-input" placeholder="Enter your license key..." />
                    <button class="button button-primary" id="activate-license-btn">Activate License</button>
                    <div id="activation-message" style="margin-top: 10px;"></div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

ContentBoostPro::getInstance();
?>
