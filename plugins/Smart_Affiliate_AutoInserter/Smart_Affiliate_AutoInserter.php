/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using smart keyword matching and AI-like rules.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
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

    public function activate() {
        add_option('saai_keywords', json_encode(array(
            array('keyword' => 'WordPress', 'link' => 'https://example.com/aff/wp', 'max_uses' => 1)
        )));
        add_option('saai_enabled', 'yes');
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'Affiliate Inserter', 'manage_options', 'saai-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saai_settings', 'saai_keywords');
        register_setting('saai_settings', 'saai_enabled');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saai_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td>
                            <input type="checkbox" name="saai_enabled" value="yes" <?php checked(get_option('saai_enabled'), 'yes'); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th>Affiliate Keywords & Links</th>
                        <td>
                            <p>Add keywords and their affiliate links. Max uses per post limits insertions.</p>
                            <div id="saai-keywords">
                                <?php $keywords = json_decode(get_option('saai_keywords', '[]'), true);
                                foreach ($keywords as $i => $kw): ?>
                                <div class="saai-row">
                                    <input type="text" name="saai_keywords[<?php echo $i; ?>][keyword]" placeholder="Keyword" value="<?php echo esc_attr($kw['keyword']); ?>" />
                                    <input type="url" name="saai_keywords[<?php echo $i; ?>][link]" placeholder="Affiliate Link" value="<?php echo esc_attr($kw['link']); ?>" />
                                    <input type="number" name="saai_keywords[<?php echo $i; ?>][max_uses]" placeholder="1" value="<?php echo esc_attr($kw['max_uses']); ?>" min="1" />
                                    <button type="button" class="button saai-remove">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="saai-add-row" class="button">Add Row</button>
                            <script>
                            jQuery(document).ready(function($) {
                                $('#saai-add-row').click(function() {
                                    var i = $('#saai-keywords .saai-row').length;
                                    $('#saai-keywords').append(
                                        '<div class="saai-row">' +
                                        '<input type="text" name="saai_keywords[' + i + '][keyword]" placeholder="Keyword" />' +
                                        '<input type="url" name="saai_keywords[' + i + '][link]" placeholder="Affiliate Link" />' +
                                        '<input type="number" name="saai_keywords[' + i + '][max_uses]" placeholder="1" min="1" />' +
                                        '<button type="button" class="button saai-remove">Remove</button>' +
                                        '</div>'
                                    );
                                });
                                $(document).on('click', '.saai-remove', function() {
                                    $(this).closest('.saai-row').remove();
                                });
                            });
                            </script>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Premium:</strong> AI-powered keyword suggestions, performance analytics, WooCommerce integration, and no limits. <a href="https://example.com/premium">Get Premium</a></p>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (get_option('saai_enabled') != 'yes' || is_admin()) {
            return $content;
        }
        $keywords = json_decode(get_option('saai_keywords', '[]'), true);
        if (empty($keywords)) {
            return $content;
        }
        $used = array();
        foreach ($keywords as $kw) {
            $used[$kw['keyword']] = 0;
        }
        $content = preg_replace_callback('/\b(' . implode('|', array_map('preg_quote', array_column($keywords, 'keyword'))) . ')\b/i', function($matches) use ($keywords, &$used) {
            $keyword = strtolower($matches);
            foreach ($keywords as $kw) {
                if (strtolower($kw['keyword']) === $keyword && $used[$keyword] < $kw['max_uses']) {
                    $used[$keyword]++;
                    return '<a href="' . esc_url($kw['link']) . '" target="_blank" rel="nofollow sponsored">' . $matches . '</a>';
                }
            }
            return $matches;
        }, $content);
        return $content;
    }
}

SmartAffiliateAutoInserter::get_instance();

// Premium teaser notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Boost your affiliate earnings with <strong>Smart Affiliate AutoInserter Premium</strong>: AI optimization, analytics & more! <a href="https://example.com/premium">Upgrade Now</a></p></div>';
});