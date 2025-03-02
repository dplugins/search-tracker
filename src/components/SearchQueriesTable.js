/**
 * SearchQueriesTable component for displaying search queries
 */
import React, { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { SearchControl, Button } from '@wordpress/components';
import ClicksPopup from './popups/ClicksPopup';
import { formatDate } from '../utils/formatting';

const SearchQueriesTable = ({ queries, onQueryClick }) => {
    const [searchTerm, setSearchTerm] = useState('');
    const [sortConfig, setSortConfig] = useState({ key: 'count', direction: 'desc' });
    const [filteredQueries, setFilteredQueries] = useState([]);
    const [selectedQuery, setSelectedQuery] = useState(null);
    const [isClicksPopupOpen, setIsClicksPopupOpen] = useState(false);

    // Filter and sort queries when searchTerm or sortConfig changes
    useEffect(() => {
        let filtered = [...queries];
        
        // Apply search filter
        if (searchTerm) {
            const lowerCaseSearchTerm = searchTerm.toLowerCase();
            filtered = filtered.filter(query => 
                query.query.toLowerCase().includes(lowerCaseSearchTerm)
            );
        }
        
        // Apply sorting
        if (sortConfig.key) {
            filtered.sort((a, b) => {
                if (a[sortConfig.key] < b[sortConfig.key]) {
                    return sortConfig.direction === 'asc' ? -1 : 1;
                }
                if (a[sortConfig.key] > b[sortConfig.key]) {
                    return sortConfig.direction === 'asc' ? 1 : -1;
                }
                return 0;
            });
        }
        
        setFilteredQueries(filtered);
    }, [queries, searchTerm, sortConfig]);

    // Handle search input change
    const handleSearchChange = (value) => {
        setSearchTerm(value);
    };

    // Handle sort button click
    const handleSort = (key) => {
        setSortConfig(prevConfig => ({
            key,
            direction: prevConfig.key === key && prevConfig.direction === 'desc' ? 'asc' : 'desc'
        }));
    };

    // Open clicks popup for a query
    const openClicksPopup = (query, e) => {
        e.stopPropagation();
        setSelectedQuery(query);
        setIsClicksPopupOpen(true);
    };

    // Close clicks popup
    const closeClicksPopup = () => {
        setIsClicksPopupOpen(false);
        setSelectedQuery(null);
    };

    // Calculate max count for bar chart scaling
    const maxCount = Math.max(...queries.map(q => q.count), 1);

    return (
        <div className="sqt-table-container">
            <div className="sqt-search-container">
                <div className="sqt-search-input">
                    <SearchControl
                        value={searchTerm}
                        onChange={handleSearchChange}
                        placeholder={__('Search queries...', 'search-query-tracker')}
                        label={__('Search queries', 'search-query-tracker')}
                        hideLabelFromVision={true}
                    />
                </div>
                
                {searchTerm && (
                    <div className="sqt-search-info">
                        {__('Showing results for:', 'search-query-tracker')} <strong>{searchTerm}</strong> 
                        ({filteredQueries.length} {filteredQueries.length === 1 ? 'result' : 'results'})
                    </div>
                )}
            </div>

            {filteredQueries.length === 0 ? (
                <div className="sqt-no-results">
                    {__('No search queries found.', 'search-query-tracker')}
                </div>
            ) : (
                <table className="sqt-queries-table">
                    <thead>
                        <tr>
                            <th>
                                <button 
                                    className="sqt-sort-button"
                                    onClick={() => handleSort('query')}
                                >
                                    {__('Search Query', 'search-query-tracker')}
                                    {sortConfig.key === 'query' && (
                                        sortConfig.direction === 'asc' ? ' ↑' : ' ↓'
                                    )}
                                </button>
                            </th>
                            <th>
                                <button 
                                    className="sqt-sort-button"
                                    onClick={() => handleSort('count')}
                                >
                                    {__('Count', 'search-query-tracker')}
                                    {sortConfig.key === 'count' && (
                                        sortConfig.direction === 'asc' ? ' ↑' : ' ↓'
                                    )}
                                </button>
                            </th>
                            <th>
                                <button 
                                    className="sqt-sort-button"
                                    onClick={() => handleSort('last_searched')}
                                >
                                    {__('Last Searched', 'search-query-tracker')}
                                    {sortConfig.key === 'last_searched' && (
                                        sortConfig.direction === 'asc' ? ' ↑' : ' ↓'
                                    )}
                                </button>
                            </th>
                            <th>{__('Clicks', 'search-query-tracker')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredQueries.map(query => (
                            <tr 
                                key={query.id} 
                                className="sqt-row-clickable"
                                onClick={() => onQueryClick(query)}
                            >
                                <td>{query.query}</td>
                                <td>
                                    <div className="sqt-bar-chart">
                                        <div 
                                            className="sqt-bar" 
                                            style={{ width: `${(query.count / maxCount) * 100}%` }}
                                        ></div>
                                        <span className="sqt-bar-label">{query.count}</span>
                                    </div>
                                </td>
                                <td>{formatDate(query.last_searched)}</td>
                                <td>
                                    {query.clicks}
                                    {query.clicks > 0 && (
                                        <span 
                                            className="view-links"
                                            onClick={(e) => openClicksPopup(query, e)}
                                        >
                                            {__('View', 'search-query-tracker')}
                                        </span>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}

            {isClicksPopupOpen && selectedQuery && (
                <ClicksPopup
                    query={selectedQuery}
                    onClose={closeClicksPopup}
                />
            )}
        </div>
    );
};

export default SearchQueriesTable; 