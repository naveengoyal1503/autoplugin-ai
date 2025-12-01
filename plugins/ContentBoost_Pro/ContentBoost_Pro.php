<?php
/*
Plugin Name: ContentBoost Pro
Description: AI-powered content optimizer for maximizing blog revenue
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Pro.php
Text Domain: contentboost-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOOST_URL', plugin_dir_url(__FILE__));

class ContentBoostPro {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_contentboost_analyze_post', array($this, 'analyze_post'));
        add_action('wp_ajax_contentboost_get_upgrade_modal', array($this, 'get_upgrade_modal'));
        add_shortcode('contentboost_score', array($this, 'render_score_badge'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }
    
    public function activate_plugin() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_analytics';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            content_score int(3) NOT NULL,
            seo_score int(3) NOT NULL,
            readability_score int(3) NOT NULL,
            keyword_density float NOT NULL,
            estimated_impressions int(11) NOT NULL DEFAULT 0,
            estimated_revenue decimal(10,2) NOT NULL DEFAULT 0,
            suggestions longtext NOT NULL,
            analyzed_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('contentboost_activated', true);
        update_option('contentboost_plan', 'free');
    }
    
    public function deactivate_plugin() {
        update_option('contentboost_activated', false);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost Pro',
            'ContentBoost',
            'manage_options',
            'contentboost',
            array($this, 'render_dashboard'),
            'dashicons-trending-up',
            30
        );
        
        add_submenu_page(
            'contentboost',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentboost',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'contentboost',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-settings',
            array($this, 'render_settings')
        );
        
        add_submenu_page(
            'contentboost',
            'Pricing',
            'Upgrade Pro',
            'manage_options',
            'contentboost-pricing',
            array($this, 'render_pricing')
        );
    }
    
    public function register_settings() {
        register_setting('contentboost_settings', 'contentboost_license_key');
        register_setting('contentboost_settings', 'contentboost_auto_analyze');
        register_setting('contentboost_settings', 'contentboost_min_word_count');
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'contentboost') === false) return;
        
        wp_enqueue_style('contentboost-admin', CONTENTBOOST_URL . 'assets/admin.css', array(), CONTENTBOOST_VERSION);
        wp_enqueue_script('contentboost-admin', CONTENTBOOST_URL . 'assets/admin.js', array('jquery'), CONTENTBOOST_VERSION, true);
        
        wp_localize_script('contentboost-admin', 'contentboostData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentboost_nonce'),
            'plan' => get_option('contentboost_plan', 'free')
        ));
    }
    
    public function render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_analytics';
        
        $recent_analyses = $wpdb->get_results("SELECT * FROM $table_name ORDER BY analyzed_date DESC LIMIT 10");
        $avg_score = $wpdb->get_var("SELECT AVG(content_score) FROM $table_name");
        $total_estimated_revenue = $wpdb->get_var("SELECT SUM(estimated_revenue) FROM $table_name");
        
        echo '<div class="wrap contentboost-dashboard">';
        echo '<h1>ContentBoost Pro Dashboard</h1>';
        echo '<div class="contentboost-stats">';
        echo '<div class="stat-card"><h3>Average Score</h3><p class="big-number">' . round($avg_score) . '</p></div>';
        echo '<div class="stat-card"><h3>Est. Revenue Boost</h3><p class="big-number">$' . round($total_estimated_revenue, 2) . '</p></div>';
        echo '<div class="stat-card"><h3>Posts Analyzed</h3><p class="big-number">' . count($recent_analyses) . '</p></div>';
        echo '</div>';
        
        echo '<h2>Recent Analyses</h2>';
        echo '<table class="wp-list-table fixed striped">';
        echo '<thead><tr><th>Post</th><th>Content Score</th><th>SEO Score</th><th>Readability</th><th>Est. Revenue</th><th>Date</th></tr></thead><tbody>';
        
        foreach ($recent_analyses as $analysis) {
            $post = get_post($analysis->post_id);
            echo '<tr>';
            echo '<td><a href="' . get_edit_post_link($analysis->post_id) . '">' . $post->post_title . '</a></td>';
            echo '<td>' . $analysis->content_score . '%</td>';
            echo '<td>' . $analysis->seo_score . '%</td>';
            echo '<td>' . $analysis->readability_score . '%</td>';
            echo '<td>$' . round($analysis->estimated_revenue, 2) . '</td>';
            echo '<td>' . $analysis->analyzed_date . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    public function render_settings() {
        echo '<div class="wrap">';
        echo '<h1>ContentBoost Pro Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('contentboost_settings');
        
        echo '<table class="form-table">';
        echo '<tr><th scope="row"><label for="contentboost_min_word_count">Minimum Word Count</label></th>';
        echo '<td><input type="number" id="contentboost_min_word_count" name="contentboost_min_word_count" value="' . get_option('contentboost_min_word_count', 800) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="contentboost_auto_analyze">Auto-analyze new posts</label></th>';
        echo '<td><input type="checkbox" id="contentboost_auto_analyze" name="contentboost_auto_analyze" value="1" ' . checked(get_option('contentboost_auto_analyze'), 1) . ' /></td></tr>';
        echo '</table>';
        
        submit_button();
        echo '</form>';
        echo '</div>';
    }
    
    public function render_pricing() {
        echo '<div class="wrap contentboost-pricing">';
        echo '<h1>ContentBoost Pro - Upgrade Your Plan</h1>';
        echo '<div class="pricing-cards">';
        echo '<div class="card free"><h2>Free</h2><p class="price">$0/mo</p><ul><li>✓ Basic post analysis</li><li>✓ Content score</li><li>✓ 1 analysis/day</li><li>✗ Advanced SEO</li><li>✗ Revenue forecasting</li></ul></div>';
        echo '<div class="card pro"><h2>Pro</h2><p class="price">$9.99/mo</p><ul><li>✓ Unlimited analyses</li><li>✓ Advanced SEO optimization</li><li>✓ Revenue forecasting</li><li>✓ Affiliate suggestions</li><li>✓ Bulk optimization</li></ul><button class="btn-upgrade">Upgrade Now</button></div>';
        echo '<div class="card agency"><h2>Agency</h2><p class="price">$29.99/mo</p><ul><li>✓ Everything in Pro</li><li>✓ Multi-site support</li><li>✓ Priority support</li><li>✓ White-label option</li><li>✓ API access</li></ul><button class="btn-upgrade">Upgrade Now</button></div>';
        echo '</div>';
        echo '</div>';
    }
    
    public function analyze_post() {
        check_ajax_referer('contentboost_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Post not found');
        }
        
        $content = $post->post_content;
        $title = $post->post_title;
        
        // Calculate scores
        $content_score = $this->calculate_content_score($content);
        $seo_score = $this->calculate_seo_score($title, $content);
        $readability_score = $this->calculate_readability_score($content);
        $keyword_density = $this->calculate_keyword_density($content);
        $estimated_impressions = $this->estimate_impressions($content_score, $seo_score);
        $estimated_revenue = $this->estimate_revenue($estimated_impressions);
        
        // Generate suggestions
        $suggestions = $this->generate_suggestions($content_score, $seo_score, $readability_score, $keyword_density);
        
        // Store in database
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'contentboost_analytics',
            array(
                'post_id' => $post_id,
                'content_score' => $content_score,
                'seo_score' => $seo_score,
                'readability_score' => $readability_score,
                'keyword_density' => $keyword_density,
                'estimated_impressions' => $estimated_impressions,
                'estimated_revenue' => $estimated_revenue,
                'suggestions' => json_encode($suggestions)
            )
        );
        
        wp_send_json_success(array(
            'content_score' => $content_score,
            'seo_score' => $seo_score,
            'readability_score' => $readability_score,
            'estimated_impressions' => $estimated_impressions,
            'estimated_revenue' => round($estimated_revenue, 2),
            'suggestions' => $suggestions
        ));
    }
    
    private function calculate_content_score($content) {
        $score = 50;
        
        if (strlen($content) > 1500) $score += 15;
        if (substr_count($content, '<h2>') + substr_count($content, '<h3>') > 3) $score += 10;
        if (preg_match_all('/\[affiliate.*?\]/i', $content) > 2) $score += 15;
        if (substr_count($content, 'https://') > 5) $score += 10;
        
        return min($score, 100);
    }
    
    private function calculate_seo_score($title, $content) {
        $score = 50;
        
        if (strlen($title) > 50 && strlen($title) < 60) $score += 15;
        if (preg_match('/^[A-Z]/', $title)) $score += 10;
        if (strlen($content) > 2000) $score += 15;
        if (substr_count($content, '<h2>') > 2) $score += 10;
        
        return min($score, 100);
    }
    
    private function calculate_readability_score($content) {
        $words = str_word_count(strip_tags($content));
        $sentences = substr_count($content, '.') + substr_count($content, '!') + substr_count($content, '?');
        
        if ($sentences === 0) return 50;
        
        $avg_word_length = strlen(preg_replace('/\s/', '', $content)) / $words;
        $flesch_kincaid = 0.39 * ($words / $sentences) + 11.8 * ($avg_word_length) - 15.59;
        
        $score = 100 - ($flesch_kincaid * 2);
        return max(0, min(100, $score));
    }
    
    private function calculate_keyword_density($content) {
        $words = str_word_count(strtolower(strip_tags($content)), 1);
        if (empty($words)) return 0;
        
        $word_freq = array_count_values($words);
        arsort($word_freq);
        
        $top_word_count = reset($word_freq);
        return round(($top_word_count / count($words)) * 100, 2);
    }
    
    private function estimate_impressions($content_score, $seo_score) {
        $avg_score = ($content_score + $seo_score) / 2;
        return intval((1000 + ($avg_score * 50)));
    }
    
    private function estimate_revenue($impressions) {
        $cpm = 5; // $5 per 1000 impressions average
        return ($impressions / 1000) * $cpm;
    }
    
    private function generate_suggestions($content_score, $seo_score, $readability, $keyword_density) {
        $suggestions = array();
        
        if ($content_score < 70) {
            $suggestions[] = 'Add more internal links to increase content depth';
        }
        
        if ($seo_score < 70) {
            $suggestions[] = 'Optimize your title tag to include primary keywords';
            $suggestions[] = 'Add more header tags (H2, H3) for better structure';
        }
        
        if ($readability < 60) {
            $suggestions[] = 'Break paragraphs into shorter sentences for better readability';
        }
        
        if ($keyword_density > 3) {
            $suggestions[] = 'Reduce keyword density to avoid over-optimization penalties';
        }
        
        $suggestions[] = 'Add affiliate product recommendations to monetize this post';
        $suggestions[] = 'Consider converting this post into a video for multiple revenue streams';
        
        return $suggestions;
    }
    
    public function get_upgrade_modal() {
        check_ajax_referer('contentboost_nonce', 'nonce');
        echo '<div class="contentboost-modal"><h2>Upgrade to Pro</h2><p>This feature requires ContentBoost Pro plan.</p><button class="btn-primary">Upgrade Now</button></div>';
        wp_die();
    }
    
    public function render_score_badge($atts) {
        $post_id = get_the_ID();
        global $wpdb;
        
        $analysis = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}contentboost_analytics WHERE post_id = %d ORDER BY analyzed_date DESC LIMIT 1",
                $post_id
            )
        );
        
        if (!$analysis) {
            return '<div class="contentboost-badge">Not yet analyzed</div>';
        }
        
        return '<div class="contentboost-badge"><span class="score">' . $analysis->content_score . '%</span><span class="label">Content Score</span></div>';
    }
}

ContentBoostPro::get_instance();
?>