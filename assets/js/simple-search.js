jQuery(document).ready(function($) {
    const $form = $('form.simple-search-form');

    $form.on('submit', function(e) {
        e.preventDefault();

        const search = $('input[name=search]').val();
        const category_1 = $('select[name=category_1]')?.val();
        const category_2 = $('select[name=category_2]')?.val();

        const params = new URLSearchParams('');
        if (search) {
            params.append('search',  search);
        }
        if (category_1) {
            params.append('category', category_1);
        }
        if (category_2) {
            params.append('category', category_2);
        }
        const actionUrl = $form.attr('action') || window.location.href;
        window.location.href = actionUrl + '?' + params.toString();
    });


});