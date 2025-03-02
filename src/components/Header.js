import { Button } from '@wordpress/components';

/**
 * Header component for Search Query Tracker
 */
const Header = ({ onSettingsClick }) => {
    return (
        <div className="bg-white border-b border-gray-200 p-6 flex justify-between items-center">
            <div className="flex gap-4 items-center">
                <div className="w-[36px] h-[36px]">

                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <g>
                        <path d="m47.102 32.602c-8.1016 0-14.602 6.6016-14.602 14.602 0 8.1016 6.6016 14.602 14.602 14.602 8.1016 0 14.602-6.6016 14.602-14.602-0.003906-8.0039-6.5039-14.602-14.602-14.602z" />
                        <path d="m50.102 1.3984c-26.902 0-48.703 21.801-48.703 48.703 0 26.898 21.801 48.699 48.699 48.699 26.902 0 48.703-21.801 48.703-48.699 0-26.902-21.801-48.703-48.699-48.703zm23.699 73.703c-0.69922 0.69922-1.8008 1.1016-3 1.1016s-2.1992-0.39844-3-1.1016l-9.1992-9.1992-0.89844 0.5c-3.3008 1.8008-6.8984 2.8008-10.602 2.8008-12.102 0-21.898-9.8008-21.898-21.898 0-12.102 9.8008-21.898 21.898-21.898 12.102 0 21.898 9.8008 21.898 21.898 0 4.3008-1.1992 8.3984-3.6016 12l-0.60156 1 9.1016 9.1016c0.69922 0.80078 1.1992 1.8008 1.1992 2.8984-0.097656 0.99609-0.49609 1.9961-1.2969 2.7969z" />
                    </g>
                </svg>
                </div>
                <h1 className="!text-3xl font-bold leading-none !p-0">Search Query Tracker</h1>
            </div>

            
            <Button 
                variant="primary"
                onClick={onSettingsClick}
            >
                Settings
            </Button>
        </div>
    );
};

export default Header; 