;(function ($, window) {
    const REGISTER_PAGE_PARTICIPANTS = {
        SELECTORS: {
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
        },
        CLASSNAMES: {
            fieldError: 'form-field__input--error',
        },
        TEMPLATE: {
            removeButton: '<button type="button" class="register-form__remove_participant cv-button" title="{title}">{label}</button>',
        },
        REGEX: {
            participantName: /^deelnemer\[\d+\]/,
        },
        TEXT: {
            currencyPrefix: '&euro; ',
            decimalPoint: '.',
            decimalReplacement: ',',
            participantPrefix: 'Deelnemer ',
            participantSingularSuffix: ' deelnemer',
            participantPluralSuffix: 's',
            removeParticipantTitle: 'Deelnemer verwijderen',
            confirmRemoveParticipant: 'Weet je zeker dat je deze deelnemer wilt verwijderen?',
            closeSymbol: '×',
        },
    };

    class RegisterPageParticipants {
        constructor($form) {
            this.$form = null;
            this.$participantsContainer = $();

            if (!$form || !$form.length) {
                return;
            }

            this.$form = $form;
            this.$participantsContainer = this.$form.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantsContainer);

            if (!this.$participantsContainer.length) {
                this.$form = null;
                return;
            }

            this.bindEvents();
            this.updateDeleteButtonsVisibility();
            this.updatePrices();
        }

        bindEvents() {
            this.$form.on('click', REGISTER_PAGE_PARTICIPANTS.SELECTORS.addParticipantButton, (event) => {
                event.preventDefault();
                this.addParticipant();
            });

            this.$form.on('click', REGISTER_PAGE_PARTICIPANTS.SELECTORS.removeParticipantButton, (event) => {
                event.preventDefault();
                const $participant = $(event.currentTarget).closest(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantWrapper);
                this.removeParticipant($participant);
            });
        }

        addParticipant() {
            const $firstParticipant = this.$participantsContainer.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantWrapper).first();

            if (!$firstParticipant.length) {
                return;
            }

            const $newParticipant = $firstParticipant.clone();
            const newIndex = this.$participantsContainer.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantWrapper).length + 1;

            $newParticipant.attr('data-participant-index', newIndex);
            $newParticipant
                .find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantHeader)
                .text(`${REGISTER_PAGE_PARTICIPANTS.TEXT.participantPrefix}${newIndex}`);

            this.ensureRemoveButton($newParticipant);
            this.resetParticipantFields($newParticipant);
            this.updateParticipantFieldIdentifiers($newParticipant, newIndex);

            $newParticipant.find(`.${REGISTER_PAGE_PARTICIPANTS.CLASSNAMES.fieldError}`).removeClass(REGISTER_PAGE_PARTICIPANTS.CLASSNAMES.fieldError);
            this.$participantsContainer.append($newParticipant);

            this.updateDeleteButtonsVisibility();
            this.reindexParticipants();
            this.updatePrices();
        }

        ensureRemoveButton($participant) {
            if ($participant.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.removeParticipantButton).length) {
                return;
            }

            let $headerWrapper = $participant
                .find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantHeader)
                .closest(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantHeaderWrapper);

            if (!$headerWrapper.length) {
                const $header = $participant.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantHeader);
                $header.wrap('<div class="register-form__participant-header-wrapper"></div>');
                $headerWrapper = $participant
                    .find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantHeader)
                    .closest(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantHeaderWrapper);
            }

            const removeButtonHtml = REGISTER_PAGE_PARTICIPANTS.TEMPLATE.removeButton
                .replace('{title}', REGISTER_PAGE_PARTICIPANTS.TEXT.removeParticipantTitle)
                .replace('{label}', REGISTER_PAGE_PARTICIPANTS.TEXT.closeSymbol);

            $headerWrapper.append(removeButtonHtml);
        }

        resetParticipantFields($participant) {
            $participant.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.inputFields).val('');
            $participant.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.checkableFields).prop('checked', false);
            $participant.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.selectFields).prop('selectedIndex', 0);
        }

        updateParticipantFieldIdentifiers($participant, index) {
            $participant.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.fieldsWithId).each(function () {
                const $element = $(this);
                const oldId = $element.attr('id');

                if (!oldId) {
                    return;
                }

                const newId = `${oldId}_${index}`;
                $element.attr('id', newId);
                $participant.find(`label[for="${oldId}"]`).attr('for', newId);
            });

            $participant.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.fieldsWithName).each(function () {
                const $element = $(this);
                const name = $element.attr('name');

                if (!name || name.includes('[')) {
                    return;
                }

                $element.attr('name', `${name}[${index}]`);
            });
        }

        removeParticipant($participant) {
            const participantCount = this.$participantsContainer.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantWrapper).length;

            if (participantCount <= 1) {
                return;
            }

            if (
                REGISTER_PAGE_PARTICIPANTS.TEXT.confirmRemoveParticipant &&
                !window.confirm(REGISTER_PAGE_PARTICIPANTS.TEXT.confirmRemoveParticipant)
            ) {
                return;
            }

            $participant.remove();
            this.reindexParticipants();
            this.updatePrices();
        }

        updatePrices() {
            if (!this.$participantsContainer.length) {
                return;
            }

            const numberOfParticipants = this.$participantsContainer.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantWrapper).length;
            const $itemPrice = $(REGISTER_PAGE_PARTICIPANTS.SELECTORS.cartItem);
            const $itemQuantityElem = $(REGISTER_PAGE_PARTICIPANTS.SELECTORS.cartQuantity);
            const $totalPrice = $(REGISTER_PAGE_PARTICIPANTS.SELECTORS.cartTotalPrice);

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
                .replace(REGISTER_PAGE_PARTICIPANTS.TEXT.decimalPoint, REGISTER_PAGE_PARTICIPANTS.TEXT.decimalReplacement);
            const participantSuffix = numberOfParticipants > 1 ? REGISTER_PAGE_PARTICIPANTS.TEXT.participantPluralSuffix : '';

            $itemQuantityElem.data('quantity', numberOfParticipants);
            $itemQuantityElem.text(
                `${numberOfParticipants}${REGISTER_PAGE_PARTICIPANTS.TEXT.participantSingularSuffix}${participantSuffix}`
            );
            $totalPrice.html(`${REGISTER_PAGE_PARTICIPANTS.TEXT.currencyPrefix}${formattedTotalPrice}`);
        }

        reindexParticipants() {
            this.$participantsContainer.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantWrapper).each(function (index) {
                const $participant = $(this);
                const displayIndex = index + 1;

                $participant.attr('data-participant-index', displayIndex);
                $participant
                    .find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantHeader)
                    .text(`${REGISTER_PAGE_PARTICIPANTS.TEXT.participantPrefix}${displayIndex}`);

                $participant.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.fieldsWithName).each(function () {
                    const $element = $(this);
                    const name = $element.attr('name');

                    if (!name) {
                        return;
                    }

                    if (REGISTER_PAGE_PARTICIPANTS.REGEX.participantName.test(name)) {
                        const newName = name.replace(REGISTER_PAGE_PARTICIPANTS.REGEX.participantName, `deelnemer[${index}]`);
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
        }

        updateDeleteButtonsVisibility() {
            const participantCount = this.$participantsContainer.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.participantWrapper).length;
            const $removeButtons = this.$participantsContainer.find(REGISTER_PAGE_PARTICIPANTS.SELECTORS.removeParticipantButton);

            if (participantCount <= 1) {
                $removeButtons.hide();
            } else {
                $removeButtons.show();
            }
        }
    }

    window.RegisterPageParticipants = RegisterPageParticipants;
})(jQuery, window);

