/**
 * Snippe Payment Gateway - Blocks Support
 */

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement, useState, useEffect, useCallback } = window.wp.element;
const { decodeEntities } = window.wp.htmlEntities;
const { getSetting } = window.wc.wcSettings;

const settings = getSetting('snippe_data', {});

const defaultLabel = decodeEntities(settings.title) || 'Snippe Payment';
const defaultDescription = decodeEntities(settings.description) || 'Pay securely using Mobile Money, Card, or QR Code';

const Label = (props) => {
    const { PaymentMethodLabel } = props.components;

    return createElement(
        'div',
        { className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--snippe' },
        createElement(
            PaymentMethodLabel,
            { text: defaultLabel },
            settings.logo_url && createElement('img', {
                src: settings.logo_url,
                alt: defaultLabel,
                style: { maxHeight: '30px', marginLeft: '10px' }
            })
        )
    );
};

/**
 * Phone number input field component
 */
const PhoneField = ({ onPhoneChange }) => {
    return createElement(
        'div',
        { className: 'snippe-blocks-field' },
        createElement('label', {
            className: 'snippe-blocks-label',
            htmlFor: 'snippe-phone-number'
        }, 'Phone Number ', createElement('span', { className: 'snippe-blocks-required' }, '*')),
        createElement('input', {
            type: 'tel',
            id: 'snippe-phone-number',
            placeholder: '0782123456',
            className: 'snippe-blocks-input',
            onChange: (e) => onPhoneChange(e.target.value)
        }),
        createElement('span', { className: 'snippe-blocks-help' },
            'Enter your mobile money phone number (e.g., 0782123456, +255782123456, or 255782123456)'
        )
    );
};

/**
 * Content component rendered when payment method is selected
 */
const Content = ({ eventRegistration, emitResponse }) => {
    const configuredType = settings.payment_type || 'mobile';
    const isCustomerChoice = configuredType === 'customer_choice';

    const [selectedType, setSelectedType] = useState(isCustomerChoice ? '' : configuredType);
    const [phoneNumber, setPhoneNumber] = useState('');

    const { onPaymentSetup } = eventRegistration;

    useEffect(() => {
        const unsubscribe = onPaymentSetup(() => {
            const currentType = isCustomerChoice ? selectedType : configuredType;

            if (isCustomerChoice && !currentType) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: 'Please select a payment method.'
                };
            }

            if (currentType === 'mobile' && !phoneNumber.trim()) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: 'Phone number is required for mobile money payments.'
                };
            }

            return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                    paymentMethodData: {
                        snippe_payment_type: currentType,
                        snippe_phone_number: phoneNumber
                    }
                }
            };
        });

        return unsubscribe;
    }, [onPaymentSetup, emitResponse, selectedType, phoneNumber, isCustomerChoice, configuredType]);

    const showPhone = isCustomerChoice ? selectedType === 'mobile' : configuredType === 'mobile';

    return createElement(
        'div',
        { className: 'snippe-blocks-content' },
        createElement('p', { className: 'snippe-blocks-description' }, defaultDescription),

        // Payment type selector (only for customer_choice)
        isCustomerChoice && createElement(
            'div',
            { className: 'snippe-blocks-field' },
            createElement('label', {
                className: 'snippe-blocks-label',
                htmlFor: 'snippe-payment-type-select'
            }, 'Select Payment Method ', createElement('span', { className: 'snippe-blocks-required' }, '*')),
            createElement('select', {
                id: 'snippe-payment-type-select',
                className: 'snippe-blocks-select',
                value: selectedType,
                onChange: (e) => setSelectedType(e.target.value)
            },
                createElement('option', { value: '' }, '— Choose payment method —'),
                createElement('option', { value: 'mobile' }, 'Mobile Money'),
                createElement('option', { value: 'card' }, 'Credit/Debit Card')
                // createElement('option', { value: 'dynamic-qr' }, 'QR Code') // QR Code currently offline
            )
        ),

        // Phone field (for mobile money)
        showPhone && createElement(PhoneField, { onPhoneChange: setPhoneNumber })
    );
};

const snippePaymentMethod = {
    name: 'snippe',
    label: createElement(Label, null),
    content: createElement(Content, null),
    edit: createElement(Content, null),
    canMakePayment: () => true,
    ariaLabel: defaultLabel,
    supports: {
        features: settings.supports || ['products']
    }
};

registerPaymentMethod(snippePaymentMethod);
