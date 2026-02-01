/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using keyword matching and context analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'api_key' => '',
            'affiliate_links' => array(),
            'max_links_per_post' => 3,
            'enabled' => true
        ));
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (!isset($this->options['enabled']) || !$this->options['enabled']) {
            return $content;
        }
        if (is_admin() || !is_single()) {
            return $content;
        }

        $max_links = intval($this->options['max_links_per_post']);
        $links = $this->options['affiliate_links'];
        if (empty($links)) {
            return $content;
        }

        $paragraphs = explode('</p>', $content);
        $inserted = 0;

        foreach ($paragraphs as &$paragraph) {
            if ($inserted >= $max_links) break;

            foreach ($links as $link) {
                $keyword = $link['keyword'];
                $aff_link = $link['url'];
                $text = $link['text'];

                if (stripos($paragraph, $keyword) !== false && stripos($paragraph, 'href') === false) {
                    $replace = '<a href="' . esc_url($aff_link) . '" target="_blank" rel="nofollow noopener">' . esc_html($text) . '</a>';
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
            'Smart Affiliate Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('smart_affiliate_options_group', 'smart_affiliate_options', array($this, 'sanitize_options'));
    }

    public function sanitize_options($input) {
        $input['enabled'] = isset($input['enabled']);
        $input['max_links_per_post'] = intval($input['max_links_per_post']);
        $input['affiliate_links'] = isset($input['affiliate_links']) ? $input['affiliate_links'] : array();
        return $input;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_options_group'); ?>
                <?php do_settings_sections('smart_affiliate_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_options[enabled]" value="1" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Links Per Post</th>
                        <td><input type="number" name="smart_affiliate_options[max_links_per_post]" value="<?php echo esc_attr($this->options['max_links_per_post']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td>
                            <div id="links-container">
                                <?php foreach ($this->options['affiliate_links'] as $i => $link): ?>
                                    <div class="link-row">
                                        <input type="text" name="smart_affiliate_options[affiliate_links][<?php echo $i; ?>][keyword]" placeholder="Keyword" value="<?php echo esc_attr($link['keyword']); ?>" />
                                        <input type="text" name="smart_affiliate_options[affiliate_links][<?php echo $i; ?>][text]" placeholder="Link Text" value="<?php echo esc_attr($link['text']); ?>" />
                                        <input type="url" name="smart_affiliate_options[affiliate_links][<?php echo $i; ?>][url]" placeholder="Affiliate URL" value="<?php echo esc_attr($link['url']); ?>" />
                                        <button type="button" class="button remove-link">Remove</button>
                                    </div>
                                <?php endforeach; ?>
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
            let linkIndex = <?php echo count($this->options['affiliate_links']); ?>;
            $('#add-link').click(function() {
                $('#links-container').append(
                    '<div class="link-row">' +
                    '<input type="text" name="smart_affiliate_options[affiliate_links][" + linkIndex + "][keyword]" placeholder="Keyword" />' +
                    '<input type="text" name="smart_affiliate_options[affiliate_links][" + linkIndex + "][text]" placeholder="Link Text" />' +
                    '<input type="url" name="smart_affiliate_options[affiliate_links][" + linkIndex + "][url]" placeholder="Affiliate URL" />' +
                    '<button type="button" class="button remove-link">Remove</button>' +
                    '</div>'
                );
                linkIndex++;
            });
            $(document).on('click', '.remove-link', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <style>
        .link-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .link-row input { margin-right: 10px; }
        </style>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_options', array(
            'enabled' => true,
            'max_links_per_post' => 3,
            'affiliate_links' => array()
        ));
    }
}

new SmartAffiliateAutoInserter();