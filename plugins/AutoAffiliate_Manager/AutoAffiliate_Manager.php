/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AutoAffiliate_Manager.php
*/
<?php
/**
 * Plugin Name: AutoAffiliate Manager
 * Description: Automate your affiliate program with AI-powered influencer selection and flexible commission tiers.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if(!defined('ABSPATH')) exit;

class AutoAffiliateManager {
    private $option_name = 'autoaffiliate_manager_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_post_aa_add_affiliate', array($this, 'handle_add_affiliate'));
    }

    public function add_admin_menu() {
        add_menu_page('AutoAffiliate Manager', 'AutoAffiliate', 'manage_options', 'autoaffiliate_manager', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('autoaffiliate_manager', $this->option_name);

        add_settings_section('autoaffiliate_manager_section', __('Affiliate Program Settings', 'autoaffiliate_manager'), null, 'autoaffiliate_manager');

        add_settings_field('commission_rate', __('Default Commission Rate (%)', 'autoaffiliate_manager'), array($this, 'commission_rate_render'), 'autoaffiliate_manager', 'autoaffiliate_manager_section');
    }

    public function commission_rate_render() {
        $options = get_option($this->option_name);
        $value = isset($options['commission_rate']) ? esc_attr($options['commission_rate']) : '10';
        echo "<input type='number' min='0' max='100' name='{$this->option_name}[commission_rate]' value='$value' />";
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>AutoAffiliate Manager</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('autoaffiliate_manager');
                do_settings_sections('autoaffiliate_manager');
                submit_button();
                ?>
            </form>

            <h2>Add New Affiliate</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="aa_add_affiliate" />
                <?php wp_nonce_field('aa_add_affiliate_action', 'aa_add_affiliate_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="aff_name">Affiliate Name</label></th>
                        <td><input type="text" id="aff_name" name="affiliate_name" required /></td>
                    </tr>
                    <tr>
                        <th><label for="aff_email">Affiliate Email</label></th>
                        <td><input type="email" id="aff_email" name="affiliate_email" required /></td>
                    </tr>
                    <tr>
                        <th><label for="aff_commission">Commission Rate (%)</label></th>
                        <td><input type="number" id="aff_commission" name="affiliate_commission" min="0" max="100" /></td>
                    </tr>
                </table>
                <?php submit_button('Add Affiliate'); ?>
            </form>

            <h2>Affiliates List</h2>
            <?php $this->display_affiliates(); ?>
        </div>
        <?php
    }

    public function handle_add_affiliate() {
        if(!isset($_POST['aa_add_affiliate_nonce']) || !wp_verify_nonce($_POST['aa_add_affiliate_nonce'], 'aa_add_affiliate_action')) {
            wp_die('Security check failed');
        }

        if(!current_user_can('manage_options')) {
            wp_die('Not allowed');
        }

        $name = sanitize_text_field($_POST['affiliate_name']);
        $email = sanitize_email($_POST['affiliate_email']);
        $commission = isset($_POST['affiliate_commission']) && is_numeric($_POST['affiliate_commission']) ? floatval($_POST['affiliate_commission']) : null;

        if(empty($name) || empty($email) || !is_email($email)) {
            wp_redirect(admin_url('admin.php?page=autoaffiliate_manager&error=1'));
            exit;
        }

        $affiliates = get_option('aa_affiliates', []);

        $affiliates[] = array(
            'name' => $name,
            'email' => $email,
            'commission' => $commission,
            'joined' => current_time('mysql')
        );

        update_option('aa_affiliates', $affiliates);

        wp_redirect(admin_url('admin.php?page=autoaffiliate_manager&success=1'));
        exit;
    }

    private function display_affiliates() {
        $affiliates = get_option('aa_affiliates', []);
        if(empty($affiliates)) {
            echo '<p>No affiliates added yet.</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Name</th><th>Email</th><th>Commission (%)</th><th>Date Joined</th></tr></thead><tbody>';

        foreach($affiliates as $affiliate) {
            $comm = isset($affiliate['commission']) && $affiliate['commission'] !== null ? esc_html($affiliate['commission']) : esc_html(get_option($this->option_name)['commission_rate'] ?? '10');
            echo '<tr>'; 
            echo '<td>' . esc_html($affiliate['name']) . '</td>';
            echo '<td>' . esc_html($affiliate['email']) . '</td>';
            echo '<td>' . $comm . '</td>';
            echo '<td>' . esc_html($affiliate['joined']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}

new AutoAffiliateManager();
