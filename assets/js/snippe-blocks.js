/**
 * Snippe Payment Gateway - Blocks Support
 */

const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { createElement } = window.wp.element;
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

const Content = () => {
    const paymentType = settings.payment_type || 'mobile';
    
    return createElement(
        'div',
        { className: 'snippe-payment-method-content' },
        createElement('p', null, defaultDescription),
        paymentType === 'customer_choice' && createElement(
            'div',
            { className: 'wc-block-components-text-input' },
            createElement('p', { className: 'wc-block-components-validation-error' }, 
                'Please complete payment selection in the classic checkout for customer choice option.'
            )
        ),
        paymentType === 'mobile' && createElement(
            'div',
            { className: 'wc-block-components-text-input' },
            createElement('label', { htmlFor: 'snippe-phone-number' }, 'Phone Number *'),
            createElement('input', {
                type: 'tel',
                id: 'snippe-phone-number',
                name: 'snippe_phone_number',
                placeholder: '255781000000',
                className: 'wc-block-components-text-input__input',
                required: true
            }),
            createElement('small', null, 'Enter your mobile money phone number (e.g., 255781000000)')
        )
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
