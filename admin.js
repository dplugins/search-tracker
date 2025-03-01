jQuery(document).ready(function($) {
    // Initialize chart if we're on the search queries tab
    initChart();
    
    // Function to initialize the chart
    function initChart() {
        if (typeof sqtChartData !== 'undefined' && document.getElementById('sqtQueriesChart')) {
            // Get the canvas element
            const ctx = document.getElementById('sqtQueriesChart').getContext('2d');
            
            // Create a horizontal bar chart
            const queriesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: sqtChartData.labels,
                    datasets: [{
                        label: 'Search Count',
                        data: sqtChartData.counts,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y', // This makes the bars horizontal
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Most Used Search Queries'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.parsed.x} searches`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Searches'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Search Query'
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Handle tab switching via URL parameters
    function getParameterByName(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
    
    // Highlight the active tab based on URL
    const currentTab = getParameterByName('tab') || 'search_queries';
    $('.nav-tab').removeClass('nav-tab-active');
    $(`.nav-tab[href*="tab=${currentTab}"]`).addClass('nav-tab-active');
    
    // Highlight the corresponding row in the table when hovering over a bar in the chart
    if (document.getElementById('sqtQueriesChart')) {
        const chart = Chart.getChart('sqtQueriesChart');
        
        if (chart) {
            chart.canvas.addEventListener('mousemove', function(e) {
                const activePoints = chart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, false);
                
                if (activePoints.length > 0) {
                    const firstPoint = activePoints[0];
                    const label = chart.data.labels[firstPoint.index];
                    
                    // Remove highlight from all rows
                    $('.sqt-table-area tr').removeClass('highlighted-row');
                    
                    // Find and highlight the matching row
                    $(`.sqt-table-area tr td:contains("${label}")`).parent().addClass('highlighted-row');
                }
            });
            
            chart.canvas.addEventListener('mouseout', function() {
                // Remove highlight from all rows when mouse leaves the chart
                $('.sqt-table-area tr').removeClass('highlighted-row');
            });
        }
    }
}); 