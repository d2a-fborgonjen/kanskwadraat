<?php

namespace Coachview\Sync;

use Coachview\Sync\Dataloaders\CategoryDataloader;

class CategorySync
{
    public static function run(): void
    {
        log_cv_info('Starting category synchronization.');
        $categories = CategoryDataloader::load_categories(100);

        foreach ($categories as $parent => $children) {
            $parentId = get_or_create_category($parent);
            foreach ($children as $child) {
                get_or_create_category($child, $parentId);
            }
        }
        log_cv_info('Category synchronization completed.');
    }
}