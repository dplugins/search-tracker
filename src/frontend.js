/**
 * Search Query Tracker - Frontend Tracker
 * 
 * Tracks clicks on search results
 */
import { trackClick } from './api/api';

document.addEventListener('DOMContentLoaded', () => {
    // Get the search query from URL
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('s');
    
    // If we're on a search page with a query
    if (searchQuery) {
        console.log('Search Query Tracker: Tracking search results for query:', searchQuery);
        
        // Find all search result links using more generic selectors
        // This covers most WordPress themes' search result structures
        const searchResultLinks = document.querySelectorAll([
            '.search-results article a', // Standard theme structure
            '.search-result a',          // Common class
            '.search-result-item a',     // Our original selector
            '.post-item a',              // Common for posts in search
            '.entry-title a',            // Title links in search results
            '.search .post a',           // General post links in search context
            'article.post a',            // Article links
            '.search article a',         // Any link in an article within search
            '.search-post a'             // Another common class
        ].join(', '));
        
        console.log('Search Query Tracker: Found', searchResultLinks.length, 'potential search result links');
        
        if (searchResultLinks.length === 0) {
            // If no links found with specific selectors, try a more generic approach
            // This is a fallback that might catch more links but could include non-result links
            console.log('Search Query Tracker: No specific search result links found, trying generic approach');
            
            // Get the main content area (most themes have a main content container)
            const mainContent = document.querySelector([
                'main',
                '#main',
                '#content',
                '.content',
                'article',
                '.site-main',
                '.content-area'
            ].join(', '));
            
            if (mainContent) {
                // Only get links within the main content area to avoid tracking navigation/sidebar links
                searchResultLinks = mainContent.querySelectorAll('a[href]:not([href^="#"]):not([href^="javascript"])');
                console.log('Search Query Tracker: Found', searchResultLinks.length, 'links in main content area');
            }
        }
        
        // Attach click handlers to all found links
        searchResultLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const url = link.getAttribute('href');
                console.log('Search Query Tracker: Tracking click on:', url);
                
                // Track the click
                trackClick(searchQuery, url);
            });
        });
    }
}); 