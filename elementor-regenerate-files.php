<?php
/**
 * Plugin Name: Elementor Log
 * Plugin URI: https://500designs.com
 * Description: This plugin logs Elementor CSS cache regeneration events and displays them in the WordPress admin panel.
 * Version: 1.0.0
 * Author: Mauro Carrera
 * Author URI: https://500designs.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Add action to create the admin menu item
add_action('admin_menu', 'elementor_log_menu');

function elementor_log_menu() {
    // Add a new menu item under the Tools menu
    add_management_page('Elementor Log', 'Elementor Log', 'manage_options', 'elementor-log', 'elementor_log_page');
}

function elementor_log_page() {
    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Set the log file path
    $log_file = WP_CONTENT_DIR . '/elementor_log.txt';

    // Read the log file content
    $log_content = file_get_contents($log_file);

    // Split the content into lines
    $log_entries = explode(PHP_EOL, trim($log_content));

    // Start output buffering
    ob_start();
    ?>
    <div class="wrap">
        <h1>Elementor Log</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col">Timestamp</th>
                    <th scope="col">Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($log_entries as $entry) : ?>
                    <?php
                    // Split the entry into timestamp and message
                    list($timestamp, $message) = explode('] ', $entry, 2);
                    $timestamp = trim($timestamp, '[');
                    ?>
                    <tr>
                        <td><?php echo $timestamp; ?></td>
                        <td><?php echo $message; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    // End output buffering and display the content
    echo ob_get_clean();
}

// Add the logging functionality to the Elementor save action
add_action('elementor/editor/after_save', 'qcwp_regenerate_css', 10, 2);
function qcwp_regenerate_css($post_id, $editor_data){
    // Make sure that Elementor loaded and the hook fired
    if ( did_action( 'elementor/loaded' ) ) {
        // Automatically purge and regenerate the Elementor CSS cache
        \Elementor\Plugin::instance()->files_manager->clear_cache();
        \Elementor\Plugin::instance()->posts_css_manager->clear_cache();

        // Get the post title
        $post = get_post($post_id);
        $post_title = $post->post_title;

        // Log the event
        log_elementor_event("Elementor CSS cache regenerated for the page: {$post_title}");
    }
}

function log_elementor_event($message) {
    // Set the log file path
    $log_file = WP_CONTENT_DIR . '/elementor_log.txt';

    // Read the current log entries
    $log_entries = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Add the new entry
$log_entries[] = '[' . date('Y-m-d H:i:s') . '] ' . $message;

// Keep only the last 50 entries
$log_entries = array_slice($log_entries, -50);

// Save the updated log entries
file_put_contents($log_file, implode(PHP_EOL, $log_entries) . PHP_EOL);
}



