function searchTerms(query) {
    if (query.length === 0) {
        document.getElementById('search-results').innerHTML = '';
        return;
    }
    var data = {
        'action': 'fg_search_terms',
        'query': query
    };
    jQuery.post(ajaxurl, data, function(response) {
        document.getElementById('search-results').innerHTML = response;
    });
}
