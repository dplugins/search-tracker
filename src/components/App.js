/**
 * Main App component for Search Query Tracker
 */
import { useState, useEffect } from '@wordpress/element';
import Header from './Header';
import SearchQueriesTable from './SearchQueriesTable';
import SettingsPopup from './popups/SettingsPopup';
import ClicksPopup from './popups/ClicksPopup';
import { fetchSearchData } from '../api/api';

const App = () => {
    const [searchQueries, setSearchQueries] = useState({});
    const [searchClicks, setSearchClicks] = useState({});
    const [maxCount, setMaxCount] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [activePopup, setActivePopup] = useState(null);
    const [selectedQuery, setSelectedQuery] = useState('');

    // Fetch data on component mount
    useEffect(() => {
        const loadData = async () => {
            try {
                setLoading(true);
                const data = await fetchSearchData();
                
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
                    'Content-Type': 'application/x-www-form-urlencoded',
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
        return (
            <div className="sqt-loading">
                <p>Loading search data...</p>
            </div>
        );
    }

    if (error) {
        return (
            <div className="sqt-error">
                <p>{error}</p>
            </div>
        );
    }

    return (
        <div className="wrap">
            <Header onSettingsClick={() => openPopup('settings')} />
            
            {Object.keys(searchQueries).length === 0 ? (
                <div className="sqt-no-data">
                    <p>No search queries data available yet.</p>
                </div>
            ) : (
                <SearchQueriesTable 
                    searchQueries={searchQueries}
                    searchClicks={searchClicks}
                    maxCount={maxCount}
                    onRowClick={(query) => openPopup('clicks', query)}
                />
            )}
            
            {/* Popups */}
            <SettingsPopup 
                isOpen={activePopup === 'settings'} 
                onClose={closePopup}
                onReset={handleDataReset}
            />
            
            <ClicksPopup 
                isOpen={activePopup === 'clicks'} 
                onClose={closePopup}
                query={selectedQuery}
                clickData={searchClicks[selectedQuery] || {}}
            />
        </div>
    );
};

export default App;