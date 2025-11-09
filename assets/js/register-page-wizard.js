;(function ($, window) {
    const WIZARD_TEXT = {
        stepPrefix: 'Stap ',
        stepSeparator: ' van ',
        yes: 'Ja',
        no: 'Nee',
    };
    const SELECTORS = {
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
    };
    const SUMMARY_CLASSES = {
        section: 'register-form__summary-section',
        sectionTitle: 'register-form__summary-section-title',
        item: 'register-form__summary-item',
        itemLabel: 'register-form__summary-item-label',
        itemValue: 'register-form__summary-item-value',
    };
    const STEP_CLASSES = {
        active: 'active',
        inactive: 'inactive',
        slideOutLeft: 'slide-out-left',
        slideOutRight: 'slide-out-right',
        slideInLeft: 'slide-in-left',
        slideInRight: 'slide-in-right',
    };
    const FIELD_ERROR_CLASS = 'form-field__input--error';


    const STEP_RESET_CLASSES = [
        STEP_CLASSES.inactive,
        STEP_CLASSES.slideOutLeft,
        STEP_CLASSES.slideOutRight,
        STEP_CLASSES.slideInLeft,
        STEP_CLASSES.slideInRight,
    ].join(' ');

    const ACTIVE_RESET_CLASSES = [
        STEP_CLASSES.active,
        STEP_CLASSES.slideOutLeft,
        STEP_CLASSES.slideOutRight,
        STEP_CLASSES.slideInLeft,
        STEP_CLASSES.slideInRight,
    ].join(' ');

    const NAV_BUTTONS = {
        next: '.register-form__next',
        check: '.register-form__check',
        previous: '.register-form__previous',
    };
    const ANIMATION_DURATION = 300;
    const SCROLL_DURATION = 300;
    const SCROLL_OFFSET = 100;
    const DEFAULT_DATE_LOCALE = 'nl-NL';
    const DEFAULT_DATE_FORMAT = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    };

    function RegisterPageWizard($form) {
        if (!$form || !$form.length) {
            return;
        }

        this.$form = $form;
        this.$steps = this.$form.find(SELECTORS.steps);
        this.$progressText = this.$form.find(SELECTORS.progressText);
        this.$progressBar =  this.$form.find(SELECTORS.progressBar);
        this.$currentStepTitle =  this.$form.find(SELECTORS.currentStepTitle);
        this.currentStep = 0;
        this.totalSteps = this.$steps.length;

        this.setStepState(0);
        this.bindEvents();
    }

    RegisterPageWizard.prototype.bindEvents = function () {
        const self = this;

        this.$form.on('click', NAV_BUTTONS.next, function (event) {
            event.preventDefault();
            self.goToNextStep();
        });

        this.$form.on('click', NAV_BUTTONS.check, function (event) {
            event.preventDefault();
            self.goToNextStep();
        });

        this.$form.on('click', NAV_BUTTONS.previous, function (event) {
            event.preventDefault();
            self.goToPreviousStep();
        });

        this.$form.on('input change', SELECTORS.input, function () {
            $(this).removeClass(FIELD_ERROR_CLASS);
        });
    };

    RegisterPageWizard.prototype.setStepState = function (targetIndex) {
        this.$steps.each(function (index) {
            const $step = $(this);
            if (index === targetIndex) {
                $step.removeClass(STEP_RESET_CLASSES).addClass(STEP_CLASSES.active);
            } else {
                $step.removeClass(ACTIVE_RESET_CLASSES).addClass(STEP_CLASSES.inactive);
            }
        });

        this.currentStep = targetIndex;
        this.updateProgressForStep(targetIndex);
        this.updateStepTitle();

        if (this.isSummaryStep(targetIndex)) {
            this.buildSummary();
        }
    };

    RegisterPageWizard.prototype.updateStepTitle = function () {
        if (!this.$currentStepTitle || !this.$currentStepTitle.length) {
            return;
        }

        const $currentStepElement = this.$steps.eq(this.currentStep);
        const label = $currentStepElement.data('label');

        if (label) {
            this.$currentStepTitle.text(label);
        }
    };

    RegisterPageWizard.prototype.updateProgressForStep = function (stepIndex) {
        if (!this.$progressText || !this.$progressBar) {
            return;
        }

        const stepNumber = stepIndex + 1;
        const percentage = (stepNumber / this.totalSteps) * 100;
        const stepLabel = `${WIZARD_TEXT.stepPrefix || ''}${stepNumber}${WIZARD_TEXT.stepSeparator || ''}${this.totalSteps}`;

        this.$progressText.text(stepLabel);
        this.$progressBar.css('width', `${percentage}%`);
    };

    RegisterPageWizard.prototype.validateStep = function (stepIndex, options) {
        const settings = options || {};
        const scrollOnError = settings.scrollOnError !== false;
        const $step = this.$steps.eq(stepIndex);

        if (!$step.length) {
            return { isValid: true, firstInvalidField: null };
        }

        const $fields = $step.find(SELECTORS.requiredFields);
        let isValid = true;
        let firstInvalidField = null;

        $step.find(SELECTORS.input).removeClass(FIELD_ERROR_CLASS);

        $fields.each(function () {
            const field = this;

            if (typeof field.checkValidity === 'function' && !field.checkValidity()) {
                isValid = false;
                const $field = $(field);
                $field.addClass(FIELD_ERROR_CLASS);
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            }
        });

        if (!isValid && scrollOnError && firstInvalidField) {
            this.scrollToField(firstInvalidField);
        }

        return { isValid, firstInvalidField };
    };

    RegisterPageWizard.prototype.scrollToField = function (field) {
        if (!field) {
            return;
        }

        $('html, body').animate(
            {
                scrollTop: $(field).offset().top - SCROLL_OFFSET,
            },
            SCROLL_DURATION
        );
    };

    RegisterPageWizard.prototype.goToNextStep = function () {
        const validation = this.validateStep(this.currentStep, { scrollOnError: true });

        if (!validation.isValid || this.currentStep >= this.totalSteps - 1) {
            return false;
        }

        const nextStepIndex = this.currentStep + 1;
        const $currentStepElement = this.$steps.eq(this.currentStep);
        const $nextStepElement = this.$steps.eq(nextStepIndex);

        this.updateProgressForStep(nextStepIndex);

        $currentStepElement.removeClass(STEP_CLASSES.active).addClass(STEP_CLASSES.slideOutLeft);
        $nextStepElement.removeClass(`${STEP_CLASSES.inactive} ${STEP_CLASSES.slideOutLeft}`).addClass(STEP_CLASSES.slideInRight);

        const self = this;
        setTimeout(function () {
            $currentStepElement.removeClass(STEP_CLASSES.slideOutLeft).addClass(STEP_CLASSES.inactive);
            $nextStepElement.removeClass(STEP_CLASSES.slideInRight).addClass(STEP_CLASSES.active);
            self.currentStep = nextStepIndex;
            self.updateStepTitle();

            if (self.isSummaryStep(nextStepIndex)) {
                self.buildSummary();
            }
        }, ANIMATION_DURATION);

        return true;
    };

    RegisterPageWizard.prototype.goToPreviousStep = function () {
        if (this.currentStep <= 0) {
            return false;
        }

        const previousStepIndex = this.currentStep - 1;
        const $currentStepElement = this.$steps.eq(this.currentStep);
        const $previousStepElement = this.$steps.eq(previousStepIndex);

        this.updateProgressForStep(previousStepIndex);

        $currentStepElement.removeClass(STEP_CLASSES.active).addClass(STEP_CLASSES.slideOutRight);
        $previousStepElement.removeClass(`${STEP_CLASSES.inactive} ${STEP_CLASSES.slideOutRight}`).addClass(STEP_CLASSES.slideInLeft);

        const self = this;
        setTimeout(function () {
            $currentStepElement.removeClass(STEP_CLASSES.slideOutRight).addClass(STEP_CLASSES.inactive);
            $previousStepElement.removeClass(STEP_CLASSES.slideInLeft).addClass(STEP_CLASSES.active);
            self.currentStep = previousStepIndex;
            self.updateStepTitle();
        }, ANIMATION_DURATION);

        return true;
    };

    RegisterPageWizard.prototype.buildSummary = function () {
        const $summaryDetails = this.$form.find(SELECTORS.summaryDetails);

        if (!$summaryDetails.length) {
            return;
        }

        $summaryDetails.empty();

        const $formSections = this.$form.find(SELECTORS.formSections);
        const self = this;

        $formSections.each(function () {
            const $section = $(this);
            const $formSectionSummary = $('<div />', { class: SUMMARY_CLASSES.section });
            const $formGroups = $section.find(SELECTORS.formSectionContent);

            $formGroups.each(function () {
                const $group = $(this);
                let sectionTitle = $group.closest(SELECTORS.formSections).data('label');
                const participantWrapper = $group.closest(SELECTORS.participantWrapper);

                if (participantWrapper.length) {
                    const participantHeader = participantWrapper.find(SELECTORS.participantHeader).text();
                    sectionTitle = participantHeader || sectionTitle;
                }

                if (sectionTitle) {
                    $formSectionSummary.append(
                        `<h2 class="${SUMMARY_CLASSES.sectionTitle}">${sectionTitle}</h2>`
                    );
                }

                $group.find('input, select, textarea').each(function () {
                    const $input = $(this);
                    const label = $input.attr('aria-label');
                    const inputType = $input.data('input-type');
                    let value = '';

                    if (!label || !inputType) {
                        return;
                    }

                    if ($input.is(':checkbox')) {
                        value = $input.is(':checked') ? WIZARD_TEXT.yes : WIZARD_TEXT.no;
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
                        value = date.toLocaleDateString(DEFAULT_DATE_LOCALE, DEFAULT_DATE_FORMAT);
                    }

                    if (value) {
                        $formSectionSummary.append(
                            `<div class="${SUMMARY_CLASSES.item}">
                                <span class="${SUMMARY_CLASSES.itemLabel}">${label}</span>
                                <span class="${SUMMARY_CLASSES.itemValue}">${value}</span>
                             </div>`
                        );
                    }
                });
            });

            $summaryDetails.append($formSectionSummary);
        });
    };

    RegisterPageWizard.prototype.isSummaryStep = function (index) {
        return index === this.totalSteps - 1;
    };

    RegisterPageWizard.prototype.validateAllSteps = function () {
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
    };

    window.RegisterPageWizard = RegisterPageWizard;
})(jQuery, window);

