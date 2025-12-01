/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentMoat.php
*/
<?php
/**
 * Plugin Name: ContentMoat
 * Plugin URI: https://contentmoat.io
 * Description: Convert blog posts into multiple content formats and manage monetization across channels
 * Version: 1.0.0
 * Author: ContentMoat Team
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

define('CONTENTMOAT_VERSION', '1.0.0');
define('CONTENTMOAT_PATH', plugin_dir_path(__FILE__));
define('CONTENTMOAT_URL', plugin_dir_url(__FILE__));

class ContentMoat {
    private static $instance = null;
    private $db_version = '1.0';

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('init', [$this, 'registerPostType']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendScripts']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post', [$this, 'savePostMeta']);
        add_action('wp_ajax_cm_convert_content', [$this, 'ajaxConvertContent']);
        add_action('wp_ajax_cm_save_monetization', [$this, 'ajaxSaveMonetization']);
        add_filter('admin_footer_text', [$this, 'adminFooterText']);
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentmoat_conversions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            format VARCHAR(50) NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentmoat_monetization (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            channel VARCHAR(50) NOT NULL,
            affiliate_links TEXT,
            sponsored_brands TEXT,
            donation_enabled BOOLEAN DEFAULT 0,
            revenue_tracked DECIMAL(10,2) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY post_channel (post_id, channel)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        
        add_option('contentmoat_db_version', $this->db_version);
        add_option('contentmoat_conversions_used', 0);
    }

    public function deactivate() {
        wp_clear_scheduled_hook('contentmoat_daily_digest');
    }

    public function registerPostType() {
        register_post_type('cm_conversion', [
            'label' => 'ContentMoat Conversions',
            'public' => false,
            'show_ui' => false,
            'supports' => ['title', 'editor']
        ]);
    }

    public function addAdminMenu() {
        add_menu_page(
            'ContentMoat',
            'ContentMoat',
            'manage_options',
            'contentmoat',
            [$this, 'adminDashboard'],
            'dashicons-layout',
            25
        );
        
        add_submenu_page(
            'contentmoat',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentmoat',
            [$this, 'adminDashboard']
        );
        
        add_submenu_page(
            'contentmoat',
            'Settings',
            'Settings',
            'manage_options',
            'contentmoat-settings',
            [$this, 'adminSettings']
        );
    }

    public function adminDashboard() {
        global $wpdb;
        $conversions_used = get_option('contentmoat_conversions_used', 0);
        $plan = get_option('contentmoat_plan', 'free');
        $monthly_limit = $plan === 'free' ? 5 : ($plan === 'premium' ? 999 : 9999);
        
        $total_conversions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}contentmoat_conversions");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue_tracked) FROM {$wpdb->prefix}contentmoat_monetization");
        
        ?>
        <div class="wrap">
            <h1>ContentMoat Dashboard</h1>
            <div class="contentmoat-dashboard">
                <div class="stat-box">
                    <h3>Total Conversions</h3>
                    <p class="stat-number"><?php echo intval($total_conversions); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Monthly Limit</h3>
                    <p class="stat-number"><?php echo $conversions_used . '/' . $monthly_limit; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Revenue Tracked</h3>
                    <p class="stat-number">$<?php echo number_format(floatval($total_revenue), 2); ?></p>
                </div>
                <div class="stat-box">
                    <h3>Current Plan</h3>
                    <p class="stat-number"><?php echo ucfirst($plan); ?></p>
                </div>
            </div>
            <style>
                .contentmoat-dashboard { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
                .stat-box { background: #f5f5f5; padding: 20px; border-radius: 5px; text-align: center; }
                .stat-box h3 { margin: 0 0 10px 0; color: #333; font-size: 14px; }
                .stat-number { margin: 0; font-size: 28px; font-weight: bold; color: #0073aa; }
            </style>
        </div>
        <?php
    }

    public function adminSettings() {
        ?>
        <div class="wrap">
            <h1>ContentMoat Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('contentmoat_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="cm_api_key">API Key</label></th>
                        <td><input type="text" name="contentmoat_api_key" id="cm_api_key" value="<?php echo get_option('contentmoat_api_key'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cm_openai_key">OpenAI API Key</label></th>
                        <td><input type="password" name="contentmoat_openai_key" id="cm_openai_key" value="<?php echo get_option('contentmoat_openai_key'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cm_plan">Subscription Plan</label></th>
                        <td>
                            <select name="contentmoat_plan" id="cm_plan">
                                <option value="free" <?php selected(get_option('contentmoat_plan'), 'free'); ?>>Free</option>
                                <option value="premium" <?php selected(get_option('contentmoat_plan'), 'premium'); ?>>Premium</option>
                                <option value="agency" <?php selected(get_option('contentmoat_plan'), 'agency'); ?>>Agency</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function addMetaBoxes() {
        add_meta_box('contentmoat_converter', 'ContentMoat Converter', [$this, 'converterMetaBox'], 'post', 'normal', 'high');
        add_meta_box('contentmoat_monetization', 'Monetization Settings', [$this, 'monetizationMetaBox'], 'post', 'side', 'default');
    }

    public function converterMetaBox($post) {
        global $wpdb;
        $conversions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}contentmoat_conversions WHERE post_id = %d",
            $post->ID
        ));
        ?>
        <div class="contentmoat-meta-box">
            <p>Convert this post into multiple formats:</p>
            <div class="format-buttons">
                <button type="button" class="button cm-convert-btn" data-format="youtube">Convert to YouTube Script</button>
                <button type="button" class="button cm-convert-btn" data-format="twitter">Convert to Twitter Thread</button>
                <button type="button" class="button cm-convert-btn" data-format="newsletter">Convert to Newsletter</button>
                <button type="button" class="button cm-convert-btn" data-format="podcast">Convert to Podcast Script</button>
            </div>
            <div id="cm-conversions-list" style="margin-top: 15px;">
                <?php if ($conversions) : ?>
                    <h4>Previous Conversions:</h4>
                    <ul>
                        <?php foreach ($conversions as $conversion) : ?>
                            <li><strong><?php echo ucfirst($conversion->format); ?>:</strong> <?php echo date('M d, Y', strtotime($conversion->created_at)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <input type="hidden" id="cm_post_id" value="<?php echo $post->ID; ?>">
            <style>
                .format-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin: 10px 0; }
                .format-buttons button { white-space: nowrap; }
            </style>
        </div>
        <?php
    }

    public function monetizationMetaBox($post) {
        global $wpdb;
        $monetization = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}contentmoat_monetization WHERE post_id = %d LIMIT 1",
            $post->ID
        ));
        ?>
        <div class="contentmoat-monetization-box">
            <p>
                <label>
                    <input type="checkbox" name="cm_enable_donations" <?php checked($monetization->donation_enabled ?? 0); ?> />
                    Enable Donations
                </label>
            </p>
            <p>
                <label>Affiliate Links:</label><br />
                <textarea name="cm_affiliate_links" rows="3" style="width: 100%;"><?php echo esc_textarea($monetization->affiliate_links ?? ''); ?></textarea>
            </p>
            <p>
                <label>Sponsored Brands:</label><br />
                <textarea name="cm_sponsored_brands" rows="2" style="width: 100%;"><?php echo esc_textarea($monetization->sponsored_brands ?? ''); ?></textarea>
            </p>
            <button type="button" class="button button-primary cm-save-monetization" data-post-id="<?php echo $post->ID; ?>">Save Monetization</button>
        </div>
        <?php
    }

    public function savePostMeta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
    }

    public function ajaxConvertContent() {
        check_ajax_referer('contentmoat_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) wp_die('Unauthorized');
        
        $post_id = intval($_POST['post_id']);
        $format = sanitize_text_field($_POST['format']);
        $post = get_post($post_id);
        
        if (!$post) wp_die('Post not found');
        
        $content = $post->post_content;
        $converted = $this->convertContent($content, $format);
        
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'contentmoat_conversions', [
            'post_id' => $post_id,
            'format' => $format,
            'content' => $converted
        ]);
        
        wp_send_json_success([
            'message' => 'Content converted successfully',
            'content' => $converted,
            'format' => $format
        ]);
    }

    public function ajaxSaveMonetization() {
        check_ajax_referer('contentmoat_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) wp_die('Unauthorized');
        
        $post_id = intval($_POST['post_id']);
        $donation_enabled = isset($_POST['enable_donations']) ? 1 : 0;
        $affiliate_links = sanitize_textarea_field($_POST['affiliate_links'] ?? '');
        $sponsored_brands = sanitize_textarea_field($_POST['sponsored_brands'] ?? '');
        
        global $wpdb;
        $wpdb->replace($wpdb->prefix . 'contentmoat_monetization', [
            'post_id' => $post_id,
            'channel' => 'all',
            'affiliate_links' => $affiliate_links,
            'sponsored_brands' => $sponsored_brands,
            'donation_enabled' => $donation_enabled
        ]);
        
        wp_send_json_success(['message' => 'Monetization settings saved']);
    }

    private function convertContent($content, $format) {
        $content = wp_strip_all_tags($content);
        $content = substr($content, 0, 500);
        
        $templates = [
            'youtube' => "🎬 YOUTUBE SCRIPT\n\nTitle: [Create an engaging title based on this content]\n\n" . $content . "\n\nCTA: Subscribe for more content!",
            'twitter' => "🐦 TWITTER THREAD\n\n1/ " . substr($content, 0, 100) . "...\n\n2/ [Continue with more insights]\n\n3/ [Add key takeaway]",
            'newsletter' => "📧 NEWSLETTER\n\nSubject: [Catchy subject line]\n\nDear Subscribers,\n\n" . $content,
            'podcast' => "🎙️ PODCAST SCRIPT\n\n[INTRO]\nHey listeners! Today we're discussing...\n\n[MAIN CONTENT]\n" . $content . "\n\n[OUTRO]\nThanks for listening!"
        ];
        
        return $templates[$format] ?? $content;
    }

    public function enqueueAdminScripts($hook) {
        if (get_post_type() === 'post') {
            wp_enqueue_script('contentmoat-admin', CONTENTMOAT_URL . 'js/admin.js', ['jquery'], CONTENTMOAT_VERSION);
            wp_localize_script('contentmoat-admin', 'contentmoatData', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentmoat_nonce')
            ]);
        }
    }

    public function enqueueFrontendScripts() {
        wp_enqueue_style('contentmoat-frontend', CONTENTMOAT_URL . 'css/frontend.css', [], CONTENTMOAT_VERSION);
    }

    public function adminFooterText($text) {
        return str_replace('WordPress', 'ContentMoat & WordPress', $text);
    }
}

ContentMoat::getInstance();
?>
