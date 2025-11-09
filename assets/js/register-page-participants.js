;(function ($, window) {
    const SELECTORS = {
        participantsContainer: '.register-form__participants',
        participantWrapper: '.register-form__participant',
        participantHeader: '.register-form__participant-header',
        participantHeaderWrapper: '.register-form__participant-header-wrapper',
        addParticipantButton: '.register-form__add_participant',
        removeParticipantButton: '.register-form__remove_participant',
        cartItem: '.register-page__cart--item',
        cartQuantity: '.register-page__cart--quantity',
        cartTotalPrice: '.register-page__cart--total-price',
        fieldsWithId: '[id]',
        fieldsWithName: '[name]',
        inputFields: 'input[type="text"], input[type="email"], input[type="number"], input[type="date"], textarea',
        checkableFields: 'input[type="checkbox"], input[type="radio"]',
        selectFields: 'select',
    };

    const FIELD_ERROR_CLASS = 'form-field__input--error';
    const REMOVE_BUTTON_TEMPLATE = '<button type="button" class="register-form__remove_participant cv-button" title="{title}">{label}</button>';
    const PARTICIPANT_NAME_REGEX = /^deelnemer\[\d+\]/;
    const PARTICIPANT_TEXT = {
        currencyPrefix: '&euro; ',
        decimalPoint: '.',
        decimalReplacement: ',',
        participantPrefix: 'Deelnemer ',
        participantSingularSuffix: ' deelnemer',
        participantPluralSuffix: 's',
        removeParticipantTitle: 'Deelnemer verwijderen',
        confirmRemoveParticipant: 'Weet je zeker dat je deze deelnemer wilt verwijderen?',
        closeSymbol: '×',
    };

    function RegisterPageParticipants($form) {
        if (!$form || !$form.length) {
            return;
        }

        this.$form = $form;
        this.$participantsContainer = this.$form.find(SELECTORS.participantsContainer);

        if (!this.$participantsContainer.length) {
            return;
        }

        this.bindEvents();
        this.updateDeleteButtonsVisibility();
        this.updatePrices();
    }

    RegisterPageParticipants.prototype.bindEvents = function () {
        const self = this;

        this.$form.on('click', SELECTORS.addParticipantButton, function (event) {
            event.preventDefault();
            self.addParticipant();
        });

        this.$form.on('click', SELECTORS.removeParticipantButton, function (event) {
            event.preventDefault();
            const $participant = $(this).closest(SELECTORS.participantWrapper);
            self.removeParticipant($participant);
        });
    };

    RegisterPageParticipants.prototype.addParticipant = function () {
        const $firstParticipant = this.$participantsContainer.find(SELECTORS.participantWrapper).first();

        if (!$firstParticipant.length) {
            return;
        }

        const $newParticipant = $firstParticipant.clone();
        const currentCount = this.$participantsContainer.find(SELECTORS.participantWrapper).length;
        const newIndex = currentCount + 1;

        $newParticipant.attr('data-participant-index', newIndex);
        $newParticipant.find(SELECTORS.participantHeader).text(`${PARTICIPANT_TEXT.participantPrefix}${newIndex}`);

        this.ensureRemoveButton($newParticipant);
        this.resetParticipantFields($newParticipant);
        this.updateParticipantFieldIdentifiers($newParticipant, newIndex);

        $newParticipant.find(`.${FIELD_ERROR_CLASS}`).removeClass(FIELD_ERROR_CLASS);
        this.$participantsContainer.append($newParticipant);

        this.updateDeleteButtonsVisibility();
        this.reindexParticipants();
        this.updatePrices();
    };

    RegisterPageParticipants.prototype.ensureRemoveButton = function ($participant) {
        if ($participant.find(SELECTORS.removeParticipantButton).length) {
            return;
        }

        let $headerWrapper = $participant.find(SELECTORS.participantHeader).closest(SELECTORS.participantHeaderWrapper);
        if (!$headerWrapper.length) {
            const $header = $participant.find(SELECTORS.participantHeader);
            $header.wrap('<div class="register-form__participant-header-wrapper"></div>');
            $headerWrapper = $participant.find(SELECTORS.participantHeader).closest(SELECTORS.participantHeaderWrapper);
        }

        const removeButtonHtml = REMOVE_BUTTON_TEMPLATE
            .replace('{title}', PARTICIPANT_TEXT.removeParticipantTitle)
            .replace('{label}', PARTICIPANT_TEXT.closeSymbol);

        $headerWrapper.append(removeButtonHtml);
    };

    RegisterPageParticipants.prototype.resetParticipantFields = function ($participant) {
        $participant.find(SELECTORS.inputFields).val('');
        $participant.find(SELECTORS.checkableFields).prop('checked', false);
        $participant.find(SELECTORS.selectFields).prop('selectedIndex', 0);
    };

    RegisterPageParticipants.prototype.updateParticipantFieldIdentifiers = function ($participant, index) {
        $participant.find(SELECTORS.fieldsWithId).each(function () {
            const $element = $(this);
            const oldId = $element.attr('id');

            if (!oldId) {
                return;
            }

            const newId = `${oldId}_${index}`;
            $element.attr('id', newId);
            $participant.find(`label[for="${oldId}"]`).attr('for', newId);
        });

        $participant.find(SELECTORS.fieldsWithName).each(function () {
            const $element = $(this);
            const name = $element.attr('name');

            if (!name || name.includes('[')) {
                return;
            }

            $element.attr('name', `${name}[${index}]`);
        });
    };

    RegisterPageParticipants.prototype.removeParticipant = function ($participant) {
        const participantCount = this.$participantsContainer.find(SELECTORS.participantWrapper).length;

        if (participantCount <= 1) {
            return;
        }

        if (PARTICIPANT_TEXT.confirmRemoveParticipant && !window.confirm(PARTICIPANT_TEXT.confirmRemoveParticipant)) {
            return;
        }

        $participant.remove();
        this.reindexParticipants();
        this.updatePrices();
    };

    RegisterPageParticipants.prototype.updatePrices = function () {
        if (!this.$participantsContainer.length) {
            return;
        }

        const numberOfParticipants = this.$participantsContainer.find(SELECTORS.participantWrapper).length;
        const $itemPrice = $(SELECTORS.cartItem);
        const $itemQuantityElem = $(SELECTORS.cartQuantity);
        const $totalPrice = $(SELECTORS.cartTotalPrice);

        if (!$itemPrice.length || !$itemQuantityElem.length || !$totalPrice.length) {
            return;
        }

        const itemPrice = parseFloat($itemPrice.data('price'));
        if (Number.isNaN(itemPrice)) {
            return;
        }

        const totalPrice = itemPrice * numberOfParticipants;
        const formattedTotalPrice = totalPrice
            .toFixed(2)
            .replace(PARTICIPANT_TEXT.decimalPoint, PARTICIPANT_TEXT.decimalReplacement);
        const participantSuffix = numberOfParticipants > 1 ? PARTICIPANT_TEXT.participantPluralSuffix : '';

        $itemQuantityElem.data('quantity', numberOfParticipants);
        $itemQuantityElem.text(`${numberOfParticipants}${PARTICIPANT_TEXT.participantSingularSuffix}${participantSuffix}`);
        $totalPrice.html(`${PARTICIPANT_TEXT.currencyPrefix}${formattedTotalPrice}`);
    };

    RegisterPageParticipants.prototype.reindexParticipants = function () {
        const self = this;

        this.$participantsContainer.find(SELECTORS.participantWrapper).each(function (index) {
            const $participant = $(this);
            const displayIndex = index + 1;

            $participant.attr('data-participant-index', displayIndex);
            $participant.find(SELECTORS.participantHeader).text(`${PARTICIPANT_TEXT.participantPrefix}${displayIndex}`);

            $participant.find(SELECTORS.fieldsWithName).each(function () {
                const $element = $(this);
                const name = $element.attr('name');

                if (!name) {
                    return;
                }

                if (PARTICIPANT_NAME_REGEX.test(name)) {
                    const newName = name.replace(PARTICIPANT_NAME_REGEX, `deelnemer[${index}]`);
                    $element.attr('name', newName);
                    $element.attr('id', newName);

                    const $label = $element.prev('label');
                    if ($label.length) {
                        $label.attr('for', newName);
                    }
                }
            });
        });

        this.updateDeleteButtonsVisibility();
    };

    RegisterPageParticipants.prototype.updateDeleteButtonsVisibility = function () {
        const participantCount = this.$participantsContainer.find(SELECTORS.participantWrapper).length;
        const $removeButtons = this.$participantsContainer.find(SELECTORS.removeParticipantButton);

        if (participantCount <= 1) {
            $removeButtons.hide();
        } else {
            $removeButtons.show();
        }
    };

    window.RegisterPageParticipants = RegisterPageParticipants;
})(jQuery, window);

