        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
        <script type="text/javascript">
            // Load Charts and the corechart package.
            google.charts.load('current', {'packages':['corechart']});

            // Draw the second chart when Charts is loaded.
            google.charts.setOnLoadCallback(drawChart);

            // Callback that draws the second chart.
            function drawChart() {

                // Create the data table.
                var data = google.visualization.arrayToDataTable([
                    ['Date', 'Day Cases', 'Day Deaths', 'Avg Cases', 'Avg Deaths'],
                    <% loop $HistoryData %>
                        ['$Me.Datestr', $Me.DayCase, $Me.DayDeath, $Me.AvgCase, $Me.AvgDeath],
                    <% end_loop %>
                ]);

                // Set options.
                var options = {title:'$Caption',
                    curveType: 'function',
                    height:$Height,
                    vAxis: { scaleType: 'log' },
                    legend: { position: 'top' },
                    curveType: 'function',
                    chartArea:{left: 100, right: 25, top: 50, bottom: 100, width:'auto',height:'auto'},
                    series: {
                        0: { lineWidth: 2 },
                        1: { lineWidth: 2 },
                        2: { lineWidth: 4 },
                        3: { lineWidth: 4 },
                    },
                    colors: ['#B0E0E6', '#DB7093', '#4682B4', '#FF6347']
                };

                // Instantiate and draw the chart.
                var chart = new google.visualization.LineChart(document.getElementById('$Divid'));
                chart.draw(data, options);
            }
        </script>

        <div id="$Divid" style="border: 1px solid #ccc; width: $Width;"></div>