jQuery(document).ready(function($) {
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
    
    // Add hover effect to the bars
    $('.sqt-bar-style > div').hover(
        function() {
            $(this).addClass('sqt-bar-hover');
        },
        function() {
            $(this).removeClass('sqt-bar-hover');
        }
    );

    // Click handler for clickable count values
    $('.sqt-clickable').on('click', function() {
        var query = $(this).data('query');
        showClickedUrls(query);
    });

    // Close overlay when clicking the X
    $('.sqt-close').on('click', function() {
        $('#sqt-overlay').hide();
    });

    // Close overlay when clicking outside the content
    $(window).on('click', function(event) {
        if ($(event.target).is('#sqt-overlay')) {
            $('#sqt-overlay').hide();
        }
    });

    // Function to display clicked URLs in the overlay
    function showClickedUrls(query) {
        if (!sqtSearchClicks[query]) {
            return;
        }

        var urls = sqtSearchClicks[query];
        var html = '<table class="wp-list-table widefat fixed striped">';
        html += '<thead><tr><th>URL</th><th>Clicks</th></tr></thead><tbody>';

        // Sort URLs by click count (descending)
        var sortedUrls = [];
        for (var url in urls) {
            sortedUrls.push({ url: url, count: urls[url] });
        }
        sortedUrls.sort(function(a, b) {
            return b.count - a.count;
        });

        // Generate table rows
        for (var i = 0; i < sortedUrls.length; i++) {
            var urlData = sortedUrls[i];
            html += '<tr>';
            html += '<td><a href="' + urlData.url + '" target="_blank">' + urlData.url + '</a></td>';
            html += '<td>' + urlData.count + '</td>';
            html += '</tr>';
        }

        html += '</tbody></table>';

        // Update overlay content and display it
        $('#sqt-overlay-title').text('Clicked URLs for: "' + query + '"');
        $('#sqt-overlay-data').html(html);
        $('#sqt-overlay').show();
    }
}); 