jQuery(document).ready(function($) {
    // Initialize chart if we're on the top queries tab
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
    const currentTab = getParameterByName('tab') || 'top_queries';
    $('.nav-tab').removeClass('nav-tab-active');
    $(`.nav-tab[href*="tab=${currentTab}"]`).addClass('nav-tab-active');
}); 