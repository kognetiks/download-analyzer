<?php
/**
 * Download Analzyer for WordPress - Shortcode Registration
 *
 * This file contains the code for registering the shortcode used
 * to display the Download Analyzer on the website.
 *
 * @package download-analyzer
 */

function download_analyzer_shortcode( $atts = array() ) {

    // Set default Parameters
    $atts = shortcode_atts(array(
        'slug' => 'null',
        'type' => 'Plugin' // Either 'Plugin' or 'Theme'
    ), $atts);

    ob_start(); // Start output buffering

    ?>
    <div id="download_analyzer">
        <div id="-analyzer-header">
            <div id="download-analyzer-stats-table" class="stats-body">
                <!-- <div><h1>SHORT CODE DEMO <?php echo $atts['slug']; ?> <?php echo $atts['type']; ?></h1></div> -->
                <?php echo download_analyzer($atts); ?>
            </div>
        </div>
    <?php

    return ob_get_clean(); // Return the buffered output
}
add_shortcode( 'download_stats', 'download_analyzer_shortcode' );