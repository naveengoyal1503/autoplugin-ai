/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your content using keyword matching and basic AI-like context analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_links;
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'api_key' => '',
            'enabled' => true,
            'max_links' => 3,
            'networks' => array('amazon', 'clickbank')
        ));
        $this->load_affiliate_links();
    }

    private function load_affiliate_links() {
        $this->affiliate_links = array(
            'hosting' => array(
                'keyword' => 'hosting|wordpress hosting|web hosting',
                'url' => 'https://youraffiliate.link/bluehost',
                'text' => 'Best WordPress Hosting',
                'network' => 'affiliate'
            ),
            'seo' => array(
                'keyword' => 'seo|search engine|ranking',
                'url' => 'https://youraffiliate.link/seotool',
                'text' => 'Top SEO Tool',
                'network' => 'affiliate'
            ),
            'plugin' => array(
                'keyword' => 'wordpress plugin|wp plugin',
                'url' => 'https://youraffiliate.link/plugin',
                'text' => 'Premium WP Plugin',
                'network' => 'affiliate'
            ),
            'course' => array(
                'keyword' => 'online course|learn|tutorial',
                'url' => 'https://youraffiliate.link/course',
                'text' => 'Online Course',
                'network' => 'affiliate'
            )
        );
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (!$this->options['enabled'] || is_admin() || !is_single()) {
            return $content;
        }

        $inserted = 0;
        $words = explode(' ', $content);
        $new_content = '';
        $paragraphs = explode('\n\n', $content);

        foreach ($paragraphs as $para) {
            if ($inserted >= $this->options['max_links']) {
                $new_content .= $para;
            } else {
                foreach ($this->affiliate_links as $link) {
                    if (preg_match('/\b(' . $link['keyword'] . ')\b/i', $para) && $inserted < $this->options['max_links']) {
                        $link_html = '<a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow sponsored">' . esc_html($link['text']) . '</a>';
                        $para = preg_replace('/(' . $link['keyword'] . ')/i', '$1 ' . $link_html, $para, 1);
                        $inserted++;
                    }
                }
                $new_content .= $para;
            }
            $new_content .= '\n\n';
        }

        return trim($new_content);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_options');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_group'); ?>
                <?php do_settings_sections('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_options[enabled]" value="1" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_options[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Networks</th>
                        <td>
                            <label><input type="checkbox" name="smart_affiliate_options[networks][]" value="amazon" <?php echo in_array('amazon', (array)$this->options['networks']) ? 'checked' : ''; ?>> Amazon</label><br>
                            <label><input type="checkbox" name="smart_affiliate_options[networks][]" value="clickbank" <?php echo in_array('clickbank', (array)$this->options['networks']) ? 'checked' : ''; ?>> ClickBank</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited links, Amazon API integration, click analytics, and more for $49/year.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_options', array('enabled' => true, 'max_links' => 3));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new SmartAffiliateAutoInserter();

// Freemium upsell notice
function smart_affiliate_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id == 'settings_page_smart-affiliate') {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Pro</strong> for advanced features like real-time analytics and more networks! <a href="https://example.com/pro" target="_blank">Get Pro Now</a></p></div>';
    }
}
add_action('admin_notices', 'smart_affiliate_admin_notice');

// Create assets dir and dummy JS
register_activation_hook(__FILE__, 'smart_affiliate_create_assets');
function smart_affiliate_create_assets() {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
    $js_content = "jQuery(document).ready(function($) {
        console.log('Smart Affiliate loaded');
    });
    ";
    file_put_contents($upload_dir . '/script.js', $js_content);
}
