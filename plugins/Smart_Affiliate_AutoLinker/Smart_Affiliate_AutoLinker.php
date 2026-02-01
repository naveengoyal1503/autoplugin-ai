/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your content and converts them into trackable affiliate links from Amazon, boosting commissions without manual work.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoLinker {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('saal_options', array(
            'amazon_tag' => '',
            'keywords' => array(
                array('keyword' => 'WordPress', 'url' => ''),
            ),
            'pro' => false,
            'max_links' => 3
        ));

        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        } else {
            add_filter('the_content', array($this, 'auto_link_content'));
        }
    }

    public function activate() {
        if (!get_option('saal_options')) {
            update_option('saal_options', array(
                'amazon_tag' => '',
                'keywords' => array(),
                'pro' => false,
                'max_links' => 3
            ));
        }
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('saal_options_group', 'saal_options', array($this, 'sanitize_options'));
    }

    public function sanitize_options($input) {
        $input['amazon_tag'] = sanitize_text_field($input['amazon_tag']);
        $input['keywords'] = isset($input['keywords']) ? array_map(function($k) {
            return array(
                'keyword' => sanitize_text_field($k['keyword']),
                'url' => esc_url_raw($k['url'])
            );
        }, $input['keywords']) : array();
        $input['max_links'] = intval($input['max_links']);
        $input['pro'] = isset($input['pro']);
        return $input;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saal_options_group'); ?>
                <?php do_settings_sections('saal_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="saal_options[amazon_tag]" value="<?php echo esc_attr($this->options['amazon_tag']); ?>" class="regular-text" placeholder="yourtag-20"></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post (Free: 3)</th>
                        <td><input type="number" name="saal_options[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10"></td>
                    </tr>
                    <tr>
                        <th>Keywords</th>
                        <td>
                            <?php foreach ($this->options['keywords'] as $i => $kw): ?>
                            <div style="border:1px solid #ccc; margin:5px; padding:10px;">
                                Keyword: <input type="text" name="saal_options[keywords][<?php echo $i; ?>][keyword]" value="<?php echo esc_attr($kw['keyword']); ?>"><br>
                                Affiliate URL: <input type="url" name="saal_options[keywords][<?php echo $i; ?>][url]" value="<?php echo esc_attr($kw['url']); ?>" style="width:300px;"><br>
                                <small>Leave URL empty to auto-generate Amazon link</small>
                                <a href="#" onclick="jQuery(this).parent().remove(); return false;">Remove</a>
                            </div>
                            <?php endforeach; ?>
                            <button type="button" id="add-keyword">Add Keyword</button>
                            <script>
                            jQuery(document).ready(function($) {
                                var i = <?php echo count($this->options['keywords']); ?>;
                                $('#add-keyword').click(function() {
                                    $(this).before('<div style="border:1px solid #ccc; margin:5px; padding:10px;">
                                        Keyword: <input type="text" name="saal_options[keywords][" + i + "][keyword]" value=""><br>
                                        Affiliate URL: <input type="url" name="saal_options[keywords][" + i + "][url]" value="" style="width:300px;"><br>
                                        <small>Leave URL empty to auto-generate Amazon link</small>
                                        <a href="#" onclick="jQuery(this).parent().remove(); return false;">Remove</a>
                                    </div>');
                                    i++;
                                });
                            });
                            </script>
                        </td>
                    </tr>
                    <tr>
                        <th>Pro Version</th>
                        <td><label><input type="checkbox" name="saal_options[pro]" <?php checked($this->options['pro']); ?>> Enable Pro Features (Upgrade for unlimited)</label><br><small><a href="https://example.com/pro" target="_blank">Upgrade to Pro</a></small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Unlimited keywords, analytics dashboard, multiple affiliate networks, A/B testing, priority support. <a href="https://example.com/pro" target="_blank">Get Pro Now</a></p>
        </div>
        <?php
    }

    public function auto_link_content($content) {
        if (is_feed() || is_admin() || empty($this->options['keywords'])) {
            return $content;
        }

        $max_links = $this->options['pro'] ? 10 : min(3, $this->options['max_links']);
        $links_added = 0;

        foreach ($this->options['keywords'] as $keyword) {
            if ($links_added >= $max_links) break;

            if (empty($keyword['keyword']) || stripos($content, $keyword['keyword']) === false) continue;

            $url = !empty($keyword['url']) ? $keyword['url'] : $this->generate_amazon_url($keyword['keyword']);
            if (empty($url)) continue;

            $link = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword['keyword']) . '</a>';
            $content = preg_replace('/\b' . preg_quote($keyword['keyword'], '/') . '\b/i', $link, $content, 1);
            $links_added++;
        }

        return $content;
    }

    private function generate_amazon_url($keyword) {
        if (empty($this->options['amazon_tag'])) return '';

        $keyword = urlencode($keyword);
        return "https://amazon.com/s?k={$keyword}&tag={$this->options['amazon_tag']}";
    }
}

new SmartAffiliateAutoLinker();

// Freemium nag
add_action('admin_notices', function() {
    $options = get_option('saal_options', array());
    if (!isset($options['pro']) || !$options['pro']) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Smart Affiliate AutoLinker Pro</strong> for unlimited keywords & analytics! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
});