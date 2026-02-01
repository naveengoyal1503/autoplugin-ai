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
 * Author URI: https://example.com
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
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_settings'));
        } else {
            add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        }
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('saa_keywords', array('WordPress' => 'https://youraffiliate.link/wordpress', 'plugin' => 'https://youraffiliate.link/plugin'));
        add_option('saa_max_links', 3);
        add_option('saa_pro_version', false);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'settings_page')
        );
    }

    public function admin_settings() {
        register_setting('saa_settings', 'saa_keywords');
        register_setting('saa_settings', 'saa_max_links');
        register_setting('saa_settings', 'saa_pro_version');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Keyword to Affiliate Link', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <?php $keywords = get_option('saa_keywords', array()); ?>
                            <div id="saa-keyword-list">
                                <?php foreach ($keywords as $kw => $link): ?>
                                    <p><input type="text" name="saa_keywords[<?php echo esc_attr($kw); ?>][keyword]" value="<?php echo esc_attr($kw); ?>" placeholder="Keyword"> 
                                    <input type="url" name="saa_keywords[<?php echo esc_attr($kw); ?>][link]" value="<?php echo esc_attr($link); ?>" placeholder="Affiliate URL"> 
                                    <button type="button" class="button" onclick="removeKeyword(this)">Remove</button></p>
                                <?php endforeach; ?>
                            </div>
                            <p><button type="button" class="button" onclick="addKeyword()">Add Keyword</button></p>
                            <script>
                            let kwCount = <?php echo count($keywords); ?>;
                            function addKeyword() {
                                const list = document.getElementById('saa-keyword-list');
                                const p = document.createElement('p');
                                p.innerHTML = '<input type="text" name="saa_keywords[' + kwCount + '][keyword]" placeholder="Keyword"> <input type="url" name="saa_keywords[' + kwCount + '][link]" placeholder="Affiliate URL"> <button type="button" class="button" onclick="removeKeyword(this)">Remove</button>';
                                list.appendChild(p);
                                kwCount++;
                            }
                            function removeKeyword(btn) {
                                btn.parentElement.remove();
                            }
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Links per Post', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <input type="number" name="saa_max_links" value="<?php echo esc_attr(get_option('saa_max_links', 3)); ?>" min="1" max="10">
                        </td>
                    </tr>
                    <?php if (!get_option('saa_pro_version')): ?>
                    <tr>
                        <th><?php _e('Upgrade to Pro', 'smart-affiliate-autoinserter'); ?></th>
                        <td><a href="https://example.com/pro" class="button button-primary">Get Pro for AI Features & Analytics ($49/year)</a></td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (is_feed() || is_preview() || empty($content)) {
            return $content;
        }

        $keywords = get_option('saa_keywords', array());
        if (empty($keywords)) {
            return $content;
        }

        $max_links = intval(get_option('saa_max_links', 3));
        $inserted = 0;
        $words = explode(' ', $content);
        $new_words = array();

        foreach ($words as $word) {
            $new_words[] = $word;
            foreach ($keywords as $kw => $link) {
                if (stripos($word, $kw) !== false && $inserted < $max_links) {
                    $link_html = '<a href="' . esc_url($link) . '" rel="nofollow sponsored" target="_blank">' . esc_html($word) . '</a>';
                    $new_words[count($new_words) - 1] = $link_html;
                    $inserted++;
                    break;
                }
            }
            if ($inserted >= $max_links) {
                break;
            }
        }

        return implode(' ', $new_words);
    }
}

SmartAffiliateAutoInserter::get_instance();