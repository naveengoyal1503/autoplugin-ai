/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into posts and pages using keyword matching to maximize commissions.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (is_admin() || is_feed() || !is_single()) {
            return $content;
        }

        $settings = get_option('smart_affiliate_settings', array());
        if (empty($settings['enabled']) || empty($settings['links'])) {
            return $content;
        }

        $paragraphs = explode('</p>', $content);
        $inserted = 0;
        $max_inserts = isset($settings['max_inserts']) ? (int)$settings['max_inserts'] : 2;

        foreach ($paragraphs as $index => &$paragraph) {
            if ($inserted >= $max_inserts) {
                break;
            }

            foreach ($settings['links'] as $link) {
                $keyword = $link['keyword'];
                $aff_link = $link['affiliate_link'];
                $text = $link['link_text'];

                if (stripos($paragraph, $keyword) !== false && stripos($paragraph, 'rel="nofollow"') === false) {
                    $replace = '<a href="' . esc_url($aff_link) . '" target="_blank" rel="nofollow noopener">' . esc_html($text) . '</a> ';
                    $paragraph = str_ireplace($keyword, $replace, $paragraph);
                    $inserted++;
                    break;
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
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_settings_group', 'smart_affiliate_settings');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_settings_group'); ?>
                <?php do_settings_sections('smart_affiliate_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_settings[enabled]" value="1" <?php checked(get_option('smart_affiliate_settings')['enabled'] ?? 0); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_settings[max_inserts]" value="<?php echo esc_attr(get_option('smart_affiliate_settings')['max_inserts'] ?? 2); ?>" min="1" max="5" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td>
                            <div id="links-container">
                                <?php
                                $settings = get_option('smart_affiliate_settings', array());
                                $links = $settings['links'] ?? array();
                                foreach ($links as $index => $link) {
                                    echo '<div class="link-row">';
                                    echo '<input type="text" name="smart_affiliate_settings[links][' . $index . '][keyword]" placeholder="Keyword" value="' . esc_attr($link['keyword'] ?? '') . '" />';
                                    echo '<input type="text" name="smart_affiliate_settings[links][' . $index . '][link_text]" placeholder="Link Text" value="' . esc_attr($link['link_text'] ?? '') . '" />';
                                    echo '<input type="url" name="smart_affiliate_settings[links][' . $index . '][affiliate_link]" placeholder="Affiliate URL" value="' . esc_url($link['affiliate_link'] ?? '') . '" />';
                                    echo '<button type="button" class="button remove-link">Remove</button>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <button type="button" id="add-link" class="button">Add Link</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let linkIndex = <?php echo count($links); ?>;
            $('#add-link').click(function() {
                $('#links-container').append(
                    '<div class="link-row">' +
                    '<input type="text" name="smart_affiliate_settings[links][' + linkIndex + '][keyword]" placeholder="Keyword" />' +
                    '<input type="text" name="smart_affiliate_settings[links][' + linkIndex + '][link_text]" placeholder="Link Text" />' +
                    '<input type="url" name="smart_affiliate_settings[links][' + linkIndex + '][affiliate_link]" placeholder="Affiliate URL" />' +
                    '<button type="button" class="button remove-link">Remove</button>' +
                    '</div>'
                );
                linkIndex++;
            });
            $(document).on('click', '.remove-link', function() {
                $(this).closest('.link-row').remove();
            });
        });
        </script>
        <style>
        .link-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .link-row input { margin-right: 10px; width: 200px; }
        </style>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_settings', array('enabled' => 1, 'max_inserts' => 2, 'links' => array()));
    }

    public function deactivate() {
        // No-op
    }
}

SmartAffiliateAutoInserter::get_instance();

// Pro upgrade notice
function smart_affiliate_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoInserter Pro</strong> for unlimited links, analytics, and more! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
}
add_action('admin_notices', 'smart_affiliate_pro_notice');
?>