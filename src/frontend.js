/**
 * Search Query Tracker - Frontend Tracker
 * 
 * Lightweight vanilla JavaScript implementation to track clicks on search results
 * No dependencies required
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get the search query from URL
    const searchQuery = new URLSearchParams(window.location.search).get('s');
    
    // Only proceed if we're on a search page with a query
    if (!searchQuery) return;
    
    // Common selectors for search result links across different WordPress themes
    const selectors = [
        '.search-results article a',  // Standard theme structure
        '.search-result a',           // Common class
        '.search-result-item a',      // Original selector
        '.post-item a',               // Common for posts in search
        '.entry-title a',             // Title links in search results
        '.search .post a',            // General post links in search context
        'article.post a',             // Article links
        '.search article a',          // Any link in an article within search
        '.search-post a'              // Another common class
    ].join(', ');
    
    // Try to find search result links
    let links = document.querySelectorAll(selectors);
    
    // If no specific links found, try to find links in the main content area
    if (links.length === 0) {
        const mainContentSelectors = [
            'main', '#main', '#content', '.content', 
            'article', '.site-main', '.content-area'
        ].join(', ');
        
        const mainContent = document.querySelector(mainContentSelectors);
        if (mainContent) {
            // Only get links within the main content area (exclude navigation, etc.)
            links = mainContent.querySelectorAll('a[href]:not([href^="#"]):not([href^="javascript"])');
        }
    }
    
    // Function to track clicks
    function trackClick(url) {
        // Get the AJAX URL from the global variable or use a relative path
        // This works with WordPress in subfolder installations
        const ajaxURL = window.sqtData?.ajaxurl || window.ajaxurl || './wp-admin/admin-ajax.php';
        
        // Use the Fetch API to send the data
        fetch(ajaxURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sqt_track_click',
                query: searchQuery,
                url: url
            })
        }).catch(function(error) {
            // Silent fail - don't interrupt user experience if tracking fails
        });
    }
    
    // Add click event listeners to all found links
    links.forEach(function(link) {
        link.addEventListener('click', function() {
            trackClick(this.getAttribute('href'));
        });
    });
}); 