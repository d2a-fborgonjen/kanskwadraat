const REGISTER_MESSAGE_TYPES = {
    info: 'info',
    success: 'success',
    error: 'error',
};

const REGISTER_PAGE_FEEDBACK_RESET_CLASSES = 'register-page__feedback--success register-page__feedback--error register-page__feedback--info';

jQuery(document).ready(function($) {
    const $form = $('.register-page__form');
    if (!$form.length) {
        return;
    }

    const $currentStepTitle = $('.register-page__title');
    const $feedbackContainer = $('.register-page__feedback');
    const restRoot = (window.wpApiSettings && window.wpApiSettings.root) ? window.wpApiSettings.root : '/wp-json/';
    const registerEndpoint = restRoot.replace(/\/$/, '') + '/coachview/v1/register';
    const $submitButton = $form.find('.register-form__submit');
    let isSubmitting = false;
    const registerPageText = {
        submitting: 'Bezig met verzenden...',
        defaultSuccess: 'Dankjewel voor je aanmelding.',
        defaultError: 'Er is iets misgegaan bij het verwerken van je aanmelding. Probeer het later opnieuw.',
        successTitleDefault: 'Aanmelding afgerond',
    };

    const wizard = window.RegisterPageWizard ? new window.RegisterPageWizard($form) : null;
    const participantsManager = window.RegisterPageParticipants ? new window.RegisterPageParticipants($form) : null;

    function showFormMessage(type, message) {
        if (!$feedbackContainer.length) {
            return;
        }

        $feedbackContainer
            .removeClass(REGISTER_PAGE_FEEDBACK_RESET_CLASSES)
            .addClass(`register-page__feedback--${type}`)
            .text(message)
            .show();
    }

    function clearFormMessage() {
        if (!$feedbackContainer.length) {
            return;
        }

        $feedbackContainer
            .removeClass(REGISTER_PAGE_FEEDBACK_RESET_CLASSES)
            .hide()
            .text('');
    }

    function toggleSubmitting(isLoading) {
        isSubmitting = isLoading;
        if (!$submitButton.length) {
            return;
        }

        if (isLoading) {
            if (!$submitButton.data('original-text')) {
                $submitButton.data('original-text', $submitButton.text());
            }
            $submitButton.prop('disabled', true).addClass('is-loading');
            const loadingText = $submitButton.data('loading-text') || registerPageText.submitting;
            $submitButton.text(loadingText);
        } else {
            const originalText = $submitButton.data('original-text');
            if (originalText) {
                $submitButton.text(originalText);
            }
            $submitButton.prop('disabled', false).removeClass('is-loading');
        }
    }

    function buildFormPayload() {
        const formData = new FormData($form[0]);
        const payload = new URLSearchParams();
        formData.forEach((value, key) => {
            const isFileValue = typeof File !== 'undefined' && value instanceof File;
            if (isFileValue && value.name === '' && value.size === 0) {
                return;
            }
            payload.append(key, value);
        });
        return payload;
    }

    function clearRegistrationUI() {
        if ($form.length) {
            $form.remove();
        }

        const $cart = $('.register-page__cart');
        if ($cart.length) {
            $cart.remove();
        }
    }

    function completeRegistration(successMessage, redirectUrl) {
        const successTitle = $currentStepTitle.data('success-title') || registerPageText.successTitleDefault;
        $currentStepTitle.text(successTitle);
        clearRegistrationUI();
        showFormMessage(REGISTER_MESSAGE_TYPES.success, successMessage);

        if (redirectUrl) {
            setTimeout(function() {
                window.location.href = redirectUrl;
            }, 3000);
        }
    }

    function submitRegistrationForm() {
        toggleSubmitting(true);
        showFormMessage(REGISTER_MESSAGE_TYPES.info, registerPageText.submitting);

        const payload = buildFormPayload();
        const headers = {
            'Accept': 'application/json',
        };

        if (window.wpApiSettings && window.wpApiSettings.nonce) {
            headers['X-WP-Nonce'] = window.wpApiSettings.nonce;
        }

        return fetch(registerEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers,
            body: payload,
        })
            .then((response) => response.json()
                .catch(() => ({}))
                .then((data) => ({
                    ok: response.ok,
                    status: response.status,
                    data,
                }))
            )
            .then(({ ok, data }) => {
                if (!ok) {
                    const message = data && data.message
                        ? data.message
                        : registerPageText.defaultError;
                    throw new Error(message);
                }

                const redirectUrl = data && data.redirect_url ? data.redirect_url : null;
                let successMessage = data && data.message
                    ? data.message
                    : registerPageText.defaultSuccess;
                completeRegistration(successMessage, redirectUrl);
            })
            .catch((error) => {
                const message = error && error.message
                    ? error.message
                    : registerPageText.defaultError;
                showFormMessage(REGISTER_MESSAGE_TYPES.error, message);
            })
            .finally(() => {
                toggleSubmitting(false);
            });
    }
    
    // Validate on form submit (final step)
    $form.on('submit', function(e) {
        e.preventDefault();

        if (isSubmitting) {
            return false;
        }

        clearFormMessage();

        if (wizard && !wizard.validateAllSteps()) {
            return false;
        }

        submitRegistrationForm();
        return false;
    });
    
    if (participantsManager) {
        participantsManager.updatePrices();
    }
});