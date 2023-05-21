<?php
/**
 * Download Analyzer for WordPress - Dashboard Widget
 *
 * This file contains the code for the Download Analyzer Dashboard page.
 * It allows users to display high level statistcs.
 *
 * @package download-analyzer
 */

function download_analyzer_dashboard_widget_content() {

    // Fetch the downloads_data
    $default_options = array('slug' => '');
    $options = get_option('download_analyzer_options', $default_options);
    $slug = $options['slug'];

    // Diagnostics Switch
    $diagnostics = 'Off';

    // Set the Analysis Type: Plugin or Theme
    $options = get_option('download_analyzer_options');
    $analysis_type = isset($options['analysis_type']) ? $options['analysis_type'] : 'Plugin';
    
    if (empty($slug)) {
        return "Please set a slug for the Plugin or Theme downloads you wish to analyze.";
    }
    
    if ($analysis_type == 'Plugin'){
        $url = "https://api.wordpress.org/stats/plugin/1.0/downloads.php?slug={$slug}";
        $history_url = "https://api.wordpress.org/stats/plugin/1.0/downloads.php?slug={$slug}&historical_summary=1";
        $api_url = "https://api.wordpress.org/stats/plugin/1.0/?slug={$slug}";
    } elseif ($analysis_type == 'Theme'){
        $url = "https://api.wordpress.org/stats/themes/1.0/downloads.php?slug={$slug}";
        $history_url = "https://api.wordpress.org/stats/themes/1.0/downloads.php?slug={$slug}&historical_summary=1";
        $api_url = "https://api.wordpress.org/themes/info/1.0/";
    }

    // Retrieve the detailed data
    if ($analysis_type == 'Plugin') {
        $response = wp_remote_get($history_url);
    } elseif ($analysis_type == 'Theme') {
        $response = wp_remote_get($history_url);
    }

    if (is_wp_error($response)) {
        return "Slug Error: " . $response->get_error_message();
    }

    $downloads_data = json_decode(wp_remote_retrieve_body($response), true);

    $table = "";
    $table .= "<h1>{$slug}</h1>";

    $history_data = json_decode(wp_remote_retrieve_body($response), true);

    if (is_wp_error($history_data)) {
        return "History Error: " . $history_data->get_error_message();
    }

    // Summary data if available
    $table .= "<h2>Summary Data</h2>";
    $table .= "<div>";
    $table .= '<table id="dashboard_download_analyzer_dashboard_widget" class="download-analyzer-stats-table">';
    $table .= '<thead><tr><th>Period</th><th>Downloads</th></tr></thead><tbody>';
    
    foreach ($history_data as $period => $downloads) {
        $period_label = ucfirst(str_replace('_', ' ', $period));
        $table .= "<tr><td>{$period_label}</td><td>{$downloads}</td></tr>";
    }
    
    $table .= '</tbody></table>';
    $table .= '</div>';

    // Results
    echo $table;

    // Retrieve the detailed data
    if ($analysis_type == 'Plugin') {
        $response = wp_remote_get($url);
    } elseif ($analysis_type == 'Theme') {
        $response = wp_remote_get($url);
    }

    if (is_wp_error($response)) {
        return "Slug Error: " . $response->get_error_message();
    }

    $downloads_data = json_decode(wp_remote_retrieve_body($response), true);
    
    // Call the download_analyzer_render_chart function to generate the chart:
    $chart_js = download_analyzer_render_chart($downloads_data);

    // echo $summary_data . $chart_js;
    // echo "Downloads";
    echo $chart_js;

    // Add hover over to link to the Options page from the dashboard widget
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var widget_id = 'download_analyzer_dashboard_widget';
        var widget_header = $('#' + widget_id + ' h2.hndle');
        var widget_title = widget_header.text();
        var options_url = 'options-general.php?page=download-analyzer-settings'; // Update with your actual options page URL
        
        widget_header.hover(
            function() {
                $(this).html('Download Analyzer <a href="' + options_url + '">Settings</a>');
            }, 
            function() {
                $(this).text(widget_title);
            }
        );
    });
    </script>
    <?php

}


// Add Download Analyzer widget to the admin dashboard
function download_analyzer_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'download_analyzer_dashboard_widget', // Widget ID
        'Download Analyzer', // Widget title
        'download_analyzer_dashboard_widget_content' // Callback function to display the widget content
    );
}
add_action('wp_dashboard_setup', 'download_analyzer_add_dashboard_widget');