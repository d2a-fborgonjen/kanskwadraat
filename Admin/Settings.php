<?php

namespace Coachview\Admin;

use Coachview\Models\RegistrationFormType;

class Settings
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_submenu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('updated_option', [$this, 'refresh_api_token_on_save']);
    }

    public function add_submenu(): void
    {
        add_submenu_page(
                'coachview',
                'Coachview Instellingen',
                'Instellingen',
                'manage_options',
                'coachview-settings',
                [$this, 'settings_page']
        );
    }

    public function register_settings(): void
    {
        register_setting('coachview_sync_settings', 'coachview_api_mode');
        register_setting('coachview_sync_settings', 'coachview_client_id');
        register_setting('coachview_sync_settings', 'coachview_secret');
        register_setting('coachview_sync_settings', 'coachview_test_client_id');
        register_setting('coachview_sync_settings', 'coachview_test_secret');
        register_setting('coachview_sync_settings', 'coachview_training_import_limit');
        register_setting('coachview_sync_settings', 'coachview_register_page');
        register_setting('coachview_sync_settings', 'cv_default_payment_method_ids');
        register_setting('coachview_sync_settings', 'cv_register_success_message_default');
        register_setting('coachview_sync_settings', 'cv_register_success_message_partou');
        register_setting('coachview_sync_settings', 'cv_register_success_message_elearning');
        register_setting('coachview_sync_settings', 'cv_register_success_message_redirect');
    }

    public function refresh_api_token_on_save($option): void
    {
        if ($option === 'coachview_api_mode') {
            error_log("API mode changed, refreshing token...");
            coachview_api_token(true);
        }
    }

    public function settings_page()
    {
        $success_messages = [
                'default' => 'Trainnig/opleiding',
                'elearning' => 'E-learning',
                'partou' => 'Partou',
                'redirect' => 'Registratie gelukt, doorsturen naar betaalpagina'
        ];

        ?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                settings_fields('coachview_sync_settings');
                do_settings_sections('coachview_sync_settings');
                $mode = get_option('coachview_api_mode', 'test');
                ?>
                <h1>Coachview Instellingen</h1>

                <h2>API instellingen</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Api mode</th>
                        <td>
                            <input type="radio" name="coachview_api_mode" id="test_mode"
                                   value="test" <?php echo $mode === 'test' ? 'checked' : ''; ?>><label for="test_mode">Test</label>
                            <input type="radio" name="coachview_api_mode" id="production_mode"
                                   value="production" <?php echo $mode !== 'test' ? 'checked' : ''; ?>><label
                                    for="production_mode">Production</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Client ID</th>
                        <td><input type="text" name="coachview_client_id"
                                   value="<?php echo esc_attr(get_option('coachview_client_id')); ?>"
                                   class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Client Secret</th>
                        <td><input type="password" name="coachview_secret"
                                   value="<?php echo esc_attr(get_option('coachview_secret')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test Client ID</th>
                        <td><input type="text" name="coachview_test_client_id"
                                   value="<?php echo esc_attr(get_option('coachview_test_client_id')); ?>"
                                   class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Test Client Secret</th>
                        <td><input type="password" name="coachview_test_secret"
                                   value="<?php echo esc_attr(get_option('coachview_test_secret')); ?>"
                                   class="regular-text"></td>
                    </tr>
                </table>

                <h2>Synchronisatie instellingen</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Limiteer import</th>
                        <td>
                            <input type="text" name="coachview_training_import_limit"
                                   value="<?php echo esc_attr(get_option('coachview_training_import_limit')); ?>"
                                   class="regular-text">
                            <p class="description">
                                Geef hier aan hoeveel trainingen er vanuit coachview gesynchoniseerd worden tijdens
                                een import. Gebruik deze optie bijvoorbeeld om sneller te kunnen testen met een beperkte
                                dataset.
                            </p>
                        </td>
                    </tr>
                </table>

                <h2>Betaalmethodes</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Standaard betaalmethodes</th>
                        <td>
                            <?php
                            $payment_methods = coachview_get_payment_methods();
                            $saved_method_ids = coachview_get_default_payment_method_ids();
                            foreach ($payment_methods as $method) {
                                $checked = in_array($method['id'], $saved_method_ids) ? 'checked' : '';
                                echo "<label><input type='checkbox' name='cv_default_payment_method_ids[]' value='{$method['id']}' $checked> {$method['name']}</label><br />";
                            }
                            ?>
                            <p class="description">
                                Selecteer de betaalmethodes die standaard ingesteld staan bij nieuwe producten. <br />
                                Om een afwijkende selectie in te stellen per training, kun je gebruik maken van instellingen bij de training zelf.
                            </p>
                        </td>
                    </tr>
                </table>

                <h2>Aanmelden / Registratie</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Registratie pagina</th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                    'name' => 'coachview_register_page',
                                    'show_option_none' => '— Selecteer een pagina —',
                                    'option_none_value' => '0',
                                    'selected' => get_option('coachview_register_page')
                            ]);
                            ?>
                            <p class="description">
                                Selecteer de wordpress pagina die ingericht is als registratie pagina.
                                Op deze pagina dient de [cv_register_form] shortcode geplaatst te zijn,
                                zodat gebruikers zich kunnen registreren / aanmelden voor een training.
                            </p>
                        </td>
                    </tr>
                </table>

                <h2>Succesberichten</h2>
                <table class="form-table">
                    <tbody>
                    <?php
                    foreach ($success_messages as $type => $label) {
                        echo '<tr>';
                        echo '<th scope="row">' . $label . '</th>';
                        echo "<td>";
                        $editor_id = 'cv_register_success_message_' . $type;
                        $value = get_option($editor_id, 'Dankjewel voor je inschrijving');
                        $settings = [
                            'textarea_name' => $editor_id,
                            'textarea_rows' => 5,
                            'media_buttons' => false,
                            'tinymce' => [
                                'toolbar1' => 'bold italic underline | alignleft aligncenter alignright | bulletlist numlist | removeformat',
                                'toolbar2' => '',
                                'toolbar3' => '',
                            ],
                        ];
                        wp_editor($value, $editor_id, $settings);

                        echo "</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
                <?php submit_button('Instellingen opslaan'); ?>
            </form>
        </div>
        <?php
    }
}
