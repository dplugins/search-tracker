/**
 * ClicksPopup component for displaying clicked URLs
 */
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Panel, PanelBody, PanelRow } from '@wordpress/components';

const ClicksPopup = ({ query, clickData, onClose }) => {
    // Sort URLs by click count (descending)
    const sortedUrls = Object.entries(clickData || {})
        .map(([url, count]) => ({ url, count }))
        .sort((a, b) => b.count - a.count);

    // State to control the slide panel
    const [isOpen, setIsOpen] = useState(false);
    const [isVisible, setIsVisible] = useState(true);
    const panelRef = useRef(null);

    // Handle initial animation on mount
    useEffect(() => {
        // Small delay to ensure DOM is ready before animation starts
        const timer = setTimeout(() => {
            setIsOpen(true);
        }, 10);
        
        return () => clearTimeout(timer);
    }, []);

    // Handle close with animation
    const handleClose = () => {
        setIsOpen(false);
        // Wait for animation to complete before calling onClose
        setTimeout(() => {
            setIsVisible(false);
            setTimeout(() => {
                onClose();
            }, 50);
        }, 300);
    };

    // Add escape key listener
    useEffect(() => {
        const handleEscKey = (event) => {
            if (event.key === 'Escape') {
                handleClose();
            }
        };

        document.addEventListener('keydown', handleEscKey);
        return () => {
            document.removeEventListener('keydown', handleEscKey);
        };
    }, []);

    if (!isVisible) return null;

    return (
        <>
            {/* Overlay */}
            <div 
                className={`sqt-slide-overlay ${isOpen ? 'sqt-slide-overlay-visible' : ''}`}
                onClick={handleClose}
            ></div>
            
            {/* Slide Panel */}
            <div 
                ref={panelRef}
                className={`sqt-slide-panel ${isOpen ? 'sqt-slide-panel-open' : ''}`}
            >
                <div className="p-4 flex justify-between items-center">
                    <h2 className="!m-0">{__('Search term:', 'search-query-tracker')} {query}</h2>
                    <Button 
                        icon="no-alt" 
                        onClick={handleClose}
                        className="sqt-slide-panel-close"
                        label={__('Close panel', 'search-query-tracker')}
                        showTooltip
                    />
                </div>
                
                <div className="sqt-slide-panel-content">
                    {sortedUrls.length > 0 ? (
                        <table className="sqt-clicks-table">
                            <thead>
                                <tr>
                                    <th className="!px-4">{__('URL', 'search-query-tracker')}</th>
                                    <th >{__('Clicks', 'search-query-tracker')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sortedUrls.map(({ url, count }) => (
                                    <tr key={url}>
                                        <td className="!px-4">
                                            <a href={url} target="_blank" rel="noopener noreferrer">
                                                {url}
                                            </a>
                                        </td>
                                        <td>{count}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <div className="sqt-no-clicks">
                            <p>{__('No click data available for this search query.', 'search-query-tracker')}</p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
};

export default ClicksPopup; 