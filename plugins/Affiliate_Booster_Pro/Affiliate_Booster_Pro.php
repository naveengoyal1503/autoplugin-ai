/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Description: Manage and optimize affiliate marketing campaigns with automated tracking and payouts.
 * Version: 1.0
 * Author: Affiliate Booster Team
 */

if (!defined('ABSPATH')) exit;

class AffiliateBoosterPro {
    private static $instance = null;
    private $option_name = 'affiliate_booster_pro_options';
    private $affiliate_table;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->affiliate_table = $wpdb->prefix . 'abp_affiliates';

        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));

        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_abp_add_affiliate', array($this, 'handle_add_affiliate'));
        add_action('init', array($this, 'track_affiliate_referral'));

        add_shortcode('abp_affiliate_link', array($this, 'affiliate_link_shortcode'));
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$this->affiliate_table} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_email VARCHAR(100) NOT NULL,
            affiliate_code VARCHAR(50) NOT NULL UNIQUE,
            referrals INT DEFAULT 0,
            earnings DECIMAL(10,2) DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate_plugin() {
        // Optional: Cleanup actions on deactivation
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Booster',
            'Affiliate Booster',
            'manage_options',
            'affiliate-booster-pro',
            array($this, 'admin_page'),
            'dashicons-networking',
            30
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }
        global $wpdb;
        $affiliates = $wpdb->get_results("SELECT * FROM {$this->affiliate_table} ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Affiliate Booster Pro</h1>
            <h2>Add New Affiliate</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="abp_add_affiliate" />
                <?php wp_nonce_field('abp_add_affiliate_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="user_email">Affiliate Email</label></th>
                        <td><input name="user_email" type="email" id="user_email" required class="regular-text"/></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="affiliate_code">Affiliate Code</label></th>
                        <td><input name="affiliate_code" type="text" id="affiliate_code" required pattern="[a-zA-Z0-9_-]{4,20}" class="regular-text"/>
                        <p class="description">Unique code (4-20 chars): letters, numbers, underscore or dash</p>
                        </td>
                    </tr>
                </table>
                <input type="submit" class="button button-primary" value="Add Affiliate" />
            </form>
            <h2>Affiliates List</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Email</th><th>Code</th><th>Referrals</th><th>Earnings ($)</th><th>Registered</th></tr></thead>
                <tbody>
                    <?php if ($affiliates) {
                        foreach ($affiliates as $aff) {
                            echo '<tr>' .
                                '<td>' . esc_html($aff->user_email) . '</td>' .
                                '<td>' . esc_html($aff->affiliate_code) . '</td>' .
                                '<td>' . intval($aff->referrals) . '</td>' .
                                '<td>' . number_format(floatval($aff->earnings), 2) . '</td>' .
                                '<td>' . esc_html($aff->created_at) . '</td>' .
                                '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5">No affiliates found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_add_affiliate() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'abp_add_affiliate_nonce')) {
            wp_die('Nonce verification failed');
        }
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        $email = sanitize_email($_POST['user_email']);
        $code = sanitize_text_field($_POST['affiliate_code']);

        if (!is_email($email) || empty($code)) {
            wp_redirect(admin_url('admin.php?page=affiliate-booster-pro&msg=error')); exit;
        }

        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->affiliate_table} WHERE affiliate_code = %s", $code));
        if ($existing) {
            wp_redirect(admin_url('admin.php?page=affiliate-booster-pro&msg=code_exists')); exit;
        }

        $wpdb->insert(
            $this->affiliate_table,
            array('user_email' => $email, 'affiliate_code' => $code),
            array('%s', '%s')
        );

        wp_redirect(admin_url('admin.php?page=affiliate-booster-pro&msg=added'));
        exit;
    }

    public function track_affiliate_referral() {
        if (is_admin()) return;

        if (isset($_GET['ref'])) {
            $ref_code = sanitize_text_field($_GET['ref']);
            global $wpdb;

            $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->affiliate_table} WHERE affiliate_code = %s", $ref_code));
            if ($affiliate) {
                setcookie('abp_ref', $ref_code, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
            }
        } elseif (isset($_COOKIE['abp_ref'])) {
            $ref_code = sanitize_text_field($_COOKIE['abp_ref']);
        } else {
            return;
        }

        // Example: track conversion on purchase page (for demonstration)
        // In real usages, hook into order completion, payment confirmation, etc.
        if (is_page('thank-you-for-purchase')) {
            global $wpdb;

            $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->affiliate_table} WHERE affiliate_code = %s", $ref_code));
            if ($affiliate) {
                // Increment referrals and earnings
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$this->affiliate_table} SET referrals = referrals + 1, earnings = earnings + 10.00 WHERE id = %d",
                    $affiliate->id
                ));
                // Clear cookie after conversion
                setcookie('abp_ref', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
            }
        }
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts, 'abp_affiliate_link');
        if (empty($atts['code'])) return '';

        $url = home_url('/?ref=' . urlencode($atts['code']));
        return esc_url($url);
    }

}

AffiliateBoosterPro::get_instance();
