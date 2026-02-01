/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content to boost commissions.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_filter('widget_text', array($this, 'auto_insert_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            __('Smart Affiliate Settings', 'smart-affiliate-autoinserter'),
            __('Affiliate Inserter', 'smart-affiliate-autoinserter'),
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_settings', 'smart_affiliate_options');
        add_settings_section('main_section', __('Affiliate Links', 'smart-affiliate-autoinserter'), null, 'smart_affiliate');
        add_settings_field('affiliate_links', __('Links', 'smart-affiliate-autoinserter'), array($this, 'links_field'), 'smart_affiliate', 'main_section');
        add_settings_field('max_links', __('Max Links per Post', 'smart-affiliate-autoinserter'), array($this, 'max_links_field'), 'smart_affiliate', 'main_section');
    }

    public function links_field() {
        $options = get_option('smart_affiliate_options', array('links' => '', 'max_links' => 2));
        echo '<textarea name="smart_affiliate_options[links]" rows="10" cols="50" placeholder="Keyword|Affiliate URL&#10;WordPress|https://example.com/aff/wp&#10;Plugin|https://example.com/aff/plugin">' . esc_textarea($options['links']) . '</textarea>';
        echo '<p class="description">' . __('One per line: Keyword|Affiliate URL', 'smart-affiliate-autoinserter') . '</p>';
    }

    public function max_links_field() {
        $options = get_option('smart_affiliate_options', array('links' => '', 'max_links' => 2));
        echo '<input type="number" name="smart_affiliate_options[max_links]" value="' . esc_attr($options['max_links']) . '" min="1" max="10" />';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_settings');
                do_settings_sections('smart_affiliate');
                submit_button();
                ?>
            </form>
            <?php if (false) { // Premium teaser ?>
            <div class="notice notice-info">
                <p><?php _e('Upgrade to Pro for AI keyword matching and analytics!', 'smart-affiliate-autoinserter'); ?></p>
            </div>
            <?php } ?>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (is_admin() || !is_single() || empty($content)) {
            return $content;
        }
        $options = get_option('smart_affiliate_options', array('links' => '', 'max_links' => 2));
        $links = array();
        if (!empty($options['links'])) {
            foreach (explode("\n", $options['links']) as $line) {
                $parts = explode('|', trim($line), 2);
                if (count($parts) === 2) {
                    $links[trim($parts)] = trim($parts[1]);
                }
            }
        }
        if (empty($links)) {
            return $content;
        }
        $words = preg_split('/(\s+)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $inserted = 0;
        $max_links = intval($options['max_links']);
        for ($i = 0; $i < count($words) && $inserted < $max_links; $i++) {
            foreach ($links as $keyword => $url) {
                if (stripos($words[$i], $keyword) !== false && !preg_match('/<a\s/i', $words[$i])) {
                    $words[$i] = str_ireplace($keyword, '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">' . $keyword . '</a>', $words[$i]);
                    $inserted++;
                    break;
                }
            }
        }
        return implode('', $words);
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliate_stats';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT CURRENT_TIMESTAMP,
            post_id bigint(20) NOT NULL,
            clicks int(11) DEFAULT 0,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateAutoInserter::get_instance();

// Freemium notice
function smart_affiliate_freemium_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Smart Affiliate AutoInserter is active! Add your affiliate links in <a href="' . admin_url('options-general.php?page=smart-affiliate') . '">Settings &gt; Affiliate Inserter</a>. <strong>Upgrade to Pro for AI features!</strong>', 'smart-affiliate-autoinserter') . '</p></div>';
}
add_action('admin_notices', 'smart_affiliate_freemium_notice');

// Asset placeholders (create empty files in /assets/)
// script.js: jQuery(document).ready(function($){ /* Preview logic */ });
// style.css: .smart-affiliate { /* styles */ }