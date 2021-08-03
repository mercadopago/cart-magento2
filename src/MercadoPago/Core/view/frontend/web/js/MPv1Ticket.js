var mercadoPagoDocNumber = 'CPF';

validateDocumentInputs = function (siteId) {
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
};
