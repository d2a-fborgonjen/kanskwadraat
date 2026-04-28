/**
 * Syncs field values between form inputs on the registration page.
 * Updates the destination field only if it is empty or still matches the previous source value.
 */
(function($) {
    window.RegisterPageFieldSync = function($form) {
        const syncPairs = [
            {
                src: 'deelnemer[0][vrijeveldenPersoon][VrijeveldBedrijfsnaam]',
                dest: 'bedrijf[naam]',
            },
        ];

        syncPairs.forEach(function({ src, dest }) {
            const $src = $form.find(`[name="${src}"]`);
            const $dest = $form.find(`[name="${dest}"]`);
            if (!$src.length || !$dest.length) {
                return;
            }

            let previousValue = $src.val() || '';
            $src.on('input change', function() {
                const newValue = $src.val() || '';
                const destValue = $dest.val() || '';
                if (destValue === '' || destValue === previousValue) {
                    $dest.val(newValue).trigger('change');
                }
                previousValue = newValue;
            });
        });
    };
})(jQuery);

