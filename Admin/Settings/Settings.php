<?php

namespace Coachview\Admin\Settings;

use Coachview\Constants;

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
        register_setting('coachview_sync_settings', Constants::OPTION_API_MODE);
        register_setting('coachview_sync_settings', Constants::OPTION_API_CLIENT_ID);
        register_setting('coachview_sync_settings', Constants::OPTION_API_SECRET);
        register_setting('coachview_sync_settings', Constants::OPTION_API_TEST_CLIENT_ID);
        register_setting('coachview_sync_settings', Constants::OPTION_API_TEST_SECRET);
        register_setting('coachview_sync_settings', Constants::TRAINING_IMPORT_LIMIT);
        register_setting('coachview_sync_settings', Constants::OPTION_REGISTER_PAGE_ID);
        register_setting('coachview_sync_settings', Constants::OPTION_DEFAULT_PAYMENT_METHOD_IDS);
        register_setting('coachview_sync_settings', Constants::OPTION_REGISTER_SUCCESS_MESSAGE_DEFAULT);
        register_setting('coachview_sync_settings', Constants::OPTION_REGISTER_SUCCESS_MESSAGE_PARTOU);
        register_setting('coachview_sync_settings', Constants::OPTION_REGISTER_SUCCESS_MESSAGE_ELEARNING);
        register_setting('coachview_sync_settings', Constants::OPTION_REGISTER_SUCCESS_REDIRECT_MESSAGE);
    }

    public function refresh_api_token_on_save($option): void
    {
        if ($option === Constants::OPTION_API_MODE) {
            error_log("API mode changed, refreshing token...");
            coachview_api_token(true);
        }
    }

    public function settings_page()
    {
        $success_messages = [
                'default' => esc_html__('Training/opleiding', Constants::TEXT_DOMAIN),
                'elearning' => esc_html__('E-learning', Constants::TEXT_DOMAIN),
                'partou' => esc_html__('Partou', Constants::TEXT_DOMAIN),
                'redirect' => esc_html__('Registratie gelukt, doorsturen naar betaalpagina', Constants::TEXT_DOMAIN)
        ];

        ?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                settings_fields('coachview_sync_settings');
                do_settings_sections('coachview_sync_settings');
                $mode = get_option(Constants::OPTION_API_MODE, Constants::API_MODE_TEST);
                ?>
                <h1><?php echo esc_html__('Coachview Instellingen', Constants::TEXT_DOMAIN); ?></h1>

                <h2><?php echo esc_html__('API instellingen', Constants::TEXT_DOMAIN); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('API-modus', Constants::TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="radio" name="<?php echo esc_attr(Constants::OPTION_API_MODE); ?>" id="test_mode"
                                   value="<?php echo esc_attr(Constants::API_MODE_TEST); ?>" <?php echo $mode === Constants::API_MODE_TEST ? 'checked' : ''; ?>>
                            <label for="test_mode"><?php echo esc_html__('Test', Constants::TEXT_DOMAIN); ?></label>
                            <input type="radio" name="<?php echo esc_attr(Constants::OPTION_API_MODE); ?>" id="production_mode"
                                   value="<?php echo esc_attr(Constants::API_MODE_PRODUCTION); ?>" <?php echo $mode !== Constants::API_MODE_TEST ? 'checked' : ''; ?>>
                            <label for="production_mode"><?php echo esc_html__('Productie', Constants::TEXT_DOMAIN); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Client ID', Constants::TEXT_DOMAIN); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(Constants::OPTION_API_CLIENT_ID); ?>"
                                   value="<?php echo esc_attr(get_option(Constants::OPTION_API_CLIENT_ID)); ?>"
                                   class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Client Secret', Constants::TEXT_DOMAIN); ?></th>
                        <td><input type="password" name="<?php echo esc_attr(Constants::OPTION_API_SECRET); ?>"
                                   value="<?php echo esc_attr(get_option(Constants::OPTION_API_SECRET)); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Test Client ID', Constants::TEXT_DOMAIN); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(Constants::OPTION_API_TEST_CLIENT_ID); ?>"
                                   value="<?php echo esc_attr(get_option(Constants::OPTION_API_TEST_CLIENT_ID)); ?>"
                                   class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Test Client Secret', Constants::TEXT_DOMAIN); ?></th>
                        <td><input type="password" name="<?php echo esc_attr(Constants::OPTION_API_TEST_SECRET); ?>"
                                   value="<?php echo esc_attr(get_option(Constants::OPTION_API_TEST_SECRET)); ?>"
                                   class="regular-text"></td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Synchronisatie instellingen', Constants::TEXT_DOMAIN); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Limiteer import', Constants::TEXT_DOMAIN); ?></th>
                        <td>
                            <input type="text" name="<?php echo Constants::TRAINING_IMPORT_LIMIT; ?>"
                                   value="<?php echo esc_attr(get_option(Constants::TRAINING_IMPORT_LIMIT)); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php echo esc_html__('Geef hier aan hoeveel trainingen er vanuit Coachview gesynchroniseerd worden tijdens een import. Gebruik deze optie bijvoorbeeld om sneller te kunnen testen met een beperkte dataset.', Constants::TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Betaalmethodes', Constants::TEXT_DOMAIN); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Standaard betaalmethodes', Constants::TEXT_DOMAIN); ?></th>
                        <td>
                            <?php
                            $payment_methods = coachview_get_payment_methods();
                            $saved_method_ids = coachview_get_default_payment_method_ids();
                            foreach ($payment_methods as $method) {
                                $checked = in_array($method['id'], $saved_method_ids) ? 'checked' : '';
                                echo "<label><input type='checkbox' name='" . esc_attr(Constants::OPTION_DEFAULT_PAYMENT_METHOD_IDS) . "[]' value='" . esc_attr($method['id']) . "' $checked> " . esc_html($method['name']) . "</label><br />";
                            }
                            ?>
                            <p class="description">
                                <?php echo wp_kses_post(__('Selecteer de betaalmethodes die standaard ingesteld staan bij nieuwe producten.<br />Om een afwijkende selectie in te stellen per training, kun je gebruik maken van instellingen bij de training zelf.', Constants::TEXT_DOMAIN)); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Aanmelden / Registratie', Constants::TEXT_DOMAIN); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Registratiepagina', Constants::TEXT_DOMAIN); ?></th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                    'name' => Constants::OPTION_REGISTER_PAGE_ID,
                                    'show_option_none' => '— ' . esc_html__('Selecteer een pagina', Constants::TEXT_DOMAIN) . ' —',
                                    'option_none_value' => '0',
                                    'selected' => get_option(Constants::OPTION_REGISTER_PAGE_ID)
                            ]);
                            ?>
                            <p class="description">
                                <?php echo esc_html__('Selecteer de WordPress-pagina die ingericht is als registratiepagina. Op deze pagina dient de [cv_register_form] shortcode geplaatst te zijn, zodat gebruikers zich kunnen registreren / aanmelden voor een training.', Constants::TEXT_DOMAIN); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Succesberichten', Constants::TEXT_DOMAIN); ?></h2>
                <table class="form-table">
                    <tbody>
                    <?php
                    foreach ($success_messages as $type => $label) {
                        echo '<tr>';
                        echo '<th scope="row">' . esc_html($label) . '</th>';
                        echo "<td>";
                        $editor_id = 'cv_register_success_message_' . $type;
                        $default_success = esc_html__('Dankjewel voor je inschrijving', Constants::TEXT_DOMAIN);
                        $value = get_option($editor_id, $default_success);
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
                <?php submit_button(esc_html__('Instellingen opslaan', Constants::TEXT_DOMAIN)); ?>
            </form>
        </div>
        <?php
    }
}
