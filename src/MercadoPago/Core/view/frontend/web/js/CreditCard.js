var additionalInfoNeeded = {};

function getFormCustom() {
  return document.querySelector('#co-mercadopago-form');
}

function setImageCard(secureThumbnail) {
  document.getElementById('mpCardNumber').style.background = 'url(' + secureThumbnail + ') 98% 50% no-repeat #fff';
}

function loadAdditionalInfo(sdkAdditionalInfoNeeded) {
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

function additionalInfoHandler() {
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

  if (!additionalInfoNeeded.cardholder_identification_type && !additionalInfoNeeded.cardholder_identification_number) {
    document.getElementById('mp-doc-div').style.display = 'none';
  }
}

function clearInputs() {
  hideErrors();
  document.getElementById('mpCardNumber').style.background = 'no-repeat #fff';
  document.getElementById('mpCardExpirationMonth').value = '';
  document.getElementById('mpCardExpirationMonthSelect').value = '';
  document.getElementById('mpCardExpirationYear').value = '';
  document.getElementById('mpCardExpirationYearSelect').value = '';
  document.getElementById('mpDocNumber').value = '';
  document.getElementById('mpSecurityCode').value = '';
  document.getElementById('mpCardholderName').value = '';
}

function validateFixedInputs() {
  var emptyInputs = false;
  var form = this.getFormCustom();
  var formInputs = form.querySelectorAll('[data-checkout]');
  var fixedInputs = [
    'mpCardNumber',
    'mpCardExpirationMonthSelect',
    'mpCardExpirationYearSelect',
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

function validateAdditionalInputs() {
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
    if (docNumber.value === '-1' || docNumber.value === '') {
      docNumber.classList.add('mp-form-control-error');
      document.getElementById('mp-error-324').style.display = 'block';
      emptyInputs = true;
    }
  }

  return emptyInputs;
}

function showErrors(errors) {
  var form = this.getFormCustom();

  for (var x = 0; x < errors.length; x++) {
    var code = errors[x].code;
    var span = undefined;

    span = form.querySelector('#mp-error-' + code);

    if (span !== undefined) {
      var input = form.querySelector(span.getAttribute('data-main'));
      span.style.display = 'block';
      input.classList.add('mp-form-control-error');
    }
  }
}

function focusInputError() {
  if (document.querySelectorAll('.mp-form-control-error') !== undefined) {
    var formInputs = document.querySelectorAll('.mp-form-control-error');
    formInputs[0].focus();
  }
}

function hideErrors() {
  for (var x = 0; x < document.querySelectorAll('[data-checkout]').length; x++) {
    var field = document.querySelectorAll('[data-checkout]')[x];
    field.classList.remove('mp-form-control-error');
  }

  for (var y = 0; y < document.querySelectorAll('.mp-form-error').length; y++) {
    var small = document.querySelectorAll('.mp-form-error')[y];
    small.style.display = 'none';
  }
}
