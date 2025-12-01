/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentAI_Optimizer.php
*/
<?php
/**
 * Plugin Name: ContentAI Optimizer
 * Plugin URI: https://contentaioptimizer.com
 * Description: AI-powered content optimization with real-time SEO and engagement suggestions
 * Version: 1.0.0
 * Author: ContentAI Team
 * Author URI: https://contentaioptimizer.com
 * License: GPL v2 or later
 * Text Domain: contentai-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTAI_VERSION', '1.0.0');
define('CONTENTAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTAI_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentAIOptimizer {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
        add_action('wp_ajax_contentai_analyze', array($this, 'analyzeContent'));
        add_action('wp_ajax_contentai_get_license', array($this, 'getLicenseInfo'));
        register_activation_hook(__FILE__, array($this, 'activatePlugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivatePlugin'));
    }

    public function addAdminMenu() {
        add_menu_page(
            'ContentAI Optimizer',
            'ContentAI',
            'manage_options',
            'contentai-optimizer',
            array($this, 'renderDashboard'),
            'dashicons-chart-line',
            30
        );
        add_submenu_page(
            'contentai-optimizer',
            'Settings',
            'Settings',
            'manage_options',
            'contentai-settings',
            array($this, 'renderSettings')
        );
    }

    public function enqueueScripts($hook) {
        if (strpos($hook, 'contentai') === false) {
            return;
        }
        wp_enqueue_script(
            'contentai-admin',
            CONTENTAI_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            CONTENTAI_VERSION
        );
        wp_enqueue_style(
            'contentai-admin',
            CONTENTAI_PLUGIN_URL . 'assets/admin.css',
            array(),
            CONTENTAI_VERSION
        );
        wp_localize_script('contentai-admin', 'contentaiData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentai-nonce'),
            'isPremium' => $this->isPremiumUser(),
        ));
    }

    public function analyzeContent() {
        check_ajax_referer('contentai-nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $postId = intval($_POST['post_id']);
        $post = get_post($postId);

        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $analysis = $this->performAnalysis($post);
        wp_send_json_success($analysis);
    }

    private function performAnalysis($post) {
        $content = $post->post_content;
        $wordCount = str_word_count(strip_tags($content));
        $sentences = strlen($content) > 0 ? preg_match_all('/[.!?]+/', $content, $m) : 0;
        $readingTime = ceil($wordCount / 200);
        $headingCount = preg_match_all('/<h[1-6]/', $content, $m);
        $keywordDensity = $this->calculateKeywordDensity($content, $post->post_title);

        $suggestions = array();

        if ($wordCount < 300) {
            $suggestions[] = array(
                'type' => 'warning',
                'message' => 'Content is too short. Aim for at least 300 words for better SEO.',
                'priority' => 'high'
            );
        }

        if ($headingCount < 2) {
            $suggestions[] = array(
                'type' => 'warning',
                'message' => 'Add more headings to structure your content better.',
                'priority' => 'medium'
            );
        }

        if ($keywordDensity < 0.5) {
            $suggestions[] = array(
                'type' => 'info',
                'message' => 'Consider using your target keyword more frequently.',
                'priority' => 'low'
            );
        }

        return array(
            'wordCount' => $wordCount,
            'readingTime' => $readingTime,
            'headingCount' => $headingCount,
            'keywordDensity' => round($keywordDensity, 2),
            'suggestions' => $suggestions,
            'score' => min(100, 50 + ($wordCount > 300 ? 20 : 0) + ($headingCount > 1 ? 15 : 0) + ($keywordDensity > 0.5 ? 15 : 0))
        );
    }

    private function calculateKeywordDensity($content, $keyword) {
        $cleanContent = strtolower(strip_tags($content));
        $cleanKeyword = strtolower($keyword);
        $wordCount = str_word_count($cleanContent);

        if ($wordCount === 0) {
            return 0;
        }

        $keywordCount = substr_count($cleanContent, $cleanKeyword);
        return ($keywordCount / $wordCount) * 100;
    }

    public function getLicenseInfo() {
        check_ajax_referer('contentai-nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $license = get_option('contentai_license');
        wp_send_json_success(array(
            'isPremium' => !empty($license),
            'licenseKey' => substr($license, -4),
            'expiryDate' => get_option('contentai_license_expiry')
        ));
    }

    private function isPremiumUser() {
        $license = get_option('contentai_license');
        if (empty($license)) {
            return false;
        }
        $expiry = get_option('contentai_license_expiry');
        return strtotime($expiry) > current_time('timestamp');
    }

    public function renderDashboard() {
        ?>
        <div class="wrap">
            <h1>ContentAI Optimizer</h1>
            <div class="contentai-dashboard">
                <div class="contentai-card">
                    <h2>Latest Posts Analysis</h2>
                    <div id="contentai-analysis" class="contentai-results"></div>
                </div>
                <div class="contentai-card contentai-sidebar">
                    <h2>Quick Stats</h2>
                    <p><strong>Premium Status:</strong> <?php echo $this->isPremiumUser() ? 'Active' : 'Free'; ?></p>
                    <button class="button button-primary" id="contentai-upgrade">Upgrade to Premium</button>
                </div>
            </div>
        </div>
        <?php
    }

    public function renderSettings() {
        ?>
        <div class="wrap">
            <h1>ContentAI Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentai-settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="contentai_license">License Key</label></th>
                        <td><input type="text" id="contentai_license" name="contentai_license" value="<?php echo esc_attr(get_option('contentai_license')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function activatePlugin() {
        add_option('contentai_activated', current_time('mysql'));
        add_option('contentai_free_analyses', 5);
    }

    public function deactivatePlugin() {
        delete_option('contentai_activated');
    }
}

add_action('plugins_loaded', function() {
    ContentAIOptimizer::getInstance();
});
?>