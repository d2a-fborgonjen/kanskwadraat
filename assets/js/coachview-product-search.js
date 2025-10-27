jQuery(document).ready(function($) {
    const $results = $('.coachview-search__results');
    const $spinner = $('.coachview-search__spinner');
    const $search = $('.coachview-search__input');
    const $checkboxes = $('.coachview-search__category-label input');

    function fetchProducts() {
        $spinner.show();
        const categories = $checkboxes.filter(':checked').map(function() {
            return $(this).val();
        }).get();

        $.ajax({
            url: '/wp-admin/admin-ajax.php',
            method: 'POST',
            data: {
                action: 'filter_products',
                search: $search.val(),
                'categories[]': categories
            },
            success: function(html) {
                $results.html('<div class="coachview-search__spinner"></div>' + html);
                $spinner.hide();
            }
        });
    }

    $search.on('input', fetchProducts);
    $checkboxes.on('change', fetchProducts);
    fetchProducts();
});
