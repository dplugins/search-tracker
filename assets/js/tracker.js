jQuery(document).ready(function ($) {
    $(".search-result-item a").on("click", function () {
        let query = new URLSearchParams(window.location.search).get("s");
        let url = $(this).attr("href");
        if (query && url) {
            $.post(sqtAjax.ajaxurl, {
                action: "sqt_track_click",
                query: query,
                url: url,
            });
        }
    });
});