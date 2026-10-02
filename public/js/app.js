(function() {
    var originalFetch = window.fetch;
    if (!originalFetch) return;
    window.fetch = function(url, options) {
        options = options || {};
        if (!options.headers) {
            options.headers = {};
        }
        if (options.headers instanceof Headers) {
            options.headers.set('X-Requested-With', 'XMLHttpRequest');
        } else {
            options.headers['X-Requested-With'] = 'XMLHttpRequest';
        }
        return originalFetch.call(window, url, options);
    };
})();

$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
