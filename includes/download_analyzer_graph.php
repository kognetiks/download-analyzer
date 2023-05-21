<?php
/**
 * Download Analyzer for WordPress - Graph Support
 *
 * This file contains the code for the Download Analyzer Graphs.
 *
 * @package download-analyzer
 */

 function download_analyzer_render_chart($downloads_data) {

    $table = "";

    // Generate a unique UUID
    $downloadsChartID = generateUUID();

    // Chart the data
    $table .= "<h2>Chart Detail</h2>";
    $table .= "<div class='download-analyzer-chart-container'><canvas id='" . $downloadsChartID . "'></canvas></div>";

    $chart_data = array(
        'labels' => array_keys($downloads_data),
        'datasets' => array(
            array(
                'label' => 'Downloads',
                'data' => array_values($downloads_data),
                'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 1
            )
        )
    );

    // Chart data results
    $chart_data_json = json_encode($chart_data);

    // Chart the data
    $chart_js = <<<EOT
    <script>
    jQuery(document).ready(function() {
        // var ctx = document.getElementById('downloadsChart').getContext('2d');
        var ctx = document.getElementById('{$downloadsChartID}').getContext('2d');
        var chartData = {$chart_data_json};
        var downloadsChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
    </script>
    EOT;

    // Append the chart to the results table
    $table = $table . $chart_js;

    return $table;
    
}


// Generate a random UUIS
function generateUUID() {
    $data = random_bytes(16);
    assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}