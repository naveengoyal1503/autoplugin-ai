/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using keyword matching and AI-like rules.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function auto_insert_affiliate_links($content) {
        if (is_admin() || !is_single()) return $content;

        $settings = get_option('smart_affiliate_settings', array('enabled' => false));
        if (!$settings['enabled']) return $content;

        $affiliates = $settings['affiliates'] ?? array();
        if (empty($affiliates)) return $content;

        $paragraphs = explode('</p>', $content);
        $inserted = 0;
        $max_inserts = 3;

        foreach ($paragraphs as $index => &$paragraph) {
            if ($inserted >= $max_inserts) break;

            foreach ($affiliates as $aff) {
                $keyword = $aff['keyword'];
                $link = $aff['link'];
                $text = $aff['text'];

                if (stripos($paragraph, $keyword) !== false && stripos($paragraph, 'href') === false) {
                    $replace = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener">' . esc_html($text) . '</a> ';
                    $paragraph = str_ireplace($keyword, $replace, $paragraph, $count);
                    if ($count > 0) {
                        $inserted++;
                        break;
                    }
                }
            }
        }

        return implode('</p>', $paragraphs);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_group'); ?>
                <?php do_settings_sections('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_settings[enabled]" value="1" <?php checked((bool)get_option('smart_affiliate_settings')['enabled'] ?? false); ?> /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td>
                            <div id="affiliate-list">
                                <?php
                                $settings = get_option('smart_affiliate_settings', array());
                                $affiliates = $settings['affiliates'] ?? array();
                                foreach ($affiliates as $i => $aff) {
                                    echo '<div class="affiliate-item">';
                                    echo '<input type="text" name="smart_affiliate_settings[affiliates][' . $i . '][keyword]" placeholder="Keyword" value="' . esc_attr($aff['keyword'] ?? '') . '" />';
                                    echo '<input type="text" name="smart_affiliate_settings[affiliates][' . $i . '][text]" placeholder="Link Text" value="' . esc_attr($aff['text'] ?? '') . '" />';
                                    echo '<input type="url" name="smart_affiliate_settings[affiliates][' . $i . '][link]" placeholder="Affiliate URL" value="' . esc_url($aff['link'] ?? '') . '" />';
                                    echo '<button type="button" class="button remove-aff">Remove</button>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <button type="button" id="add-affiliate" class="button">Add Affiliate</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let index = <?php echo count($affiliates); ?>;
            $('#add-affiliate').click(function() {
                $('#affiliate-list').append(
                    '<div class="affiliate-item">' +
                    '<input type="text" name="smart_affiliate_settings[affiliates][' + index + '][keyword]" placeholder="Keyword" />' +
                    '<input type="text" name="smart_affiliate_settings[affiliates][' + index + '][text]" placeholder="Link Text" />' +
                    '<input type="url" name="smart_affiliate_settings[affiliates][' + index + '][link]" placeholder="Affiliate URL" />' +
                    '<button type="button" class="button remove-aff">Remove</button>' +
                    '</div>'
                );
                index++;
            });
            $(document).on('click', '.remove-aff', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_settings', array('enabled' => true));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

SmartAffiliateAutoInserter::get_instance();

// Create assets directories if needed
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets/';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Minimal CSS
file_put_contents($assets_dir . 'style.css', '.affiliate-item { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }');

// Minimal JS
file_put_contents($assets_dir . 'script.js', '// Frontend JS if needed\njQuery(document).ready(function(){});');