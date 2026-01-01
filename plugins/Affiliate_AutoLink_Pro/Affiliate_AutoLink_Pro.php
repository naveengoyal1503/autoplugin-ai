/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_AutoLink_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate AutoLink Pro
 * Plugin URI: https://example.com/affiliate-autolink-pro
 * Description: Automatically converts keywords in posts to affiliate links.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateAutoLinkPro {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'replace_keywords'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('aalp_options', array(
            'keywords' => array(
                'WordPress' => 'https://amazon.com/wordpress-book?tag=youraffiliateid',
                'plugin' => 'https://clickbank.com/plugin-offer?aff=yourid'
            ),
            'free_limit' => 5
        ));
    }

    public function activate() {
        if (!get_option('aalp_options')) {
            add_option('aalp_options', $this->options);
        }
    }

    public function admin_menu() {
        add_options_page('Affiliate AutoLink Pro', 'AutoLink Pro', 'manage_options', 'aalp', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->options['keywords'] = $_POST['keywords'];
            update_option('aalp_options', $this->options);
            echo '<div class="updated"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Affiliate AutoLink Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Keywords & Links</th>
                        <td>
                            <?php foreach ($this->options['keywords'] as $kw => $link): ?>
                            <p><input type="text" name="keywords[<?php echo esc_attr($kw); ?>]" value="<?php echo esc_attr($kw); ?>" size="20"> -> <input type="url" name="keywords[<?php echo esc_attr($kw); ?>][link]" value="<?php echo esc_attr($link); ?>" size="50"></p>
                            <?php endforeach; ?>
                            <p><em>Add more pairs as needed (key=value format).</em></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlimited keywords, analytics, more networks. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function replace_keywords($content) {
        if (is_feed() || is_preview()) return $content;

        $free_limit = $this->options['free_limit'];
        $used = 0;
        $pro = false; // Check license or flag for pro

        foreach ($this->options['keywords'] as $keyword => $link) {
            if (!$pro && $used >= $free_limit) {
                $content .= '<p><em>Upgrade to Pro for unlimited links!</em></p>';
                break;
            }
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="' . esc_url($link) . '" rel="nofollow sponsored" target="_blank">$0</a>', $content, 1, $count);
            if ($count) $used++;
        }
        return $content;
    }
}

new AffiliateAutoLinkPro();

// Pro upsell notice
function aalp_upsell_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate AutoLink Pro:</strong> Unlock unlimited links & analytics. <a href="' . admin_url('options-general.php?page=aalp') . '">Upgrade now</a></p></div>';
}
add_action('admin_notices', 'aalp_upsell_notice');
?>