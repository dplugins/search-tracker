import { Notice, Modal, Button, __experimentalInputControl as InputControl } from '@wordpress/components';
/**
 * SettingsPopup component for plugin settings
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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
            title={__("Plugin Settings", "search-query-tracker")}
            onRequestClose={onClose}
            className="sqt-modal"
            size="large"
        >
            <div className="sqt-settings-section">
                <h1>{__("Troubleshooting", "search-query-tracker")}</h1>
                <p>{__("To make things simple we have hardcoded search trigger. Add class", "search-query-tracker")} <code>.search-result-item</code> {__("to the search result item.", "search-query-tracker")}</p>
                <p>{__("This will check for every a inside wrapper .search-result-item.", "search-query-tracker")}</p>
            </div>

            <div className="sqt-settings-section mt-4 flex flex-col gap-4">
                <h1>{__("Clear All Data", "search-query-tracker")}</h1>
                <Notice
                    isDismissible={false}
                    politeness="assertive"
                    status="warning"
                >
                    {__("This will permanently delete all search query data and click tracking information. This action cannot be undone.", "search-query-tracker")}
                </Notice>
                
                <form onSubmit={handleResetSubmit} className="sqt-reset-confirmation flex gap-4 items-end">
                        <InputControl
                            label={__("Type \"reset\" to confirm:", "search-query-tracker")}
                            id="reset-confirmation"
                            value={resetConfirmation}
                            onChange={setResetConfirmation}
                            placeholder={__("reset", "search-query-tracker")}
                            className="w-full"
                        />
                        <Button 
                            isDestructive
                            isDanger
                            type="submit"
                            disabled={isResetButtonDisabled}
                            size="compact"
                            className="text-nowrap"
                        >
                            {__("Clear All Data", "search-query-tracker")}
                        </Button>                    
                </form>
            </div>
        </Modal>
    );
};

export default SettingsPopup; 