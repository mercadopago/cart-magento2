(function () {

  window.cvvLength = null;
  window.additionalInfoNeeded = {};

  var mp = null;
  var mpCardForm = null;
  var mpRemountCardForm = false;

  window.initCardForm = function (pk, quote, processMode, country, customMethod) {
    mp = new MercadoPago(pk);

    // Instance SDK v2
    mpCardForm = mp.cardForm({
      amount: String(quote.totals().base_grand_total),
      autoMount: true,
      processingMode: processMode,
      form: {
        id: 'co-mercadopago-form',
        cardNumber: { id: 'mpCardNumber' },
        cardholderName: { id: 'mpCardholderName' },
        cardExpirationMonth: { id: 'mpCardExpirationMonth' },
        cardExpirationYear: { id: 'mpCardExpirationYear' },
        securityCode: { id: 'mpSecurityCode' },
        installments: { id: 'mpInstallments' },
        identificationType: { id: 'mpDocType' },
        identificationNumber: { id: 'mpDocNumber' },
        issuer: { id: 'mpIssuer' }
      },
      callbacks: {
        onFormMounted: error => {
          if (error) return console.warn('FormMounted handling error: ', error);
        },
        onFormUnmounted: error => {
          if (error) return console.warn('FormMounted handling error: ', error);
          fullClearInputs()

          if (mpRemountCardForm) {
            initCardForm(pk, quote, processMode, country, customMethod);
            mpRemountCardForm = false;
          } else {
            setTimeout(() => {
              initCardForm(pk, quote, processMode, country, customMethod)
            }, 5000);
          }
        },
        onIdentificationTypesReceived: (error, identificationTypes) => {
          if (error) return console.warn('IdentificationTypes handling error: ', error);
        },
        onInstallmentsReceived: (error, installments) => {
          if (error) {
            return console.warn('Installments handling error: ', error)
          }

          setChangeEventOnInstallments(country, installments.payer_costs);
        },
        onCardTokenReceived: (error, token) => {
          if (error) {
            showErrors(error);
            return console.warn('Token handling error: ', error);
          }

          customMethod.placeOrder();
        },
        onPaymentMethodsReceived: (error, paymentMethods) => {
          clearInputs();

          if (error) {
            return console.warn('PaymentMethods handling error: ', error);
          }

          setImageCard(paymentMethods[0].thumbnail);
          setCvvLength(paymentMethods[0].settings[0].security_code.length);
          handleInstallments(paymentMethods[0].payment_type_id);
          loadAdditionalInfo(paymentMethods[0].additional_info_needed);
          additionalInfoHandler();
        },
      },
    });
  }

  window.mpRemountCardForm = function () {
    mpRemountCardForm = true;
    mpCardForm.unmount();
  }

  window.mpDeleteCardForm = function () {
    mpCardForm.unmount()
  }

  window.getMpCardFormData = function () {
    return mpCardForm.getCardFormData();
  }

  window.mpCreateCardToken = function () {
    mpCardForm.createCardToken();
  }

  window.getFormCustom = function () {
    return document.querySelector('#co-mercadopago-form');
  }

  window.setChangeEventOnCardNumber = function () {
    document.getElementById('mpCardNumber').addEventListener('keyup', function (e) {
      if (e.target.value.length <= 4) {
        clearInputs();
      }
    });
  }

  window.setChangeEventExpirationDate = function () {
    document.getElementById('mpCardExpirationDate').addEventListener('change', function (e) {
      var card_expiration_date = document.getElementById('mpCardExpirationDate').value;
      var card_expiration_month = card_expiration_date.split('/')[0] | '';
      var card_expiration_year = card_expiration_date.split('/')[1] | '';

      document.getElementById('mpCardExpirationMonth').value = ('0' + card_expiration_month).slice(-2);
      document.getElementById('mpCardExpirationYear').value = card_expiration_year;
    });
  }

  window.setChangeEventOnInstallments = function (siteId, payer_costs) {
    if (siteId === 'MLA') {
      document.querySelector('#mpInstallments').addEventListener('change', function (e) {
        showTaxes(payer_costs);
      });
    }
  }

  window.setImageCard = function (secureThumbnail) {
    var mpCardNumber = document.getElementById('mpCardNumber');
    mpCardNumber.style.background = 'url(' + secureThumbnail + ') 98% 50% no-repeat #fff';
    mpCardNumber.style.backgroundSize = 'auto 24px';
  }

  window.setCvvLength = function (length) {
    cvvLength = length;
  }

  window.loadAdditionalInfo = function (sdkAdditionalInfoNeeded) {
    additionalInfoNeeded = {
      issuer: false,
      cardholder_name: false,
      cardholder_identification_type: false,
      cardholder_identification_number: false
    };

    for (var i = 0; i < sdkAdditionalInfoNeeded.length; i++) {
      if (sdkAdditionalInfoNeeded[i] === 'issuer_id') {
        additionalInfoNeeded.issuer = true;
      }
      if (sdkAdditionalInfoNeeded[i] === 'cardholder_name') {
        additionalInfoNeeded.cardholder_name = true;
      }
      if (sdkAdditionalInfoNeeded[i] === 'cardholder_identification_type') {
        additionalInfoNeeded.cardholder_identification_type = true;
      }
      if (sdkAdditionalInfoNeeded[i] === 'cardholder_identification_number') {
        additionalInfoNeeded.cardholder_identification_number = true;
      }
    }
  }

  window.additionalInfoHandler = function () {
    if (additionalInfoNeeded.cardholder_name) {
      document.getElementById('mp-card-holder-div').style.display = 'block';
    } else {
      document.getElementById('mp-card-holder-div').style.display = 'none';
    }

    if (additionalInfoNeeded.issuer) {
      document.getElementById('mp-issuer-div').style.display = 'block';
    } else {
      document.getElementById('mp-issuer-div').style.display = 'none';
    }

    if (additionalInfoNeeded.cardholder_identification_type) {
      document.getElementById('mp-doc-type-div').style.display = 'block';
    } else {
      document.getElementById('mp-doc-type-div').style.display = 'none';
    }

    if (additionalInfoNeeded.cardholder_identification_number) {
      document.getElementById('mp-doc-number-div').style.display = 'block';
    } else {
      document.getElementById('mp-doc-number-div').style.display = 'none';
    }

    if (additionalInfoNeeded.cardholder_identification_type && additionalInfoNeeded.cardholder_identification_number) {
      document.getElementById('mp-doc-div').style.display = 'block';
    } else {
      document.getElementById('mp-doc-div').style.display = 'none';
    }
  }

  window.clearInputs = function () {
    hideErrors();
    document.getElementById('mpCardNumber').style.background = 'no-repeat #fff';
    document.getElementById('mpCardExpirationDate').value = '';
    document.getElementById('mpDocNumber').value = '';
    document.getElementById('mpSecurityCode').value = '';
    document.getElementById('mpCardholderName').value = '';
  }

  window.fullClearInputs = function () {
    clearInputs()
    document.getElementById('mpCardNumber').value = '';
    document.getElementById("mpInstallments").value = '';
    document.getElementById("mpInstallments").innerHTML = '';
  }

  window.validateFixedInputs = function () {
    var emptyInputs = false;
    var form = this.getFormCustom();
    var formInputs = form.querySelectorAll('[data-checkout]');
    var fixedInputs = [
      'mpCardNumber',
      'mpCardExpirationDate',
      'mpSecurityCode',
      'mpInstallments'
    ];

    for (var x = 0; x < formInputs.length; x++) {
      var element = formInputs[x];

      if (fixedInputs.indexOf(element.getAttribute('data-checkout')) > -1) {
        if (element.value === -1 || element.value === '') {
          var small = form.querySelectorAll('small[data-main="#' + element.id + '"]');

          if (small.length > 0) {
            small[0].style.display = 'block';
          }

          element.classList.add('mp-form-control-error');
          emptyInputs = true;
        }
      }
    }

    return emptyInputs;
  }

  window.validateAdditionalInputs = function () {
    var emptyInputs = false;

    if (additionalInfoNeeded.issuer) {
      var inputMpIssuer = document.getElementById('mpIssuer');
      if (inputMpIssuer.value === '-1' || inputMpIssuer.value === '') {
        inputMpIssuer.classList.add('mp-form-control-error');
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_name) {
      var inputCardholderName = document.getElementById('mpCardholderName');
      if (inputCardholderName.value === '-1' || inputCardholderName.value === '') {
        inputCardholderName.classList.add('mp-form-control-error');
        document.getElementById('mp-error-221').style.display = 'block';
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_identification_type) {
      var inputDocType = document.getElementById('mpDocType');
      if (inputDocType.value === '-1' || inputDocType.value === '') {
        inputDocType.classList.add('mp-form-control-error');
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_identification_number) {
      var docNumber = document.getElementById('mpDocNumber');
      if (docNumber.value === '-1' || docNumber.value === '' || ! /^[a-zA-Z0-9]+$/.test(docNumber.value)) {
        docNumber.classList.add('mp-form-control-error');
        document.getElementById('mp-error-324').style.display = 'block';
        emptyInputs = true;
      }
    }

    return emptyInputs;
  }

  window.validateCvv = function () {
    var span = getFormCustom().querySelectorAll('small[data-main="#mpSecurityCode"]');
    var cvvInput = document.getElementById('mpSecurityCode');
    var cvvValidation = cvvLength === cvvInput.value.length;

    if (!cvvValidation) {
      span[0].style.display = 'block';
      cvvInput.classList.add('mp-form-control-error');
      cvvInput.focus();
    }

    return cvvValidation;
  }

  window.showErrors = function (error) {
    var form = this.getFormCustom();
    var serializedError = error.cause || error;

    for (var x = 0; x < serializedError.length; x++) {
      var code = serializedError[x].code;
      var span = undefined;

      span = form.querySelector('#mp-error-' + code);

      if (span !== undefined) {
        span.style.display = 'block';
        form.querySelector(span.getAttribute('data-main')).classList.add('mp-form-control-error');
      }
    }

    focusInputError();
  }

  window.focusInputError = function () {
    if (document.querySelectorAll('.mp-form-control-error') !== undefined) {
      var formInputs = document.querySelectorAll('.mp-form-control-error');
      formInputs[0].focus();
    }
  }

  window.hideErrors = function () {
    for (var x = 0; x < document.querySelectorAll('[data-checkout]').length; x++) {
      var field = document.querySelectorAll('[data-checkout]')[x];
      field.classList.remove('mp-form-control-error');
    }

    for (var y = 0; y < document.querySelectorAll('.mp-form-error').length; y++) {
      var small = document.querySelectorAll('.mp-form-error')[y];
      small.style.display = 'none';
    }
  }

  window.handleInstallments = function (payment_type_id) {
    if (payment_type_id === 'debit_card') {
      document.getElementById('mpInstallments').setAttribute("disabled", "disabled");
    } else {
      document.getElementById('mpInstallments').removeAttribute("disabled");
    }
  }

  /**
   * Show taxes resolution 51/2017 for MLA
   */
  window.showTaxes = function (payer_costs) {
    var installmentsSelect = document.querySelector('#mpInstallments');

    for (var i = 0; i < payer_costs.length; i++) {
      if (payer_costs[i].installments === installmentsSelect.value) {
        var taxes_split = payer_costs[i].labels[0].split('|');
        var cft = taxes_split[0].replace('_', ' ');
        var tea = taxes_split[1].replace('_', ' ');

        if (cft === 'CFT 0,00%' && tea === 'TEA 0,00%') {
          cft = '';
          tea = '';
        }

        document.querySelector('#mp-tax-cft-text').innerHTML = cft;
        document.querySelector('#mp-tax-tea-text').innerHTML = tea;
      }
    }
  }
}).call(this);
