jQuery(document).ready(function($) {
    const $form = $('.register-page__form');
    const $steps = $('.form-section__step');
    const $progressText = $('.form-section__progress-text');
    const $progressBar = $('.form-section__progress-bar');
    const $currentStepTitle = $('.form-section__current-step-title');
    const $participantsContainer = $('.register-form__participants');

    let currentStep = 0; // Using 0-based index
    const totalSteps = $steps.length;
    
    // Initialize: hide all steps except first
    function initWizard() {
        $steps.each(function(index) {
            const $step = $(this);
            if (index === 0) {
                $step.removeClass('inactive slide-out-left slide-out-right slide-in-left slide-in-right')
                     .addClass('active');
            } else {
                $step.removeClass('active slide-out-left slide-out-right slide-in-left slide-in-right')
                     .addClass('inactive');
            }
        });
        updateProgress();
        updateStepTitle();
    }
    
    // Update step title from data-label
    function updateStepTitle() {
        const $currentStepElement = $steps.eq(currentStep);
        const label = $currentStepElement.data('label');
        if (label) {
            $currentStepTitle.text(label);
        }
    }
    
    // Update progress bar and text
    function updateProgress() {
        const stepNumber = currentStep + 1; // Display as 1-based
        const percentage = (stepNumber / totalSteps) * 100;
        $progressText.text(`Stap ${stepNumber} van ${totalSteps}`);
        $progressBar.css('width', percentage + '%');
    }
    
    // Validate current step
    function validateCurrentStep() {
        const $currentStepElement = $steps.eq(currentStep);
        const $fields = $currentStepElement.find('input[required], select[required], textarea[required]');
        
        let isValid = true;
        let firstInvalidField = null;
        
        // Clear previous validation errors
        $currentStepElement.find('.form-field__error').remove();
        $currentStepElement.find('.form-field__input').removeClass('form-field__input--error');
        
        // Validate each required field in the current step
        $fields.each(function() {
            const $field = $(this);
            const fieldElement = this;
            
            // Check if field is valid
            if (!fieldElement.checkValidity()) {
                isValid = false;
                
                // Add error class to field
                $field.addClass('form-field__input--error');
                
                // Find the form field container
                const $formField = $field.closest('.form-field');
                if ($formField.length) {
                    // Get validation message
                    let errorMessage = fieldElement.validationMessage;
                    if (!errorMessage) {
                        errorMessage = 'Dit veld is verplicht';
                    }
                    
                    // Add error message
                    $formField.append(
                        `<div class="form-field__error">${errorMessage}</div>`
                    );
                }
                
                // Store first invalid field for scrolling
                if (!firstInvalidField) {
                    firstInvalidField = fieldElement;
                }
            }
        });
        
        // Scroll to first invalid field if any
        if (!isValid && firstInvalidField) {
            $('html, body').animate({
                scrollTop: $(firstInvalidField).offset().top - 100
            }, 300);
        }
        
        return isValid;
    }
    
    // Go to next step
    function goToNextStep() {
        // Validate current step before proceeding
        if (!validateCurrentStep()) {
            return false;
        }
        
        if (currentStep < totalSteps - 1) {
            const $currentStepElement = $steps.eq(currentStep);
            const $nextStepElement = $steps.eq(currentStep + 1);
            const nextStepIndex = currentStep + 1;
            
            // Update progress bar immediately based on target step
            const stepNumber = nextStepIndex + 1; // Display as 1-based
            const percentage = (stepNumber / totalSteps) * 100;
            $progressText.text(`Stap ${stepNumber} van ${totalSteps}`);
            $progressBar.css('width', percentage + '%');
            
            // Slide current step out to left
            $currentStepElement.removeClass('active').addClass('slide-out-left');
            
            // Slide next step in from right
            $nextStepElement.removeClass('inactive slide-out-left').addClass('slide-in-right');
            
            setTimeout(function() {
                $currentStepElement.removeClass('slide-out-left').addClass('inactive');
                $nextStepElement.removeClass('slide-in-right').addClass('active');
                currentStep = nextStepIndex;
                updateStepTitle();
            }, 300);
        }
        return true;
    }
    
    // Go to previous step
    function goToPreviousStep() {
        if (currentStep > 0) {
            const $currentStepElement = $steps.eq(currentStep);
            const $previousStepElement = $steps.eq(currentStep - 1);
            const previousStepIndex = currentStep - 1;
            
            // Update progress bar immediately based on target step
            const stepNumber = previousStepIndex + 1; // Display as 1-based
            const percentage = (stepNumber / totalSteps) * 100;
            $progressText.text(`Stap ${stepNumber} van ${totalSteps}`);
            $progressBar.css('width', percentage + '%');
            
            // Slide current step out to right
            $currentStepElement.removeClass('active').addClass('slide-out-right');
            
            // Slide previous step in from left
            $previousStepElement.removeClass('inactive slide-out-right').addClass('slide-in-left');
            
            setTimeout(function() {
                $currentStepElement.removeClass('slide-out-right').addClass('inactive');
                $previousStepElement.removeClass('slide-in-left').addClass('active');
                currentStep = previousStepIndex;
                updateStepTitle();
            }, 300);
        }
    }
    
    // Clone participant form
    function addParticipant() {
        const $firstParticipant = $participantsContainer.find('.register-form__participant').first();
        
        if ($firstParticipant.length === 0) {
            return;
        }
        
        // Clone the first participant form
        const $newParticipant = $firstParticipant.clone();
        
        // Get the next participant index
        const currentCount = $participantsContainer.find('.register-form__participant').length;
        const newIndex = currentCount + 1;
        
        // Update participant index
        $newParticipant.attr('data-participant-index', newIndex);
        
        // Update header text and ensure delete button is present
        $newParticipant.find('.register-form__participant-header').text('Deelnemer ' + newIndex);
        
        // Ensure delete button exists in cloned participant
        if ($newParticipant.find('.register-form__remove_participant').length === 0) {
            const $headerWrapper = $newParticipant.find('.register-form__participant-header').closest('.register-form__participant-header-wrapper');
            if ($headerWrapper.length === 0) {
                // Wrap header in wrapper if not already wrapped
                const $header = $newParticipant.find('.register-form__participant-header');
                $header.wrap('<div class="register-form__participant-header-wrapper"></div>');
            }
            const $wrapper = $newParticipant.find('.register-form__participant-header-wrapper');
            $wrapper.append('<button type="button" class="register-form__remove_participant" title="Deelnemer verwijderen">×</button>');
        }
        
        // Clear all input values
        $newParticipant.find('input[type="text"], input[type="email"], input[type="number"], input[type="date"], textarea').val('');
        $newParticipant.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
        $newParticipant.find('select').prop('selectedIndex', 0);
        
        // Update all IDs and labels to be unique
        $newParticipant.find('[id]').each(function() {
            const $element = $(this);
            const oldId = $element.attr('id');
            if (!oldId) return;
            
            const newId = oldId + '_' + newIndex;
            $element.attr('id', newId);
            
            // Update corresponding label 'for' attribute within the cloned participant
            $newParticipant.find('label[for="' + oldId + '"]').each(function() {
                $(this).attr('for', newId);
            });
        });
        
        // Update name attributes to include index for array submission
        $newParticipant.find('[name]').each(function() {
            const $element = $(this);
            const name = $element.attr('name');
            // Only update if it's not already an array
            if (name && !name.includes('[')) {
                $element.attr('name', name + '[' + newIndex + ']');
            }
        });
        
        // Remove any error states
        $newParticipant.find('.form-field__error').remove();
        $newParticipant.find('.form-field__input--error').removeClass('form-field__input--error');
        
        // Append the new participant
        $participantsContainer.append($newParticipant);
        
        // Update delete button visibility
        updateDeleteButtonsVisibility();
        updatePrices();
    }

    
    // Remove participant form
    function removeParticipant($participant) {
        const participantCount = $participantsContainer.find('.register-form__participant').length;
        
        // Don't allow deletion if only one participant remains
        if (participantCount <= 1) {
            return;
        }
        
        // Show confirmation dialog
        if (!confirm('Weet je zeker dat je deze deelnemer wilt verwijderen?')) {
            return;
        }
        
        // Remove the participant
        $participant.remove();
        
        // Re-index remaining participants
        reindexParticipants();
        updatePrices();
    }

    function updatePrices() {
        const numberOfParticipants = $participantsContainer.find('.register-form__participant').length;
        const $itemPrice = $('.register-page__cart--item')
        const $itemQuantityElem = $('.register-page__cart--quantity');
        const $totalPrice = $('.register-page__cart--total-price');

        const itemPrice = parseFloat($itemPrice.data('price'));
        const totalPrice = itemPrice * numberOfParticipants;
        const formattedTotalPrice = totalPrice.toFixed(2).replace('.', ',');

        console.log(itemPrice, numberOfParticipants, totalPrice, formattedTotalPrice);

        $itemQuantityElem.data('quantity', numberOfParticipants);
        $itemQuantityElem.text(numberOfParticipants + ' deelnemer' + (numberOfParticipants > 1 ? 's' : ''));
        $totalPrice.html('&euro; ' + formattedTotalPrice );
    }
    
    // Re-index all participants after deletion
    function reindexParticipants() {
        const $participants = $participantsContainer.find('.register-form__participant');
        
        $participants.each(function(index) {
            const newIndex = index + 1;
            const $participant = $(this);
            
            // Update data attribute
            $participant.attr('data-participant-index', newIndex);
            
            // Update header text
            $participant.find('.register-form__participant-header').text('Deelnemer ' + newIndex);
            
            // Update all IDs
            $participant.find('[id]').each(function() {
                const $element = $(this);
                const oldId = $element.attr('id');
                if (!oldId) return;
                
                // Extract base ID (remove old index suffix if present)
                let baseId = oldId;
                const idMatch = oldId.match(/^(.+?)_\d+$/);
                if (idMatch) {
                    baseId = idMatch[1];
                }
                
                const newId = baseId + '_' + newIndex;
                $element.attr('id', newId);
                
                // Update corresponding label 'for' attribute
                $participant.find('label[for="' + oldId + '"]').each(function() {
                    $(this).attr('for', newId);
                });
            });
            
            // Update name attributes
            $participant.find('[name]').each(function() {
                const $element = $(this);
                let name = $element.attr('name');
                if (!name) return;
                
                // Extract base name (remove array index if present)
                let baseName = name;
                const nameMatch = name.match(/^(.+?)\[\d+\]$/);
                if (nameMatch) {
                    baseName = nameMatch[1];
                }
                
                // Only update if it was an array or if it's not already an array
                if (nameMatch || !name.includes('[')) {
                    $element.attr('name', baseName + '[' + newIndex + ']');
                }
            });
        });
        
        // Update delete button visibility
        updateDeleteButtonsVisibility();
    }
    
    // Update delete button visibility (hide if only one participant)
    function updateDeleteButtonsVisibility() {
        const $participantsContainer = $('.register-form__participants');
        const participantCount = $participantsContainer.find('.register-form__participant').length;
        
        if (participantCount <= 1) {
            $participantsContainer.find('.register-form__remove_participant').hide();
        } else {
            $participantsContainer.find('.register-form__remove_participant').show();
        }
    }
    
    // Event handlers using event delegation
    $form.on('click', '.register-form__add_participant', function(e) {
        e.preventDefault();
        addParticipant();
    });
    
    $form.on('click', '.register-form__remove_participant', function(e) {
        e.preventDefault();
        const $participant = $(this).closest('.register-form__participant');
        removeParticipant($participant);
    });
    
    $form.on('click', '.register-form__next', function(e) {
        e.preventDefault();
        goToNextStep();
    });
    
    $form.on('click', '.register-form__check', function(e) {
        e.preventDefault();
        goToNextStep();
    });
    
    $form.on('click', '.register-form__previous', function(e) {
        e.preventDefault();
        goToPreviousStep();
    });
    
    // Remove error styling on input
    $form.on('input change', '.form-field__input', function() {
        const $field = $(this);
        $field.removeClass('form-field__input--error');
        $field.closest('.form-field').find('.form-field__error').remove();
    });
    
    // Validate on form submit (final step)
    $form.on('submit', function(e) {
        // Validate all steps before final submit
        let allStepsValid = true;
        
        // Store current step
        const originalStep = currentStep;
        
        // Validate each step
        for (let i = 0; i < totalSteps; i++) {
            currentStep = i;
            if (!validateCurrentStep()) {
                allStepsValid = false;
                // Navigate to first invalid step
                if (i !== originalStep) {
                    // Navigate to invalid step (simplified - you might want to add navigation logic)
                    $steps.eq(i).removeClass('inactive').addClass('active');
                    $steps.not($steps.eq(i)).removeClass('active').addClass('inactive');
                    updateProgress();
                    updateStepTitle();
                }
                break;
            }
        }
        
        // Restore original step
        currentStep = originalStep;
        
        if (!allStepsValid) {
            e.preventDefault();
            return false;
        }
    });
    
    // Initialize wizard
    initWizard();
    
    // Initialize delete button visibility
    updateDeleteButtonsVisibility();
});