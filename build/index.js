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

/***/ }),

/***/ "./src/components/App.js":
/*!*******************************!*\
  !*** ./src/components/App.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _Header__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Header */ "./src/components/Header.js");
/* harmony import */ var _SearchQueriesTable__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SearchQueriesTable */ "./src/components/SearchQueriesTable.js");
/* harmony import */ var _popups_SettingsPopup__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./popups/SettingsPopup */ "./src/components/popups/SettingsPopup.js");
/* harmony import */ var _popups_ClicksPopup__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./popups/ClicksPopup */ "./src/components/popups/ClicksPopup.js");
/* harmony import */ var _api_api__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../api/api */ "./src/api/api.js");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__);
/**
 * Main App component for Search Query Tracker
 */







const App = () => {
  const [searchQueries, setSearchQueries] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [searchClicks, setSearchClicks] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [maxCount, setMaxCount] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(0);
  const [loading, setLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(true);
  const [error, setError] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [activePopup, setActivePopup] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)(null);
  const [selectedQuery, setSelectedQuery] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');

  // Fetch data on component mount
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const loadData = async () => {
      try {
        setLoading(true);
        const data = await (0,_api_api__WEBPACK_IMPORTED_MODULE_5__.fetchSearchData)();
        setSearchQueries(data.searchQueries || {});
        setSearchClicks(data.searchClicks || {});

        // Calculate max count for percentage calculations
        let max = 0;
        Object.values(data.searchQueries || {}).forEach(count => {
          if (count > max) max = count;
        });
        setMaxCount(max);
        setLoading(false);
      } catch (err) {
        setError('Failed to load search data');
        setLoading(false);
        console.error(err);
      }
    };
    loadData();
  }, []);

  // Open popup handler
  const openPopup = (popupName, query = '') => {
    setActivePopup(popupName);
    if (query) {
      setSelectedQuery(query);
    }
  };

  // Close popup handler
  const closePopup = () => {
    setActivePopup(null);
    setSelectedQuery('');
  };

  // Handle data reset
  const handleDataReset = async () => {
    try {
      setLoading(true);
      await fetch(ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: 'sqt_reset_data',
          nonce: sqtData.nonce
        })
      });

      // Reset state
      setSearchQueries({});
      setSearchClicks({});
      setMaxCount(0);
      closePopup();
      setLoading(false);
    } catch (err) {
      setError('Failed to reset data');
      setLoading(false);
      console.error(err);
    }
  };
  if (loading && Object.keys(searchQueries).length === 0) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
      className: "sqt-loading",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
        children: "Loading search data..."
      })
    });
  }
  if (error) {
    return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
      className: "sqt-error",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
        children: error
      })
    });
  }
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_Header__WEBPACK_IMPORTED_MODULE_1__["default"], {
      onSettingsClick: () => openPopup('settings')
    }), Object.keys(searchQueries).length === 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
      className: "sqt-no-data",
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("p", {
        children: "No search queries data available yet."
      })
    }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_SearchQueriesTable__WEBPACK_IMPORTED_MODULE_2__["default"], {
      searchQueries: searchQueries,
      searchClicks: searchClicks,
      maxCount: maxCount,
      onRowClick: query => openPopup('clicks', query)
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_popups_SettingsPopup__WEBPACK_IMPORTED_MODULE_3__["default"], {
      isOpen: activePopup === 'settings',
      onClose: closePopup,
      onReset: handleDataReset
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_popups_ClicksPopup__WEBPACK_IMPORTED_MODULE_4__["default"], {
      isOpen: activePopup === 'clicks',
      onClose: closePopup,
      query: selectedQuery,
      clickData: searchClicks[selectedQuery] || {}
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (App);

/***/ }),

/***/ "./src/components/Header.js":
/*!**********************************!*\
  !*** ./src/components/Header.js ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);


/**
 * Header component for Search Query Tracker
 */

const Header = ({
  onSettingsClick
}) => {
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
    className: "bg-white border-b border-gray-200 p-6 flex justify-between items-center",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("div", {
      className: "flex gap-4 items-center",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
        className: "w-[36px] h-[36px]",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("svg", {
          viewBox: "0 0 100 100",
          xmlns: "http://www.w3.org/2000/svg",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("g", {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("path", {
              d: "m47.102 32.602c-8.1016 0-14.602 6.6016-14.602 14.602 0 8.1016 6.6016 14.602 14.602 14.602 8.1016 0 14.602-6.6016 14.602-14.602-0.003906-8.0039-6.5039-14.602-14.602-14.602z"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("path", {
              d: "m50.102 1.3984c-26.902 0-48.703 21.801-48.703 48.703 0 26.898 21.801 48.699 48.699 48.699 26.902 0 48.703-21.801 48.703-48.699 0-26.902-21.801-48.703-48.699-48.703zm23.699 73.703c-0.69922 0.69922-1.8008 1.1016-3 1.1016s-2.1992-0.39844-3-1.1016l-9.1992-9.1992-0.89844 0.5c-3.3008 1.8008-6.8984 2.8008-10.602 2.8008-12.102 0-21.898-9.8008-21.898-21.898 0-12.102 9.8008-21.898 21.898-21.898 12.102 0 21.898 9.8008 21.898 21.898 0 4.3008-1.1992 8.3984-3.6016 12l-0.60156 1 9.1016 9.1016c0.69922 0.80078 1.1992 1.8008 1.1992 2.8984-0.097656 0.99609-0.49609 1.9961-1.2969 2.7969z"
            })]
          })
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("h1", {
        className: "!text-3xl font-bold leading-none !p-0",
        children: "Search Query Tracker"
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Button, {
      variant: "primary",
      onClick: onSettingsClick,
      children: "Settings"
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Header);

/***/ }),

/***/ "./src/components/SearchQueriesTable.js":
/*!**********************************************!*\
  !*** ./src/components/SearchQueriesTable.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);
/**
 * SearchQueriesTable component for displaying search queries
 */



const SearchQueriesTable = ({
  searchQueries,
  searchClicks,
  maxCount,
  onRowClick
}) => {
  const [filteredQueries, setFilteredQueries] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [searchTerm, setSearchTerm] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('');
  const [sortBy, setSortBy] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('search');
  const [sortOrder, setSortOrder] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)('desc');

  // Filter and sort queries when dependencies change
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    // Filter queries based on search term
    const filtered = Object.entries(searchQueries).reduce((acc, [query, count]) => {
      if (query.toLowerCase().includes(searchTerm.toLowerCase())) {
        acc[query] = count;
      }
      return acc;
    }, {});

    // Sort the filtered queries
    const sorted = sortQueries(filtered, searchClicks, sortBy, sortOrder);
    setFilteredQueries(sorted);
  }, [searchQueries, searchClicks, searchTerm, sortBy, sortOrder]);

  // Handle sort column click
  const handleSortClick = column => {
    if (sortBy === column) {
      // Toggle sort order if clicking the same column
      setSortOrder(sortOrder === 'desc' ? 'asc' : 'desc');
    } else {
      // Set new sort column and default to descending
      setSortBy(column);
      setSortOrder('desc');
    }
  };

  // Get sort indicator for column headers
  const getSortIndicator = column => {
    if (sortBy !== column) return null;
    return sortOrder === 'desc' ? '▼' : '▲';
  };

  // Calculate total clicks for a query
  const calculateTotalClicks = query => {
    if (!searchClicks[query]) return 0;
    return Object.values(searchClicks[query]).reduce((sum, count) => sum + count, 0);
  };

  // Sort queries based on selected column and order
  const sortQueries = (queries, clicks, sortBy, sortOrder) => {
    const entries = Object.entries(queries);
    entries.sort(([queryA, countA], [queryB, countB]) => {
      let comparison = 0;
      switch (sortBy) {
        case 'search':
          comparison = countB - countA; // Sort by search count
          break;
        case 'term':
          comparison = queryA.localeCompare(queryB); // Sort alphabetically
          break;
        case 'clicks':
          const clicksA = calculateTotalClicks(queryA);
          const clicksB = calculateTotalClicks(queryB);
          comparison = clicksB - clicksA; // Sort by click count
          break;
        default:
          comparison = countB - countA;
      }

      // Reverse for ascending order
      return sortOrder === 'asc' ? -comparison : comparison;
    });

    // Convert back to object
    return Object.fromEntries(entries);
  };

  // Prepare row data
  const prepareRowData = (query, count) => {
    const percentage = maxCount > 0 ? count / maxCount * 100 : 0;
    const totalClicks = calculateTotalClicks(query);
    const hasClicks = totalClicks > 0;
    return {
      percentage,
      hasClicks,
      totalClicks
    };
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
    className: "sqt-table-container",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      className: "flex gap-4 items-center",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SearchControl, {
        value: searchTerm,
        onChange: setSearchTerm,
        placeholder: "Search terms...",
        label: "Search",
        hideLabelFromVision: true,
        className: "w-[400px]"
      }), searchTerm && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
        className: "sqt-search-info",
        children: ["Showing results for: ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("strong", {
          children: searchTerm
        }), "(", Object.keys(filteredQueries).length, " results)"]
      })]
    }), (Object.keys(filteredQueries).length > 0 || !searchTerm) && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("table", {
      className: "sqt-queries-table",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("thead", {
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("tr", {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("th", {
            style: {
              width: '80px'
            },
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("button", {
              className: "sqt-sort-button",
              onClick: () => handleSortClick('search'),
              children: ["Search ", getSortIndicator('search')]
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("th", {
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("button", {
              className: "sqt-sort-button",
              onClick: () => handleSortClick('term'),
              children: ["Term ", getSortIndicator('term')]
            })
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("th", {
            style: {
              width: '120px'
            },
            children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("button", {
              className: "sqt-sort-button",
              onClick: () => handleSortClick('clicks'),
              children: ["Clicks ", getSortIndicator('clicks')]
            })
          })]
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("tbody", {
        children: Object.entries(filteredQueries).map(([query, count]) => {
          const rowData = prepareRowData(query, count);
          return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("tr", {
            className: rowData.hasClicks ? 'sqt-row-clickable' : '',
            onClick: rowData.hasClicks ? () => onRowClick(query) : undefined,
            style: {
              cursor: rowData.hasClicks ? 'pointer' : 'default'
            },
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("td", {
              children: count
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("td", {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
                className: "sqt-bar-chart",
                children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
                  className: "sqt-bar",
                  style: {
                    width: `${rowData.percentage}%`
                  }
                }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
                  className: "sqt-bar-label",
                  children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
                    children: query
                  })
                })]
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("td", {
              children: [rowData.totalClicks, rowData.hasClicks && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("span", {
                className: "view-links",
                children: "View \u2192"
              })]
            })]
          }, query);
        })
      })]
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SearchQueriesTable);

/***/ }),

/***/ "./src/components/popups/ClicksPopup.js":
/*!**********************************************!*\
  !*** ./src/components/popups/ClicksPopup.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__);
/**
 * ClicksPopup component for displaying clicked URLs
 */


const ClicksPopup = ({
  isOpen,
  onClose,
  query,
  clickData
}) => {
  // Sort URLs by click count (descending)
  const sortedUrls = Object.entries(clickData).map(([url, count]) => ({
    url,
    count
  })).sort((a, b) => b.count - a.count);
  if (!isOpen) return null;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Modal, {
    title: `Clicked URLs for: "${query}"`,
    onRequestClose: onClose,
    className: "sqt-modal",
    children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
      className: "sqt-clicks-content",
      children: sortedUrls.length > 0 ? /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("table", {
        className: "sqt-clicks-table",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("thead", {
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("tr", {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("th", {
              children: "URL"
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("th", {
              children: "Clicks"
            })]
          })
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("tbody", {
          children: sortedUrls.map(({
            url,
            count
          }) => /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsxs)("tr", {
            children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("td", {
              children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("a", {
                href: url,
                target: "_blank",
                rel: "noopener noreferrer",
                children: url
              })
            }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("td", {
              children: count
            })]
          }, url))
        })]
      }) : /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("div", {
        className: "sqt-no-clicks",
        children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_1__.jsx)("p", {
          children: "No click data available for this search query."
        })
      })
    })
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (ClicksPopup);

/***/ }),

/***/ "./src/components/popups/SettingsPopup.js":
/*!************************************************!*\
  !*** ./src/components/popups/SettingsPopup.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__);

/**
 * SettingsPopup component for plugin settings
 */



const SettingsPopup = ({
  isOpen,
  onClose,
  onReset
}) => {
  const [resetConfirmation, setResetConfirmation] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('');
  const [isResetButtonDisabled, setIsResetButtonDisabled] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);

  // Update reset button state when confirmation text changes
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    setIsResetButtonDisabled(resetConfirmation.toLowerCase() !== 'reset');
  }, [resetConfirmation]);

  // Handle reset form submission
  const handleResetSubmit = e => {
    e.preventDefault();
    if (resetConfirmation.toLowerCase() === 'reset') {
      onReset();
      setResetConfirmation('');
    }
  };
  if (!isOpen) return null;
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Modal, {
    title: "Plugin Settings",
    onRequestClose: onClose,
    className: "sqt-modal",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      className: "sqt-settings-section",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("h1", {
        children: "Troubleshooting"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("p", {
        children: ["To make things simple we have hardcoded search trigger. Add class ", /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("code", {
          children: ".search-result-item"
        }), " to the search result item."]
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("p", {
        children: "This will check for every a inside wrapper .search-result-item."
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
      className: "sqt-settings-section mt-4 flex flex-col gap-4",
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("h1", {
        children: "Clear All Data"
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_0__.Notice, {
        isDismissible: false,
        politeness: "assertive",
        status: "warning",
        children: "This will permanently delete all search query data and click tracking information. This action cannot be undone."
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("form", {
        onSubmit: handleResetSubmit,
        className: "sqt-reset-confirmation",
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsxs)("div", {
          className: "sqt-form-row",
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("label", {
            htmlFor: "reset-confirmation",
            children: "Type \"reset\" to confirm:"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("input", {
            id: "reset-confirmation",
            type: "text",
            value: resetConfirmation,
            onChange: e => setResetConfirmation(e.target.value),
            placeholder: "reset"
          })]
        }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("div", {
          className: "sqt-form-row",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_2__.jsx)("button", {
            type: "submit",
            className: "sqt-reset-button",
            disabled: isResetButtonDisabled,
            children: "Clear All Data"
          })
        })]
      })]
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (SettingsPopup);

/***/ }),

/***/ "./src/styles/main.scss":
/*!******************************!*\
  !*** ./src/styles/main.scss ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

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
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
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
/*!**********************!*\
  !*** ./src/index.js ***!
  \**********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_App__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/App */ "./src/components/App.js");
/* harmony import */ var _styles_main_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./styles/main.scss */ "./src/styles/main.scss");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);
/**
 * Search Query Tracker - Admin Dashboard
 * 
 * Main entry point for the React application
 */




// Wait for DOM to be ready

document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('sqt-app');
  if (container) {
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.render)(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_components_App__WEBPACK_IMPORTED_MODULE_1__["default"], {}), container);
  }
});
})();

/******/ })()
;
//# sourceMappingURL=index.js.map