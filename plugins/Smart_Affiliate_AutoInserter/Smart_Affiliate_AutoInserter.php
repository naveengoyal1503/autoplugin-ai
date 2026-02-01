/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using keyword matching to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'links' => array(),
            'max_links' => 3,
            'enabled' => true
        ));
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (!isset($this->options['enabled']) || !$this->options['enabled'] || empty($this->options['links'])) {
            return $content;
        }

        $words = explode(' ', strip_tags($content));
        $inserted = 0;
        $max_links = intval($this->options['max_links']);

        foreach ($this->options['links'] as $link_data) {
            $keyword = strtolower($link_data['keyword']);
            $aff_link = $link_data['affiliate_link'];
            $link_text = !empty($link_data['link_text']) ? $link_data['link_text'] : $keyword;

            if ($inserted >= $max_links) break;

            for ($i = 0; $i < count($words) - 1; $i++) {
                if (strpos(strtolower($words[$i]), $keyword) !== false && rand(1, 3) === 1) { // 33% chance to insert
                    $link = '<a href="' . esc_url($aff_link) . '" target="_blank" rel="nofollow noopener">' . esc_html($link_text) . '</a> ';
                    array_splice($words, $i, 1, $link);
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $words);
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
        register_setting('smart_affiliate_options_group', 'smart_affiliate_options', array($this, 'sanitize_options'));
    }

    public function sanitize_options($input) {
        $input['enabled'] = isset($input['enabled']);
        $input['max_links'] = intval($input['max_links']);
        $input['links'] = isset($input['links']) ? array_map(array($this, 'sanitize_link'), $input['links']) : array();
        return $input;
    }

    private function sanitize_link($link) {
        return array(
            'keyword' => sanitize_text_field($link['keyword']),
            'affiliate_link' => esc_url_raw($link['affiliate_link']),
            'link_text' => sanitize_text_field($link['link_text'])
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_options_group'); ?>
                <?php do_settings_sections('smart_affiliate_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Auto-Insertion', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <input type="checkbox" name="smart_affiliate_options[enabled]" value="1" <?php checked($this->options['enabled']); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Links per Post', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <input type="number" name="smart_affiliate_options[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Affiliate Links', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <div id="links-container">
                                <?php $this->render_links(); ?>
                            </div>
                            <p><a href="#" id="add-link"><?php _e('Add New Link', 'smart-affiliate-autoinserter'); ?></a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var idx = $('#links-container .link-row').length;
            $('#add-link').click(function(e) {
                e.preventDefault();
                var row = '<div class="link-row">' +
                    '<input type="text" name="smart_affiliate_options[links][' + idx + '][keyword]" placeholder="Keyword" required /> ' +
                    '<input type="url" name="smart_affiliate_options[links][' + idx + '][affiliate_link]" placeholder="Affiliate URL" required /> ' +
                    '<input type="text" name="smart_affiliate_options[links][' + idx + '][link_text]" placeholder="Link Text" /> ' +
                    '<button type="button" class="remove-link">Remove</button>' +
                    '</div>';
                $('#links-container').append(row);
                idx++;
            });
            $(document).on('click', '.remove-link', function() {
                $(this).closest('.link-row').remove();
            });
        });
        </script>
        <style>
        .link-row { margin-bottom: 10px; }
        .link-row input { width: 200px; margin-right: 10px; }
        </style>
        <?php
    }

    private function render_links() {
        if (empty($this->options['links'])) return;
        foreach ($this->options['links'] as $index => $link) {
            echo '<div class="link-row">';
            echo '<input type="text" name="smart_affiliate_options[links][' . $index . '][keyword]" value="' . esc_attr($link['keyword']) . '" placeholder="Keyword" required /> ';
            echo '<input type="url" name="smart_affiliate_options[links][' . $index . '][affiliate_link]" value="' . esc_attr($link['affiliate_link']) . '" placeholder="Affiliate URL" required /> ';
            echo '<input type="text" name="smart_affiliate_options[links][' . $index . '][link_text]" value="' . esc_attr($link['link_text']) . '" placeholder="Link Text" /> ';
            echo '<button type="button" class="remove-link">Remove</button>';
            echo '</div>';
        }
    }

    public function activate() {
        if (!get_option('smart_affiliate_options')) {
            update_option('smart_affiliate_options', array('links' => array(), 'max_links' => 3, 'enabled' => true));
        }
    }

    public function deactivate() {
        // No-op
    }
}

new SmartAffiliateAutoInserter();

// Freemium notice
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen->id !== 'settings_page_smart-affiliate') return;
    echo '<div class="notice notice-info"><p><strong>Pro Upgrade:</strong> Unlock AI keyword suggestions, click tracking, A/B testing, and premium integrations for $49/year. <a href="https://example.com/pro" target="_blank">Learn More</a></p></div>';
});