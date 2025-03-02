/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/api/api.js":
/*!************************!*\
  !*** ./src/api/api.js ***!
  \************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   fetchSearchData: () => (/* binding */ fetchSearchData),
/* harmony export */   trackClick: () => (/* binding */ trackClick)
/* harmony export */ });
/**
 * API functions for Search Query Tracker
 */

/**
 * Fetch search data from the server
 * 
 * @returns {Promise<Object>} Search queries and clicks data
 */
const fetchSearchData = async () => {
  try {
    const response = await fetch(ajaxurl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
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
const trackClick = async (query, url) => {
  try {
    // Determine the correct AJAX URL to use
    const ajaxURL = typeof sqtData !== 'undefined' ? sqtData.ajaxurl : typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php';
    console.log('Search Query Tracker: Sending click data to', ajaxURL);
    console.log('Search Query Tracker: Query:', query, 'URL:', url);
    await fetch(ajaxURL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: new URLSearchParams({
        action: 'sqt_track_click',
        query,
        url,
        nonce: typeof sqtData !== 'undefined' ? sqtData.nonce : ''
      })
    });
    console.log('Search Query Tracker: Click tracked successfully');
  } catch (error) {
    console.error('Search Query Tracker: Error tracking click:', error);
  }
};

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*************************!*\
  !*** ./src/frontend.js ***!
  \*************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _api_api__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./api/api */ "./src/api/api.js");
/**
 * Search Query Tracker - Frontend Tracker
 * 
 * Tracks clicks on search results
 */

document.addEventListener('DOMContentLoaded', () => {
  // Get the search query from URL
  const urlParams = new URLSearchParams(window.location.search);
  const searchQuery = urlParams.get('s');

  // If we're on a search page with a query
  if (searchQuery) {
    console.log('Search Query Tracker: Tracking search results for query:', searchQuery);

    // Find all search result links using more generic selectors
    // This covers most WordPress themes' search result structures
    const searchResultLinks = document.querySelectorAll(['.search-results article a',
    // Standard theme structure
    '.search-result a',
    // Common class
    '.search-result-item a',
    // Our original selector
    '.post-item a',
    // Common for posts in search
    '.entry-title a',
    // Title links in search results
    '.search .post a',
    // General post links in search context
    'article.post a',
    // Article links
    '.search article a',
    // Any link in an article within search
    '.search-post a' // Another common class
    ].join(', '));
    console.log('Search Query Tracker: Found', searchResultLinks.length, 'potential search result links');
    if (searchResultLinks.length === 0) {
      // If no links found with specific selectors, try a more generic approach
      // This is a fallback that might catch more links but could include non-result links
      console.log('Search Query Tracker: No specific search result links found, trying generic approach');

      // Get the main content area (most themes have a main content container)
      const mainContent = document.querySelector(['main', '#main', '#content', '.content', 'article', '.site-main', '.content-area'].join(', '));
      if (mainContent) {
        // Only get links within the main content area to avoid tracking navigation/sidebar links
        searchResultLinks = mainContent.querySelectorAll('a[href]:not([href^="#"]):not([href^="javascript"])');
        console.log('Search Query Tracker: Found', searchResultLinks.length, 'links in main content area');
      }
    }

    // Attach click handlers to all found links
    searchResultLinks.forEach(link => {
      link.addEventListener('click', e => {
        const url = link.getAttribute('href');
        console.log('Search Query Tracker: Tracking click on:', url);

        // Track the click
        (0,_api_api__WEBPACK_IMPORTED_MODULE_0__.trackClick)(searchQuery, url);
      });
    });
  }
});
})();

/******/ })()
;
//# sourceMappingURL=frontend.js.map