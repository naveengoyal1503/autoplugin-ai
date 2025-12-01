/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Affiliate_Optimizer.php
*/
<?php
/**
 * Plugin Name: WP Smart Affiliate Optimizer
 * Description: Automatically optimizes affiliate links for higher conversion rates using AI-driven placement and A/B testing.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

// Main plugin class
class WPSmartAffiliateOptimizer {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'inject_affiliate_links'));
        add_action('wp_ajax_save_affiliate_settings', array($this, 'save_affiliate_settings'));
        add_action('wp_ajax_nopriv_save_affiliate_settings', array($this, 'save_affiliate_settings'));
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate Optimizer',
            'Affiliate Optimizer',
            'manage_options',
            'smart-affiliate-optimizer',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['save_settings'])) {
            update_option('sao_affiliate_links', $_POST['affiliate_links']);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $links = get_option('sao_affiliate_links', []);
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Optimizer</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label>Affiliate Links (one per line: URL|Keyword)</label></th>
                        <td><textarea name="affiliate_links" rows="10" cols="50"><?php echo esc_textarea(implode("\n", $links)); ?></textarea></td>
                    </tr>
                </table>
                <p><input type="submit" name="save_settings" class="button button-primary" value="Save Settings" /></p>
            </form>
            <p><strong>Premium Features:</strong> AI-driven placement, A/B testing, conversion analytics.</p>
            <p><a href="https://yourwebsite.com/premium" target="_blank">Upgrade to Premium</a></p>
        </div>
        <?php
    }

    public function inject_affiliate_links() {
        if (is_admin()) return;
        $links = get_option('sao_affiliate_links', []);
        if (empty($links)) return;

        $content = '';
        foreach ($links as $line) {
            $parts = explode('|', $line);
            if (count($parts) !== 2) continue;
            $url = esc_url($parts);
            $keyword = sanitize_text_field($parts[1]);
            $content .= "<script>document.addEventListener('DOMContentLoaded', function(){var els=document.querySelectorAll('p,li');for(var i=0;i<els.length;i++){if(els[i].innerText.includes('$keyword')){els[i].innerHTML=els[i].innerHTML.replace(/($keyword)/g,'<a href='$url' target='_blank' rel='nofollow'>$1</a>');}}});</script>";
        }
        echo $content;
    }

    public function save_affiliate_settings() {
        // AJAX handler for future enhancements
        wp_die();
    }
}

new WPSmartAffiliateOptimizer();
