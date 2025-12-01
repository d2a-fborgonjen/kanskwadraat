;(function ($, window) {
    const REGISTER_PAGE_WIZARD = {
        TEXT: {
            stepPrefix: 'Stap ',
            stepSeparator: ' van ',
            unknownField: 'Onbekend veld',
            yes: 'Ja',
            no: 'Nee',
        },
        SELECTORS: {
            input: '.form-field__input',
            requiredFields: 'input[required], select[required], textarea[required]',
            summaryDetails: '.register-form__summary-details',
            formSections: '.form-section__step:not(.register-form--summary)',
            formSectionContent: '.form-section__content',
            participantWrapper: '.register-form__participant',
            participantHeader: '.register-form__participant-header',
            steps: '.form-section__step',
            progressText: '.form-section__progress-text',
            progressBar: '.form-section__progress-bar',
            currentStepTitle: '.register-page__title',
        },
        SUMMARY_CLASSES: {
            section: 'register-form__summary-section col-6 d-flex flex-column',
            sectionTitle: 'register-form__summary-section-title mb-4 gap-1',
            item: 'register-form__summary-item mb-4 d-flex flex-column gap-1',
            itemLabel: 'register-form__summary-item-label',
            itemValue: 'register-form__summary-item-value',
        },
        STEP_CLASSES: {
            active: 'active',
            inactive: 'inactive',
            slideOutLeft: 'slide-out-left',
            slideOutRight: 'slide-out-right',
            slideInLeft: 'slide-in-left',
            slideInRight: 'slide-in-right',
        },
        NAV_BUTTONS: {
            next: '.register-form__next',
            check: '.register-form__check',
            previous: '.register-form__previous',
        },
        CLASSNAMES: {
            fieldError: 'form-field__input--error',
        },
        DURATIONS: {
            animation: 300,
            scroll: 300,
        },
        SCROLL_OFFSET: 100,
        DATE: {
            locale: 'nl-NL',
            format: {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            },
        },
    };

    const STEP_RESET_CLASSES = [
        REGISTER_PAGE_WIZARD.STEP_CLASSES.inactive,
        REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutLeft,
        REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutRight,
        REGISTER_PAGE_WIZARD.STEP_CLASSES.slideInLeft,
        REGISTER_PAGE_WIZARD.STEP_CLASSES.slideInRight,
    ].join(' ');

    const ACTIVE_RESET_CLASSES = [
        REGISTER_PAGE_WIZARD.STEP_CLASSES.active,
        REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutLeft,
        REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutRight,
        REGISTER_PAGE_WIZARD.STEP_CLASSES.slideInLeft,
        REGISTER_PAGE_WIZARD.STEP_CLASSES.slideInRight,
    ].join(' ');

    class RegisterPageWizard {
        constructor($form) {
            if (!$form || !$form.length) {
                return;
            }
            this.$form = $form;
            this.$steps = $(REGISTER_PAGE_WIZARD.SELECTORS.steps);
            this.$currentStepTitle = $(REGISTER_PAGE_WIZARD.SELECTORS.currentStepTitle);
            this.$progressText = $(REGISTER_PAGE_WIZARD.SELECTORS.progressText);
            this.$progressBar = $(REGISTER_PAGE_WIZARD.SELECTORS.progressBar);

            if (!this.$steps.length) {
                return;
            }
            this.currentStep = 0;
            this.totalSteps = this.$steps.length;

            this.bindEvents();
            this.setStepState(0);
        }

        bindEvents() {
            if (!this.$form) {
                return;
            }

            this.$form.on('click', REGISTER_PAGE_WIZARD.NAV_BUTTONS.next, (event) => {
                event.preventDefault();
                this.goToNextStep();
            });

            this.$form.on('click', REGISTER_PAGE_WIZARD.NAV_BUTTONS.check, (event) => {
                event.preventDefault();
                this.goToNextStep();
            });

            this.$form.on('click', REGISTER_PAGE_WIZARD.NAV_BUTTONS.previous, (event) => {
                event.preventDefault();
                this.goToPreviousStep();
            });

            this.$form.on('input change', REGISTER_PAGE_WIZARD.SELECTORS.input, (event) => {
                $(event.currentTarget).removeClass(REGISTER_PAGE_WIZARD.CLASSNAMES.fieldError);
            });
        }

        setStepState(targetIndex) {
            this.$steps.each(function (index) {
                const $step = $(this);
                if (index === targetIndex) {
                    $step.removeClass(STEP_RESET_CLASSES).addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.active);
                } else {
                    $step.removeClass(ACTIVE_RESET_CLASSES).addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.inactive);
                }
            });

            this.currentStep = targetIndex;
            this.updateProgressForStep(targetIndex);
            this.updateStepTitle();

            if (this.isSummaryStep(targetIndex)) {
                this.buildSummary();
            }
        }

        updateStepTitle() {
            if (!this.$currentStepTitle || !this.$currentStepTitle.length) {
                return;
            }

            const $currentStepElement = this.$steps.eq(this.currentStep);
            const label = $currentStepElement.data('label');

            if (label) {
                this.$currentStepTitle.text(label);
            }
        }

        updateProgressForStep(stepIndex) {
            if (!this.$progressText || !this.$progressBar) {
                return;
            }

            const stepNumber = stepIndex + 1;
            const percentage = (stepNumber / this.totalSteps) * 100;
            const stepLabel = `${REGISTER_PAGE_WIZARD.TEXT.stepPrefix}${stepNumber}${REGISTER_PAGE_WIZARD.TEXT.stepSeparator}${this.totalSteps}`;

            this.$progressText.text(stepLabel);
            this.$progressBar.css('width', `${percentage}%`);
        }

        validateStep(stepIndex, options) {
            const settings = options || {};
            const scrollOnError = settings.scrollOnError !== false;
            const $step = this.$steps.eq(stepIndex);

            if (!$step.length) {
                return { isValid: true, firstInvalidField: null };
            }

            const $fields = $step.find(REGISTER_PAGE_WIZARD.SELECTORS.requiredFields);
            let isValid = true;
            let firstInvalidField = null;

            $step.find(REGISTER_PAGE_WIZARD.SELECTORS.input).removeClass(REGISTER_PAGE_WIZARD.CLASSNAMES.fieldError);

            $fields.each(function () {
                const field = this;

                if (typeof field.checkValidity === 'function' && !field.checkValidity()) {
                    isValid = false;
                    const $field = $(field);
                    $field.addClass(REGISTER_PAGE_WIZARD.CLASSNAMES.fieldError);
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                }
            });

            if (!isValid && scrollOnError && firstInvalidField) {
                this.scrollToField(firstInvalidField);
            }

            return { isValid, firstInvalidField };
        }

        scrollToField(field) {
            if (!field) {
                return;
            }

            $('html, body').animate(
                {
                    scrollTop: $(field).offset().top - REGISTER_PAGE_WIZARD.SCROLL_OFFSET,
                },
                REGISTER_PAGE_WIZARD.DURATIONS.scroll
            );
        }

        goToNextStep() {
            if (this.totalSteps <= 0) {
                return false;
            }

            const validation = this.validateStep(this.currentStep, { scrollOnError: true });

            if (!validation.isValid || this.currentStep >= this.totalSteps - 1) {
                return false;
            }

            const nextStepIndex = this.currentStep + 1;
            const $currentStepElement = this.$steps.eq(this.currentStep);
            const $nextStepElement = this.$steps.eq(nextStepIndex);

            this.updateProgressForStep(nextStepIndex);

            $currentStepElement.removeClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.active).addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutLeft);
            $nextStepElement
                .removeClass(`${REGISTER_PAGE_WIZARD.STEP_CLASSES.inactive} ${REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutLeft}`)
                .addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.slideInRight);

            setTimeout(() => {
                $currentStepElement.removeClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutLeft).addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.inactive);
                $nextStepElement.removeClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.slideInRight).addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.active);
                this.currentStep = nextStepIndex;
                this.updateStepTitle();

                if (this.isSummaryStep(nextStepIndex)) {
                    this.buildSummary();
                }
            }, REGISTER_PAGE_WIZARD.DURATIONS.animation);

            return true;
        }

        goToPreviousStep() {
            if (this.totalSteps <= 0 || this.currentStep <= 0) {
                return false;
            }

            const previousStepIndex = this.currentStep - 1;
            const $currentStepElement = this.$steps.eq(this.currentStep);
            const $previousStepElement = this.$steps.eq(previousStepIndex);

            this.updateProgressForStep(previousStepIndex);

            $currentStepElement
                .removeClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.active)
                .addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutRight);
            $previousStepElement
                .removeClass(`${REGISTER_PAGE_WIZARD.STEP_CLASSES.inactive} ${REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutRight}`)
                .addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.slideInLeft);

            setTimeout(() => {
                $currentStepElement
                    .removeClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.slideOutRight)
                    .addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.inactive);
                $previousStepElement
                    .removeClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.slideInLeft)
                    .addClass(REGISTER_PAGE_WIZARD.STEP_CLASSES.active);
                this.currentStep = previousStepIndex;
                this.updateStepTitle();
            }, REGISTER_PAGE_WIZARD.DURATIONS.animation);

            return true;
        }

        buildSummary() {
            const $summaryDetails = this.$form.find(REGISTER_PAGE_WIZARD.SELECTORS.summaryDetails);

            if (!$summaryDetails.length) {
                return;
            }

            $summaryDetails.empty();

            const $formSections = this.$form.find(REGISTER_PAGE_WIZARD.SELECTORS.formSections);

            $formSections.each((_, section) => {
                const $section = $(section);
                const $formSectionSummary = $('<div />', { class: REGISTER_PAGE_WIZARD.SUMMARY_CLASSES.section });
                const $formGroups = $section.find(REGISTER_PAGE_WIZARD.SELECTORS.formSectionContent);

                $formGroups.each((__, group) => {
                    const $group = $(group);
                    let sectionTitle = $group.closest(REGISTER_PAGE_WIZARD.SELECTORS.formSections).data('label');
                    const participantWrapper = $group.closest(REGISTER_PAGE_WIZARD.SELECTORS.participantWrapper);

                    if (participantWrapper.length) {
                        const participantHeader = participantWrapper.find(REGISTER_PAGE_WIZARD.SELECTORS.participantHeader).text();
                        sectionTitle = participantHeader || sectionTitle;
                    }

                    if (sectionTitle) {
                        $formSectionSummary.append(
                            `<h3 class="${REGISTER_PAGE_WIZARD.SUMMARY_CLASSES.sectionTitle}">${sectionTitle}</h3>`
                        );
                    }

                    $group.find('input, select, textarea').each((___, input) => {
                        const $input = $(input);
                        const label = $input.attr('aria-label') || REGISTER_PAGE_WIZARD.TEXT.unknownField;
                        const inputType = $input.data('input-type');
                        let value = '';

                        if (!label || !inputType) {
                            return;
                        }

                        if ($input.is(':checkbox')) {
                            value = $input.is(':checked') ? REGISTER_PAGE_WIZARD.TEXT.yes : REGISTER_PAGE_WIZARD.TEXT.no;
                        } else if ($input.is(':radio')) {
                            if ($input.is(':checked')) {
                                value = $input.val();
                            } else {
                                return;
                            }
                        } else {
                            value = $input.val();
                        }

                        if (inputType === 'date' && value) {
                            const date = new Date(value);
                            value = date.toLocaleDateString(REGISTER_PAGE_WIZARD.DATE.locale, REGISTER_PAGE_WIZARD.DATE.format);
                        }

                        if (value) {
                            $formSectionSummary.append(
                                `<div class="${REGISTER_PAGE_WIZARD.SUMMARY_CLASSES.item}">
                                    <span class="${REGISTER_PAGE_WIZARD.SUMMARY_CLASSES.itemLabel}">${label}</span>
                                    <span class="${REGISTER_PAGE_WIZARD.SUMMARY_CLASSES.itemValue}">${value}</span>
                                 </div>`
                            );
                        }
                    });
                });

                $summaryDetails.append($formSectionSummary);
            });
        }

        isSummaryStep(index) {
            return index === this.totalSteps - 1;
        }

        validateAllSteps() {
            if (this.totalSteps <= 0) {
                return true;
            }

            const originalStep = this.currentStep;

            for (let index = 0; index < this.totalSteps; index += 1) {
                const result = this.validateStep(index, { scrollOnError: false });

                if (!result.isValid) {
                    this.setStepState(index);
                    this.scrollToField(result.firstInvalidField);
                    return false;
                }
            }

            if (originalStep !== this.currentStep) {
                this.setStepState(originalStep);
            } else {
                this.updateProgressForStep(originalStep);
                this.updateStepTitle();

                if (this.isSummaryStep(originalStep)) {
                    this.buildSummary();
                }
            }

            return true;
        }
    }

    window.RegisterPageWizard = RegisterPageWizard;
})(jQuery, window);

