import { Notice } from '@wordpress/components';
/**
 * SettingsPopup component for plugin settings
 */
import { useState, useEffect } from '@wordpress/element';
import { Modal } from '@wordpress/components';

const SettingsPopup = ({ isOpen, onClose, onReset }) => {
    const [resetConfirmation, setResetConfirmation] = useState('');
    const [isResetButtonDisabled, setIsResetButtonDisabled] = useState(true);

    // Update reset button state when confirmation text changes
    useEffect(() => {
        setIsResetButtonDisabled(resetConfirmation.toLowerCase() !== 'reset');
    }, [resetConfirmation]);

    // Handle reset form submission
    const handleResetSubmit = (e) => {
        e.preventDefault();
        if (resetConfirmation.toLowerCase() === 'reset') {
            onReset();
            setResetConfirmation('');
        }
    };

    if (!isOpen) return null;

    return (
        <Modal
            title="Plugin Settings"
            onRequestClose={onClose}
            className="sqt-modal"
        >
            <div className="sqt-settings-section">
                <h1>Troubleshooting</h1>
                <p>To make things simple we have hardcoded search trigger. Add class <code>.search-result-item</code> to the search result item.</p>
                <p>This will check for every a inside wrapper .search-result-item.</p>
            </div>

            <div className="sqt-settings-section mt-4 flex flex-col gap-4">
                <h1>Clear All Data</h1>
                <Notice
                    isDismissible={false}
                    politeness="assertive"
                    status="warning"
                >
                    This will permanently delete all search query data and click tracking information. 
                    This action cannot be undone.
                </Notice>
                
               
                
                <form onSubmit={handleResetSubmit} className="sqt-reset-confirmation">
                    <div className="sqt-form-row">
                        <label htmlFor="reset-confirmation">Type "reset" to confirm:</label>
                        <input
                            id="reset-confirmation"
                            type="text"
                            value={resetConfirmation}
                            onChange={(e) => setResetConfirmation(e.target.value)}
                            placeholder="reset"
                        />
                    </div>
                    <div className="sqt-form-row">
                        <button 
                            type="submit"
                            className="sqt-reset-button"
                            disabled={isResetButtonDisabled}
                        >
                            Clear All Data
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
};

export default SettingsPopup; 