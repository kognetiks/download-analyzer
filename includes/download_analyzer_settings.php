<?php
/**
 * Download Analyzer for WordPress - Settings Page
 *
 * This file contains the code for the Download Analyzer Settings page.
 * It allows users to configure the Slug and other parameters.
 *
 * @package download-analyzer
 */

// Add the settings page to the WordPress admin menu.
function download_analyzer_menu() {
    add_options_page(
        'Download Analyzer Options',
        'Download Analyzer',
        'manage_options',
        'download-analyzer-settings',
        'download_analyzer_options_page'
    );
}
add_action('admin_menu', 'download_analyzer_menu');


// Download Analyszer Options
function download_analyzer_options_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'options';

    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-admin-plugins"></span> Download Analyzer Settings</h1>

        <!-- Message Box - Ver 1.0.0 -->
        <div id="message-box-container"></div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const downloadAnalyzerSettingsForm = document.getElementById('download-analyzer-setting-form');
                // Read the start status
                const downloadAnalyzerReminderCount = localStorage.getItem('downloadAnalyzerReminderCount') || 0;

                if (downloadAnalyzerReminderCount < 5) {
                    const messageBox = document.createElement('div');
                    messageBox.id = 'rateReviewMessageBox';
                    messageBox.innerHTML = `
                    <div id="rateReviewMessageBox" style="background-color: white; border: 1px solid black; padding: 10px; position: relative;">
                        <div class="message-content" style="display: flex; justify-content: space-between; align-items: center;">
                            <span><b>Are you enjoying the enhanced experience provided by our plugin?</b> Your input is incredibly valuable! Please click here to leave a <a href="https://wordpress.org/support/plugin/download-analyzer/reviews/" target="_blank">rating and review</a> for our plugin. It won't take long, but your feedback helps us improve and grow. <b>Thank you for being an essential part of our journey!<b></span>
                            <button id="closeMessageBox" class="dashicons dashicons-dismiss" style="background: none; border: none; cursor: pointer; outline: none; padding: 0; margin-left: 10px;"></button>
                            
                        </div>
                    </div>
                    `;

                    document.querySelector('#message-box-container').insertAdjacentElement('beforeend', messageBox);

                    document.getElementById('closeMessageBox').addEventListener('click', function() {
                        messageBox.style.display = 'none';
                        localStorage.setItem('downloadAnalyzerReminderCount', parseInt(downloadAnalyzerReminderCount, 10) + 1);
                    });
                }
            });
        </script>

        <h2 class="nav-tab-wrapper">
            <a href="?page=download-analyzer-settings&tab=options" class="nav-tab <?php echo $active_tab == 'options' ? 'nav-tab-active' : ''; ?>">Options</a>
            <a href="?page=download-analyzer-settings&tab=results" class="nav-tab <?php echo $active_tab == 'results' ? 'nav-tab-active' : ''; ?>">Results</a>
            <a href="?page=download-analyzer-settings&tab=support" class="nav-tab <?php echo $active_tab == 'support' ? 'nav-tab-active' : ''; ?>">Support</a>
        </h2>
        <form id="download-analyzer-setting-form" method="post" action="options.php">
            <?php
            if ($active_tab == 'options') {
                settings_fields('download_analyzer_options');
                do_settings_sections('download-analyzer-settings');
            } elseif ($active_tab == 'results') {
                echo download_analyzer(); // Display the results here
            } elseif ($active_tab == 'support') {
                settings_fields('download_analyzer_support');
                do_settings_sections('download_analyzer_support');
            }
            if ($active_tab !== 'results') {
                submit_button();
            } else {
                submit_button('Refresh Results', 'primary', 'submit', true, array('id' => 'refresh-results-button'));
                echo '<script>
                document.getElementById("refresh-results-button").addEventListener("click", function(event) {
                    event.preventDefault();
                    location.reload();
                });
                </script>';
            }
            ?>
        </form>
    </div>
    <?php
}


// Handle form submission
function download_analyzer_form_submit() {
    // Check for and process the form data
    if (isset($_POST['download_analyzer_options'])) {
        update_option('download_analyzer_options', $_POST['download_analyzer_options']);
    }

    // Redirect to the options page with the 'updated' query parameter
    $redirect_url = add_query_arg(array(
        'page' => 'download-analyzer-settings',
        'updated' => 'true'
    ), admin_url('options-general.php'));

    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_download_analyzer_form_submit', 'download_analyzer_form_submit');


// Register and define the plugin settings.
function download_analyzer_settings() {
    register_setting(
        'download_analyzer_options',
        'download_analyzer_options',
        'download_analyzer_options_validate'
    );

    add_settings_section(
        'download_analyzer_main',
        'Main Settings',
        'download_analyzer_section_text',
        'download-analyzer-settings'
    );
    

    add_settings_field(
        'download_analyzer_slug',
        'Plugin Slug',
        'download_analyzer_setting_slug',
        'download-analyzer-settings',
        'download_analyzer_main'
    );

    add_settings_field(
        'download_analyzer_analysis_type',
        'Analysis Type',
        'download_analyzer_setting_analysis_type',
        'download-analyzer-settings',
        'download_analyzer_main'
    );

    // Support settings tab
    register_setting('download-analyzer-support', 'download_analyzer_support_key');

    add_settings_section(
        'download_analyzer_support_section',
        'Support',
        'download_analyzer_support_callback_section',
        'download_analyzer_support'
    );

}
add_action('admin_init', 'download_analyzer_settings');


// Display the section text.
function download_analyzer_section_text() {
    echo '<p>Enter the plugin or theme slug to display its downloads statistics:</p>';
}


// Setting Slug
function download_analyzer_setting_slug() {
    $default_options = array('slug' => '');
    $options = get_option('download_analyzer_options', $default_options);
    echo "<input id='download_analyzer_slug' name='download_analyzer_options[slug]' size='40' type='text' value='{$options['slug']}' />";
}


// Validate and snitize input
function download_analyzer_options_validate($input) {
    $newinput['slug'] = sanitize_text_field($input['slug']);
    $newinput['analysis_type'] = in_array($input['analysis_type'], array('Plugin', 'Theme')) ? $input['analysis_type'] : 'Plugin';
    return $newinput;
}


// Set the type of analysis: plugin or theme
function download_analyzer_setting_analysis_type() {
    $default_options = array('analysis_type' => 'Plugin');
    $options = get_option('download_analyzer_options', $default_options);
    $analysis_type = isset($options['analysis_type']) ? $options['analysis_type'] : 'Plugin';
    ?>
    <select id='download_analyzer_analysis_type' name='download_analyzer_options[analysis_type]'>
        <option value='Plugin' <?php selected($analysis_type, 'Plugin'); ?>>Plugin</option>
        <option value='Theme' <?php selected($analysis_type, 'Theme'); ?>>Theme</option>
    </select>
    <?php
}


// Support settings section callback
function download_analyzer_support_callback_section($args) {
    ?>
    <div>
	<h3>Description</h3>
    <p>Download Analyzer is a user-friendly plugin designed for developers and creators who need an efficient way to track daily downloads of plugins and themes.</p>
    <p>This plugin presents a valuable opportunity to monitor adoption rates for free plugins or themes. It is based on the principle that the number of daily downloads, indicated by how often the zip file has been downloaded, is a strong measure of popularity and acceptance.</p>
    <p>The Download Analyzer makes tracking easy by displaying both summary and detailed data, including graphical charts that represent the daily downloads of plugins and themes. This data is conveniently displayed on your WordPress site's Dashboard, allowing for easy access and review.</p>
    <p>For more in-depth insights, detailed information is also available on the Results tab of the Plugin's Settings page. This feature enables developers and creators to dive deeper into the analytics of their products.</p>
    <p>One of the standout features of the Download Analyzer is its ability to export the daily download history of a plugin or theme into a CSV file. This feature empowers developers and creators to carry out further analysis, helping to drive decision making and strategy planning.</p>
    <p>In sum, Download Analyzer is an essential tool for WordPress developers and creators aiming to monitor and analyze the popularity and reach of their plugins and themes effectively. It's a must-have for any SEO-focused strategy, ensuring that your WordPress creations are reaching their intended audience and delivering on their potential.</p>
    <h3>Official Sites:</h3>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;"> 
    <li><a href="https://kognetiks.com/wordpress-plugins/download-analyzer/" rel="nofollow ugc" target="_blank">Kognetiks.com</a></li>
    <li><a href="https://github.com/kognetiks/download-analyzer" target="_blank">https://github.com/kognetiks/download-analyzer</a></li>
    <li><a href="https://wordpress.org/plugins/download-analyzer/" target="_blank">https://wordpress.org/plugins/download-analyzer/</a></li>
    </ul>
    <h3>Features</h3>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;">
    <li>Works with Plugins and Themes</li>
    <li>Results displayed on the Dashboard include Summary Data and a Chart</li>
    <li>Results tab in Setting shows Version Data, Summary Data, Detail Data and a Chart</li>
    <li>Version Data includes the percentage by Version</li>
    <li>Summary Data includes Today, Yesterday, Last week, All time</li>
    <li>Detail Data is by Day with Download Count</li>
    </ul>
    <h3>Getting Started</h3>
    <ol>
    <li>Install and activate the plugin.</li>
    <li>Navigate to the settings page (Settings > Options) and enter your slug of the plugin or theme.</li>
    <li>Be sure to select the correct Analysis Type for either "Plugin" or "Theme" as appropriate.</li>
    </ol>
    <h2>Installation</h2>
	<ol>
    <li>Upload the &#8216;download-analyzer&#8217; folder to the &#8216;/wp-content/plugins/&#8217; directory.</li>
    <li>Activate the plugin through the &#8216;Plugins&#8217; menu in WordPress.</li>
    <li>Go to the &#8216;Settings &gt; Downaload Analyzer&#8217; page and enter your slug for the plugin or theme to monitor.</li>
    </ol>
    <h2>Frequently Asked Questions</h2>
	<ol>
    <li>What is the source of this daily download data for plugins and themes?</li>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;"><li>The data is obtained using the api endpoints described in the WordPress.org Documentation.  This documentation can be found at <a href="https://codex.wordpress.org/WordPress.org_API" target="_blank">https://codex.wordpress.org/WordPress.org_API</a>.</li></ul>
    <li>Is active installs available for plugins and themes?</li>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;"><li>Active installs is only available at this time for themes.</li></ul>
    <li>Can I customize the appearance of the results and chart?</li>
    <ul style="list-style-type: disc; list-style-position: inside; padding-left: 1em;"><li>Yes, the plugin comes with a default style, but you can easily customize the appearance the results and chart by editing the style.css file or adding custom CSS rules to your WordPress theme.</li></ul>
    </ol>
    </div>
    <?php
}