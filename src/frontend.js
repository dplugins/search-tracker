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
        // Find all search result items and attach click handlers
        const searchResultItems = document.querySelectorAll('.search-result-item a');
        
        searchResultItems.forEach(link => {
            link.addEventListener('click', (e) => {
                const url = link.getAttribute('href');
                
                // Track the click
                trackClick(searchQuery, url);
            });
        });
    }
}); 