<?php
/**
 * Plugin Name: Download Analyzer
 * Plugin URI:  https://github.com/kognetiks/download-analyzer
 * Description: A simple plugin to display plugin and theme downloads statistics from the WordPress API.
 * Version:     1.0.0
 * Author:      Kognetiks.com
 * Author URI:  https://www.kognetiks.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation. You may NOT assume
 * that you can use any other version of the GPL.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Download Analyzer. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 * 
 */

// Enqueue all styles
function download_analyzer_enqueue_all_styles() {
    // Always enqueue dashicons and the plugin's style.css
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style('download-analyzer-styles', plugin_dir_url(__FILE__) . 'assets/css/download_analyzer_style.css');

    // If we're on the Download Analyzer settings page or the front-end, enqueue extra styles
    $hook = isset($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : false;
    if ( $hook === 'settings_page_download-analyzer-settings' || !is_admin() ) {
        wp_register_style('download-analyzer-extra-style', false);
        wp_enqueue_style('download-analyzer-extra-style');

        $custom_css = "
            .download-button-container {
                display: flex;
                gap: 8px;
                margin-bottom: 16px;
            }
            .download-analyzer-button {
                border-radius: 4px;
            }
        ";

        wp_add_inline_style('download-analyzer-extra-style', $custom_css);

    }
}
add_action('wp_enqueue_scripts', 'download_analyzer_enqueue_all_styles');
add_action('admin_enqueue_scripts', 'download_analyzer_enqueue_all_styles');


// Chart Support for the admin page
function download_analyzer_enqueue_scripts($hook) {
    // Check if we're on the Download Analyzer settings page
    if ($hook === 'settings_page_download-analyzer-settings' || $hook === 'index.php') {
        // Enqueue the required scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array('jquery'), false, true);
        wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js', array('chartjs'), false, true);
    }
}
add_action('admin_enqueue_scripts', 'download_analyzer_enqueue_scripts');


// Add Chart Support for the frontend/shortcode
function download_analyzer_enqueue_frontend_scripts() {
    // Enqueue the required scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array('jquery'), false, true);
    wp_enqueue_script('chartjs-adapter-date-fns', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js', array('chartjs'), false, true);
    }
add_action('wp_enqueue_scripts', 'download_analyzer_enqueue_frontend_scripts');


// Add link to Download Analyzer options - setting page
function download_analyzer_plugin_action_links($links) {
    $settings_link = '<a href="../admin/options-general.php?page=download-analyzer-settings">' . __('Settings', 'download_analyzer') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'download_analyzer_plugin_action_links');

// Settings
include plugin_dir_path(__FILE__) . 'includes/download_analyzer_settings.php';

// Results
include plugin_dir_path(__FILE__) . 'includes/download_analyzer_results.php';

// Graph
include plugin_dir_path(__FILE__) . 'includes/download_analyzer_graph.php';

// Dashboard
include plugin_dir_path(__FILE__) . 'includes/download_analyzer_dashboard.php';

// Shortcode
include plugin_dir_path(__FILE__) . 'includes/download_analyzer_shortcode.php';