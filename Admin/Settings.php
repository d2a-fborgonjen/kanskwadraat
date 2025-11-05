<?php
namespace Coachview\Admin;

class Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_submenu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_submenu() {
        add_submenu_page(
            'coachview',
            'Coachview Instellingen',
            'Instellingen',
            'manage_options',
            'coachview-settings',
            [$this, 'settings_page']
        );
    }

    public function register_settings() {
//        register_setting('coachview_sync_settings', 'coachview_api_url');
        register_setting('coachview_sync_settings', 'coachview_api_mode');
        register_setting('coachview_sync_settings', 'coachview_client_id');
        register_setting('coachview_sync_settings', 'coachview_secret');
        register_setting('coachview_sync_settings', 'coachview_test_client_id');
        register_setting('coachview_sync_settings', 'coachview_test_secret');
        register_setting('coachview_sync_settings', 'training_import_limit');
        register_setting('coachview_sync_settings', 'coachview_order_success_redirect_url');

        // Regenerate API token on settings save
        coachview_api_token(true);
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php
                settings_fields('coachview_sync_settings');
                do_settings_sections('coachview_sync_settings');
                $mode = get_option('coachview_api_mode', 'test');
                ?>
                <h1>Coachview API </h1>
                <h2><?php echo get_option('coachview_api_mode'); ?></h2>
                <table class="form-table">
<!--                    <tr><th scope="row">API URL</th><td><input type="text" name="coachview_api_url" value="--><?php //echo esc_attr(get_option('coachview_api_url')); ?><!--" class="regular-text"></td></tr>-->
                    <tr><th scope="row">Api mode</th>
                        <td>
                            <input type="radio" name="coachview_api_mode" id="test_mode" value="test" <?php echo $mode === 'test' ? 'checked' : ''; ?>><label for="test_mode">Test</label>
                            <input type="radio" name="coachview_api_mode" id="production_mode" value="production" <?php echo $mode !== 'test' ? 'checked' : ''; ?>><label for="production_mode">Production</label>
                        </td>
                    </tr>
                    <tr><th scope="row">Client ID</th><td><input type="text" name="coachview_client_id" value="<?php echo esc_attr(get_option('coachview_client_id')); ?>" class="regular-text"></td></tr>
                    <tr><th scope="row">Client Secret</th><td><input type="password" name="coachview_secret" value="<?php echo esc_attr(get_option('coachview_secret')); ?>" class="regular-text"></td></tr>
                    <tr><th scope="row">Test Client ID</th><td><input type="text" name="coachview_test_client_id" value="<?php echo esc_attr(get_option('coachview_test_client_id')); ?>" class="regular-text"></td></tr>
                    <tr><th scope="row">Test Client Secret</th><td><input type="password" name="coachview_test_secret" value="<?php echo esc_attr(get_option('coachview_test_secret')); ?>" class="regular-text"></td></tr>
                </table>

                <h1>Synchronisatie instellingen</h1>
                <table class="form-table">
                    <tr><th scope="row">Limiteer import</th><td><input type="text" name="training_import_limit" value="<?php echo esc_attr(get_option('training_import_limit')); ?>" class="regular-text"></td></tr>
                </table>

                <h1>Webaanvragen</h1>
                <table class="form-table">
                    <tr><th scope="row">Aanvraag success pagina</th><td><input type="text" name="coachview_order_success_redirect_url" value="<?php echo esc_attr(get_option('coachview_order_success_redirect_url')); ?>" class="regular-text"></td></tr>
                </table>
                <?php submit_button('Instellingen opslaan'); ?>
            </form>
        </div>
        <?php
    }
}
