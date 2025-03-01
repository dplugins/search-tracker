jQuery(document).ready(function($) {
    // Handle tab switching via URL parameters
    function getParameterByName(name, url = window.location.href) {
        name = name.replace(/[\[\]]/g, '\\$&');
        var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }
    
    // Highlight the active tab based on URL
    const currentTab = getParameterByName('tab') || 'search_queries';
    $('.nav-tab').removeClass('nav-tab-active');
    $(`.nav-tab[href*="tab=${currentTab}"]`).addClass('nav-tab-active');
    
    // Add hover effect to the bars
    $('.sqt-plausible-style > div').hover(
        function() {
            $(this).addClass('sqt-bar-hover');
        },
        function() {
            $(this).removeClass('sqt-bar-hover');
        }
    );
}); 