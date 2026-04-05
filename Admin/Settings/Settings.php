<?php

namespace Coachview\Admin\Settings;

use Coachview\Constants;
use Coachview\Helpers\Api;
use Coachview\Helpers\Payment;

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
        $group = Constants::OPTION_GROUP_SYNC_SETTINGS;
        register_setting($group, Constants::OPTION_API_MODE);
        register_setting($group, Constants::OPTION_API_CLIENT_ID);
        register_setting($group, Constants::OPTION_API_SECRET);
        register_setting($group, Constants::OPTION_API_TEST_CLIENT_ID);
        register_setting($group, Constants::OPTION_API_TEST_SECRET);
        register_setting($group, Constants::TRAINING_IMPORT_LIMIT);
        register_setting($group, Constants::OPTION_REGISTER_PAGE_ID);
        register_setting($group, Constants::OPTION_DEFAULT_PAYMENT_METHOD_IDS);
        register_setting($group, Constants::OPTION_REGISTER_SUCCESS_MESSAGE_DEFAULT);
        register_setting($group, Constants::OPTION_REGISTER_SUCCESS_MESSAGE_PARTOU);
        register_setting($group, Constants::OPTION_REGISTER_SUCCESS_MESSAGE_ELEARNING);
        register_setting($group, Constants::OPTION_REGISTER_SUCCESS_REDIRECT_MESSAGE);
    }

    public function refresh_api_token_on_save($option): void
    {
        if ($option === Constants::OPTION_API_MODE) {
            error_log("API mode changed, refreshing token...");
            Api::getToken(true);
        }
    }

    public function settings_page()
    {
        $success_messages = [
                'default'  => 'Trainnig/opleiding',
                'elearning' => 'E-learning',
                'partou'   => 'Partou',
                'redirect' => 'Registratie gelukt, doorsturen naar betaalpagina'
        ];

        ?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                settings_fields(Constants::OPTION_GROUP_SYNC_SETTINGS);
                do_settings_sections(Constants::OPTION_GROUP_SYNC_SETTINGS);
                $mode = get_option(Constants::OPTION_API_MODE, Constants::API_MODE_TEST);
                ?>
                <h1>Coachview Instellingen</h1>

                <h2>API instellingen</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Api mode</th>
                        <td>
                            <input type="radio"
                                   name="<?php echo Constants::OPTION_API_MODE; ?>"
                                   value="<?php echo Constants::API_MODE_TEST; ?>"
                                   id="test_mode"
                                    <?php echo $mode === Constants::API_MODE_TEST ? 'checked' : ''; ?>>
                            <label for="test_mode">Test</label>
                            <input type="radio"
                                   name="<?php echo Constants::OPTION_API_MODE; ?>"
                                   value="<?php echo Constants::API_MODE_PRODUCTION; ?>"
                                   id="production_mode"
                                    <?php echo $mode !== Constants::API_MODE_TEST ? 'checked' : ''; ?>>
                            <label for="production_mode">Production</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Client ID</th>
                        <td><input type="text" name="<?php echo Constants::OPTION_API_CLIENT_ID; ?>"
                                   value="<?php echo esc_attr(get_option(Constants::OPTION_API_CLIENT_ID)); ?>"
                                   class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Client Secret</th>
                        <td><input type="password" name="<?php echo Constants::OPTION_API_SECRET; ?>"
                                   value="<?php echo esc_attr(get_option(Constants::OPTION_API_SECRET)); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Test Client ID</th>
                        <td><input type="text" name="<?php echo Constants::OPTION_API_TEST_CLIENT_ID; ?>"
                                   value="<?php echo esc_attr(get_option(Constants::OPTION_API_TEST_CLIENT_ID)); ?>"
                                   class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Test Client Secret</th>
                        <td><input type="password" name="<?php echo Constants::OPTION_API_TEST_SECRET; ?>"
                                   value="<?php echo esc_attr(get_option(Constants::OPTION_API_TEST_SECRET)); ?>"
                                   class="regular-text"></td>
                    </tr>
                </table>

                <h2>Synchronisatie instellingen</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Limiteer import</th>
                        <td>
                            <input type="text" name="<?php echo Constants::TRAINING_IMPORT_LIMIT; ?>"
                                   value="<?php echo esc_attr(get_option(Constants::TRAINING_IMPORT_LIMIT)); ?>"
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
                            $payment_methods = Payment::getPaymentMethods();
                            $saved_method_ids = Payment::getDefaultPaymentMethodIds();
                            foreach ($payment_methods as $method) {
                                $checked = in_array($method['id'], $saved_method_ids) ? 'checked' : '';
                                echo "<label><input type='checkbox' name='" . Constants::OPTION_DEFAULT_PAYMENT_METHOD_IDS . "[]' value='{$method['id']}' $checked> {$method['name']}</label><br />";
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
                                    'name' => Constants::OPTION_REGISTER_PAGE_ID,
                                    'show_option_none' => '— Selecteer een pagina —',
                                    'option_none_value' => '0',
                                    'selected' => get_option(Constants::OPTION_REGISTER_PAGE_ID)
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
                        $editor_id = Constants::OPTION_REGISTER_SUCCESS_MESSAGE_PREFIX . $type;
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
