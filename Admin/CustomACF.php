<?php

namespace Coachview\Admin;

class CustomACF {

    public function __construct()
    {
        add_filter('acf/location/rule_values/post_type', [$this, 'add_product_variation_rule']);
        add_filter('acf/load_field_group', [$this, 'customize_acf_field_group']);
        add_action('woocommerce_product_after_variable_attributes', [$this, 'render_acf_fields_for_variation'], 10, 3);


        add_action('acf/render_field_settings/type=text', [$this, 'add_readonly_and_disabled_to_field']);
        add_action('acf/render_field_settings/type=number', [$this, 'add_readonly_and_disabled_to_field']);
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


    /**
     * Render ACF fields inside each product variation panel
     */
    public function render_acf_fields_for_variation($loop, $variation_data, $variation): void
    {
        $fields = $this->get_acf_fields();
        echo '<h1>Coachview training gegevens</h1>';
        foreach ($fields as $field) {
            acf_render_field_wrap(array_merge($field, [
                'value'  => get_field($field['name'], $variation->ID),
                'prefix' => "acf[var_{$loop}]",
            ]));
        }
    }

    private function get_acf_fields(): array {
        $field_groups = acf_get_field_groups(['post_type' => 'product_variation']);

        if (!empty($field_groups)) {
            return acf_get_fields($field_groups[0]['ID']);
        }
        return [];
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
