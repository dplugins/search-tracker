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

    // Click handler for clickable rows
    $('.sqt-row-clickable').on('click', function() {
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

    // Close overlay when pressing ESC key
    $(document).on('keydown', function(event) {
        if (event.key === "Escape" || event.keyCode === 27) {
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
    
    // ===== SETTINGS POPUP FUNCTIONALITY =====
    const resetInput = document.getElementById('sqt-reset-confirm');
    const resetButton = document.getElementById('sqt-reset-button');
    const settingsButton = document.getElementById('sqt-settings-button');
    const settingsPopup = document.getElementById('sqt-settings-popup');
    const settingsClose = document.querySelector('.sqt-settings-close');
    
    // Enable/disable reset button based on input
    if (resetInput) {
        resetInput.addEventListener('input', function() {
            resetButton.disabled = this.value.toLowerCase() !== 'reset';
        });
    }
    
    // Show popup when settings button is clicked
    if (settingsButton) {
        settingsButton.addEventListener('click', function() {
            settingsPopup.style.display = 'block';
        });
    }
    
    // Close popup when X is clicked
    if (settingsClose) {
        settingsClose.addEventListener('click', function() {
            settingsPopup.style.display = 'none';
        });
    }
    
    // Close popup when clicking outside the content
    $(window).on('click', function(event) {
        if ($(event.target).is('#sqt-settings-popup')) {
            $('#sqt-settings-popup').hide();
        }
    });
    
    // Close settings popup when pressing ESC key
    $(document).on('keydown', function(event) {
        if (event.key === "Escape" || event.keyCode === 27) {
            $('#sqt-settings-popup').hide();
        }
    });
    
    // ===== TABLE SEARCH FUNCTIONALITY =====
    const searchInput = document.getElementById('sqt-search-input');
    const clearButton = document.getElementById('sqt-clear-search');
    const table = document.getElementById('sqt-queries-table');
    
    if (searchInput && clearButton && table) {
        const rows = table.querySelectorAll('tbody tr');
        const filterInfo = document.getElementById('sqt-filter-info');
        const filterTerm = document.getElementById('sqt-filter-term');
        const resultCount = document.getElementById('sqt-result-count');
        const noResults = document.getElementById('sqt-no-results');
        
        // Function to filter the table
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;
            
            rows.forEach(row => {
                const term = row.getAttribute('data-search-term');
                if (term.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update filter info
            if (searchTerm) {
                filterTerm.textContent = searchTerm;
                resultCount.textContent = visibleCount;
                filterInfo.style.display = 'block';
                
                // Show no results message if needed
                if (visibleCount === 0) {
                    table.style.display = 'none';
                    noResults.style.display = 'block';
                } else {
                    table.style.display = '';
                    noResults.style.display = 'none';
                }
            } else {
                filterInfo.style.display = 'none';
                table.style.display = '';
                noResults.style.display = 'none';
            }
        }
        
        // Filter as you type
        searchInput.addEventListener('input', filterTable);
        
        // Clear search
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            filterTable();
            searchInput.focus();
        });
    }
}); 