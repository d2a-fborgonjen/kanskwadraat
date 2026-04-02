<?php

namespace Coachview\Sync;

use Coachview\Constants;
use Coachview\Sync\Dataloaders\CategoryDataloader;

class CategorySync
{
    public static function run(): void
    {
        log_cv_info('Starting category synchronization.');
        $categories = CategoryDataloader::load_categories(100);
        foreach ($categories as $parent_name => $children) {
            $parent_id = get_or_create_category($parent_name);
            $child_cat_ids = [];
            foreach ($children as $child_name) {
                $child_cat_ids[] = get_or_create_category($child_name, $parent_id);
            }
            CategorySync::delete_category_children_not_in_list($parent_id, $child_cat_ids);
        }
    }

    public static function cleanup_after_sync()
    {
        self::delete_categories_without_sync_time();
        self::delete_categories_with_expired_sync_time();
    }

    private static function delete_category_children_not_in_list($parent_id, array $valid_child_ids): void
    {
        $children = get_categories_by_parent_not_in($parent_id, $valid_child_ids);
        if (is_wp_error($children)) {
            return;
        }

        foreach ($children as $child) {
            log_cv_info("Delete category {$child->name} as it no longer exists in Coachview.");
            wp_delete_term($child->term_id, 'product_cat');
        }
    }

    private static function delete_categories_with_expired_sync_time(): void {
        $terms = get_terms(CategorySync::get_term_sync_query('<', time() - 600)); // 10 minutes ago
        $cats = is_wp_error($terms) ? [] : $terms;

        foreach ($cats as $term) {
            log_cv_info("Delete category {$term->name} {$term->parent} as its sync time has expired.");
            wp_delete_term($term->term_id, 'product_cat');
        }
    }

    private static function delete_categories_without_sync_time(): void
    {
        $terms = get_terms(CategorySync::get_term_sync_query('NOT EXISTS'));
        $cats = is_wp_error($terms) ? [] : $terms;

        foreach ($cats as $term) {
            log_cv_info("Delete category {$term->name} as it has no sync time.");
            wp_delete_term($term->term_id, 'product_cat');
        }
    }

    private static function get_term_sync_query($compare, $value = null): array {
        return [
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'   => Constants::META_LAST_SYNC,
                    'value' => $value,
                    'compare' => $compare
                ],
            ],
        ];
    }

}