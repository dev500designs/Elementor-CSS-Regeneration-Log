<?php
/**
 * Plugin Name: Elementor CSS Regeneration Log
 * Plugin URI: https://500designs.com
 * Description: A plugin that allow to Regenerate File and Data when an Elementor Template / Post is updated. It will log and display Elementor CSS regeneration events in the WordPress admin.
 * Version: 1.0.0
 * Author: Mauro Carrera
 * Author URI: https://500designs.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: elementor-css-regeneration-log
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
// Add the action hook for Elementor
add_action('elementor/editor/after_save', 'qcwp_regenerate_css', 10, 2);

// Add the action hook for the custom admin page
add_action('admin_menu', 'qcwp_elementor_log_admin_page');

// Function to create the custom admin page
function qcwp_elementor_log_admin_page() {
    add_menu_page(
        'CSS Reg Log',
        'CSS Reg Log',
        'manage_options',
        'elementor-log',
        'qcwp_display_elementor_log_page'
    );
}
    

// Function to display the custom admin page
function qcwp_display_elementor_log_page() {
    // Check for clear log action
    if (isset($_POST['clear_log']) && check_admin_referer('clear_elementor_log')) {
        qcwp_clear_elementor_log();
        echo '<div class="notice notice-success is-dismissible" style="margin-top:10px;margin-bottom:10px;margin-left:0px"><p>Log has been cleared.</p></div>';
    }


    $log_file = plugin_dir_path(__FILE__) . 'elementor_log.txt';
    $log_entries = array();

    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        $log_lines = explode(PHP_EOL, trim($log_content));

        foreach ($log_lines as $line) {
            $entry_parts = explode(' - ', $line, 2);
            $log_entries[] = array(
                'timestamp' => $entry_parts[0],
                'message' => $entry_parts[1]
            );
        }

        echo '<h2 style="text-align:center;">Elementor CSS Regeneration Logs</h2>';
          echo '<p style="text-align:center;">This log will save the latest 50 CSS Regenerations and will purge automatically.</p>';

        // Sort log entries in descending order (latest to oldest)
        usort($log_entries, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Limit the number of displayed log entries
        $log_entries = array_slice($log_entries, 0, 10);

        // Display clear log button
        echo '<form method="post" style="margin-bottom: 1rem;">';
        wp_nonce_field('clear_elementor_log');
        echo '<input type="submit" name="clear_log" class="button" value="Clear Log" style="margin-top:20px;margin-bottom:20px;">';
        echo '</form>';

 // Display log in a table
// Display log in a table
echo '<table class="widefat">';
echo '<thead><tr><th>Timestamp</th><th>Message</th><th>Post ID</th><th>Post Title</th><th>User</th></tr></thead>';
echo '<tbody>';

foreach ($log_entries as $entry) {
    $entry_parts = explode(' - ', $entry['message'], 3); // Split the message into separate parts
    $message = $entry_parts[0];
    $post_info_parts = explode(' | ', $entry_parts[1]); // Split the post and user information

    $post_id = substr($post_info_parts[0], 8); // Remove the "Post ID: " prefix
    $post_title = substr($post_info_parts[1], 12); // Remove the "Post Title: " prefix
    $user_display_name = substr($post_info_parts[2], 6); // Remove the "User: " prefix

    echo '<tr>';
       echo '<tr>';
    echo '<td>' . esc_html($entry['timestamp']) . '</td>';
    echo '<td>' . esc_html($message) . '</td>';
    echo '<td>' . esc_html($post_id) . '</td>';
    echo '<td>' . esc_html($post_title) . '</td>';
    echo '<td>' . esc_html($user_display_name) . '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';


    } else {
        echo '<p>No log entries found.</p>';
    }
}

// Function to clear the log
function qcwp_clear_elementor_log() {
    $log_file = plugin_dir_path(__FILE__) . 'elementor_log.txt';
    file_put_contents($log_file, '');
}

function qcwp_regenerate_css($post_id) {
    // Make sure that Elementor loaded and the hook fired
    if (did_action('elementor/loaded')) {
        // Automatically purge and regenerate the Elementor CSS cache
        \Elementor\Plugin::instance()->files_manager->clear_cache();
        \Elementor\Plugin::instance()->posts_css_manager->clear_cache();

        // Get the current user ID
        $user_id = get_current_user_id();

        // Log the event with the post ID and user ID
        qcwp_log_elementor_event('CSS regenerated', $post_id, $user_id);
    }
}

function qcwp_log_elementor_event($message, $post_id, $user_id) {
    $log_file = plugin_dir_path(__FILE__) . 'elementor_log.txt';
    $current_time = date('Y-m-d H:i:s');

    // Get the post title and user display name
    $post_title = get_the_title($post_id);
    $user = get_user_by('id', $user_id);
    $user_display_name = $user->display_name;

    // Format the log message with the post ID, title, and user display name
    $log_message = "{$current_time} - {$message} - Post ID: {$post_id} | Post Title: {$post_title} | User: {$user_display_name}" . PHP_EOL;

    $log_entries = array();

    // Read existing log entries
    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        $log_lines = explode(PHP_EOL, trim($log_content));

        foreach ($log_lines as $line) {
            $log_entries[] = $line;
        }
    }

    // Limit the log entries to 50 (excluding the new entry)
    $log_entries = array_slice($log_entries, -49);

    // Add the new entry to the log entries
    array_unshift($log_entries, $log_message);

    // Write the limited log entries back to the log file
    file_put_contents($log_file, implode(PHP_EOL, $log_entries));
}

