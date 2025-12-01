/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Vault.php
*/
<?php
/**
 * Plugin Name: WP Affiliate Vault
 * Description: Securely store, manage, and share affiliate links with tracking.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_AFFILIATE_VAULT_VERSION', '1.0');

class WPAffiliateVault {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Affiliate Vault',
            'Affiliate Vault',
            'manage_options',
            'affiliate-vault',
            array($this, 'plugin_settings_page'),
            'dashicons-admin-links',
            60
        );
    }

    public function settings_init() {
        register_setting('affiliateVault', 'affiliate_vault_settings');

        add_settings_section(
            'affiliateVault_section',
            __('Manage Your Affiliate Links', 'affiliateVault'),
            null,
            'affiliateVault'
        );

        add_settings_field(
            'affiliate_links',
            __('Affiliate Links', 'affiliateVault'),
            array($this, 'affiliate_links_render'),
            'affiliateVault',
            'affiliateVault_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('affiliate_vault_settings');
        $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();
        echo '<div id="affiliate-links-container">';
        foreach ($links as $index => $link) {
            echo '<div class="affiliate-link-item">
                    <input type="text" name="affiliate_vault_settings[affiliate_links][' . $index . '][url]" value="' . esc_attr($link['url']) . '" placeholder="Affiliate URL" style="width: 70%;" />
                    <input type="text" name="affiliate_vault_settings[affiliate_links][' . $index . '][label]" value="' . esc_attr($link['label']) . '" placeholder="Label" style="width: 20%;" />
                    <button type="button" class="remove-link button">Remove</button>
                  </div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-link" class="button">Add Link</button>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById("add-link").addEventListener("click", function() {
                    var container = document.getElementById("affiliate-links-container");
                    var index = container.children.length;
                    var div = document.createElement("div");
                    div.className = "affiliate-link-item";
                    div.innerHTML = 
                        `<input type="text" name="affiliate_vault_settings[affiliate_links][${index}][url]" placeholder="Affiliate URL" style="width: 70%;" />
                         <input type="text" name="affiliate_vault_settings[affiliate_links][${index}][label]" placeholder="Label" style="width: 20%;" />
                         <button type="button" class="remove-link button">Remove</button>`;
                    container.appendChild(div);
                });
                document.addEventListener("click", function(e) {
                    if (e.target && e.target.classList.contains("remove-link")) {
                        e.target.parentElement.remove();
                    }
                });
            });
        </script>';
    }

    public function plugin_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('affiliateVault');
                do_settings_sections('affiliateVault');
                submit_button('Save');
                ?>
            </form>
        </div>
        <?php
    }
}

new WPAffiliateVault();
?>