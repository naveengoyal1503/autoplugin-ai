/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your content to boost revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'pro_nag'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        } else {
            add_filter('the_content', array($this, 'auto_insert_links'), 99);
            add_filter('widget_text', array($this, 'auto_insert_links'), 99);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (is_feed() || is_admin() || !is_single()) return $content;

        $settings = get_option('smart_affiliate_settings', array('enabled' => true, 'keywords' => "buy|purchase|best|review|shop", 'link' => 'https://your-affiliate-link.com/?ref=wp', 'max_links' => 3));
        if (!$settings['enabled']) return $content;

        $keywords = explode('|', $settings['keywords']);
        $max_links = min((int)$settings['max_links'], 5); // Free limit
        $inserted = 0;

        foreach ($keywords as $keyword) {
            if ($inserted >= $max_links) break;
            $pattern = '/\b' . preg_quote(trim($keyword), '/') . '\b/i';
            if (preg_match($pattern, $content)) {
                $link = '<a href="' . esc_url($settings['link']) . '" target="_blank" rel="nofollow noopener" class="smart-affiliate-link">' . strtoupper(trim($keyword)) . '</a>';
                $content = preg_replace($pattern, $link, $content, 1);
                $inserted++;
            }
        }

        return $content;
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate Inserter', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_group'); ?>
                <?php do_settings_sections('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insert</th>
                        <td><input type="checkbox" name="smart_affiliate_settings[enabled]" value="1" <?php checked(get_option('smart_affiliate_settings')['enabled'] ?? true); ?> /></td>
                    </tr>
                    <tr>
                        <th>Keywords (pipe-separated)</th>
                        <td><input type="text" name="smart_affiliate_settings[keywords]" value="<?php echo esc_attr((get_option('smart_affiliate_settings')['keywords'] ?? 'buy|purchase|best|review|shop')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="smart_affiliate_settings[link]" value="<?php echo esc_attr((get_option('smart_affiliate_settings')['link'] ?? 'https://your-affiliate-link.com/?ref=wp')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post (Free: max 3)</th>
                        <td><input type="number" name="smart_affiliate_settings[max_links]" value="<?php echo esc_attr((get_option('smart_affiliate_settings')['max_links'] ?? 3)); ?>" min="1" max="5" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Go Pro for:</strong> Unlimited links, AI keyword detection, analytics, Amazon/ClickBank integrations & more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function pro_nag() {
        if (!current_user_can('manage_options')) return;
        echo '<div style="position:fixed;bottom:20px;right:20px;background:#0073aa;color:white;padding:10px;border-radius:5px;z-index:9999;font-size:12px;">Smart Affiliate: <a href="' . admin_url('options-general.php?page=smart-affiliate') . '" style="color:#fff;">Settings</a> | <a href="https://example.com/pro" target="_blank" style="color:#ffd700;">Go Pro!</a></div>';
    }

    public function activate() {
        add_option('smart_affiliate_settings', array('enabled' => true, 'keywords' => 'buy|purchase|best|review|shop', 'link' => 'https://your-affiliate-link.com/?ref=wp', 'max_links' => 3));
    }
}

SmartAffiliateAutoInserter::get_instance();

// Pro teaser - in real pro version, this would check license
if (!function_exists('is_pro_version')) {
    function is_pro_version() { return false; }
}

// Assets folder note: Create /assets/script.js with: jQuery(document).ready(function($){ $('.smart-affiliate-link').hover(function(){ $(this).css('color','#ff6600'); }); });
?>