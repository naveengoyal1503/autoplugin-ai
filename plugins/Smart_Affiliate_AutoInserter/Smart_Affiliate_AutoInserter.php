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
    public $options;

    public static function get_instance() {
        if (null == self::$instance) {
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
        add_filter('wp_insert_post_data', array($this, 'insert_affiliate_links_on_save'), 99, 2);
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'affiliates' => array(),
            'max_links_per_post' => 3,
            'enabled' => true
        ));
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
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
        register_setting('smart_affiliate_options_group', 'smart_affiliate_options', array($this, 'sanitize_options'));
    }

    public function sanitize_options($input) {
        $input['enabled'] = isset($input['enabled']) ? 1 : 0;
        $input['max_links_per_post'] = max(1, intval($input['max_links_per_post']));
        return $input;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_options_group'); ?>
                <?php do_settings_sections('smart_affiliate_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="enabled"><?php _e('Enable Auto-Insertion', 'smart-affiliate-autoinserter'); ?></label></th>
                        <td><input type="checkbox" id="enabled" name="smart_affiliate_options[enabled]" value="1" <?php checked(1, $this->options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_links_per_post"><?php _e('Max Links Per Post', 'smart-affiliate-autoinserter'); ?></label></th>
                        <td><input type="number" id="max_links_per_post" name="smart_affiliate_options[max_links_per_post]" value="<?php echo esc_attr($this->options['max_links_per_post']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Affiliate Links', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <div id="affiliates-list">
                                <?php $this->render_affiliates(); ?>
                            </div>
                            <p><a href="#" id="add-affiliate" class="button"><?php _e('Add Affiliate Link', 'smart-affiliate-autoinserter'); ?></a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var i = <?php echo count($this->options['affiliates']); ?>;
            $('#add-affiliate').click(function(e) {
                e.preventDefault();
                $('#affiliates-list').append(
                    '<div class="affiliate-row">' +
                    '<input type="text" name="smart_affiliate_options[affiliates][' + i + '][keyword]" placeholder="Keyword" /> ' +
                    '<input type="url" name="smart_affiliate_options[affiliates][' + i + '][link]" placeholder="Affiliate URL" /> ' +
                    '<input type="text" name="smart_affiliate_options[affiliates][' + i + '][text]" placeholder="Link Text" /> ' +
                    '<a href="#" class="remove-row button">Remove</a>' +
                    '</div>'
                );
                i++;
            });
            $(document).on('click', '.remove-row', function(e) {
                e.preventDefault();
                $(this).parent().remove();
            });
        });
        </script>
        <style>
        .affiliate-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .affiliate-row input { width: 200px; margin-right: 10px; }
        </style>
        <?php
    }

    private function render_affiliates() {
        if (empty($this->options['affiliates'])) return;
        foreach ($this->options['affiliates'] as $index => $aff) {
            echo '<div class="affiliate-row">';
            echo '<input type="text" name="smart_affiliate_options[affiliates][' . $index . '][keyword]" value="' . esc_attr($aff['keyword']) . '" placeholder="Keyword" /> ';
            echo '<input type="url" name="smart_affiliate_options[affiliates][' . $index . '][link]" value="' . esc_url($aff['link']) . '" placeholder="Affiliate URL" /> ';
            echo '<input type="text" name="smart_affiliate_options[affiliates][' . $index . '][text]" value="' . esc_attr($aff['text']) . '" placeholder="Link Text" /> ';
            echo '<a href="#" class="remove-row button">Remove</a>';
            echo '</div>';
        }
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || !$this->options['enabled'] || is_admin()) {
            return $content;
        }
        $inserted = 0;
        $max = intval($this->options['max_links_per_post']);
        foreach ($this->options['affiliates'] as $aff) {
            if ($inserted >= $max) break;
            $keyword = '/' . preg_quote($aff['keyword'], '/') . '/i';
            if (preg_match_all($keyword, $content, $matches)) {
                $link_text = !empty($aff['text']) ? $aff['text'] : $aff['keyword'];
                $link_html = '<a href="' . esc_url($aff['link']) . '" target="_blank" rel="nofollow sponsored">' . esc_html($link_text) . '</a>';
                $content = preg_replace($keyword, $link_html, $content, 1);
                $inserted++;
            }
        }
        return $content;
    }

    public function insert_affiliate_links_on_save($data, $postarr) {
        if ($data['post_type'] !== 'post' || empty($data['post_content']) || !$this->options['enabled']) {
            return $data;
        }
        $content = $this->insert_affiliate_links($data['post_content']);
        $data['post_content'] = $content;
        return $data;
    }

    public function activate() {
        add_option('smart_affiliate_options', array(
            'affiliates' => array(
                array('keyword' => 'WordPress', 'link' => 'https://example.com/aff/wp', 'text' => 'WordPress Hosting'),
                array('keyword' => 'plugin', 'link' => 'https://example.com/aff/plugin', 'text' => 'Best Plugins')
            ),
            'max_links_per_post' => 3,
            'enabled' => true
        ));
    }
}

SmartAffiliateAutoInserter::get_instance();