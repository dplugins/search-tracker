/**
 * ClicksPopup component for displaying clicked URLs
 */
import { Modal } from '@wordpress/components';

const ClicksPopup = ({ isOpen, onClose, query, clickData }) => {
    // Sort URLs by click count (descending)
    const sortedUrls = Object.entries(clickData)
        .map(([url, count]) => ({ url, count }))
        .sort((a, b) => b.count - a.count);

    if (!isOpen) return null;

    return (
        <Modal
            title={`Clicked URLs for: "${query}"`}
            onRequestClose={onClose}
            className="sqt-modal"
            size="large"
        >
            <div className="sqt-clicks-content">
                {sortedUrls.length > 0 ? (
                    <table className="sqt-clicks-table">
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                            {sortedUrls.map(({ url, count }) => (
                                <tr key={url}>
                                    <td>
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
                        <p>No click data available for this search query.</p>
                    </div>
                )}
            </div>
        </Modal>
    );
};

export default ClicksPopup; 