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
});
