/**
 * Search Query Tracker - Analytics Tracking
 *
 * Privacy-focused session and pageview tracking without cookies
 * Tracks user flows, referrers, and engagement metrics
 */

(function() {
    'use strict';

    // Configuration
    const SESSION_STORAGE_KEY = 'sqt_session_id';
    const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes in milliseconds
    const SCROLL_TRACKING_INTERVAL = 5000; // Track scroll depth every 5 seconds

    let sessionId = null;
    let pageLoadTime = Date.now();
    let maxScrollDepth = 0;
    let scrollTrackingInterval = null;
    let isFirstPageview = false;

    /**
     * Generate a simple hash from string
     */
    function simpleHash(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        return Math.abs(hash).toString(36);
    }

    /**
     * Generate a unique session ID based on browser fingerprint
     */
    function generateSessionId() {
        const fingerprint = [
            navigator.userAgent,
            navigator.language,
            screen.width + 'x' + screen.height,
            screen.colorDepth,
            new Date().getTimezoneOffset()
        ].join('|');

        return simpleHash(fingerprint) + '-' + Date.now();
    }

    /**
     * Get or create session ID
     */
    function getOrCreateSession() {
        try {
            // Check if session exists in sessionStorage
            const stored = sessionStorage.getItem(SESSION_STORAGE_KEY);

            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    const now = Date.now();

                    // Check if session is still valid (within timeout)
                    if (now - data.lastActivity < SESSION_TIMEOUT) {
                        // Update last activity
                        data.lastActivity = now;
                        sessionStorage.setItem(SESSION_STORAGE_KEY, JSON.stringify(data));
                        return data.id;
                    }
                } catch (e) {
                    // Invalid JSON, create new session
                }
            }

            // Create new session
            const newSessionId = generateSessionId();
            const sessionData = {
                id: newSessionId,
                created: Date.now(),
                lastActivity: Date.now()
            };

            sessionStorage.setItem(SESSION_STORAGE_KEY, JSON.stringify(sessionData));
            isFirstPageview = true;
            return newSessionId;

        } catch (e) {
            // sessionStorage not available (private browsing), use temp ID
            return generateSessionId();
        }
    }

    /**
     * Get URL parameters
     */
    function getUrlParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            utm_source: params.get('utm_source') || '',
            utm_medium: params.get('utm_medium') || '',
            utm_campaign: params.get('utm_campaign') || '',
            utm_term: params.get('utm_term') || '',
            utm_content: params.get('utm_content') || ''
        };
    }

    /**
     * Calculate scroll depth percentage
     */
    function getScrollDepth() {
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollTop = window.scrollY || window.pageYOffset || document.documentElement.scrollTop;

        const scrollableHeight = documentHeight - windowHeight;
        if (scrollableHeight <= 0) return 100;

        const depth = Math.round((scrollTop / scrollableHeight) * 100);
        return Math.min(100, Math.max(0, depth));
    }

    /**
     * Update max scroll depth
     */
    function updateScrollDepth() {
        const currentDepth = getScrollDepth();
        if (currentDepth > maxScrollDepth) {
            maxScrollDepth = currentDepth;
        }
    }

    /**
     * Track session start/update
     */
    function trackSession() {
        // Get the AJAX URL
        const ajaxURL = window.sqtData?.ajaxurl || window.ajaxurl || './wp-admin/admin-ajax.php';
        const nonce = window.sqtData?.nonce || '';

        sessionId = getOrCreateSession();

        // Only send landing page data on first pageview of session
        if (isFirstPageview) {
            const urlParams = getUrlParams();

            fetch(ajaxURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sqt_track_session',
                    session_id: sessionId,
                    landing_page: window.location.href,
                    landing_referrer: document.referrer || '',
                    utm_source: urlParams.utm_source,
                    utm_medium: urlParams.utm_medium,
                    utm_campaign: urlParams.utm_campaign,
                    utm_term: urlParams.utm_term,
                    utm_content: urlParams.utm_content,
                    nonce: nonce
                })
            }).catch(function(error) {
                // Silent fail
            });
        } else {
            // Just update session (pages_viewed, duration)
            fetch(ajaxURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sqt_track_session',
                    session_id: sessionId,
                    nonce: nonce
                })
            }).catch(function(error) {
                // Silent fail
            });
        }
    }

    /**
     * Track pageview
     */
    function trackPageview() {
        const ajaxURL = window.sqtData?.ajaxurl || window.ajaxurl || './wp-admin/admin-ajax.php';
        const nonce = window.sqtData?.nonce || '';

        if (!sessionId) {
            sessionId = getOrCreateSession();
        }

        fetch(ajaxURL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'sqt_track_pageview',
                session_id: sessionId,
                page_url: window.location.href,
                page_title: document.title,
                referrer: document.referrer || '',
                nonce: nonce
            })
        }).catch(function(error) {
            // Silent fail
        });
    }

    /**
     * Send pageview data when user leaves page
     */
    function sendPageLeaveData() {
        const ajaxURL = window.sqtData?.ajaxurl || window.ajaxurl || './wp-admin/admin-ajax.php';
        const nonce = window.sqtData?.nonce || '';

        if (!sessionId) return;

        const timeOnPage = Math.round((Date.now() - pageLoadTime) / 1000); // seconds

        // Use sendBeacon for reliable delivery when page unloads
        const data = new URLSearchParams({
            action: 'sqt_update_pageview',
            session_id: sessionId,
            page_url: window.location.href,
            time_on_page: timeOnPage,
            scroll_depth: maxScrollDepth,
            nonce: nonce
        });

        if (navigator.sendBeacon) {
            navigator.sendBeacon(ajaxURL, data);
        } else {
            // Fallback for older browsers
            fetch(ajaxURL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data,
                keepalive: true
            }).catch(function(error) {
                // Silent fail
            });
        }
    }

    /**
     * Initialize tracking
     */
    function init() {
        // Track session
        trackSession();

        // Track pageview
        trackPageview();

        // Track scroll depth
        updateScrollDepth(); // Initial depth
        scrollTrackingInterval = setInterval(updateScrollDepth, SCROLL_TRACKING_INTERVAL);
        window.addEventListener('scroll', updateScrollDepth, { passive: true });

        // Send data on page leave
        window.addEventListener('beforeunload', sendPageLeaveData);

        // Also try to send on visibility change (mobile browsers)
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                sendPageLeaveData();
            }
        });

        // Send periodic updates for long page sessions
        setInterval(function() {
            const timeOnPage = Math.round((Date.now() - pageLoadTime) / 1000);

            // Update every 30 seconds if user is active
            if (timeOnPage > 0 && timeOnPage % 30 === 0) {
                const ajaxURL = window.sqtData?.ajaxurl || window.ajaxurl || './wp-admin/admin-ajax.php';
                const nonce = window.sqtData?.nonce || '';

                fetch(ajaxURL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'sqt_update_pageview',
                        session_id: sessionId,
                        page_url: window.location.href,
                        time_on_page: timeOnPage,
                        scroll_depth: maxScrollDepth,
                        nonce: nonce
                    })
                }).catch(function(error) {
                    // Silent fail
                });
            }
        }, 1000); // Check every second
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM already loaded
        init();
    }

})();
