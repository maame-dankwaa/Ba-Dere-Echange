/**
 * Ba Dere Exchange - Checkout JavaScript
 * Handles checkout form validation, payment method selection, and submission
 */

(function() {
    'use strict';

    /**
     * Initialize checkout functionality
     */
    function init() {
        setupPaymentMethods();
        setupDeliveryMethods();
        setupFormValidation();
        setupOrderSummary();
        setupPhoneValidation();
    }

    /**
     * Setup payment method selection
     */
    function setupPaymentMethods() {
        const paymentOptions = document.querySelectorAll('input[name="payment_method"]');
        const paymentDetails = document.querySelectorAll('.payment-details');

        if (paymentOptions.length === 0) return;

        paymentOptions.forEach(option => {
            option.addEventListener('change', function() {
                // Update visual selection
                document.querySelectorAll('.payment-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.closest('.payment-option')?.classList.add('selected');

                // Show relevant details
                paymentDetails.forEach(detail => {
                    detail.style.display = 'none';
                });

                const detailSection = document.querySelector(`.payment-details[data-method="${this.value}"]`);
                if (detailSection) {
                    detailSection.style.display = 'block';
                }

                // Update required fields based on method
                updateRequiredFields(this.value);
            });
        });

        // Trigger initial state
        const checkedPayment = document.querySelector('input[name="payment_method"]:checked');
        if (checkedPayment) {
            checkedPayment.dispatchEvent(new Event('change'));
        }
    }

    /**
     * Update required fields based on payment method
     */
    function updateRequiredFields(method) {
        // Mobile money phone field
        const momoPhone = document.querySelector('[name="momo_phone"]');
        if (momoPhone) {
            momoPhone.required = method === 'mobile_money';
        }

        // Card fields
        const cardFields = document.querySelectorAll('.card-field');
        cardFields.forEach(field => {
            field.required = method === 'visa';
        });
    }

    /**
     * Setup delivery method selection
     */
    function setupDeliveryMethods() {
        const deliveryOptions = document.querySelectorAll('input[name="delivery_method"]');
        const deliveryDetails = document.querySelectorAll('.delivery-details');
        const deliveryFeeEl = document.querySelector('.delivery-fee');

        if (deliveryOptions.length === 0) return;

        deliveryOptions.forEach(option => {
            option.addEventListener('change', function() {
                // Update visual selection
                document.querySelectorAll('.delivery-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.closest('.delivery-option')?.classList.add('selected');

                // Show relevant details
                deliveryDetails.forEach(detail => {
                    detail.style.display = 'none';
                });

                const detailSection = document.querySelector(`.delivery-details[data-method="${this.value}"]`);
                if (detailSection) {
                    detailSection.style.display = 'block';
                }

                // Update delivery fee
                if (deliveryFeeEl) {
                    const fee = this.dataset.fee || 0;
                    deliveryFeeEl.textContent = formatCurrency(fee);
                    updateOrderTotal();
                }

                // Update required fields
                const addressFields = document.querySelectorAll('.address-field');
                addressFields.forEach(field => {
                    field.required = this.value === 'delivery';
                });
            });
        });

        // Trigger initial state
        const checkedDelivery = document.querySelector('input[name="delivery_method"]:checked');
        if (checkedDelivery) {
            checkedDelivery.dispatchEvent(new Event('change'));
        }
    }

    /**
     * Setup form validation
     */
    function setupFormValidation() {
        const form = document.querySelector('.checkout-form, form[action*="checkout"]');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            const errors = validateCheckoutForm(this);

            if (errors.length > 0) {
                e.preventDefault();
                showErrors(errors);
                return;
            }

            // Disable submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
            }
        });

        // Real-time validation
        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', () => validateField(field));
            field.addEventListener('input', () => {
                field.classList.remove('is-invalid');
                const error = field.parentElement.querySelector('.field-error');
                if (error) error.remove();
            });
        });
    }

    /**
     * Validate checkout form
     */
    function validateCheckoutForm(form) {
        const errors = [];
        const data = new FormData(form);
        const paymentMethod = data.get('payment_method');
        const deliveryMethod = data.get('delivery_method');

        // Payment method
        if (!paymentMethod) {
            errors.push({ field: 'payment_method', message: 'Please select a payment method' });
        }

        // Mobile Money validation
        if (paymentMethod === 'mobile_money') {
            const momoPhone = data.get('momo_phone')?.trim();
            if (!momoPhone) {
                errors.push({ field: 'momo_phone', message: 'Mobile money number is required' });
            } else if (!isValidGhanaPhone(momoPhone)) {
                errors.push({ field: 'momo_phone', message: 'Please enter a valid Ghana phone number' });
            }
        }

        // Card validation
        if (paymentMethod === 'visa') {
            const cardNumber = data.get('card_number')?.replace(/\s/g, '');
            const cardExpiry = data.get('card_expiry')?.trim();
            const cardCvv = data.get('card_cvv')?.trim();

            if (!cardNumber || cardNumber.length < 13) {
                errors.push({ field: 'card_number', message: 'Please enter a valid card number' });
            }
            if (!cardExpiry || !isValidExpiry(cardExpiry)) {
                errors.push({ field: 'card_expiry', message: 'Please enter a valid expiry date (MM/YY)' });
            }
            if (!cardCvv || cardCvv.length < 3) {
                errors.push({ field: 'card_cvv', message: 'Please enter a valid CVV' });
            }
        }

        // Delivery method
        if (!deliveryMethod) {
            errors.push({ field: 'delivery_method', message: 'Please select a delivery method' });
        }

        // Address validation for delivery
        if (deliveryMethod === 'delivery') {
            const address = data.get('delivery_address')?.trim();
            const city = data.get('delivery_city')?.trim();

            if (!address) {
                errors.push({ field: 'delivery_address', message: 'Delivery address is required' });
            }
            if (!city) {
                errors.push({ field: 'delivery_city', message: 'City is required' });
            }
        }

        // Contact phone
        const contactPhone = data.get('contact_phone')?.trim();
        if (!contactPhone) {
            errors.push({ field: 'contact_phone', message: 'Contact phone is required' });
        } else if (!isValidGhanaPhone(contactPhone)) {
            errors.push({ field: 'contact_phone', message: 'Please enter a valid Ghana phone number' });
        }

        // Mark invalid fields
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        errors.forEach(err => {
            const field = form.querySelector(`[name="${err.field}"]`);
            if (field) field.classList.add('is-invalid');
        });

        return errors;
    }

    /**
     * Validate single field
     */
    function validateField(field) {
        const name = field.name;
        const value = field.value?.trim();
        let error = null;

        switch (name) {
            case 'momo_phone':
            case 'contact_phone':
                if (field.required && !value) {
                    error = 'Phone number is required';
                } else if (value && !isValidGhanaPhone(value)) {
                    error = 'Please enter a valid Ghana phone number';
                }
                break;
            case 'card_number':
                if (field.required && (!value || value.replace(/\s/g, '').length < 13)) {
                    error = 'Please enter a valid card number';
                }
                break;
            case 'card_expiry':
                if (field.required && (!value || !isValidExpiry(value))) {
                    error = 'Please enter a valid expiry (MM/YY)';
                }
                break;
            case 'card_cvv':
                if (field.required && (!value || value.length < 3)) {
                    error = 'Please enter a valid CVV';
                }
                break;
            case 'delivery_address':
                if (field.required && !value) {
                    error = 'Address is required';
                }
                break;
        }

        field.classList.toggle('is-invalid', !!error);

        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) existingError.remove();

        if (error) {
            const errorEl = document.createElement('span');
            errorEl.className = 'field-error';
            errorEl.textContent = error;
            field.parentElement.appendChild(errorEl);
        }
    }

    /**
     * Validate Ghana phone number
     */
    function isValidGhanaPhone(phone) {
        // Remove spaces and dashes
        phone = phone.replace(/[\s-]/g, '');

        // Ghana phone formats: 0XX XXX XXXX or +233 XX XXX XXXX
        const patterns = [
            /^0[235][0-9]{8}$/,           // 0XX XXX XXXX
            /^\+233[235][0-9]{8}$/,       // +233 XX XXX XXXX
            /^233[235][0-9]{8}$/          // 233 XX XXX XXXX
        ];

        return patterns.some(pattern => pattern.test(phone));
    }

    /**
     * Validate card expiry
     */
    function isValidExpiry(expiry) {
        const match = expiry.match(/^(\d{2})\/(\d{2})$/);
        if (!match) return false;

        const month = parseInt(match[1], 10);
        const year = parseInt('20' + match[2], 10);
        const now = new Date();
        const currentYear = now.getFullYear();
        const currentMonth = now.getMonth() + 1;

        if (month < 1 || month > 12) return false;
        if (year < currentYear) return false;
        if (year === currentYear && month < currentMonth) return false;

        return true;
    }

    /**
     * Show validation errors
     */
    function showErrors(errors) {
        // Remove existing
        const existing = document.querySelector('.checkout-errors');
        if (existing) existing.remove();

        const errorDiv = document.createElement('div');
        errorDiv.className = 'checkout-errors alert alert-danger';
        errorDiv.innerHTML = `
            <strong>Please fix the following:</strong>
            <ul>${errors.map(e => `<li>${e.message}</li>`).join('')}</ul>
        `;

        const form = document.querySelector('.checkout-form, form[action*="checkout"]');
        if (form) {
            form.insertBefore(errorDiv, form.firstChild);
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        if (typeof BaDere !== 'undefined') {
            BaDere.toast('Please fix the errors in the form', 'error');
        }
    }

    /**
     * Setup order summary updates
     */
    function setupOrderSummary() {
        // Listen for delivery fee changes
        const deliveryOptions = document.querySelectorAll('input[name="delivery_method"]');
        deliveryOptions.forEach(option => {
            option.addEventListener('change', updateOrderTotal);
        });
    }

    /**
     * Update order total
     */
    function updateOrderTotal() {
        const subtotalEl = document.querySelector('.order-subtotal');
        const deliveryFeeEl = document.querySelector('.delivery-fee');
        const totalEl = document.querySelector('.order-total');

        if (!subtotalEl || !totalEl) return;

        const subtotal = parseFloat(subtotalEl.dataset.value || subtotalEl.textContent.replace(/[^\d.]/g, '')) || 0;

        let deliveryFee = 0;
        const selectedDelivery = document.querySelector('input[name="delivery_method"]:checked');
        if (selectedDelivery) {
            deliveryFee = parseFloat(selectedDelivery.dataset.fee) || 0;
        }

        const total = subtotal + deliveryFee;
        totalEl.textContent = formatCurrency(total);

        if (deliveryFeeEl) {
            deliveryFeeEl.textContent = formatCurrency(deliveryFee);
        }
    }

    /**
     * Setup phone number formatting and validation
     */
    function setupPhoneValidation() {
        const phoneInputs = document.querySelectorAll('input[type="tel"], [name*="phone"]');

        phoneInputs.forEach(input => {
            // Format on input
            input.addEventListener('input', function() {
                let value = this.value.replace(/[^\d+]/g, '');

                // Limit length
                if (value.startsWith('+')) {
                    value = value.slice(0, 13); // +233XXXXXXXXX
                } else {
                    value = value.slice(0, 10); // 0XXXXXXXXX
                }

                this.value = value;
            });

            // Add placeholder
            if (!input.placeholder) {
                input.placeholder = '024 XXX XXXX';
            }
        });

        // Card number formatting
        const cardNumber = document.querySelector('[name="card_number"]');
        if (cardNumber) {
            cardNumber.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                value = value.slice(0, 16);

                // Add spaces every 4 digits
                const formatted = value.match(/.{1,4}/g)?.join(' ') || value;
                this.value = formatted;
            });
        }

        // Expiry formatting
        const cardExpiry = document.querySelector('[name="card_expiry"]');
        if (cardExpiry) {
            cardExpiry.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                value = value.slice(0, 4);

                if (value.length >= 2) {
                    value = value.slice(0, 2) + '/' + value.slice(2);
                }

                this.value = value;
            });
            cardExpiry.placeholder = 'MM/YY';
        }

        // CVV - numbers only
        const cardCvv = document.querySelector('[name="card_cvv"]');
        if (cardCvv) {
            cardCvv.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').slice(0, 4);
            });
        }
    }

    /**
     * Format currency (Ghana Cedis)
     */
    function formatCurrency(amount) {
        if (typeof BaDere !== 'undefined' && BaDere.formatCurrency) {
            return BaDere.formatCurrency(amount);
        }
        return 'GH₵ ' + parseFloat(amount).toFixed(2);
    }

    // Add checkout styles
    function addStyles() {
        if (document.getElementById('checkout-styles')) return;

        const style = document.createElement('style');
        style.id = 'checkout-styles';
        style.textContent = `
            .payment-option,
            .delivery-option {
                border: 2px solid var(--border-color, #dee2e6);
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 10px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .payment-option:hover,
            .delivery-option:hover {
                border-color: var(--primary-color, #28a745);
            }
            .payment-option.selected,
            .delivery-option.selected {
                border-color: var(--primary-color, #28a745);
                background: rgba(40, 167, 69, 0.05);
            }
            .payment-details,
            .delivery-details {
                display: none;
                padding: 15px;
                background: var(--bg-light, #f8f9fa);
                border-radius: 8px;
                margin-top: 10px;
            }
            .checkout-errors {
                margin-bottom: 20px;
            }
            .checkout-errors ul {
                margin: 10px 0 0 20px;
            }
            .field-error {
                display: block;
                color: var(--danger-color, #dc3545);
                font-size: 12px;
                margin-top: 4px;
            }
            .is-invalid {
                border-color: var(--danger-color, #dc3545) !important;
            }
            .spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #fff;
                border-radius: 50%;
                border-top-color: transparent;
                animation: spin 0.8s linear infinite;
                margin-right: 8px;
                vertical-align: middle;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            .order-summary {
                background: var(--bg-light, #f8f9fa);
                padding: 20px;
                border-radius: 8px;
            }
            .order-total {
                font-size: 1.25rem;
                font-weight: bold;
                color: var(--primary-color, #28a745);
            }
        `;
        document.head.appendChild(style);
    }

    // Initialize
    addStyles();

    if (typeof BaDere !== 'undefined') {
        BaDere.ready(init);
    } else {
        document.addEventListener('DOMContentLoaded', init);
    }
})();
