jQuery(document).ready(function($) {
    const $results = $('.coachview-search__results');
    const $spinner = $('.coachview-search__spinner');
    const $search = $('.coachview-search__input');
    const $checkboxes = $('.coachview-search__category-label input');
    const $openFilters = $('.coachview-search__open-filters');
    const $closeFilters = $('.coachview-search__close-filters');
    const $filtersWrapper = $('.coachview-search__filters-wrapper');

    function fetchProducts() {
        $spinner.show();
        const categories = $checkboxes.filter(':checked').map(function() {
            return $(this).val();
        }).get();

        $.ajax({
            url: '/wp-json/coachview/v1/products/filter',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                search: $search.val(),
                categories: categories
            }),
            success: function(response) {
                $results.html('<div class="coachview-search__spinner"></div>' + response);
                $spinner.hide();
            }
        });
    }

    $search.on('input', fetchProducts);
    $checkboxes.on('change', fetchProducts);
    fetchProducts();

    $openFilters.on('click', function (e) {
        e.preventDefault();
        $filtersWrapper.show();
    });
    $closeFilters.on('click', function (e) {
        e.preventDefault();
        $filtersWrapper.hide();
    });
});
