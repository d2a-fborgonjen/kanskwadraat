jQuery(document).ready(function($) {
    const $results = $('.coachview-search__results');
    const $spinner = $('.coachview-search__spinner');
    const $search = $('.coachview-search__input');
    const $checkboxes = $('.coachview-search__category-label input');
    const $openFilters = $('.coachview-search__open-filters');
    const $closeFilters = $('.coachview-search__close-filters');
    const $applyFilters = $('.coachview-search__apply-filters');
    const $filtersWrapper = $('.coachview-search__filters-wrapper');
    const $activeFilters = $('.coachview-search__bar-active_filters');

    // Function to get URL parameters
    function getUrlParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            search: params.get('search') || '',
            categories: params.getAll('category') || []
        };
    }

    // Function to update URL with current filters
    function updateUrl(search, categories) {
        const url = new URL(window.location);
        url.searchParams.delete('search');
        url.searchParams.delete('category');
        
        if (search) {
            url.searchParams.set('search', search);
        }
        
        categories.forEach(function(category) {
            url.searchParams.append('category', category);
        });

        window.history.pushState({}, '', url);
    }

    // Function to get category name by term_id
    function getCategoryName(termId) {
        const termIdStr = String(termId);
        const $checkbox = $checkboxes.filter(function() {
            return String($(this).val()) === termIdStr;
        });
        if ($checkbox.length) {
            return $checkbox.closest('label').find('span').text() || termId;
        }
        return termId;
    }

    // Function to display active filters
    function displayActiveFilters() {
        const params = getUrlParams();
        let html = '';

        // Display search term if present
        if (params.search) {
            html += '<span class="coachview-search__active-filter">';
            html += '<span class="coachview-search__active-filter-label">Search:</span> ';
            html += '<span class="coachview-search__active-filter-value">' + $('<div>').text(params.search).html() + '</span>';
            html += '<button type="button" class="coachview-search__active-filter-remove" data-filter-type="search" aria-label="Remove search filter">×</button>';
            html += '</span>';
        }

        // Display selected categories
        params.categories.forEach(function(categoryId) {
            const categoryName = getCategoryName(categoryId);
            html += '<span class="coachview-search__active-filter">';
            html += '<span class="coachview-search__active-filter-value">' + $('<div>').text(categoryName).html() + '</span>';
            html += '<button type="button" class="coachview-search__active-filter-remove" data-filter-type="category" data-category-id="' + $('<div>').text(categoryId).html() + '" aria-label="Remove category filter">×</button>';
            html += '</span>';
        });

        $activeFilters.html(html);

        // Add click handlers for remove buttons
        $activeFilters.find('.coachview-search__active-filter-remove').on('click', function() {
            const $button = $(this);
            const filterType = $button.data('filter-type');
            const params = getUrlParams();

            if (filterType === 'search') {
                // Remove search filter from URL
                updateUrl('', params.categories);
                $search.val('');
            } else if (filterType === 'category') {
                // Remove category filter from URL
                const categoryId = String($button.data('category-id'));
                const filteredCategories = params.categories.filter(function(id) {
                    return String(id) !== categoryId;
                });
                updateUrl(params.search, filteredCategories);
                
                // Update checkbox to match URL
                const $checkbox = $checkboxes.filter(function() {
                    return String($(this).val()) === categoryId;
                });
                $checkbox.prop('checked', false);
            }

            // Update form to match URL state
            applyFiltersFromUrl();
            fetchProducts();
            displayActiveFilters();
        });
    }

    // Function to apply filters from URL on page load
    function applyFiltersFromUrl() {
        const params = getUrlParams();
        
        // Set search input (clear if empty)
        $search.val(params.search || '');
        
        // Set checkboxes
        $checkboxes.each(function() {
            const $checkbox = $(this);
            const value = String($checkbox.val());
            const isChecked = params.categories.some(function(catId) {
                return String(catId) === value;
            });
            $checkbox.prop('checked', isChecked);
        });
    }

    function fetchProducts() {
        $spinner.show();
        // Read from URL parameters (source of truth)
        const params = getUrlParams();
        const searchValue = params.search;
        const categories = params.categories;

        $.ajax({
            url: '/wp-json/coachview/v1/products/filter',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                search: searchValue,
                categories: categories
            }),
            success: function(response) {
                $results.html('<div class="coachview-search__spinner"></div>' + response);
                $spinner.hide();
            }
        });
    }

    // Apply filters from URL on page load
    applyFiltersFromUrl();
    
    // Initial fetch on page load
    fetchProducts();
    
    // Display active filters on page load
    displayActiveFilters();

    // Only trigger search when Apply Filters button is clicked
    $applyFilters.on('click', function (e) {
        e.preventDefault();
        // Get current form values and update URL first
        const categories = $checkboxes.filter(':checked').map(function() {
            return $(this).val();
        }).get();
        const searchValue = $search.val();
        updateUrl(searchValue, categories);
        // Then fetch products (which reads from URL)
        fetchProducts();
        // Display active filters
        displayActiveFilters();
        // hide filters wrapper
        $filtersWrapper.hide();
    });

    // Optional: Allow Enter key in search input to trigger apply
    $search.on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            // Get current form values and update URL first
            const categories = $checkboxes.filter(':checked').map(function() {
                return $(this).val();
            }).get();
            const searchValue = $search.val();
            updateUrl(searchValue, categories);
            // Then fetch products (which reads from URL)
            fetchProducts();
            // Display active filters
            displayActiveFilters();
        }
    });

    $openFilters.on('click', function (e) {
        e.preventDefault();
        $filtersWrapper.show();
    });
    $closeFilters.on('click', function (e) {
        e.preventDefault();
        $filtersWrapper.hide();
    });

    // Handle browser back/forward navigation
    window.addEventListener('popstate', function() {
        applyFiltersFromUrl();
        fetchProducts();
        displayActiveFilters();
    });
});
