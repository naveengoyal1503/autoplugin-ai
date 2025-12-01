/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Content Locker Pro
 * Description: Advanced content monetization with paywalls, email gates, and tiered access
 * Version: 1.0.0
 * Author: Content Monetization
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class SmartContentLockerPro {
    private $plugin_file = __FILE__;
    private $plugin_dir = __DIR__;
    private $db_version = '1.0';

    public function __construct() {
        register_activation_hook($this->plugin_file, array($this, 'activate'));
        register_deactivation_hook($this->plugin_file, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('the_content', array($this, 'apply_content_lock'));
        add_shortcode('content_locker', array($this, 'shortcode_content_locker'));
        add_action('wp_ajax_unlock_content', array($this, 'ajax_unlock_content'));
        add_action('wp_ajax_nopriv_unlock_content', array($this, 'ajax_unlock_content'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_locks = $wpdb->prefix . 'scl_content_locks';
        $table_unlocks = $wpdb->prefix . 'scl_user_unlocks';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_locks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            lock_type varchar(50) NOT NULL,
            lock_config longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $sql2 = "CREATE TABLE IF NOT EXISTS $table_unlocks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            post_id bigint(20) NOT NULL,
            unlock_method varchar(50) NOT NULL,
            unlock_value varchar(255),
            unlocked_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45),
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        
        update_option('smart_content_locker_db_version', $this->db_version);
        update_option('smart_content_locker_settings', array(
            'lock_enabled' => true,
            'email_subject' => 'Unlock Premium Content',
            'email_body' => 'Thank you for your interest. Click the link to unlock premium content.',
            'paywall_price' => 4.99,
            'currency' => 'USD'
        ));
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'Smart Content Locker',
            'Content Locker',
            'manage_options',
            'smart-content-locker',
            array($this, 'admin_dashboard'),
            'dashicons-lock'
        );
        
        add_submenu_page(
            'smart-content-locker',
            'Settings',
            'Settings',
            'manage_options',
            'smart-content-locker-settings',
            array($this, 'admin_settings')
        );
    }

    public function register_settings() {
        register_setting('smart_content_locker_group', 'smart_content_locker_settings');
    }

    public function admin_dashboard() {
        global $wpdb;
        $table_unlocks = $wpdb->prefix . 'scl_user_unlocks';
        $total_unlocks = $wpdb->get_var("SELECT COUNT(*) FROM $table_unlocks");
        
        echo '<div class="wrap"><h1>Smart Content Locker Dashboard</h1>';
        echo '<div class="notice notice-info"><p>Total Content Unlocks: ' . intval($total_unlocks) . '</p></div>';
        echo '</div>';
    }

    public function admin_settings() {
        $settings = get_option('smart_content_locker_settings');
        ?>
        <div class="wrap">
            <h1>Content Locker Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_content_locker_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="paywall_price">Paywall Price ($)</label></th>
                        <td><input type="number" step="0.01" name="smart_content_locker_settings[paywall_price]" value="<?php echo floatval($settings['paywall_price']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email_subject">Email Gate Subject</label></th>
                        <td><input type="text" name="smart_content_locker_settings[email_subject]" value="<?php echo esc_attr($settings['email_subject']); ?>" style="width: 100%;" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script('scl-frontend', plugins_url('assets/frontend.js', $this->plugin_file), array('jquery'));
        wp_enqueue_style('scl-frontend', plugins_url('assets/frontend.css', $this->plugin_file));
        wp_localize_script('scl-frontend', 'scl_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'smart-content-locker') !== false) {
            wp_enqueue_style('scl-admin', plugins_url('assets/admin.css', $this->plugin_file));
        }
    }

    public function apply_content_lock($content) {
        if (is_single() && !is_admin()) {
            global $post, $wpdb;
            $table_locks = $wpdb->prefix . 'scl_content_locks';
            
            $lock = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_locks WHERE post_id = %d LIMIT 1",
                $post->ID
            ));
            
            if ($lock) {
                $config = json_decode($lock->lock_config, true);
                if ($lock->lock_type === 'paywall') {
                    $preview = substr($content, 0, 200);
                    return $this->render_paywall_lock($preview, $config, $post->ID);
                }
            }
        }
        return $content;
    }

    public function shortcode_content_locker($atts, $content = null) {
        $atts = shortcode_atts(array(
            'type' => 'email',
            'price' => '4.99'
        ), $atts);
        
        if ($atts['type'] === 'email') {
            return $this->render_email_gate($content);
        } elseif ($atts['type'] === 'paywall') {
            return $this->render_paywall_lock($content, array('price' => $atts['price']));
        }
        return $content;
    }

    private function render_email_gate($content) {
        ob_start();
        ?>
        <div class="scl-email-gate">
            <div class="scl-gate-overlay"></div>
            <div class="scl-gate-form">
                <h3>Unlock Premium Content</h3>
                <p>Enter your email to access this exclusive content.</p>
                <form class="scl-email-form">
                    <input type="email" name="email" placeholder="your@email.com" required />
                    <button type="button" class="scl-unlock-btn" data-post-id="<?php echo get_the_ID(); ?>" data-method="email">Unlock Now</button>
                </form>
            </div>
            <div class="scl-content-preview"><?php echo wp_kses_post($content); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_paywall_lock($content, $config = array(), $post_id = 0) {
        if (empty($config)) {
            $settings = get_option('smart_content_locker_settings');
            $config = array('price' => $settings['paywall_price']);
        }
        if (empty($post_id)) {
            $post_id = get_the_ID();
        }
        
        ob_start();
        ?>
        <div class="scl-paywall">
            <div class="scl-preview"><?php echo wp_kses_post(substr($content, 0, 150)) . '...'; ?></div>
            <div class="scl-paywall-box">
                <h3>Premium Content</h3>
                <p class="scl-price">$<?php echo floatval($config['price']); ?></p>
                <button class="scl-unlock-btn" data-post-id="<?php echo intval($post_id); ?>" data-method="paywall">Unlock Full Article</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_unlock_content() {
        global $wpdb;
        $post_id = intval($_POST['post_id'] ?? 0);
        $method = sanitize_text_field($_POST['method'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');
        
        $table_unlocks = $wpdb->prefix . 'scl_user_unlocks';
        
        $wpdb->insert($table_unlocks, array(
            'post_id' => $post_id,
            'unlock_method' => $method,
            'unlock_value' => $value,
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'user_id' => get_current_user_id() ?: null
        ));
        
        wp_send_json_success(array('message' => 'Content unlocked!'));
    }
}

new SmartContentLockerPro();
?>