/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using keyword matching and AI-like rules for passive monetization.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_filter('widget_text', array($this, 'insert_affiliate_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_settings', 'smart_affiliate_options');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_settings');
                do_settings_sections('smart_affiliate_settings');
                $options = get_option('smart_affiliate_options', array('keywords' => array(), 'links' => array()));
                ?>
                <table class="form-table">
                    <tr>
                        <th>Keywords & Links</th>
                        <td>
                            <div id="keyword-links">
                                <?php foreach ($options['keywords'] as $i => $kw): ?>
                                    <div class="keyword-row">
                                        <input type="text" name="smart_affiliate_options[keywords][]" value="<?php echo esc_attr($kw); ?>" placeholder="Keyword" style="width:200px;">
                                        <input type="url" name="smart_affiliate_options[links][]" value="<?php echo esc_attr($options['links'][$i]); ?>" placeholder="Affiliate Link" style="width:300px;">
                                        <button type="button" class="button remove-kw">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-kw" class="button">Add Keyword/Link</button>
                            <p class="description">Free version limited to 5 keyword/link pairs. Upgrade for unlimited & AI matching.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td>
                            <input type="number" name="smart_affiliate_options[max_links]" value="<?php echo esc_attr($options['max_links'] ?? 3); ?>" min="1" max="10">
                        </td>
                    </tr>
                    <tr>
                        <th>Link Placement</th>
                        <td>
                            <select name="smart_affiliate_options[placement]">
                                <option value="after" <?php selected($options['placement'] ?? 'after', 'after'); ?>>After Keyword</option>
                                <option value="around" <?php selected($options['placement'] ?? 'after', 'around'); ?>>Around Keyword</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div class="premium-upsell">
                <h2>Go Premium!</h2>
                <p>Unlock AI-powered link suggestions, analytics, and unlimited keywords for $49/year.</p>
                <a href="https://example.com/premium" class="button button-primary">Upgrade Now</a>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-kw').click(function() {
                if ($('#keyword-links .keyword-row').length >= 5) {
                    alert('Free version limited to 5 pairs. Upgrade for more!');
                    return;
                }
                $('#keyword-links').append('<div class="keyword-row"><input type="text" name="smart_affiliate_options[keywords][]" placeholder="Keyword" style="width:200px;"><input type="url" name="smart_affiliate_options[links][]" placeholder="Affiliate Link" style="width:300px;"><button type="button" class="button remove-kw">Remove</button></div>');
            });
            $(document).on('click', '.remove-kw', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <style>
        .keyword-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .premium-upsell { background: #fff3cd; padding: 20px; border-left: 4px solid #ffc107; margin-top: 20px; }
        </style>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || !is_main_query()) return $content;

        $options = get_option('smart_affiliate_options', array('keywords' => array(), 'links' => array(), 'max_links' => 3, 'placement' => 'after'));
        $keywords = $options['keywords'];
        $links = $options['links'];
        $max_links = min((int)$options['max_links'], 3); // Free limit
        $placement = $options['placement'];
        $inserted = 0;

        foreach ($keywords as $i => $keyword) {
            if ($inserted >= $max_links) break;
            if (empty($keyword) || empty($links[$i])) continue;

            $link_html = '<a href="' . esc_url($links[$i]) . '" target="_blank" rel="nofollow noopener" class="smart-affiliate-link">' . esc_html($keyword) . '</a>';

            if ($placement === 'after') {
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '$0 ' . $link_html, $content, 1, $count);
                if ($count) $inserted++;
            } else { // around
                $content = preg_replace('/\b(' . preg_quote($keyword, '/') . ')\b/i', $link_html, $content, 1, $count);
                if ($count) $inserted++;
            }
        }

        return $content;
    }

    public function activate() {
        add_option('smart_affiliate_options', array('keywords' => array(), 'links' => array(), 'max_links' => 3));
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

SmartAffiliateAutoInserter::get_instance();