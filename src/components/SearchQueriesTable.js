/**
 * SearchQueriesTable component for displaying search queries
 */
import { useState, useEffect } from '@wordpress/element';
import { SearchControl } from '@wordpress/components';

const SearchQueriesTable = ({ searchQueries, searchClicks, maxCount, onRowClick }) => {
    const [filteredQueries, setFilteredQueries] = useState({});
    const [searchTerm, setSearchTerm] = useState('');
    const [sortBy, setSortBy] = useState('search');
    const [sortOrder, setSortOrder] = useState('desc');

    // Filter and sort queries when dependencies change
    useEffect(() => {
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
    const handleSortClick = (column) => {
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
    const getSortIndicator = (column) => {
        if (sortBy !== column) return null;
        return sortOrder === 'desc' ? '▼' : '▲';
    };

    // Calculate total clicks for a query
    const calculateTotalClicks = (query) => {
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
        const percentage = (maxCount > 0) ? (count / maxCount) * 100 : 0;
        const totalClicks = calculateTotalClicks(query);
        const hasClicks = totalClicks > 0;
        
        return {
            percentage,
            hasClicks,
            totalClicks
        };
    };

    return (
        <div className="sqt-table-container">
            <div className="flex gap-4 items-center">
                
                <SearchControl
                    value={searchTerm}
                    onChange={setSearchTerm}
                    placeholder="Search terms..."
                    label="Search"
                    hideLabelFromVision
                    className="w-[400px] mx-8 my-4"
                />                    
                
                
                {searchTerm && (
                    <div className="sqt-search-info">
                        Showing results for: <strong>{searchTerm}</strong> 
                        ({Object.keys(filteredQueries).length} results)
                    </div>
                )}                
            </div>

            {(Object.keys(filteredQueries).length > 0 || !searchTerm) && (
                <table className="sqt-queries-table">
                    <thead>
                        <tr>
                            <th className="w-[160px] !px-8">
                                <button 
                                    className="sqt-sort-button"
                                    onClick={() => handleSortClick('search')}
                                >
                                    Search {getSortIndicator('search')}
                                </button>
                            </th>
                            <th>
                                <button 
                                    className="sqt-sort-button"
                                    onClick={() => handleSortClick('term')}
                                >
                                    Term {getSortIndicator('term')}
                                </button>
                            </th>
                            <th className="w-[120px]">
                                <button 
                                    className="sqt-sort-button"
                                    onClick={() => handleSortClick('clicks')}
                                >
                                    Clicks {getSortIndicator('clicks')}
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {Object.entries(filteredQueries).map(([query, count]) => {
                            const rowData = prepareRowData(query, count);
                            
                            return (
                                <tr 
                                    key={query}
                                    className={rowData.hasClicks ? 'sqt-row-clickable' : ''}
                                    onClick={rowData.hasClicks ? () => onRowClick(query) : undefined}
                                    style={{ cursor: rowData.hasClicks ? 'pointer' : 'default' }}
                                >
                                    <td className="!px-8">{count}</td>
                                    <td>
                                        <div className="sqt-bar-chart">
                                            <div 
                                                className="sqt-bar" 
                                                style={{ width: `${rowData.percentage}%` }}
                                            ></div>
                                            <div className="sqt-bar-label">
                                                <span>{query}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {rowData.totalClicks}
                                        {rowData.hasClicks && (
                                            <span className="view-links">
                                                View →
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            )}
        </div>
    );
};

export default SearchQueriesTable; 