/**
 * PopupWrapper component for displaying popups
 */
const PopupWrapper = ({ isOpen, onClose, title, children }) => {
    if (!isOpen) return null;

    return (
        <div className="sqt-popup-wrapper">
            <div className="sqt-popup-content">
                <div className="sqt-popup-header">
                    <h2>{title}</h2>
                    <button 
                        type="button" 
                        className="sqt-popup-close" 
                        onClick={onClose}
                        aria-label="Close"
                    >
                        &times;
                    </button>
                </div>
                <div className="sqt-popup-body">
                    {children}
                </div>
            </div>
        </div>
    );
};

export default PopupWrapper; 