<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and display revenue from ads, affiliate links, digital products, and donations.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_save_revenue_entry', array($this, 'save_revenue_entry'));
        add_action('wp_ajax_get_revenue_data', array($this, 'get_revenue_data'));
        add_action('wp_ajax_delete_revenue_entry', array($this, 'delete_revenue_entry'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_admin_page'),
            'dashicons-chart-bar',
            6
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied.');
        }
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-app">
                <h2>Add Revenue Entry</h2>
                <form id="revenue-form">
                    <input type="hidden" id="entry-id" name="entry-id" value="">
                    <label>Source: <select id="source" name="source">
                        <option value="ads">Ads</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="products">Products</option>
                        <option value="donations">Donations</option>
                    </select></label>
                    <label>Amount: <input type="number" id="amount" name="amount" step="0.01" required></label>
                    <label>Date: <input type="date" id="date" name="date" required></label>
                    <button type="submit">Save</button>
                </form>
                <h2>Revenue Entries</h2>
                <table id="revenue-table">
                    <thead>
                        <tr><th>Source</th><th>Amount</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                function loadRevenueData() {
                    $.post(ajaxurl, {action: 'get_revenue_data'}, function(response) {
                        var tbody = $('#revenue-table tbody');
                        tbody.empty();
                        response.forEach(function(entry) {
                            tbody.append('<tr data-id="' + entry.id + '"><td>' + entry.source + '</td><td>$' + entry.amount + '</td><td>' + entry.date + '</td><td><button class="edit-btn">Edit</button> <button class="delete-btn">Delete</button></td></tr>');
                        });
                        $('.edit-btn').click(function() {
                            var row = $(this).closest('tr');
                            $('#entry-id').val(row.data('id'));
                            $('#source').val(row.find('td').eq(0).text());
                            $('#amount').val(row.find('td').eq(1).text().replace('$', ''));
                            $('#date').val(row.find('td').eq(2).text());
                        });
                        $('.delete-btn').click(function() {
                            var id = $(this).closest('tr').data('id');
                            if (confirm('Delete this entry?')) {
                                $.post(ajaxurl, {action: 'delete_revenue_entry', id: id}, function() {
                                    loadRevenueData();
                                });
                            }
                        });
                    });
                }
                $('#revenue-form').submit(function(e) {
                    e.preventDefault();
                    $.post(ajaxurl, {
                        action: 'save_revenue_entry',
                        id: $('#entry-id').val(),
                        source: $('#source').val(),
                        amount: $('#amount').val(),
                        date: $('#date').val()
                    }, function() {
                        $('#entry-id').val('');
                        $('#revenue-form').reset();
                        loadRevenueData();
                    });
                });
                loadRevenueData();
            });
        </script>
        <?php
    }

    public function save_revenue_entry() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $date = sanitize_text_field($_POST['date']);
        if ($id) {
            $wpdb->update($table, compact('source', 'amount', 'date'), array('id' => $id));
        } else {
            $wpdb->insert($table, compact('source', 'amount', 'date'));
        }
        wp_die();
    }

    public function get_revenue_data() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $data = $wpdb->get_results("SELECT * FROM $table ORDER BY date DESC", ARRAY_A);
        wp_send_json($data);
    }

    public function delete_revenue_entry() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $id = intval($_POST['id']);
        $wpdb->delete($table, array('id' => $id));
        wp_die();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'revenue_tracker';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            source varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            date date NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

$revenue_tracker = new WP_Revenue_Tracker();
register_activation_hook(__FILE__, array($revenue_tracker, 'activate'));