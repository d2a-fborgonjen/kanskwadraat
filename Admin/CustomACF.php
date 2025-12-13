<?php

namespace Coachview\Admin;

class CustomACF {

    public function __construct()
    {
        add_filter('acf/location/rule_values/post_type', [$this, 'add_product_variation_rule']);
        add_filter('acf/load_field_group', [$this, 'customize_acf_field_group']);

        add_action('acf/render_field_settings/type=text', [$this, 'add_readonly_and_disabled_to_field']);
        add_action('acf/render_field_settings/type=number', [$this, 'add_readonly_and_disabled_to_field']);

        // Disabled since it messes up other ACF fields saving
        //add_action('woocommerce_save_product_variation', [$this, 'save_acf_fields_for_variation'], 10, 2);
    }

    /**
     * Add "Product Variation" to ACF post type location rules
     */
    public function add_product_variation_rule($choices): array
    {
        $choices['product_variation'] = 'Product Variation';
        return $choices;
    }

    /**
     * Adjust ACF field group styling for product variations
     */
    public function customize_acf_field_group($field_group): array
    {
        if (isset($field_group['location'][0][0]['value']) &&
            $field_group['location'][0][0]['value'] === 'product_variation'
        ) {
            $field_group['style'] = 'seamless';
            $field_group['position'] = 'normal';
        }
        return $field_group;
    }

    function add_readonly_and_disabled_to_field($field) {
        acf_render_field_setting( $field, array(
            'label'      => __('Readonly','acf'),
            'instructions'  => '',
            'type'      => 'radio',
            'name'      => 'readonly',
            'choices'    => array(
                0        => __("No", 'acf'),
                1        => __("Yes", 'acf'),
            ),
            'layout'  =>  'horizontal',
        ));
    }
}
