function getSelectedCurrency() {

    const currencySelect = document.getElementById('currency_id');

    const currencyId = currencySelect.value;

    const currency = window.currencies.find(
        c => Number(c.id) === Number(currencyId)
    );

    // Default currency = EGP
    if (!currency) {
        return {
            code: 'EGP',
            symbol: 'ج.م',
            exchange_rate: 1
        };
    }

    return currency;
}


// Convert amount from EGP to selected currency
function convertFromEGP(amount) {

    const currency = getSelectedCurrency();

    return amount / Number(currency.exchange_rate);
}


function calculateTotal() {

    // =====================================================
    // Get number of people
    // =====================================================

    const adults =
        parseInt(
            document.querySelector('[name="adults"]').value
        ) || 0;

    const children =
        parseInt(
            document.querySelector('[name="children"]').value
        ) || 0;

    const infants =
        parseInt(
            document.querySelector('[name="info"]').value
        ) || 0;


    // =====================================================
    // Get prices from database
    // All prices are EGP
    // =====================================================

    const adultPrice =
        Number(window.bookingPrices?.adult) || 0;

    const childPrice =
        Number(window.bookingPrices?.child) || 0;

    const infantPrice =
        Number(window.bookingPrices?.infant) || 0;


    // =====================================================
    // Calculate Price
    // =====================================================

    const adultsTotal =
        adults * adultPrice;

    const childrenTotal =
        children * childPrice;

    const infantsTotal =
        infants * infantPrice;


    // Price = Adults + Children + Infants
    const priceEGP =
        adultsTotal +
        childrenTotal +
        infantsTotal;


    // =====================================================
    // Calculate Extras
    // =====================================================

    let extraEGP = 0;

    document
        .querySelectorAll('.extra-checkbox:checked')
        .forEach(checkbox => {

            const extraPrice =
                Number(checkbox.dataset.extra) || 0;

            extraEGP += extraPrice;

        });


    // =====================================================
    // Discount
    // =====================================================

    const discountEGP =
        parseFloat(
            document.getElementById('discount').value
        ) || 0;

    const taxRate =
        parseFloat(
            document.getElementById('rate_tax').value
        ) || 0;


    // =====================================================
    // Total
    // =====================================================

    const beforeTaxEGP =
        Math.max(0, priceEGP + extraEGP - discountEGP);

    const taxEGP =
        beforeTaxEGP * taxRate / 100;

    const totalEGP =
        beforeTaxEGP + taxEGP;


    // =====================================================
    // Convert to selected currency
    // =====================================================

    const convertedPrice =
        convertFromEGP(priceEGP);

    const convertedExtra =
        convertFromEGP(extraEGP);

    const convertedDiscount =
        convertFromEGP(discountEGP);

    const convertedTotal =
        convertFromEGP(totalEGP);


    // =====================================================
    // Update Price
    // =====================================================

    document.getElementById('price').value =
        convertedPrice.toFixed(2);


    // =====================================================
    // Update Extra
    // =====================================================

    document.getElementById('extra').value =
        convertedExtra.toFixed(2);


    // =====================================================
    // Update Total
    // =====================================================

    const currency =
        getSelectedCurrency();

    document.getElementById('total').innerText =
        `${convertedTotal.toFixed(2)} ${currency.symbol}`;
}


// =========================================================
// DOM Loaded
// =========================================================

document.addEventListener('DOMContentLoaded', () => {


    // =====================================================
    // Inputs
    // =====================================================

    const adultsInput =
        document.querySelector('[name="adults"]');

    const childrenInput =
        document.querySelector('[name="children"]');

    const infantsInput =
        document.querySelector('[name="info"]');

    const discountInput =
        document.getElementById('discount');

    const currencySelect =
        document.getElementById('currency_id');

    const paymentMethodSelect =
        document.getElementById('method_payment');

    const companySelect =
        document.getElementById('company_id');

    const companyError =
        document.getElementById('company-error');

    const paidWrapper =
        document.getElementById('paid-wrapper');

    const paidInput =
        document.getElementById('paid');

    function updatePaidVisibility() {
        const isCash = paymentMethodSelect.value === 'cash';

        paidWrapper.hidden = !isCash;
        paidInput.disabled = !isCash;

        if (!isCash) {
            paidInput.value = 0;
        }
    }


    // =====================================================
    // Adults
    // =====================================================

    adultsInput.addEventListener(
        'input',
        calculateTotal
    );


    // =====================================================
    // Children
    // =====================================================

    childrenInput.addEventListener(
        'input',
        calculateTotal
    );


    // =====================================================
    // Infants / Info
    // =====================================================

    infantsInput.addEventListener(
        'input',
        calculateTotal
    );


    // =====================================================
    // Discount
    // =====================================================

    discountInput.addEventListener(
        'input',
        calculateTotal
    );

    document.getElementById('rate_tax').addEventListener(
        'input',
        calculateTotal
    );


    // =====================================================
    // Currency
    // =====================================================

    currencySelect.addEventListener(
        'change',
        calculateTotal
    );

    paymentMethodSelect.addEventListener(
        'change',
        updatePaidVisibility
    );

    companySelect.addEventListener('change', async () => {
        const selectedCompanyId = companySelect.value;
        const previousCompanyId = companySelect.dataset.previousValue || selectedCompanyId;

        companySelect.disabled = true;
        companyError.classList.add('hidden');

        try {
            const response = await fetch(window.companyPricesUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ company_id: selectedCompanyId }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Unable to load company prices.');
            }

            window.bookingPrices = data.prices;
            companySelect.dataset.previousValue = data.company_id;
            calculateTotal();
        } catch (error) {
            companySelect.value = previousCompanyId;
            companyError.textContent = error.message;
            companyError.classList.remove('hidden');
        } finally {
            companySelect.disabled = false;
        }
    });

    companySelect.dataset.previousValue = companySelect.value;


    // =====================================================
    // Extras
    // =====================================================

    document
        .querySelectorAll('.extra-checkbox')
        .forEach(checkbox => {

            checkbox.addEventListener(
                'change',
                calculateTotal
            );

        });


    // =====================================================
    // Hotel / Pickup Time
    // =====================================================

    const hotelInput =
        document.getElementById('hotel_name');

    const hotelIdInput =
        document.getElementById('hotel_id');

    const pickTime =
        document.getElementById('pick_time');

    const hotelOptions =
        document.querySelectorAll('#hotel-options option');

    function updateHotelSelection() {
        const selectedHotel = Array.from(hotelOptions).find(
            option => option.value === hotelInput.value
        );

        hotelIdInput.value = selectedHotel?.dataset.hotelId || '';
        pickTime.value = selectedHotel?.dataset.pickupTime?.slice(0, 5) || '';
    }

    hotelInput.addEventListener('input', updateHotelSelection);


    // =====================================================
    // First calculation
    // =====================================================

    calculateTotal();
    updatePaidVisibility();

});
   document.getElementById('enableExtras').addEventListener('change', function () {

        const extrasContainer = document.getElementById('extrasContainer');

        if (this.checked) {
            extrasContainer.classList.remove('hidden');
        } else {
            extrasContainer.classList.add('hidden');

            // إلغاء اختيار أي Extra عند إخفائها
            document.querySelectorAll('.extra-checkbox').forEach(function (checkbox) {
                checkbox.checked = false;
            });
        }

    });
