<?php

namespace Coachview\Presentation\Hooks;

class TrainingHooks {

    public function __construct()
    {
        add_action('woocommerce_variable_add_to_cart', [$this, 'render_training_details'], 25);

        add_filter('acf/location/rule_values/post_type', [$this, 'add_product_variation_rule']);
        add_filter('acf/load_field_group', [$this, 'customize_acf_field_group']);

        add_action('woocommerce_product_after_variable_attributes', [$this, 'render_acf_fields_for_variation'], 10, 3);
        add_action('woocommerce_save_product_variation', [$this, 'save_acf_fields_for_variation'], 10, 2);
    }

    /**
     * Render training details below variations on the frontend
     */
    public function render_training_details(): void
    {
        global $product;
        if (!$product || !$product->is_type('variable')) {
            return;
        }

        $id = $product->get_id();
        echo do_shortcode("[cv_training_details id=\"{$id}\"]");
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
        if (
            isset($field_group['location'][0][0]['value']) &&
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
        if ($fields) {
            foreach ($fields as $field) {
                acf_render_field_wrap(array_merge($field, [
                    'value'  => get_field($field['name'], $variation->ID),
                    'prefix' => "acf[var_{$loop}]",
                ]));
            }
        }
    }

    /**
     * Save ACF fields for each variation
     */
    public function save_acf_fields_for_variation($variation_id, $i): void
    {
        $fields = $this->get_acf_fields();
        $prefix = "var_{$i}";
        if ($fields && isset($_POST['acf']) && isset($_POST['acf'][$prefix])) {
            foreach ($fields as $field) {
                $key = $field['key'];

                if (isset($_POST['acf'][$prefix][$key])) {
                    update_field($key, $_POST['acf'][$prefix][$key], $variation_id);
                }
            }
        }
    }

    private function get_acf_fields() {
        $field_groups = acf_get_field_groups(['post_type' => 'product_variation']);

        if (!empty($field_groups)) {
            return acf_get_fields($field_groups[0]['ID']);
        }
        return null;
    }

}
