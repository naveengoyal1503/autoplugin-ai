/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your content and converts them into trackable affiliate links with A/B testing for maximum conversions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoLinker {
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
        add_filter('the_content', array($this, 'auto_link_content'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolinker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_link_content($content) {
        if (is_feed() || is_preview() || is_admin()) {
            return $content;
        }

        $keywords = get_option('saal_keywords', array());
        $settings = get_option('saal_settings', array('nofollow' => 1, 'newtab' => 1));

        foreach ($keywords as $keyword => $link) {
            if (empty($link['url']) || empty($keyword)) continue;

            $limit = isset($link['limit']) ? (int)$link['limit'] : 1;
            $count = 0;
            $regex = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $content = preg_replace_callback($regex, function($matches) use ($link, $settings, &$count, $limit) {
                if ($count >= $limit) return $matches;
                $count++;
                $attrs = '';
                if (!empty($settings['nofollow'])) $attrs .= ' rel="nofollow"';
                if (!empty($settings['newtab'])) $attrs .= ' target="_blank"';
                return '<a href="' . esc_url($link['url']) . '"' . $attrs . '>' . $matches . '</a>';
            }, $content);
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('saal_settings_group', 'saal_keywords');
        register_setting('saal_settings_group', 'saal_settings');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('saal_keywords', sanitize_text_field_deep($_POST['saal_keywords']));
            update_option('saal_settings', wp_unslash($_POST['saal_settings']));
        }
        $keywords = get_option('saal_keywords', array());
        $settings = get_option('saal_settings', array());
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoLinker Settings', 'smart-affiliate-autolinker'); ?></h1>
            <form method="post" action="">
                <?php settings_fields('saal_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Keywords & Links</th>
                        <td>
                            <div id="keyword-list">
                                <?php foreach ($keywords as $k => $kw): ?>
                                    <div class="keyword-row">
                                        <input type="text" name="saal_keywords[<?php echo $k; ?>][keyword]" placeholder="Keyword" value="<?php echo esc_attr($kw['keyword']); ?>" />
                                        <input type="url" name="saal_keywords[<?php echo $k; ?>][url]" placeholder="Affiliate URL" value="<?php echo esc_url($kw['url']); ?>" />
                                        <input type="number" name="saal_keywords[<?php echo $k; ?>][limit]" placeholder="Link Limit" value="<?php echo esc_attr($kw['limit']); ?>" min="1" />
                                        <button type="button" class="button remove-keyword">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-keyword" class="button">Add Keyword</button>
                        </td>
                    </tr>
                    <tr>
                        <th>Global Settings</th>
                        <td>
                            <label><input type="checkbox" name="saal_settings[nofollow]" <?php checked($settings['nofollow']); ?> /> Add nofollow</label><br>
                            <label><input type="checkbox" name="saal_settings[newtab]" <?php checked($settings['newtab']); ?> /> Open in new tab</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> A/B testing, click analytics, premium affiliate network integrations. <a href="https://example.com/pro">Upgrade to Pro</a></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let row = 0;
            $('#add-keyword').click(function() {
                $('#keyword-list').append(
                    '<div class="keyword-row">' +
                    '<input type="text" name="saal_keywords[' + row + '][keyword]" placeholder="Keyword" />' +
                    '<input type="url" name="saal_keywords[' + row + '][url]" placeholder="Affiliate URL" />' +
                    '<input type="number" name="saal_keywords[' + row + '][limit]" placeholder="Link Limit" min="1" />' +
                    '<button type="button" class="button remove-keyword">Remove</button>' +
                    '</div>'
                );
                row++;
            });
            $(document).on('click', '.remove-keyword', function() {
                $(this).closest('.keyword-row').remove();
            });
        });
        </script>
        <style>
        .keyword-row { margin-bottom: 10px; }
        .keyword-row input { margin-right: 10px; }
        </style>
        <?php
    }

    public function activate() {
        add_option('saal_keywords', array());
        add_option('saal_settings', array('nofollow' => 1, 'newtab' => 1));
    }
}

SmartAffiliateAutoLinker::get_instance();