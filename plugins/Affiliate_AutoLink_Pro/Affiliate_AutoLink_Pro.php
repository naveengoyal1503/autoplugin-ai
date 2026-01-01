/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_AutoLink_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate AutoLink Pro
 * Plugin URI: https://example.com/affiliate-autolink-pro
 * Description: Automatically detects keywords in your posts and converts them into monetized affiliate links from multiple networks.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateAutoLinkPro {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_link_content'), 20);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('aalp_options', array(
            'keywords' => array(
                'WordPress' => 'https://example.com/ref/wordpress',
                'plugin' => 'https://example.com/ref/plugin'
            ),
            'nofollow' => true,
            'target_blank' => true,
            'max_links' => 3
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aalp-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
    }

    public function auto_link_content($content) {
        if (is_admin() || !is_singular()) return $content;

        $max_links = isset($this->options['max_links']) ? (int)$this->options['max_links'] : 3;
        $used_links = array();

        foreach ($this->options['keywords'] as $keyword => $url) {
            if (count($used_links) >= $max_links) break;

            $regex = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches as $match) {
                    if (count($used_links) >= $max_links) break;
                    $pos = $match[1];
                    if (strpos($content, '<a', $pos - 50) === false && strpos($content, '</a>', $pos) === false) {
                        $link_attr = '';
                        if (!empty($this->options['nofollow'])) $link_attr .= ' rel="nofollow"';
                        if (!empty($this->options['target_blank'])) $link_attr .= ' target="_blank"';
                        $link = '<a href="' . esc_url($url) . '"' . $link_attr . '>' . $match . '</a>';
                        $content = substr_replace($content, $link, $pos, strlen($match));
                        $used_links[] = $keyword;
                    }
                }
            }
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page('Affiliate AutoLink Pro', 'AutoLink Pro', 'manage_options', 'aalp-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('aalp_options_group', 'aalp_options');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate AutoLink Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aalp_options_group'); ?>
                <?php do_settings_sections('aalp_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Keywords & Links</th>
                        <td>
                            <div id="keywords-list">
                                <?php foreach ($this->options['keywords'] as $kw => $url): ?>
                                    <p><input type="text" name="aalp_options[keywords][<?php echo esc_attr($kw); ?>]" value="<?php echo esc_attr($kw); ?>" placeholder="Keyword"> → <input type="url" name="aalp_options[links][<?php echo esc_attr($kw); ?>]" value="<?php echo esc_url($url); ?>" placeholder="Affiliate URL"></p>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-keyword">Add Keyword</button>
                        </td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="aalp_options[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10"></td>
                    </tr>
                    <tr>
                        <th><label for="aalp_options[nofollow]">Add nofollow</label></th>
                        <td><input type="checkbox" id="aalp_options[nofollow]" name="aalp_options[nofollow]" <?php checked($this->options['nofollow']); ?>></td>
                    </tr>
                    <tr>
                        <th><label for="aalp_options[target_blank]">Open in new tab</label></th>
                        <td><input type="checkbox" id="aalp_options[target_blank]" name="aalp_options[target_blank]" <?php checked($this->options['target_blank']); ?>></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlimited keywords, analytics, A/B testing, and more! <a href="#">Get Pro ($49/year)</a></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-keyword').click(function() {
                var newKw = '<p><input type="text" name="aalp_options[keywords][]" placeholder="Keyword"> → <input type="url" name="aalp_options[links][]" placeholder="Affiliate URL"><button type="button" class="remove-kw">Remove</button></p>';
                $('#keywords-list').append(newKw);
            });
            $(document).on('click', '.remove-kw', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }

    public function activate() {
        add_option('aalp_options', array(
            'keywords' => array(
                'WordPress' => 'https://affiliate-program.com/ref/wordpress',
                'plugin' => 'https://affiliate-program.com/ref/plugin'
            ),
            'nofollow' => true,
            'target_blank' => true,
            'max_links' => 3
        ));
    }
}

new AffiliateAutoLinkPro();

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Admin JS
function aalp_admin_js() {
    ?>
    <script type="text/javascript">
    /* Inline JS for admin */
    </script>
    <?php
}

?>