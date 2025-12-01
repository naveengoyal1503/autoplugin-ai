<?php
/*
Plugin Name: ContentBoost AI Writer
Plugin URI: https://contentboost.local
Description: AI-powered content generation and SEO optimization for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_AI_Writer.php
License: GPL v2 or later
Text Domain: contentboost-ai
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOOST_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentBoostAIWriter {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_contentboost_generate_content', array($this, 'handle_generate_content'));
        add_action('wp_ajax_contentboost_check_usage', array($this, 'handle_check_usage'));
        add_action('plugins_loaded', array($this, 'init_plugin'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
    }
    
    public function init_plugin() {
        $this->create_tables();
    }
    
    public function activate_plugin() {
        $this->create_tables();
        update_option('contentboost_activation_date', current_time('mysql'));
    }
    
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentboost_usage';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            generations_used int(11) DEFAULT 0,
            generation_date DATE NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_date (user_id, generation_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost AI Writer',
            'ContentBoost AI',
            'manage_options',
            'contentboost-ai',
            array($this, 'render_dashboard'),
            'dashicons-edit',
            80
        );
        
        add_submenu_page(
            'contentboost-ai',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-settings',
            array($this, 'render_settings')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentboost') === false) {
            return;
        }
        
        wp_enqueue_script('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'assets/admin.js', array('jquery'), CONTENTBOOST_VERSION, true);
        wp_enqueue_style('contentboost-admin', CONTENTBOOST_PLUGIN_URL . 'assets/admin.css', array(), CONTENTBOOST_VERSION);
        
        wp_localize_script('contentboost-admin', 'ContentBoostAI', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentboost_nonce')
        ));
    }
    
    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $usage = $this->get_daily_usage(get_current_user_id());
        $plan = get_option('contentboost_user_plan_' . get_current_user_id(), 'free');
        $limit = $this->get_generation_limit($plan);
        ?>
        <div class="wrap contentboost-wrap">
            <h1>ContentBoost AI Writer</h1>
            <div class="contentboost-dashboard">
                <div class="contentboost-card">
                    <h2>Generate Content</h2>
                    <div class="usage-meter">
                        <div class="usage-bar" style="width: <?php echo ($usage / $limit) * 100; ?>%"></div>
                    </div>
                    <p><?php echo esc_html($usage . ' / ' . $limit . ' generations used today'); ?></p>
                </div>
                
                <div class="contentboost-card">
                    <h2>Current Plan</h2>
                    <p class="plan-badge"><?php echo esc_html(ucfirst($plan)); ?></p>
                    <?php if ($plan === 'free') { ?>
                        <a href="#" class="button button-primary">Upgrade to Premium</a>
                    <?php } ?>
                </div>
            </div>
            
            <div class="contentboost-editor">
                <h2>Content Generator</h2>
                <form id="contentboost-form">
                    <label for="topic">Topic or Keyword:</label>
                    <input type="text" id="topic" name="topic" placeholder="Enter your topic..." required>
                    
                    <label for="content_type">Content Type:</label>
                    <select id="content_type" name="content_type">
                        <option value="blog_post">Blog Post</option>
                        <option value="product_description">Product Description</option>
                        <option value="email_copy">Email Copy</option>
                        <option value="social_media">Social Media Post</option>
                    </select>
                    
                    <label for="tone">Tone:</label>
                    <select id="tone" name="tone">
                        <option value="professional">Professional</option>
                        <option value="casual">Casual</option>
                        <option value="friendly">Friendly</option>
                        <option value="persuasive">Persuasive</option>
                    </select>
                    
                    <button type="submit" class="button button-primary">Generate Content</button>
                </form>
                
                <div id="contentboost-result" style="display:none;">
                    <h3>Generated Content:</h3>
                    <textarea id="generated-text" readonly rows="10"></textarea>
                    <button id="copy-btn" class="button">Copy to Clipboard</button>
                    <button id="insert-btn" class="button button-primary">Insert into Post</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <h1>ContentBoost Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentboost_settings'); ?>
                <?php do_settings_sections('contentboost_settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function handle_generate_content() {
        check_ajax_referer('contentboost_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $topic = sanitize_text_field($_POST['topic']);
        $content_type = sanitize_text_field($_POST['content_type']);
        $tone = sanitize_text_field($_POST['tone']);
        
        $user_id = get_current_user_id();
        $plan = get_option('contentboost_user_plan_' . $user_id, 'free');
        $usage = $this->get_daily_usage($user_id);
        $limit = $this->get_generation_limit($plan);
        
        if ($usage >= $limit) {
            wp_send_json_error('Daily limit reached. Upgrade to continue.');
        }
        
        $generated_content = $this->generate_content($topic, $content_type, $tone);
        $this->increment_usage($user_id);
        
        wp_send_json_success(array(
            'content' => $generated_content,
            'usage' => $this->get_daily_usage($user_id),
            'limit' => $limit
        ));
    }
    
    public function handle_check_usage() {
        check_ajax_referer('contentboost_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $plan = get_option('contentboost_user_plan_' . $user_id, 'free');
        $usage = $this->get_daily_usage($user_id);
        $limit = $this->get_generation_limit($plan);
        
        wp_send_json_success(array(
            'usage' => $usage,
            'limit' => $limit,
            'plan' => $plan
        ));
    }
    
    private function generate_content($topic, $type, $tone) {
        $prompts = array(
            'blog_post' => "Write a detailed, SEO-optimized blog post about '$topic' with a $tone tone. Include an introduction, 3-4 main sections, and a conclusion.",
            'product_description' => "Write a compelling $tone product description for '$topic' that highlights benefits and encourages purchase.",
            'email_copy' => "Write a $tone email copy about '$topic' designed to engage and convert readers.",
            'social_media' => "Write an engaging $tone social media post about '$topic' with relevant hashtags."
        );
        
        $prompt = $prompts[$type] ?? $prompts['blog_post'];
        
        // Simulated content generation - in production, integrate with OpenAI/Claude API
        $sample_content = array(
            'blog_post' => "<h2>" . ucfirst($topic) . "</h2><p>" . $topic . " is an important topic that deserves careful consideration. In this comprehensive guide, we'll explore the key aspects and benefits.</p><h3>Getting Started</h3><p>First, understand the fundamentals of " . $topic . ". This will provide a solid foundation for your journey.</p><h3>Best Practices</h3><p>Follow these proven strategies to maximize results with " . $topic . ". Implementation is straightforward when you follow these guidelines.</p><h3>Common Mistakes to Avoid</h3><p>Many people make preventable errors. Learn from these common pitfalls to accelerate your success.</p><h3>Conclusion</h3><p>" . $topic . " can transform your approach. Start implementing these strategies today.</p>",
            'product_description' => "Discover the power of " . $topic . ". This premium product is designed for discerning customers who demand quality and performance. Experience the difference today.",
            'email_copy' => "Hi there! We're excited to share something special about " . $topic . ". This exclusive opportunity is perfect for you. Learn more inside!",
            'social_media' => "🚀 Just discovered something amazing about " . $topic . "! Who else is interested? #" . str_replace(' ', '', $topic) . " #innovation"
        );
        
        return $sample_content[$type] ?? $sample_content['blog_post'];
    }
    
    private function get_daily_usage($user_id) {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT generations_used FROM {$wpdb->prefix}contentboost_usage WHERE user_id = %d AND generation_date = %s",
            $user_id,
            current_time('Y-m-d')
        ));
        return $result ?: 0;
    }
    
    private function increment_usage($user_id) {
        global $wpdb;
        $current_usage = $this->get_daily_usage($user_id);
        
        if ($current_usage === 0) {
            $wpdb->insert(
                $wpdb->prefix . 'contentboost_usage',
                array(
                    'user_id' => $user_id,
                    'generations_used' => 1,
                    'generation_date' => current_time('Y-m-d')
                )
            );
        } else {
            $wpdb->update(
                $wpdb->prefix . 'contentboost_usage',
                array('generations_used' => $current_usage + 1),
                array('user_id' => $user_id, 'generation_date' => current_time('Y-m-d'))
            );
        }
    }
    
    private function get_generation_limit($plan) {
        $limits = array(
            'free' => 5,
            'premium' => 50,
            'enterprise' => 500
        );
        return $limits[$plan] ?? $limits['free'];
    }
}

ContentBoostAIWriter::get_instance();
?>