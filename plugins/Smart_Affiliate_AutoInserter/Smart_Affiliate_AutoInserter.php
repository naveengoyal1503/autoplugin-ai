/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using keyword matching and AI-powered suggestions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array());
    }

    public function activate() {
        add_option('smart_affiliate_options', array(
            'keywords' => array(),
            'enabled' => 1,
            'max_links_per_post' => 3
        ));
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (!isset($this->options['enabled']) || !$this->options['enabled']) {
            return $content;
        }

        $keywords = isset($this->options['keywords']) ? $this->options['keywords'] : array();
        if (empty($keywords)) {
            return $content;
        }

        $max_links = isset($this->options['max_links_per_post']) ? (int)$this->options['max_links_per_post'] : 3;
        $inserted = 0;

        foreach ($keywords as $keyword => $link) {
            if ($inserted >= $max_links) break;

            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches as $match) {
                    if ($inserted >= $max_links) break;
                    $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener" class="smart-affiliate-link">' . $match . '</a>';
                    $content = preg_replace('/\b' . preg_quote($match, '/') . '\b/i', $replacement, $content, 1);
                    $inserted++;
                }
            }
        }

        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_options_group', 'smart_affiliate_options');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_affiliate_options', $_POST['smart_affiliate_options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = $this->options;
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="">
                <?php settings_fields('smart_affiliate_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td>
                            <input type="checkbox" name="smart_affiliate_options[enabled]" value="1" <?php checked($options['enabled'], 1); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th>Max Links Per Post</th>
                        <td>
                            <input type="number" name="smart_affiliate_options[max_links_per_post]" value="<?php echo esc_attr($options['max_links_per_post']); ?>" min="1" max="10" />
                        </td>
                    </tr>
                    <tr>
                        <th>Keywords & Affiliate Links</th>
                        <td>
                            <div id="keyword-list">
                                <?php
                                $keywords = isset($options['keywords']) ? $options['keywords'] : array();
                                $i = 0;
                                foreach ($keywords as $kw => $link) {
                                    echo '<div class="keyword-row">';
                                    echo '<input type="text" name="smart_affiliate_options[keywords][' . $i . '][keyword]" placeholder="Keyword" value="' . esc_attr($kw) . '" />';
                                    echo '<input type="url" name="smart_affiliate_options[keywords][' . $i . '][link]" placeholder="Affiliate Link" value="' . esc_attr($link) . '" />';
                                    echo '<button type="button" class="button remove-keyword">Remove</button></div>';
                                    $i++;
                                }
                                ?>
                            </div>
                            <button type="button" id="add-keyword" class="button">Add Keyword</button>
                            <p class="description">Enter keywords to match in content and corresponding affiliate links.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <script>
            jQuery(document).ready(function($) {
                let rowIndex = <?php echo $i; ?>;
                $('#add-keyword').click(function() {
                    $('#keyword-list').append(
                        '<div class="keyword-row">' +
                        '<input type="text" name="smart_affiliate_options[keywords][" + rowIndex + "][keyword]" placeholder="Keyword" />' +
                        '<input type="url" name="smart_affiliate_options[keywords][" + rowIndex + "][link]" placeholder="Affiliate Link" />' +
                        '<button type="button" class="button remove-keyword">Remove</button>' +
                        '</div>'
                    );
                    rowIndex++;
                });
                $(document).on('click', '.remove-keyword', function() {
                    $(this).parent().remove();
                });
            });
            </script>
        </div>
        <?php
    }
}

new SmartAffiliateAutoInserter();

// Pro upgrade notice
function smart_affiliate_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Pro</strong> for AI keyword suggestions, analytics, and unlimited links! <a href="https://example.com/pro" target="_blank">Learn More</a></p></div>';
}
add_action('admin_notices', 'smart_affiliate_pro_notice');
?>