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
    await fetch(ajaxurl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
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
    // Find all search result items and attach click handlers
    const searchResultItems = document.querySelectorAll('.search-result-item a');
    searchResultItems.forEach(link => {
      link.addEventListener('click', e => {
        const url = link.getAttribute('href');

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