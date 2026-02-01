/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into posts and pages. Freemium model.
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
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'settings_init'));
        } else {
            add_filter('the_content', array($this, 'auto_insert_links'), 99);
        }
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('saa_free_version_active', true);
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('saa_plugin_page', 'saa_settings');

        add_settings_section(
            'saa_plugin_page_section',
            __('Keyword and Link Settings', 'smart-affiliate-autoinserter'),
            array($this, 'settings_section_callback'),
            'saa_plugin_page'
        );

        add_settings_field(
            'saa_keywords',
            __('Keywords (one per line)', 'smart-affiliate-autoinserter'),
            array($this, 'keywords_render'),
            'saa_plugin_page',
            'saa_plugin_page_section'
        );

        add_settings_field(
            'saa_links',
            __('Corresponding Affiliate Links', 'smart-affiliate-autoinserter'),
            array($this, 'links_render'),
            'saa_plugin_page',
            'saa_plugin_page_section'
        );

        add_settings_field(
            'saa_max_links',
            __('Max links per post (Free: 1, Premium: Unlimited)', 'smart-affiliate-autoinserter'),
            array($this, 'max_links_render'),
            'saa_plugin_page',
            'saa_plugin_page_section'
        );
    }

    public function settings_section_callback() {
        echo __('Enter keywords and their affiliate links below. Free version limits to 1 link per post.', 'smart-affiliate-autoinserter');
    }

    public function keywords_render() {
        $options = get_option('saa_settings');
        echo '<textarea rows="5" cols="50" name="saa_settings[saa_keywords]">' . $this->escape($options['saa_keywords'] ?? '') . '</textarea>';
    }

    public function links_render() {
        $options = get_option('saa_settings');
        echo '<textarea rows="5" cols="50" name="saa_settings[saa_links]">' . $this->escape($options['saa_links'] ?? '') . '</textarea>';
        echo '<p class="description">' . __('One link per line, matching keywords above.', 'smart-affiliate-autoinserter') . '</p>';
    }

    public function max_links_render() {
        $options = get_option('saa_settings');
        $max = $options['saa_max_links'] ?? 1;
        echo '<input type="number" min="1" max="5" name="saa_settings[saa_max_links]" value="' . $this->escape($max) . '" />';
        echo '<p class="description">' . __('Upgrade to premium for unlimited. Current: Free version.', 'smart-affiliate-autoinserter') . '</p>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('saa_plugin_page');
                do_settings_sections('saa_plugin_page');
                submit_button();
                ?>
            </form>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0;">
                <h3>Upgrade to Premium</h3>
                <p>Unlock auto-insertion, AI keyword suggestions, click tracking, and unlimited links for $9/month!</p>
                <a href="https://example.com/premium" target="_blank" class="button button-primary">Get Premium</a>
            </div>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (is_home() || is_archive() || is_search()) {
            return $content;
        }

        $options = get_option('saa_settings');
        $keywords = explode("\n", trim($options['saa_keywords'] ?? ''));
        $links = explode("\n", trim($options['saa_links'] ?? ''));
        $max_links = min((int)($options['saa_max_links'] ?? 1), 1); // Free limit

        if (empty($keywords) || empty($links) || count($keywords) !== count($links)) {
            return $content;
        }

        $inserted = 0;
        $words = explode(' ', $content);
        $new_words = array();

        foreach ($words as $word) {
            $new_words[] = $word;
            for ($i = 0; $i < count($keywords) && $inserted < $max_links; $i++) {
                $keyword = trim($keywords[$i]);
                $link = trim($links[$i]);
                if (stripos($word, $keyword) !== false && !preg_match('/<a[^>]*>/i', $word)) {
                    $new_words[count($new_words) - 1] = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<a href="' . esc_url($link) . '" rel="nofollow" target="_blank">$1</a>', $word);
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $new_words);
    }

    private function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

SmartAffiliateAutoInserter::get_instance();