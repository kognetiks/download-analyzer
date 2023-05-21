<?php
/**
 * Download Analyzer for WordPress - Settings Page
 *
 * This file contains the code for the Download Analyzer Results page.
 *
 * @package download-analyzer
 */

if (!function_exists('download_analyzer')) {
    function download_analyzer($atts = array()) {

        // Get the options from the database
        $options = get_option('download_analyzer_options', array());
    
        // Default values
        $default_atts = array(
            'slug' => isset($options['slug']) ? $options['slug'] : 'null',
            'type' => isset($options['analysis_type']) ? $options['analysis_type'] : 'Plugin' // Either 'Plugin' or 'Theme'
        );
    
        // Merge the attributes with the default values
        $atts = shortcode_atts($default_atts, $atts);
    
        // Now use $atts['slug'] and $atts['type'] within this function
    
        // Diagnostics Switch
        $diagnostics = 'Off';
        
        // Set the Analysis Type: Plugin or Theme
        $options = get_option('download_analyzer_options');
        $analysis_type = isset($options['analysis_type']) ? $options['analysis_type'] : 'Plugin';

        $slug = $atts['slug'];
        $analysis_type = $atts['type'];
        
        if (empty($slug)) {
            return "<div><p><b>ERROR: Please set a slug for the Plugin or Theme downloads you wish to analyze.</b></p></div>";
        }
        
        if ($analysis_type == 'Plugin'){
            $url =          "https://api.wordpress.org/stats/plugin/1.0/downloads.php?slug={$slug}";
            $history_url =  "https://api.wordpress.org/stats/plugin/1.0/downloads.php?slug={$slug}&historical_summary=1";
            $api_url =  "https://api.wordpress.org/stats/plugin/1.0/?slug={$slug}";
        } elseif ($analysis_type == 'Theme'){
            $url = "https://api.wordpress.org/stats/themes/1.0/downloads.php?slug={$slug}";
            $history_url = "https://api.wordpress.org/stats/themes/1.0/downloads.php?slug={$slug}&historical_summary=1";
            // $history_url = "https://api.wordpress.org/stats/themes/1.0/downloads.php?slug={$slug}";
            $api_url =  "https://api.wordpress.org/themes/info/1.0/?slug={$slug}";
        }

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

        // Retrieve this history data
        $response = wp_remote_get($history_url);

        if (is_wp_error($response)) {
            return "Detail Error: " . $response->get_error_message();
        }

        $history_data = json_decode(wp_remote_retrieve_body($response), true);

        if (is_wp_error($history_data)) {
            return "History Error: " . $history_data->get_error_message();
        }

        // Retrieve the version data
        if ($analysis_type == 'Plugin') {
            $response = wp_remote_get($api_url);

            $version_data = json_decode(wp_remote_retrieve_body($response), true);

            if (is_wp_error($version_data)) {
                return "Version Error: " . $version_data->get_error_message();
            }
        } elseif ($analysis_type == 'Theme') {
            $response = wp_remote_post($api_url, array(
                'body' => array(
                    'action' => 'theme_information',
                    'request' => serialize((object)array(
                        'slug' => $slug,
                        'fields' => array(
                            'versions' => false, // Set this to true to get version details
                            'active_installs' => true, // Set this to true to get active installs
                        )
                    ))
                )
            ));

            if (!is_wp_error($response)) {
                $theme_details = unserialize(wp_remote_retrieve_body($response));
                if ($diagnostics == 'On') {
                    // Print the theme details, including the version details and active installs
                    echo '<pre>';
                    print_r($theme_details);
                    echo '</pre>';
                }
            } else {
                echo 'ERROR: An error occurred while fetching theme details.';
            }

        }

        // Analysis header
        $header = "";
        $header .= "<h1>Download Analyzer: {$slug}</h1>";

        // Refresh Data and Download link (only on admin page for now)
        $header .= '<div class="download-container">';
        $header .= '<a class="button button-primary download-button-special" href="' . esc_url(add_query_arg(array('settings-updated' => false))) . '">Refresh Results</a>';
        if (is_admin()) {
            $header .= " ";
            $header .= '<a class="button button-primary" href="' . esc_url(admin_url('admin-post.php?action=download_analyzer_download_csv')) . '">Download Data as CSV</a>';
        }
        $header .= "</div>";
        if (!is_admin()) {
            $header .= "<br>";
        }
        $header .= "<p><b>Type: {$analysis_type}</b></p>";

        if ($analysis_type == 'Plugin') {
            $slug_link = "https://wordpress.org/plugins/{$slug}/";
        } elseif ($analysis_type == 'Theme') {
            $slug_link = "https://wordpress.org/themes/{$slug}/";
        } else {
            return $header. "<div><p>Analysis Type Error</p></div>";
        }
        
        $header .= "<p><b>Wordpress {$analysis_type} may be found here: <a href='{$slug_link}' target='_blank'>{$slug}</a></b></p>";
        $header .= "<p>{$analysis_type} Details: <a href='{$slug_link}' target='_blank' rel='nofollow'>{$slug_link}</a></p>";
        $header .= "<p>{$analysis_type} Version: <a href='{$api_url}' target='_blank' rel='nofollow'>{$api_url}</a></p>";
        $header .= "<p>{$analysis_type} History: <a href='{$url}' target='_blank' rel='nofollow'>{$url}</a></p>";
        $header .= "<p>{$analysis_type} Summary: <a href='{$history_url}' target='_blank' rel='nofollow'>{$history_url}</a></p>";
        
        // Return no data available
        if (empty($downloads_data)) {
            return $header . "<div><p>Download Data: No data available.</p><p>{$history_url}</p></div>";
        }

        // Version data if available
        $table = "";
        $table .= "<h2>Version Data</h2>";
        $table .= "<div>";
        $table .= '<table class="download-analyzer-stats-table">';
        $table .= '<thead><tr><th>Version</th><th>% Downloads</th></tr></thead><tbody>';

        if ($analysis_type == 'Plugin') {
            foreach ($version_data as $version => $downloads) {
                $version_label = ucfirst(str_replace('_', ' ', $version));
                $table .= "<tr><td>{$version_label}</td><td>{$downloads}</td></tr>";
            }
        } elseif ($analysis_type == 'Theme') {
            $version = $theme_details->version; // Extracts version (4.1.3)
            $active_installs = $theme_details->active_installs; // Extracts active installs (1000000)
            $table .= "<tr><td>" . $version . "</td><td>" . $active_installs . "</td></tr>";
        }
        
        $table .= '</tbody></table>';
        $table .= '</div>';

        // Summary data if available
        $table .= "<h2>Summary Data</h2>";
        $table .= "<div>";
        $table .= '<table class="download-analyzer-stats-table">';
        $table .= '<thead><tr><th>Period</th><th>Downloads</th></tr></thead><tbody>';
        
        foreach ($history_data as $period => $downloads) {
            $period_label = ucfirst(str_replace('_', ' ', $period));
            $table .= "<tr><td>{$period_label}</td><td>{$downloads}</td></tr>";
        }
        
        $table .= '</tbody></table>';
        $table .= '</div>';

        // Detailed data if available
        $table .= "<h2>Detail Data</h2>";
        $table .= "<div>";
        $table .= '<table class="download-analyzer-stats-table">';
        $table .= '<thead><tr><th>Date</th><th>Downloads</th></tr></thead><tbody>';

        foreach ($downloads_data as $date => $downloads) {
            $table .= "<tr><td>{$date}</td><td>{$downloads}</td></tr>";
        }

        $table .= '</tbody></table>';
        $table .= '</div>';

        // Graph the data
        $chart_js = download_analyzer_render_chart($downloads_data);

        return $header . $table . $chart_js;
    }
    add_shortcode('download_analyzer', 'download_analyzer');


    // Download the data
    function download_analyzer_download_csv() {

        $default_options = array('slug' => '');
        $options = get_option('download_analyzer_options', $default_options);

        $slug = $options['slug'];
        $analysis_type = isset($options['analysis_type']) ? $options['analysis_type'] : 'Plugin';
        
        if (empty($slug)) {
            wp_die("Please set a slug for the Plugin or Theme Downloads you wish to analyze.");
        }
        
        if ($analysis_type == 'Plugin'){
            $url = "https://api.wordpress.org/stats/plugin/1.0/downloads.php?slug={$slug}";
        } elseif ($analysis_type == 'Theme'){
            $url = "https://api.wordpress.org/stats/themes/1.0/downloads.php?slug={$slug}";
        }

        // Retrieve the detailed data
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            wp_die("Slug Error: " . $response->get_error_message());
        }

        $downloads_data = json_decode(wp_remote_retrieve_body($response), true);

        if (is_wp_error($downloads_data)) {
            wp_die("Downloads Data Error: " . $downloads_data->get_error_message());
        }

        $csv_data = "Date,Downloads\n";
        foreach ($downloads_data as $date => $downloads) {
            $csv_data .= "{$date},{$downloads}\n";
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $slug . '_download_data.csv');
        echo $csv_data;
        exit;
    }
    add_action('admin_post_download_analyzer_download_csv', 'download_analyzer_download_csv');

}