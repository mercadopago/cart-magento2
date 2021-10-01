(function () {

  window.mercadoPagoDocNumber = 'CPF';

  window.getFormTicket = function () {
    return document.getElementById('co-mercadopago-form-ticket');
  }

  window.validateDocumentInputs = function (siteId) {
    if (siteId === 'MLB') {
      var mpDocType = document.querySelectorAll('input[type=radio][name="mercadopago_custom_ticket[doc-type]"]');
      var mpBoxFirstname = document.getElementById('mp_box_firstname');
      var mpBoxLastname = document.getElementById('mp_box_lastname');
      var mpFirstnameLabel = document.getElementById('mp_firstname_label');
      var mpSocialnameLabel = document.getElementById('mp_socialname_label');
      var mpCpfLabel = document.getElementById('mp_cpf_label');
      var mpCnpjLabel = document.getElementById('mp_cnpj_label');
      var mpDocNumber = document.getElementById('mp_doc_number');

      mpCnpjLabel.style.display = 'none';
      mpSocialnameLabel.style.display = 'none';

      for (var i = 0; i < mpDocType.length; i++) {
        mpDocType[i].addEventListener('change', function () {
          if (this.value === 'CPF') {
            mpCpfLabel.style.display = 'block';
            mpBoxLastname.style.display = 'block';
            mpFirstnameLabel.style.display = 'block';
            mpCnpjLabel.style.display = 'none';
            mpSocialnameLabel.style.display = 'none';
            mpBoxFirstname.classList.add('form-col-6');
            mpBoxFirstname.classList.remove('form-col-12');
            mpDocNumber.setAttribute('maxlength', '14');
            mpDocNumber.setAttribute('onkeyup', 'maskInput(this, mcpf)');
            mercadoPagoDocNumber = 'CPF';
          } else {
            mpCpfLabel.style.display = 'none';
            mpBoxLastname.style.display = 'none';
            mpFirstnameLabel.style.display = 'none';
            mpCnpjLabel.style.display = 'block';
            mpSocialnameLabel.style.display = 'block';
            mpBoxFirstname.classList.add('form-col-12');
            mpBoxFirstname.classList.remove('form-col-6');
            mpDocNumber.setAttribute('maxlength', '18');
            mpDocNumber.setAttribute('onkeyup', 'maskInput(this, mcnpj)');
            mercadoPagoDocNumber = 'CNPJ';
          }
        });
      }
    }
  }

  window.mercadoPagoFormHandlerTicket = function (siteId) {
    if (siteId === 'MLB') {
      var inputs = validateInputs();
      var documentNumber = validateDocumentNumber();

      return inputs && documentNumber;
    }

    return true;
  }

  window.validateInputs = function () {
    var form = getFormTicket();
    var formInputs = form.querySelectorAll('[data-checkout]');
    var small = form.querySelectorAll('.mp-form-error');

    for (var i = 0; i < formInputs.length; i++) {
      var element = formInputs[i];
      var input = form.querySelector(small[i].getAttribute('data-main'));

      if (element.parentNode.style.display !== 'none' && (element.value === -1 || element.value === '')) {
        small[i].style.display = 'inline-block';
        input.classList.add('mp-form-control-error');
      } else {
        small[i].style.display = 'none';
        input.classList.remove('mp-form-control-error');
      }
    }

    for (var j = 0; j < formInputs.length; j++) {
      var focusElement = formInputs[j];
      if (focusElement.parentNode.style.display !== 'none' && (focusElement.value === -1 || focusElement.value === '')) {
        focusElement.focus();
        return false;
      }
    }

    return true;
  }

  window.validateDocumentNumber = function () {
    var docnumberInput = document.getElementById('mp_doc_number');
    var docnumberError = document.getElementById('mp_error_doc_number');
    var docnumberValidate = validateDocTypeMLB(docnumberInput.value);

    if (!docnumberValidate) {
      docnumberError.style.display = 'block';
      docnumberInput.classList.add('mp-form-control-error');
      docnumberInput.focus();
    } else {
      docnumberError.style.display = 'none';
      docnumberInput.classList.remove('mp-form-control-error');
      docnumberValidate = true;
    }

    return docnumberValidate;
  }

  window.validateDocTypeMLB = function (docnumber) {
    if (mercadoPagoDocNumber === 'CPF') {
      return validateCPF(docnumber);
    } else {
      return validateCNPJ(docnumber);
    }
  }

  window.validateCPF = function (strCPF) {
    var Soma;
    var Resto;

    Soma = 0;
    strCPF = strCPF.replace(/[.-\s]/g, '');

    if (strCPF === '00000000000' ||
      strCPF === '11111111111' ||
      strCPF === '22222222222' ||
      strCPF === '33333333333' ||
      strCPF === '44444444444' ||
      strCPF === '55555555555' ||
      strCPF === '66666666666' ||
      strCPF === '77777777777' ||
      strCPF === '88888888888' ||
      strCPF === '99999999999') {
      return false;
    }

    for (var i = 1; i <= 9; i++) {
      Soma = Soma + parseInt(strCPF.substring(i - 1, i)) * (11 - i);
    }

    Resto = (Soma * 10) % 11;
    if ((Resto === 10) || (Resto === 11)) {
      Resto = 0;
    }
    if (Resto !== parseInt(strCPF.substring(9, 10))) {
      return false;
    }

    Soma = 0;
    for (var k = 1; k <= 10; k++) {
      Soma = Soma + parseInt(strCPF.substring(k - 1, k)) * (12 - k);
    }

    Resto = (Soma * 10) % 11;
    if ((Resto === 10) || (Resto === 11)) {
      Resto = 0;
    }

    return Resto === parseInt(strCPF.substring(10, 11));
  }

  window.validateCNPJ = function (strCNPJ) {
    strCNPJ = strCNPJ.replace(/[^\d]+/g, '');

    if (strCNPJ === '') {
      return false;
    }

    if (strCNPJ.length !== 14) {
      return false;
    }

    if (strCNPJ === '00000000000000' ||
      strCNPJ === '11111111111111' ||
      strCNPJ === '22222222222222' ||
      strCNPJ === '33333333333333' ||
      strCNPJ === '44444444444444' ||
      strCNPJ === '55555555555555' ||
      strCNPJ === '66666666666666' ||
      strCNPJ === '77777777777777' ||
      strCNPJ === '88888888888888' ||
      strCNPJ === '99999999999999') {
      return false;
    }

    var tamanho = strCNPJ.length - 2;
    var numeros = strCNPJ.substring(0, tamanho);
    var digitos = strCNPJ.substring(tamanho);
    var soma = 0;
    var pos = tamanho - 7;
    for (var i = tamanho; i >= 1; i--) {
      soma += numeros.charAt(tamanho - i) * pos--;
      if (pos < 2) {
        pos = 9;
      }
    }

    var resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;

    if (resultado.toString() !== digitos.charAt(0)) {
      return false;
    }

    tamanho = tamanho + 1;
    numeros = strCNPJ.substring(0, tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (i = tamanho; i >= 1; i--) {
      soma += numeros.charAt(tamanho - i) * pos--;
      if (pos < 2) {
        pos = 9;
      }
    }
    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;

    return resultado.toString() === digitos.charAt(1);
  }
}).call(this);