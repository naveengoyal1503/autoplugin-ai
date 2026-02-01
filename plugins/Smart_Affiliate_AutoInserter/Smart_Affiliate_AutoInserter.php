/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts based on keywords. Freemium model with premium add-ons.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array());
    }

    public function load_textdomain() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        if (!get_option('smart_affiliate_settings')) {
            update_option('smart_affiliate_settings', array(
                'api_key' => '',
                'keywords' => array(),
                'max_links' => 3,
                'enabled' => true
            ));
        }
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function auto_insert_links($content) {
        if (!is_single() || empty($this->options['enabled']) || is_admin()) {
            return $content;
        }

        $keywords = $this->options['keywords'] ?? array();
        $max_links = intval($this->options['max_links'] ?? 3);
        $inserted = 0;

        foreach ($keywords as $keyword => $link) {
            if ($inserted >= $max_links) break;
            $content = preg_replace_callback(
                '/\b' . preg_quote($keyword, '/') . '\b/i',
                function($matches) use ($link, &$inserted) {
                    if ($inserted < 3) { // Free limit
                        $inserted++;
                        return '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">' . $matches . '</a>';
                    }
                    return $matches;
                },
                $content,
                1
            );
        }

        // Premium teaser
        if ($inserted >= 3) {
            $content .= '<p><em>Upgrade to Pro for unlimited AI-powered auto-links and analytics! <a href="https://example.com/pro">Get Pro</a></em></p>';
        }

        return $content;
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
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_group'); ?>
                <?php do_settings_sections('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Auto-Insert', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <?php $enabled = $this->options['enabled'] ?? true; ?>
                            <input type="checkbox" name="smart_affiliate_settings[enabled]" value="1" <?php checked($enabled); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Links per Post (Free: 3)', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <input type="number" name="smart_affiliate_settings[max_links]" value="<?php echo esc_attr($this->options['max_links'] ?? 3); ?>" min="1" max="3" />
                            <p class="description"><?php _e('Upgrade to Pro for unlimited.', 'smart-affiliate-autoinserter'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Keyword Links', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <div id="keyword-list">
                                <?php
                                $keywords = $this->options['keywords'] ?? array();
                                foreach ($keywords as $kw => $lnk): ?>
                                    <div class="keyword-row">
                                        <input type="text" name="smart_affiliate_settings[keywords][<?php echo esc_attr($kw); ?>]" value="<?php echo esc_attr($kw); ?>" placeholder="Keyword" />
                                        <input type="url" name="smart_affiliate_settings[links][<?php echo esc_attr($kw); ?>]" value="<?php echo esc_attr($lnk); ?>" placeholder="Affiliate Link" />
                                        <button type="button" class="button remove-kw">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-keyword" class="button">Add Keyword</button>
                            <p class="description">Enter keywords and their Amazon affiliate links. Free version supports up to 10.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock AI keyword detection, unlimited links, click analytics, and more. <a href="https://example.com/pro" target="_blank">Buy Now - $49/year</a></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let kwCount = <?php echo count($keywords ?? array()); ?>;
            $('#add-keyword').click(function() {
                if (kwCount < 10) { // Free limit
                    $('#keyword-list').append(
                        '<div class="keyword-row">\n' +
                        '<input type="text" name="smart_affiliate_settings[keywords][" + kwCount + "]" placeholder="Keyword" />\n' +
                        '<input type="url" name="smart_affiliate_settings[links][" + kwCount + "]" placeholder="Affiliate Link" />\n' +
                        '<button type="button" class="button remove-kw">Remove</button>\n' +
                        '</div>'
                    );
                    kwCount++;
                } else {
                    alert('Upgrade to Pro for unlimited keywords!');
                }
            });
            $(document).on('click', '.remove-kw', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }
}

new SmartAffiliateAutoInserter();

// Freemius integration placeholder for premium (requires Freemius SDK for real implementation)
// For demo, omitted full SDK to keep single-file.