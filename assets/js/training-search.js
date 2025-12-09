jQuery(document).ready(function($) {
    const $results = $('.coachview-search__results');
    const $spinner = $('.coachview-search__spinner');
    const $search = $('.coachview-search__input');
    const $checkboxes = $('.coachview-search__category-checkbox input');
    const $openFilters = $('.coachview-search__open-filters');
    const $closeFilters = $('.coachview-search__close-filters');
    const $applyFilters = $('.coachview-search__apply-filters');
    const $searchSubmitBtn = $('.coachview-search__submit-btn');
    const $resetFilters = $('.coachview-search__reset-filters');
    const $filtersWrapper = $('.coachview-search__filters-wrapper');
    const $activeFilters = $('.coachview-search__bar-active_filters');
    const $filterCount = $('.coachview-search__filter-count');
    const $moreItems = $('.coachview-search__more-items');
    const $loadMoreItems = $('.coachview-search__load-more-items');

    /* ---------- Helpers ---------- */
    function getUrlParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            search: params.get('search') || '',
            categories: params.getAll('category') || [],
            limit: params.get('limit') || 12
        };
    }

    function updateUrlFromForm() {
        const categories = getCheckedCategories();
        const searchValue = $search.val();
        updateUrl(searchValue, categories);
    }

    function updateUrl(search, categories, limit = 12) {
        const url = new URL(window.location);
        url.searchParams.delete('search');
        url.searchParams.delete('category');

        if (limit) url.searchParams.set('limit', limit);
        if (search) url.searchParams.set('search', search);
        categories.forEach(c => url.searchParams.append('category', c));

        window.history.pushState({}, '', url);
    }

    function getCheckedCategories() {
        return $checkboxes.filter(':checked').map(function() {
            return $(this).val();
        }).get();
    }

    function syncUIFromUrl() {
        applyFiltersFromUrl();
        fetchProducts();
        displayActiveFilterCount();
        toggleResetButton();
        // displayActiveFilters();
    }

    /* ---------- UI Sync ---------- */
    function applyFiltersFromUrl() {
        const params = getUrlParams();
        $search.val(params.search || '');

        $checkboxes.each(function() {
            const val = String($(this).val());
            $(this).prop('checked', params.categories.includes(val));
        });
    }

    /* ---------- Fetching ---------- */
    function fetchProducts() {
        $spinner.show();
        $moreItems.hide();
        const { search, categories, limit } = getUrlParams();

        $.ajax({
            url: '/wp-json/coachview/v1/products/filter',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ search, categories, limit }),
            success: function(response) {
                if (response.limit < response.total_count) {
                    $moreItems.show();
                }
                $results.html(response.items);
                $spinner.hide();
            }
        });
    }

    /* ---------- Active Filter Rendering ---------- */

    function getCategoryName(termId) {
        const label = $(`label[for="cat_${termId}"]`);
        return label?.text() || termId;
    }

    function displayActiveFilters() {
        const params = getUrlParams();
        let html = '';

        if (params.search) {
            html += `
                <button class="btn btn-secondary small" data-filter-type="search">
                    Search:${$('<div>').text(params.search).html()}
                    <i class="fa-thin fa-times"></i>
                </button>`;
        }

        params.categories.forEach(function(id) {
            html += `
                <button class="btn btn-secondary small search-filter-remove"
                        data-filter-type="category"
                        data-category-id="${id}">
                    ${$('<div>').text(getCategoryName(id)).html()}
                    <i class="fa-thin fa-times"></i>
                </button>`;
        });

        $activeFilters.html(html);

        $activeFilters.find('button').on('click', onRemoveFilterClick);
    }

    function getFilterCount() {
        const params = getUrlParams();
        return (params.search ? 1 : 0) + params.categories.length;
    }

    function displayActiveFilterCount() {
        const filterCount = getFilterCount();
        $filterCount.text(filterCount > 0 ? `(${filterCount})` : '');
    }

    function toggleResetButton() {
        const filterCount = getFilterCount();
        if (filterCount > 0) {
            $resetFilters.show();
        } else {
            $resetFilters.hide();
        }
    }

    function onRemoveFilterClick() {
        const $btn = $(this);
        const params = getUrlParams();
        const type = $btn.data('filter-type');

        if (type === 'search') {
            updateUrl('', params.categories);
            $search.val('');
        } else {
            const id = String($btn.data('category-id'));
            const filtered = params.categories.filter(c => c !== id);
            updateUrl(params.search, filtered);

            $checkboxes.filter(function() {
                return $(this).val() === id;
            }).prop('checked', false);
        }

        syncUIFromUrl();
    }

    /* ---------- Events ---------- */
    $applyFilters.on('click', function(e) {
        e.preventDefault();
        updateUrlFromForm();
        syncUIFromUrl();
        $filtersWrapper.hide();
    });

    $searchSubmitBtn.on('click', function(e) {
        e.preventDefault();
        updateUrlFromForm();
        syncUIFromUrl();
        $filtersWrapper.hide();
    });

    $search.on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            updateUrlFromForm();
            syncUIFromUrl();
            $filtersWrapper.hide();
        }
    });

    $resetFilters.on('click', function(e) {
        e.preventDefault();
        $search.val('');
        $checkboxes.prop('checked', false);
        updateUrl('', []);
        syncUIFromUrl();
    });

    $loadMoreItems.on('click', function(e) {
        e.preventDefault();
        const { search, categories, limit } = getUrlParams();
        updateUrl(search, categories, parseInt(limit) * 2);
        fetchProducts();
    });

    $openFilters.on('click', e => { e.preventDefault(); $filtersWrapper.show(); });
    $closeFilters.on('click', e => { e.preventDefault(); $filtersWrapper.hide(); });

    $filtersWrapper.on('click', function(e) {
        if ($(e.target).is($filtersWrapper)) {
            e.preventDefault();
            updateUrlFromForm();
            syncUIFromUrl();
            $filtersWrapper.hide();
        }
    });

    window.addEventListener('popstate', syncUIFromUrl);

    /* ---------- Init ---------- */
    // Move filters wrapper to end of body
    if ($filtersWrapper.length) {
        $filtersWrapper.detach().appendTo('body');
    }

    syncUIFromUrl();
});
