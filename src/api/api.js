/**
 * API functions for Search Query Tracker
 */

/**
 * Fetch search data from the server
 * 
 * @returns {Promise<Object>} Search queries and clicks data
 */
export const fetchSearchData = async () => {
    try {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sqt_get_data',
                nonce: sqtData.nonce
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            return {
                searchQueries: data.data.searchQueries || {},
                searchClicks: data.data.searchClicks || {}
            };
        } else {
            throw new Error(data.data || 'Failed to fetch search data');
        }
    } catch (error) {
        console.error('Error fetching search data:', error);
        throw error;
    }
};

/**
 * Track a click on a search result
 * 
 * @param {string} query The search query
 * @param {string} url The clicked URL
 * @returns {Promise<void>}
 */
export const trackClick = async (query, url) => {
    try {
        await fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sqt_track_click',
                query,
                url
            })
        });
    } catch (error) {
        console.error('Error tracking click:', error);
    }
}; 