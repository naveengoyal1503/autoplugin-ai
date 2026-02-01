/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('saai_options', array(
            'amazon_tag' => '',
            'keywords' => array(),
            'enabled' => true
        ));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('saai-script', plugin_dir_url(__FILE__) . 'saai.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (!$this->options['enabled'] || is_admin() || empty($this->options['amazon_tag'])) {
            return $content;
        }

        $keywords = $this->options['keywords'];
        foreach ($keywords as $keyword => $asin) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($pattern, $content)) {
                $link = $this->get_amazon_link($asin, $keyword);
                $content = preg_replace($pattern, $link, $content, 1);
            }
        }
        return $content;
    }

    private function get_amazon_link($asin, $keyword) {
        $tag = $this->options['amazon_tag'];
        return '<a href="https://www.amazon.com/dp/' . esc_attr($asin) . '?tag=' . esc_attr($tag) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . '</a>';
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'Affiliate Inserter', 'manage_options', 'saai-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saai_options_group', 'saai_options');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saai_options_group'); ?>
                <?php do_settings_sections('saai_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="saai_options[enabled]" value="1" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Amazon Associate Tag</th>
                        <td><input type="text" name="saai_options[amazon_tag]" value="<?php echo esc_attr($this->options['amazon_tag']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords & ASINs</th>
                        <td>
                            <textarea name="saai_options[keywords]" rows="10" cols="50" placeholder="keyword1: B08N5WRWNW&#10;keyword2: B07H8Q5V9G"><?php echo esc_textarea($this->options['keywords'] ? implode("\n", $this->options['keywords']) : ''); ?></textarea>
                            <p class="description">One per line: keyword:ASIN</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Pro Features (Upgrade for $49/year)</h2>
            <ul>
                <li>AI-powered keyword suggestions</li>
                <li>Performance analytics</li>
                <li>A/B testing</li>
            </ul>
        </div>
        <?php
    }

    public function activate() {
        add_option('saai_options', array('enabled' => true));
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

// Pro upgrade notice
function saai_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoInserter Pro</strong> for AI features and analytics! <a href="https://example.com/pro" target="_blank">Learn more</a></p></div>';
}
add_action('admin_notices', 'saai_pro_notice');
?>