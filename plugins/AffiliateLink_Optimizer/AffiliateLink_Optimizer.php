/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Optimizer.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Optimizer
 * Description: Auto-tracks, shortens, and optimizes affiliate links with reports to boost affiliate revenue.
 * Version: 1.0
 * Author: YourName
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateLinkOptimizer {
    private $option_name = 'alo_affiliate_links';

    public function __construct(){
        add_action('init', array($this, 'handle_redirect'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_alo_add_link', array($this, 'add_link'));
        add_action('admin_post_alo_delete_link', array($this, 'delete_link'));
    }

    public function get_links(){
        $links = get_option($this->option_name, array());
        if (!is_array($links)) $links = array();
        return $links;
    }

    public function save_links($links){
        update_option($this->option_name, $links);
    }

    // Handle affiliate redirect
    public function handle_redirect(){
        if (!isset($_GET['alo'])) return;
        $key = sanitize_text_field($_GET['alo']);
        $links = $this->get_links();
        if (!isset($links[$key])) {
            wp_die('Invalid affiliate link');
        }
        $links[$key]['clicks'] = isset($links[$key]['clicks']) ? $links[$key]['clicks'] + 1 : 1;
        $this->save_links($links);
        wp_redirect(esc_url_raw($links[$key]['target_url']), 302);
        exit;
    }

    // Admin menu
    public function admin_menu(){
        add_menu_page('AffiliateLink Optimizer', 'AffiliateLink Optimizer', 'manage_options', 'affiliate-link-optimizer', array($this, 'admin_page'), 'dashicons-admin-links', 80);
    }

    public function admin_page(){
        if (!current_user_can('manage_options')) return;
        $links = $this->get_links();
        ?>
        <div class="wrap">
          <h1>AffiliateLink Optimizer</h1>
          <h2>Add New Affiliate Link</h2>
          <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="alo_add_link">
            <?php wp_nonce_field('alo_add_link_verify'); ?>
            <table class="form-table">
              <tr>
                <th><label for="name">Name (identifier)</label></th>
                <td><input type="text" name="name" id="name" required></td>
              </tr>
              <tr>
                <th><label for="url">Affiliate URL</label></th>
                <td><input type="url" name="url" id="url" required></td>
              </tr>
            </table>
            <?php submit_button('Add Link'); ?>
          </form>
          <h2>Managed Affiliate Links</h2>
          <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Name</th><th>Affiliate URL</th><th>Clicks</th><th>Short Link</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if(empty($links)){ ?>
                <tr><td colspan="5">No affiliate links added.</td></tr>
              <?php } else {
                foreach ($links as $key => $link) { ?>
                  <tr>
                    <td><?php echo esc_html($key); ?></td>
                    <td><a href="<?php echo esc_url($link['target_url']); ?>" target="_blank"><?php echo esc_html($link['target_url']); ?></a></td>
                    <td><?php echo isset($link['clicks']) ? intval($link['clicks']) : 0; ?></td>
                    <td><input type="text" readonly value="<?php echo esc_url(home_url('?alo=' . urlencode($key))); ?>" style="width:100%;"></td>
                    <td>
                      <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" onsubmit="return confirm('Delete this link?');">
                        <input type="hidden" name="action" value="alo_delete_link">
                        <?php wp_nonce_field('alo_delete_link_verify'); ?>
                        <input type="hidden" name="name" value="<?php echo esc_attr($key); ?>">
                        <input type="submit" class="button button-danger" value="Delete">
                      </form>
                    </td>
                  </tr>
                <?php } } ?>
            </tbody>
          </table>
        </div>
        <?php
    }

    // Add new link handler
    public function add_link(){
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('alo_add_link_verify');
        $name = sanitize_key($_POST['name']);
        $url = esc_url_raw($_POST['url']);
        if (!$name || !$url) {
            wp_redirect(admin_url('admin.php?page=affiliate-link-optimizer&error=invalid_input'));
            exit;
        }
        $links = $this->get_links();
        if (isset($links[$name])) {
            wp_redirect(admin_url('admin.php?page=affiliate-link-optimizer&error=name_exists'));
            exit;
        }
        $links[$name] = array('target_url' => $url, 'clicks' => 0);
        $this->save_links($links);
        wp_redirect(admin_url('admin.php?page=affiliate-link-optimizer&success=1'));
        exit;
    }

    // Delete link handler
    public function delete_link(){
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('alo_delete_link_verify');
        $name = sanitize_key($_POST['name']);
        $links = $this->get_links();
        if (isset($links[$name])) {
            unset($links[$name]);
            $this->save_links($links);
        }
        wp_redirect(admin_url('admin.php?page=affiliate-link-optimizer&deleted=1'));
        exit;
    }
}

new AffiliateLinkOptimizer();