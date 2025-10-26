<?php get_header(); ?>

<div class="coachview-search">
    <div class="coachview-search__bar">
        <input type="text" class="coachview-search__input" placeholder="Search products…">
    </div>

    <div class="coachview-search__content">

        <aside class="coachview-search__sidebar">
            <h3 class="coachview-search__sidebar-title">Categories</h3>
            <?php
            $parent_cats = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => 0
            ]);

            foreach ($parent_cats as $parent) {
                echo '<div class="coachview-search__category-parent"><strong>' . esc_html($parent->name) . '</strong></div>';

                $child_cats = get_terms([
                    'taxonomy' => 'product_cat',
                    'hide_empty' => false,
                    'parent' => $parent->term_id
                ]);

                if ($child_cats) {
                    echo '<div class="coachview-search__category-children">';
                    foreach ($child_cats as $child) {
                        echo '<label class="coachview-search__category-label">
                          <input type="checkbox" value="' . esc_attr($child->term_id) . '"> 
                          <span>' . esc_html($child->name) . '</span>
                        </label>';
                    }
                    echo '</div>';
                }
            }
            ?>
        </aside>

        <section class="coachview-search__results">
            <div class="coachview-search__spinner"></div>
        </section>

    </div>
</div>

<script>
    const results = document.querySelector('.coachview-search__results');
    const spinner = document.querySelector('.coachview-search__spinner');
    const search = document.querySelector('.coachview-search__input');
    const checkboxes = document.querySelectorAll('.coachview-search__category-label input');

    function fetchProducts() {
        spinner.style.display = 'block';
        const categories = [...checkboxes].filter(c => c.checked).map(c => c.value);
        const data = new FormData();
        data.append('action', 'filter_products');
        data.append('search', search.value);
        categories.forEach(c => data.append('categories[]', c));

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: data
        })
            .then(res => res.text())
            .then(html => {
                results.innerHTML = '<div class="coachview-search__spinner"></div>' + html;
                spinner.style.display = 'none';
            });
    }

    search.addEventListener('input', fetchProducts);
    checkboxes.forEach(c => c.addEventListener('change', fetchProducts));
    fetchProducts();
</script>

<?php get_footer(); ?>
