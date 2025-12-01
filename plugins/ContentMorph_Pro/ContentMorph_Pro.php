<?php
/*
Plugin Name: ContentMorph Pro
Plugin URI: https://contentmorph.pro
Description: Convert blog posts into multiple content formats automatically
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentMorph_Pro.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

define('CONTENTMORPH_VERSION', '1.0.0');
define('CONTENTMORPH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTMORPH_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentMorphPro {
    private $db_version = '1.0';
    private $option_prefix = 'contentmorph_';
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post_meta'));
        add_action('wp_ajax_contentmorph_convert', array($this, 'ajax_convert_content'));
        add_action('wp_ajax_contentmorph_get_conversions', array($this, 'ajax_get_conversions'));
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentmorph_conversions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            format VARCHAR(50) NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option($this->option_prefix . 'db_version', $this->db_version);
        add_option($this->option_prefix . 'conversions_this_month', 0);
        add_option($this->option_prefix . 'subscription_tier', 'free');
    }
    
    public function deactivate() {}
    
    public function add_admin_menu() {
        add_menu_page(
            'ContentMorph Pro',
            'ContentMorph Pro',
            'manage_options',
            'contentmorph',
            array($this, 'dashboard_page'),
            'dashicons-images-alt2',
            80
        );
        
        add_submenu_page(
            'contentmorph',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentmorph',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'contentmorph',
            'Settings',
            'Settings',
            'manage_options',
            'contentmorph-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'contentmorph') === false) return;
        
        wp_enqueue_style(
            'contentmorph-admin',
            CONTENTMORPH_PLUGIN_URL . 'assets/admin.css',
            array(),
            CONTENTMORPH_VERSION
        );
        
        wp_enqueue_script(
            'contentmorph-admin',
            CONTENTMORPH_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            CONTENTMORPH_VERSION,
            true
        );
        
        wp_localize_script('contentmorph-admin', 'contentMorph', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentmorph_nonce')
        ));
    }
    
    public function add_meta_box() {
        add_meta_box(
            'contentmorph_meta',
            'ContentMorph Pro - Convert Content',
            array($this, 'render_meta_box'),
            'post',
            'normal',
            'high'
        );
    }
    
    public function render_meta_box($post) {
        wp_nonce_field('contentmorph_nonce', 'contentmorph_nonce');
        ?>
        <div id="contentmorph-meta-box">
            <p>Convert this post into multiple formats:</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                <button class="button button-primary" data-format="video-script">📹 Video Script</button>
                <button class="button button-primary" data-format="social-carousel">📱 Social Carousel</button>
                <button class="button button-primary" data-format="email-sequence">📧 Email Sequence</button>
                <button class="button button-primary" data-format="infographic">🎨 Infographic Brief</button>
            </div>
            <div id="contentmorph-result" style="margin-top: 20px; display: none;">
                <h4>Conversion Result:</h4>
                <textarea id="contentmorph-output" style="width: 100%; height: 300px;"></textarea>
                <button class="button button-secondary" id="contentmorph-copy">Copy to Clipboard</button>
            </div>
        </div>
        <?php
    }
    
    public function save_post_meta($post_id) {
        if (!isset($_POST['contentmorph_nonce']) || !wp_verify_nonce($_POST['contentmorph_nonce'], 'contentmorph_nonce')) return;
    }
    
    public function ajax_convert_content() {
        check_ajax_referer('contentmorph_nonce');
        
        if (!current_user_can('edit_posts')) wp_die('Unauthorized');
        
        $post_id = intval($_POST['post_id']);
        $format = sanitize_text_field($_POST['format']);
        
        $post = get_post($post_id);
        if (!$post) wp_send_json_error('Post not found');
        
        $tier = get_option($this->option_prefix . 'subscription_tier', 'free');
        $conversions = intval(get_option($this->option_prefix . 'conversions_this_month', 0));
        
        if ($tier === 'free' && $conversions >= 5) {
            wp_send_json_error('Free tier limit reached. Upgrade to premium.');
        }
        
        $content = $post->post_content;
        $title = $post->post_title;
        
        switch ($format) {
            case 'video-script':
                $result = $this->generate_video_script($title, $content);
                break;
            case 'social-carousel':
                $result = $this->generate_social_carousel($title, $content);
                break;
            case 'email-sequence':
                $result = $this->generate_email_sequence($title, $content);
                break;
            case 'infographic':
                $result = $this->generate_infographic_brief($title, $content);
                break;
            default:
                wp_send_json_error('Invalid format');
        }
        
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'contentmorph_conversions',
            array(
                'post_id' => $post_id,
                'format' => $format,
                'content' => $result
            )
        );
        
        update_option($this->option_prefix . 'conversions_this_month', $conversions + 1);
        
        wp_send_json_success(array('result' => $result));
    }
    
    private function generate_video_script($title, $content) {
        $excerpt = wp_trim_words($content, 50);
        return "[INTRO]\nHey everyone! Today we're diving into: $title\n\n[HOOK]\nHere's why this matters...\n\n[BODY]\n$excerpt\n\n[CTA]\nDon't forget to like, subscribe, and comment below!\n\n[OUTRO]\nThanks for watching!";
    }
    
    private function generate_social_carousel($title, $content) {
        $points = array_slice(array_filter(array_map('trim', explode('.', wp_strip_all_tags($content)))), 0, 5);
        $carousel = "Slide 1: $title\n\n";
        foreach ($points as $i => $point) {
            $carousel .= "Slide " . ($i + 2) . ": " . substr($point, 0, 100) . "...\n\n";
        }
        return $carousel . "Slide 7: Learn more on our blog!";
    }
    
    private function generate_email_sequence($title, $content) {
        return "Subject: Discover: $title\n\nHi [First Name],\n\nWe found something you might like: $title\n\n" . wp_trim_words($content, 75) . "\n\nRead the full article →\n\nBest regards,\n[Your Name]";
    }
    
    private function generate_infographic_brief($title, $content) {
        return "Infographic Title: $title\n\nKey Statistics:\n- Stat 1\n- Stat 2\n- Stat 3\n\nMain Points:\n" . wp_trim_words($content, 50) . "\n\nDesign Style: Modern, clean, professional";
    }
    
    public function ajax_get_conversions() {
        check_ajax_referer('contentmorph_nonce');
        
        if (!current_user_can('edit_posts')) wp_die('Unauthorized');
        
        global $wpdb;
        $conversions = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}contentmorph_conversions ORDER BY created_at DESC LIMIT 20"
        );
        
        wp_send_json_success(array('conversions' => $conversions));
    }
    
    public function dashboard_page() {
        $tier = get_option($this->option_prefix . 'subscription_tier', 'free');
        $conversions = intval(get_option($this->option_prefix . 'conversions_this_month', 0));
        ?>
        <div class="wrap">
            <h1>ContentMorph Pro Dashboard</h1>
            <div class="contentmorph-dashboard">
                <div class="contentmorph-card">
                    <h3>Current Plan</h3>
                    <p><strong><?php echo ucfirst($tier); ?></strong></p>
                </div>
                <div class="contentmorph-card">
                    <h3>Conversions This Month</h3>
                    <p><strong><?php echo $conversions; ?></strong> <?php echo ($tier === 'free') ? '/ 5' : '/ Unlimited'; ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>ContentMorph Pro Settings</h1>
            <h2>Upgrade Your Plan</h2>
            <p>Start monetizing your content repurposing efforts today!</p>
            <table class="widefat">
                <tr>
                    <th>Plan</th>
                    <th>Price</th>
                    <th>Features</th>
                    <th>Action</th>
                </tr>
                <tr>
                    <td><strong>Free</strong></td>
                    <td>$0</td>
                    <td>5 conversions/month, Basic formats</td>
                    <td><button class="button" disabled>Current Plan</button></td>
                </tr>
                <tr>
                    <td><strong>Premium</strong></td>
                    <td>$19/month</td>
                    <td>Unlimited conversions, All formats, Priority support</td>
                    <td><button class="button button-primary">Upgrade Now</button></td>
                </tr>
                <tr>
                    <td><strong>Enterprise</strong></td>
                    <td>$99/month</td>
                    <td>Unlimited for 10 sites, Advanced analytics, API access</td>
                    <td><button class="button button-primary">Contact Sales</button></td>
                </tr>
            </table>
        </div>
        <?php
    }
}

new ContentMorphPro();
?>