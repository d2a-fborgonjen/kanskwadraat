<?php

namespace Coachview\Presentation;

class Categories
{
    public function __construct()
    {
        add_action('init', [$this, 'register_taxonomies']);
    }

    public function register_taxonomies(): void {
        if (!taxonomy_exists('cv_categories')) {
            register_taxonomy(
                'cv_categories',
                'product',
                [
                    'label' => 'Training Categorieen',
                    'public' => true,
                    'show_ui' => true,
                    'show_in_nav_menus' => true,
                    'show_in_rest' => true,
                    'hierarchical' => true, // Set to true for category-like behavior
                    'rewrite' => [ 'slug' => 'cv_categories' ],
                    'show_admin_column' => true,
                ]
            );
        }

//        if (!taxonomy_exists('cv_target_group')) {
//            register_taxonomy(
//                'cv_target_group',
//                'product',
//                [
//                    'label' => 'Doelgroep',
//                    'public' => true,
//                    'show_ui' => true,
//                    'show_in_nav_menus' => true,
//                    'show_in_rest' => true,
//                    'hierarchical' => false,
//                    'rewrite' => [ 'slug' => 'cv_target_group' ],
//                    'show_admin_column' => true,
//                ]
//            );
//        }
//
//        if (!taxonomy_exists('cv_locations')) {
//            register_taxonomy(
//                'cv_locations',
//                'product',
//                [
//                    'label' => __('Locations', 'coachview'),
//                    'public' => true,
//                    'show_ui' => true,
//                    'show_in_nav_menus' => true,
//                    'show_in_rest' => true,
//                    'hierarchical' => false,
//                    'rewrite' => ['slug' => 'cv_locations'],
//                    'show_admin_column' => true
//                ]
//            );
//        }
    }
}