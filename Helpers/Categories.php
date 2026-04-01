<?php

namespace Coachview\Helpers;

class Categories
{
    public static function getHierarchicalCategories(): array
    {
        $parent_cats = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
        ]);

        $categories = [];
        foreach ($parent_cats as $parent_cat) {
            $category = self::getCategoryWithChildren($parent_cat->term_id);
            if ($category) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    public static function getCategoryWithChildren(int $id): ?array
    {
        $term = get_term($id, 'product_cat');
        if (!$term || is_wp_error($term)) {
            return null;
        }

        $result = [
            'term_id'          => $term->term_id,
            'name'             => $term->name,
            'child_categories' => [],
        ];

        $child_cats = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => $id,
        ]);

        foreach ($child_cats as $child) {
            $result['child_categories'][] = [
                'term_id' => $child->term_id,
                'name'    => $child->name,
            ];
        }

        return $result;
    }
}
