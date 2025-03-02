/**
 * SettingsPopup component for plugin settings
 */
import { useState, useEffect } from '@wordpress/element';
import PopupWrapper from './PopupWrapper';

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

    return (
        <PopupWrapper isOpen={isOpen} onClose={onClose} title="Plugin Settings">
            <div className="sqt-settings-section">
                <h3>Troubleshooting</h3>
                <p>To make things simple we have hardcoded search trigger.</p>
                <p>
                    Add class <code>.search-result-item</code> to the search result item.
                </p>
            </div>

            <div className="sqt-settings-section">
                <h3>Clear All Data</h3>
                <div className="sqt-reset-description">
                    <p className="sqt-warning">
                        This will permanently delete all search query data and click tracking information. 
                        This action cannot be undone.
                    </p>
                </div>
                
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
        </PopupWrapper>
    );
};

export default SettingsPopup; 