(function () {
  window.cvvLength = null;
  window.additionalInfoNeeded = {};

  var mp = null;
  var mpCardForm = null;
  var mpRemountCardForm = false;

  window.initCardForm = function (
    pk,
    quote,
    processMode,
    country,
    customMethod
  ) {
    mp = new MercadoPago(pk);

    // Instance SDK v2
    mpCardForm = mp.cardForm({
      amount: String(quote.totals().base_grand_total),
      autoMount: true,
      processingMode: processMode,
      iframe: true,
      form: {
        id: "co-mercadopago-form",
        cardNumber: {
          id: "mpCardNumber",
          placeholder: "0000 0000 0000 0000",
          style: {
            "font-size": "14px",
            "font-family": "Helvetica Neue",
          },
        },
        cardholderName: {
          id: "mpCardholderName",
        },
        cardExpirationDate: {
          id: "mpCardExpirationDate",
          placeholder: "MM/YY",
          mode: "short",
          style: {
            "font-size": "14px",
            "font-family": "Helvetica Neue",
          },
        },
        securityCode: {
          id: "mpSecurityCode",
          placeholder: "CVC",
          style: {
            "font-size": "14px",
            "font-family": "Helvetica Neue",
          },
        },
        installments: {
          id: "mpInstallments",
        },
        identificationType: {
          id: "mpDocType",
        },
        identificationNumber: {
          id: "mpDocNumber",
        },
        issuer: {
          id: "mpIssuer",
        },
      },
      callbacks: {
        onFormMounted: (error) => {
          if (error) return console.warn("FormMounted handling error: ", error);
          additionalInfoHandler();
        },
        onFormUnmounted: (error) => {
          fullClearInputs();

          if (error) return console.warn("FormMounted handling error: ", error);

          if (mpRemountCardForm) {
            initCardForm(pk, quote, processMode, country, customMethod);
            mpRemountCardForm = false;
          } else {
            setTimeout(() => {
              initCardForm(pk, quote, processMode, country, customMethod);
            }, 5000);
          }
        },
        onIdentificationTypesReceived: (error) => {
          if (error)
            return console.warn("IdentificationTypes handling error: ", error);
        },
        onInstallmentsReceived: (error, installments) => {
          if (error) {
            return console.warn("Installments handling error: ", error);
          }

          setChangeEventOnInstallments(country, installments.payer_costs);
        },
        onCardTokenReceived: (error, token) => {
          if (error) {
            showErrors(error);
            return console.warn("Token handling error: ", error);
          }

          customMethod.placeOrder();
        },
        onPaymentMethodsReceived: (error, paymentMethods) => {
          clearInputs();
          if (error) {
            return console.warn("PaymentMethods handling error: ", error);
          }

          setImageCard(paymentMethods[0].thumbnail);
          handleInstallments(paymentMethods[0].payment_type_id);
          loadAdditionalInfo(paymentMethods[0].additional_info_needed);
          additionalInfoHandler();
        },
        onValidityChange: function (error, field) {
          if (error) {
            console.log("errrroor", error);
            if (field == "cardNumber") {
              if (error[0].code !== "invalid_length") {
                document.querySelector("#mpCardNumber", "no-repeat #fff");
                fullClearInputs();
                hideErrors();
                mpCardForm.unmount();
                mpCardForm.mount();
              }
            }
          }
        },
      },
    });
  };

  window.showErrors = function (errors) {
    var form = this.getFormCustom();
    console.log("show errors", errors);
    var sdkErrors = trackedSDKErrors();
    console.log("tracked errors", trackedSDKErrors());
    showIframeErrors(errors);

    //used to exhibt only one message
    var previousField = undefined;

    errors.forEach((error) => {
      if (error.field && previousField !== error.field) {
        errorField = error.field;

        if (error.field === "expirationDate") {
          errorField = expirationDateHandler(error);
        }

        let formatedError = `${error.cause}_${errorField}`;

        var span = undefined;

        sdkErrors.forEach((sdkError) => {
          if (error.message === sdkError.message) {
            span = form.querySelector(`#${formatedError}_${sdkError.code}`);

            if (span !== undefined) {
              span.style.display = "block";
            }
          }

          previousField = error.field;
        });
      }
      focusInputError();
    });
  };

  window.expirationDateHandler = function (error) {
    expiration = error.message.includes("expirationMonth")
      ? `${error.field}_expirationMonth`
      : `${error.field}_expirationYear`;
    return expiration;
  };

  window.mpRemountCardForm = function () {
    mpRemountCardForm = true;
    mpCardForm.unmount();
  };

  window.mpDeleteCardForm = function () {
    mpCardForm.unmount();
  };

  window.getMpCardFormData = function () {
    return mpCardForm.getCardFormData();
  };

  window.mpCreateCardToken = function () {
    mpCardForm.createCardToken();
  };

  window.getFormCustom = function () {
    return document.querySelector("#co-mercadopago-form");
  };

  window.setChangeEventOnInstallments = function (siteId, payer_costs) {
    if (siteId === "MLA") {
      document
        .querySelector("#mpInstallments")
        .addEventListener("change", function (e) {
          showTaxes(payer_costs);
        });
    }
  };
  window.setImageCard = function (secureThumbnail) {
    var mpCardNumber = document.getElementById("mpCardNumber");
    mpCardNumber.style.background =
      "url(" + secureThumbnail + ") 98% 50% no-repeat #fff";
    mpCardNumber.style.backgroundSize = "auto 24px";
  };

  window.loadAdditionalInfo = function (sdkAdditionalInfoNeeded) {
    additionalInfoNeeded = {
      issuer: false,
      cardholder_name: false,
      cardholder_identification_type: false,
      cardholder_identification_number: false,
    };
    for (var i = 0; i < sdkAdditionalInfoNeeded.length; i++) {
      if (sdkAdditionalInfoNeeded[i] === "issuer_id") {
        additionalInfoNeeded.issuer = true;
      }
      if (sdkAdditionalInfoNeeded[i] === "cardholder_name") {
        additionalInfoNeeded.cardholder_name = true;
      }
      if (sdkAdditionalInfoNeeded[i] === "cardholder_identification_type") {
        additionalInfoNeeded.cardholder_identification_type = true;
      }
      if (sdkAdditionalInfoNeeded[i] === "cardholder_identification_number") {
        additionalInfoNeeded.cardholder_identification_number = true;
      }
    }
  };

  window.additionalInfoHandler = function () {
    if (additionalInfoNeeded.cardholder_name) {
      document.getElementById("mp-card-holder-div").style.display = "block";
    } else {
      document.getElementById("mp-card-holder-div").style.display = "none";
    }

    if (additionalInfoNeeded.issuer) {
      document.getElementById("mp-issuer-div").style.display = "block";
    } else {
      document.getElementById("mp-issuer-div").style.display = "none";
    }

    if (additionalInfoNeeded.cardholder_identification_type) {
      document.getElementById("mp-doc-type-div").style.display = "block";
    } else {
      document.getElementById("mp-doc-type-div").style.display = "none";
    }

    if (additionalInfoNeeded.cardholder_identification_number) {
      document.getElementById("mp-doc-number-div").style.display = "block";
    } else {
      document.getElementById("mp-doc-number-div").style.display = "none";
    }

    if (
      additionalInfoNeeded.cardholder_identification_type &&
      additionalInfoNeeded.cardholder_identification_number
    ) {
      document.getElementById("mp-doc-div").style.display = "block";
    } else {
      document.getElementById("mp-doc-div").style.display = "none";
    }
  };

  window.clearInputs = function () {
    hideErrors();
    document.querySelector("#mpDocNumber").value = "";
    document.querySelector("#mpCardholderName").value = "";
  };

  window.fullClearInputs = function () {
    clearInputs();
    setImageCard("");
    document.getElementById("mpInstallments").value = "";
    document.getElementById("mpInstallments").innerHTML = "";
  };

  window.validateFixedInputs = function () {
    mpCardForm.createCardToken(); //to activate callback onCardTokenReceived and verify error ocurrencies

    var emptyInputs = false;
    var form = this.getFormCustom();

    var formInputs = form.querySelectorAll("[data-checkout]");

    var fixedInputs = ["mpInstallments"];

    for (var x = 0; x < formInputs.length; x++) {
      var element = formInputs[x];

      if (fixedInputs.indexOf(element.getAttribute("data-checkout")) > -1) {
        if (
          element.value === -1 ||
          element.value === "" ||
          element.value === undefined
        ) {
          var small = form.querySelectorAll(
            'small[data-main="#' + element.id + '"]'
          );

          small[0].style.display = "block";
          element.classList.add("mp-form-control-error");
          emptyInputs = true;
        }
      }
    }
    return emptyInputs;
  };

  window.validateAdditionalInputs = function () {
    var emptyInputs = false;

    if (additionalInfoNeeded.issuer) {
      var inputMpIssuer = document.getElementById("mpIssuer");
      if (
        inputMpIssuer.value === "-1" ||
        inputMpIssuer.value === "" ||
        inputMpIssuer.value === undefined
      ) {
        inputMpIssuer.classList.add("mp-form-control-error");
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_name) {
      var inputCardholderName = document.getElementById("mpCardholderName");
      if (
        inputCardholderName.value === "-1" ||
        inputCardholderName.value === ""
      ) {
        inputCardholderName.classList.add("mp-form-control-error");
        document.getElementById(
          "mp-error-empty-cardholder-name"
        ).style.display = "block";
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_identification_type) {
      var inputDocType = document.getElementById("mpDocType");
      if (inputDocType.value === "-1" || inputDocType.value === "") {
        inputDocType.classList.add("mp-form-control-error");
        emptyInputs = true;
      }
    }

    if (additionalInfoNeeded.cardholder_identification_number) {
      var docNumber = document.getElementById("mpDocNumber");
      if (
        docNumber.value === "-1" ||
        docNumber.value === "" ||
        !/^[a-zA-Z0-9]+$/.test(docNumber.value)
      ) {
        docNumber.classList.add("mp-form-control-error");
        document.getElementById("mp-error-empty-doc-number").style.display =
          "block";
        emptyInputs = true;
      }
    }
    return emptyInputs;
  };

  window.focusInputError = function () {
    if (document.querySelectorAll(".mp-form-control-error") !== undefined) {
      var formInputs = document.querySelectorAll(".mp-form-control-error");
      formInputs[0].focus();
    }
  };

  window.hideErrors = function () {
    for (
      var x = 0;
      x < document.querySelectorAll("[data-checkout]").length;
      x++
    ) {
      var field = document.querySelectorAll("[data-checkout]")[x];
      field.classList.remove("mp-form-control-error");
    }

    for (
      var y = 0;
      y < document.querySelectorAll(".mp-form-error").length;
      y++
    ) {
      var small = document.querySelectorAll(".mp-form-error")[y];
      small.style.display = "none";
    }
  };

  window.handleInstallments = function (payment_type_id) {
    if (payment_type_id === "debit_card") {
      document
        .getElementById("mpInstallments")
        .setAttribute("disabled", "disabled");
    } else {
      document.getElementById("mpInstallments").removeAttribute("disabled");
    }
  };

  /**
   * Show taxes resolution 51/2017 for MLA
   */
  window.showTaxes = function (payer_costs) {
    var installmentsSelect = document.querySelector("#mpInstallments");

    for (var i = 0; i < payer_costs.length; i++) {
      if (payer_costs[i].installments === installmentsSelect.value) {
        var taxes_split = payer_costs[i].labels[0].split("|");
        var cft = taxes_split[0].replace("_", " ");
        var tea = taxes_split[1].replace("_", " ");

        if (cft === "CFT 0,00%" && tea === "TEA 0,00%") {
          cft = "";
          tea = "";
        }

        document.querySelector("#mp-tax-cft-text").innerHTML = cft;
        document.querySelector("#mp-tax-tea-text").innerHTML = tea;
      }
    }
  };

  window.showIframeErrors = (errors) => {
    errors.forEach((error) => {
      if (error.field === "expirationDate") {
        let field = document.querySelector("#mpCardExpirationDate");
        field.classList.add("mp-form-control-error");
      }

      if (error.field === "securityCode") {
        let field = document.querySelector("#mpSecurityCode");
        field.classList.add("mp-form-control-error");
      }
      if (error.field === "cardNumber") {
        let field = document.querySelector("#mpCardNumber");
        field.classList.add("mp-form-control-error");
      }
    });
    focusInputError();
  };

  window.trackedSDKErrors = function () {
    var date = new Date();
    var currentYear = date.getFullYear();
    var currentMonth = date.getMonth();
    var sdkErrors = [
      {
        code: "mp001",
        message: "cardNumber should be a number.",
      },
      {
        code: "mp002",
        message: "cardNumber should be of length between '8' and '19'.",
      },
      {
        code: "mp003",
        message: "expirationMonth should be a number.",
      },
      {
        code: "mp004",
        message: "expirationYear should be of length '2'.",
      },
      {
        code: "mp005",
        message: "expirationYear should be a number.",
      },
      {
        code: "mp006",
        message: "securityCode should be a number.",
      },
      {
        code: "mp007",
        message: "securityCode should be of length '3' or '4'.",
      },
      {
        code: "mp008",
        message: "expirationMonth should be a value from 1 to 12.",
      },
      {
        code: "mp009",
        message: `"expirationYear value should be greater or equal than ${currentYear}."`,
      },
      {
        code: "mp010",
        message: "securityCode should be of length '4'.",
      },
      {
        code: "mp011",
        message: "cardNumber should be of length '15'.",
      },
      {
        code: "mp012",
        message: `expirationMonth value should be greater than '${currentMonth}' or expirationYear value should be greater than '${currentYear}'.`,
      },
    ];
    return sdkErrors;
  };
}.call(this));
