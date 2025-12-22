/**
 * Initialize intl-tel-input on the phone field
 */
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.querySelector('#uic_telephone');

    if (!phoneInput) {
        return;
    }

    // Initialize intl-tel-input
    const iti = window.intlTelInput(phoneInput, {
        initialCountry: 'us',
        preferredCountries: ['us', 'ca', 'gb', 'au'],
        separateDialCode: true,
        formatOnDisplay: true,
        autoPlaceholder: 'aggressive',
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/utils.js'
    });

    // Get the form element
    const form = phoneInput.closest('form');

    if (!form) {
        return;
    }

    // On form submit, populate hidden fields with full phone number and country code
    form.addEventListener('submit', function(e) {
        const fullNumber = iti.getNumber();
        const countryData = iti.getSelectedCountryData();

        // Set hidden field values
        document.querySelector('#uic_full_telephone').value = fullNumber;
        document.querySelector('#uic_country_code').value = '+' + countryData.dialCode;

        // Validate phone number
        if (!iti.isValidNumber()) {
            e.preventDefault();
            phoneInput.classList.add('uic-input-error');

            // Show error message if not already present
            let errorSpan = phoneInput.parentElement.querySelector('.uic-field-error');
            if (!errorSpan) {
                errorSpan = document.createElement('span');
                errorSpan.className = 'uic-field-error';
                errorSpan.textContent = 'Please enter a valid phone number.';
                phoneInput.parentElement.appendChild(errorSpan);
            }

            return false;
        } else {
            phoneInput.classList.remove('uic-input-error');

            // Remove error message if present
            const errorSpan = phoneInput.parentElement.querySelector('.uic-field-error');
            if (errorSpan) {
                errorSpan.remove();
            }
        }
    });

    // Remove error styling on input
    phoneInput.addEventListener('input', function() {
        phoneInput.classList.remove('uic-input-error');
        const errorSpan = phoneInput.parentElement.querySelector('.uic-field-error');
        if (errorSpan && errorSpan.textContent === 'Please enter a valid phone number.') {
            errorSpan.remove();
        }
    });

    // Notification dot functionality
    initNotificationDots();
});

/**
 * Initialize notification dots for form fields
 * Only shows dot on the first unfilled field, progressing sequentially
 * With a 1-second delay when moving to the next field
 */
function initNotificationDots() {
    const dots = document.querySelectorAll('.uic-notification-dot');
    const fields = [];
    let updateTimeout = null;
    let lastFilledIndex = -1;

    // Collect all fields with their dots
    dots.forEach(dot => {
        const fieldId = dot.getAttribute('data-field');
        const field = document.getElementById(fieldId);

        if (field) {
            fields.push({ field, dot });
        }
    });

    // Function to update all dots based on current form state
    function updateAllDots(withDelay = false) {
        // Clear any pending timeout
        if (updateTimeout) {
            clearTimeout(updateTimeout);
            updateTimeout = null;
        }

        let foundFirstEmpty = false;
        let firstEmptyIndex = -1;

        // Find the first empty field
        fields.forEach(({ field, dot }, index) => {
            const hasValue = field.value.trim() !== '';

            if (!hasValue && !foundFirstEmpty) {
                firstEmptyIndex = index;
                foundFirstEmpty = true;
            }
        });

        // Check if a field was just filled (moving forward)
        const shouldDelay = withDelay && firstEmptyIndex > lastFilledIndex && lastFilledIndex >= 0;

        if (shouldDelay) {
            // Hide all dots immediately
            fields.forEach(({ dot }) => {
                dot.classList.add('hidden');
            });

            // Show the next dot after 1.0 seconds
            updateTimeout = setTimeout(() => {
                if (firstEmptyIndex >= 0) {
                    fields[firstEmptyIndex].dot.classList.remove('hidden');
                }
                lastFilledIndex = firstEmptyIndex;
            }, 1000);
        } else {
            // Immediate update (no delay)
            fields.forEach(({ field, dot }, index) => {
                const hasValue = field.value.trim() !== '';

                if (!hasValue && index === firstEmptyIndex) {
                    // This is the first empty field - show the dot
                    dot.classList.remove('hidden');
                } else {
                    // Either field is filled or it's not the first empty field - hide the dot
                    dot.classList.add('hidden');
                }
            });
            lastFilledIndex = firstEmptyIndex;
        }
    }

    // Initial state update
    updateAllDots(false);

    // Add event listeners to all fields
    fields.forEach(({ field }) => {
        if (field.tagName === 'SELECT') {
            field.addEventListener('change', () => updateAllDots(true));
        } else {
            field.addEventListener('input', () => updateAllDots(true));
            field.addEventListener('blur', () => updateAllDots(true));
        }
    });
};
