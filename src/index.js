/**
 * Search Query Tracker - Admin Dashboard
 * 
 * Main entry point for the React application
 */
import { render } from '@wordpress/element';
import App from './components/App';
import './styles/main.scss';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('sqt-app');
    if (container) {
        render(<App />, container);
    }
});