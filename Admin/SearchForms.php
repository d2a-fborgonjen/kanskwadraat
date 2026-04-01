<?php

namespace Coachview\Admin;

use Coachview\Constants;
use Coachview\Helpers\Categories;

class SearchForms
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_submenu']);
        add_action('admin_init', [$this, 'register_search_forms']);
        add_action('admin_post_coachview_save_search_form', [$this, 'handle_save_form']);
        add_action('admin_post_coachview_delete_search_form', [$this, 'handle_delete_form']);
    }

    public function add_submenu(): void
    {
        add_submenu_page(
                'coachview',
                'Coachview zoekformulieren',
                'Zoekformulieren',
                'manage_options',
                'coachview-search-forms',
                [$this, 'search_forms']
        );
    }

    public function register_search_forms(): void
    {
        register_setting(Constants::OPTION_SEARCH_FORMS, Constants::OPTION_SEARCH_FORMS);
    }

    public function handle_save_form(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('coachview_save_search_form');

        $forms = $this->get_forms();
        $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
        $is_edit = !empty($form_id) && isset($forms[$form_id]);

        $form_data = [
            'name' => isset($_POST['form_name']) ? sanitize_text_field($_POST['form_name']) : '',
            'coachview_search_page' => isset($_POST['coachview_search_page']) ? absint($_POST['coachview_search_page']) : 0,
            'category_1' => isset($_POST['category_1']) ? absint($_POST['category_1']) : 0,
            'category_2' => isset($_POST['category_2']) ? absint($_POST['category_2']) : 0,
        ];

        if ($is_edit) {
            $forms[$form_id] = array_merge($forms[$form_id], $form_data);
        } else {
            $new_id = uniqid('form_', true);
            $form_data['id'] = $new_id;
            $forms[$new_id] = $form_data;
        }

        update_option(Constants::OPTION_SEARCH_FORMS, $forms);

        wp_redirect(admin_url('admin.php?page=coachview-search-forms&message=saved'));
        exit;
    }

    public function handle_delete_form(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        check_admin_referer('coachview_delete_search_form');

        $form_id = isset($_GET['form_id']) ? sanitize_text_field($_GET['form_id']) : '';
        if (empty($form_id)) {
            wp_redirect(admin_url('admin.php?page=coachview-search-forms'));
            exit;
        }

        $forms = $this->get_forms();
        if (isset($forms[$form_id])) {
            unset($forms[$form_id]);
            update_option(Constants::OPTION_SEARCH_FORMS, $forms);
        }

        wp_redirect(admin_url('admin.php?page=coachview-search-forms&message=deleted'));
        exit;
    }

    private function get_forms(): array
    {
        $forms = get_option(Constants::OPTION_SEARCH_FORMS, []);
        return is_array($forms) ? $forms : [];
    }

    public function search_forms()
    {
        $forms = $this->get_forms();
        $editing_form_id = isset($_GET['edit']) ? sanitize_text_field($_GET['edit']) : '';
        $editing_form = !empty($editing_form_id) && isset($forms[$editing_form_id]) ? $forms[$editing_form_id] : null;

        // Show success message
        if (isset($_GET['message'])) {
            $message = sanitize_text_field($_GET['message']);
            if ($message === 'saved') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Zoekformulier opgeslagen.', 'coachview') . '</p></div>';
            } elseif ($message === 'deleted') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Zoekformulier verwijderd.', 'coachview') . '</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1>Zoekformulieren (simple search)</h1>
            <p>Hier kun je de zoekformulieren beheren die over de website verspreid zijn.</p>

            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div style="flex: 1;">
                    <h2><?php echo $editing_form ? esc_html__('Bewerk zoekformulier', 'coachview') : esc_html__('Nieuw zoekformulier', 'coachview'); ?></h2>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('coachview_save_search_form'); ?>
                        <input type="hidden" name="action" value="coachview_save_search_form">
                        <?php if ($editing_form): ?>
                            <input type="hidden" name="form_id" value="<?php echo esc_attr($editing_form_id); ?>">
                        <?php endif; ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="form_name"><?php esc_html_e('Naam', 'coachview'); ?></label></th>
                                <td>
                                    <input type="text" name="form_name" id="form_name" class="regular-text" 
                                           value="<?php echo $editing_form ? esc_attr($editing_form['name']) : ''; ?>" required>
                                    <p class="description"><?php esc_html_e('Geef dit zoekformulier een herkenbare naam zoals de locatie op de website.', 'coachview'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="coachview_search_page"><?php esc_html_e('Zoekresultaten pagina', 'coachview'); ?></label></th>
                                <td>
                                    <?php
                                    wp_dropdown_pages([
                                            'name' => 'coachview_search_page',
                                            'id' => 'coachview_search_page',
                                            'show_option_none' => '— Selecteer een pagina —',
                                            'option_none_value' => '0',
                                            'selected' => $editing_form ? absint($editing_form['coachview_search_page']) : 0
                                    ]);
                                    ?>
                                    <p class="description"><?php esc_html_e('Selecteer de doelpagina op, waar de zoekresultaten getoond worden.', 'coachview'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Categorieën', 'coachview'); ?></label></th>
                                <td>
                                    <?php
                                    $categories = Categories::getHierarchicalCategories();

                                    for ($i = 1; $i <= 2; $i++) {
                                        $field_name = 'category_' . $i;
                                        $selected_category = $editing_form ? absint($editing_form[$field_name]) : 0;
                                        echo '<div style="margin-bottom: 10px;">';
                                        echo '<label for="' . esc_attr($field_name) . '">' . sprintf(esc_html__('Categorie %d', 'coachview'), $i) . ':</label><br>';
                                        echo '<select name="' . esc_attr($field_name) . '" id="' . esc_attr($field_name) . '" class="postform" style="width: 100%; max-width: 400px;">';
                                        echo '<option value="0"' . ($selected_category == 0 ? ' selected="selected"' : '') . '>— Selecteer een categorie —</option>';
                                        foreach ($categories as $category) {
                                            $category_id = $category['term_id'];
                                            $selected = ($selected_category == $category_id) ? ' selected="selected"' : '';
                                            echo '<option value="' . esc_attr($category_id) . '"' . $selected . '>' . esc_html($category['name']) . "</option>";
                                        }
                                        echo '</select>';
                                        echo '</div>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>

                        <div style="display: flex; gap: 10px; margin: 20px 0;">
                            <?php submit_button($editing_form ? esc_html__('Bijwerken', 'coachview') : esc_html__('Toevoegen', 'coachview'), 'primary', 'submit', false); ?>
                            <?php if ($editing_form): ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=coachview-search-forms')); ?>" class="button"><?php esc_html_e('Annuleren', 'coachview'); ?></a>
                            <?php endif; ?>
                        </div>

                        <?php if ($editing_form): ?>
                            <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                                <h3 style="margin-top: 0;"><?php esc_html_e('Shortcode', 'coachview'); ?></h3>
                                <p><?php esc_html_e('Gebruik deze shortcode om dit zoekformulier op een pagina of bericht te plaatsen:', 'coachview'); ?></p>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <code style="flex: 1; padding: 8px; background: #fff; border: 1px solid #ddd;">[cv_simple_search name="<?php echo esc_attr($editing_form['name']); ?>"]</code>
                                    <button type="button" 
                                            class="button coachview-copy-shortcode" 
                                            data-shortcode="<?php echo esc_attr('[cv_simple_search name="' . esc_attr($editing_form['name']) . '"]'); ?>"
                                            title="<?php esc_attr_e('Kopieer shortcode', 'coachview'); ?>">
                                        <?php esc_html_e('Kopieer', 'coachview'); ?>
                                    </button>
                                </div>
                                <p style="margin-top: 10px; margin-bottom: 0; font-size: 13px;">
                                    <strong><?php esc_html_e('Optionele attributen:', 'coachview'); ?></strong><br>
                                    <?php esc_html_e('Je kunt optioneel het attribuut', 'coachview'); ?> <code>orientation</code> <?php esc_html_e('toevoegen met de waarde', 'coachview'); ?> <code>horizontal</code> <?php esc_html_e('of', 'coachview'); ?> <code>vertical</code>. <?php esc_html_e('Standaard is', 'coachview'); ?> <code>horizontal</code>.<br>
                                    <em><?php esc_html_e('Voorbeeld:', 'coachview'); ?></em> <code>[cv_simple_search name="<?php echo esc_attr($editing_form['name']); ?>" orientation="vertical"]</code>
                                </p>
                            </div>
                        <?php endif; ?>

                    </form>
                </div>

                <div style="flex: 1;">
                    <h2><?php esc_html_e('Bestaande zoekformulieren', 'coachview'); ?></h2>
                    <?php if (empty($forms)): ?>
                        <p><?php esc_html_e('Nog geen zoekformulieren toegevoegd.', 'coachview'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Naam', 'coachview'); ?></th>
                                    <th><?php esc_html_e('Categorieën', 'coachview'); ?></th>
                                    <th><?php esc_html_e('Acties', 'coachview'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forms as $form_id => $form): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($form['name']); ?></strong></td>
                                        <td>
                                            <?php
                                            $category_names = [];
                                            for ($i = 1; $i <= 2; $i++) {
                                                $field_name = 'category_' . $i;
                                                $category_id = isset($form[$field_name]) ? absint($form[$field_name]) : 0;
                                                if ($category_id > 0) {
                                                    $term = get_term($category_id, 'product_cat');
                                                    if ($term && !is_wp_error($term)) {
                                                        $category_names[] = esc_html($term->name);
                                                    }
                                                }
                                            }
                                            if (!empty($category_names)) {
                                                echo implode(', ', $category_names);
                                            } else {
                                                echo '<span style="color: #999;">—</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=coachview-search-forms&edit=' . urlencode($form_id))); ?>" class="button button-small"><?php esc_html_e('Bewerken', 'coachview'); ?></a>
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=coachview_delete_search_form&form_id=' . urlencode($form_id)), 'coachview_delete_search_form')); ?>" 
                                               class="button button-small" 
                                               onclick="return confirm('<?php esc_attr_e('Weet je zeker dat je dit zoekformulier wilt verwijderen?', 'coachview'); ?>');"><?php esc_html_e('Verwijderen', 'coachview'); ?></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                const copyButtons = document.querySelectorAll('.coachview-copy-shortcode');
                
                copyButtons.forEach(function(button) {
                    button.addEventListener('click', function() {
                        const shortcode = this.getAttribute('data-shortcode');
                        const originalText = this.textContent;
                        
                        // Use the Clipboard API if available
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(shortcode).then(function() {
                                button.textContent = '<?php echo esc_js(__('Gekopieerd!', 'coachview')); ?>';
                                setTimeout(function() {
                                    button.textContent = originalText;
                                }, 2000);
                            }).catch(function(err) {
                                console.error('Failed to copy: ', err);
                                fallbackCopy(shortcode, button, originalText);
                            });
                        } else {
                            fallbackCopy(shortcode, button, originalText);
                        }
                    });
                });
                
                function fallbackCopy(text, button, originalText) {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-999999px';
                    document.body.appendChild(textArea);
                    textArea.select();
                    
                    try {
                        document.execCommand('copy');
                        button.textContent = '<?php echo esc_js(__('Gekopieerd!', 'coachview')); ?>';
                        setTimeout(function() {
                            button.textContent = originalText;
                        }, 2000);
                    } catch (err) {
                        console.error('Fallback copy failed: ', err);
                        alert('<?php echo esc_js(__('Kon shortcode niet kopiëren. Shortcode:', 'coachview')); ?> ' + text);
                    }
                    
                    document.body.removeChild(textArea);
                }
            });
        })();
        </script>
        <?php
    }
}
