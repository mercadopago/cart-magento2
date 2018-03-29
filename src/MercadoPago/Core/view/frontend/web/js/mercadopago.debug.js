/**
 * Polyfills
 * @public
 */
(function (){

    // IE8, IE9
    if (!String.prototype.trim) {
      (function() {
        // Make sure we trim BOM and NBSP
        var rtrim = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;
        String.prototype.trim = function() {
          return this.replace(rtrim, '');
        };
      })();
    }

    var JSON = JSON || {};

    if (!JSON.parse) {
        (function() {
            JSON.parse = function (obj) {
                'use strict';
                return eval("(" + obj + ")");
            };
        })();
    }

    if (!JSON.stringify) {
        (function() {
            JSON.stringify = function (obj) {
                var t = typeof (obj);
                if (t != "object" || obj === null) {
                    // simple data type
                    if (t == "string"){ 
                        obj = '"'+obj+'"';
                    }
                    return String(obj);
                } else {
                    // recurse array or object
                    var n, v, json = [], arr = (obj && obj.constructor == Array);
                    for (n in obj) {
                        v = obj[n]; t = typeof(v);
                        if (t == "string"){
                            v = '"'+v+'"';
                        }else if (t == "object" && v !== null){
                            v = JSON.stringify(v);
                        }
                        if(t !== "function"){
                            json.push((arr ? "" : '"' + n + '":') + String(v));
                        }
                    }
                    return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
                }
            };
        })();
    }

    if (!Array.prototype.filter) {
        (function(){
            Array.prototype.filter = function (fun) {
                'use strict';
                if (this === void 0 || this === null) {
                  throw new TypeError();
                }
                var t = Object(this);
                var len = t.length >>> 0;
                if (typeof fun !== 'function') {
                  throw new TypeError();
                }

                var res = [];
                var thisArg = arguments.length >= 2 ? arguments[1] : void 0;
                for (var i = 0; i < len; i++) {
                  if (i in t) {
                    var val = t[i];
                    if (fun.call(thisArg, val, i, t)) {
                      res.push(val);
                    }
                  }
                }
                return res;
            };
        })();
    }

    // Production steps of ECMA-262, Edition 5, 15.4.4.18
    if (!Array.prototype.forEach) {

      Array.prototype.forEach = function forEach(callback, thisArg) {
        'use strict';
        var T, k;

        if (this == null) {
          throw new TypeError("this is null or not defined");
        }

        var kValue,
            // 1. Let O be the result of calling ToObject passing the |this| value as the argument.
            O = Object(this),

            // 2. Let lenValue be the result of calling the Get internal method of O with the argument "length".
            // 3. Let len be ToUint32(lenValue).
            len = O.length >>> 0; // Hack to convert O.length to a UInt32

        // 4. If IsCallable(callback) is false, throw a TypeError exception.
        // See: http://es5.github.com/#x9.11
        if ({}.toString.call(callback) !== "[object Function]") {
          throw new TypeError(callback + " is not a function");
        }

        // 5. If thisArg was supplied, let T be thisArg; else let T be undefined.
        if (arguments.length >= 2) {
          T = thisArg;
        }

        // 6. Let k be 0
        k = 0;

        // 7. Repeat, while k < len
        while (k < len) {

          // a. Let Pk be ToString(k).
          //   This is implicit for LHS operands of the in operator
          // b. Let kPresent be the result of calling the HasProperty internal method of O with argument Pk.
          //   This step can be combined with c
          // c. If kPresent is true, then
          if (k in O) {

            // i. Let kValue be the result of calling the Get internal method of O with argument Pk.
            kValue = O[k];

            // ii. Call the Call internal method of callback with T as the this value and
            // argument list containing kValue, k, and O.
            callback.call(T, kValue, k, O);
          }
          // d. Increase k by 1.
          k++;
        }
        // 8. return undefined
      };
    }

    if (!document.querySelectorAll) {
        (function(){
            document.querySelectorAll = function (selectors) {
                var style = document.createElement('style'), elements = [], element;
                document.documentElement.firstChild.appendChild(style);
                document._qsa = [];
             
                style.styleSheet.cssText = selectors + '{x-qsa:expression(document._qsa && document._qsa.push(this))}';
                window.scrollBy(0, 0);
                style.parentNode.removeChild(style);
             
                while (document._qsa.length) {
                  element = document._qsa.shift();
                  element.style.removeAttribute('x-qsa');
                  elements.push(element);
                }
                document._qsa = null;
                return elements;
            };
        })();
    }

    if (!document.querySelector) {
        (function(){
            document.querySelector = function (selectors) {
                var elements = document.querySelectorAll(selectors);
                return (elements.length) ? elements[0] : null;
            };
        })();
    }

})();


(function (){
    var Mercadopago = {
        'version' : '1.5.4',
        'initialized' : false,
        'key' : null,
        'deviceProfileId' : null,
        'tokenId' : null
    },
    _exports = {
        'utils' : {},
        'card' : {},
        'request' : {},
        'trackErrors' : {},
        'paymentMethods' : {}
    }
    ;

    Mercadopago.referer = (function () {
        var referer = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port: '');

        return referer;
    })();

    Mercadopago.setPublishableKey = function(publicKey){
            Mercadopago.key = publicKey;
            Mercadopago.initMercadopago();
    };

    /**
    * Utils
    * @private
    */
    (function (exports) {
    'use strict';

        var Utils = {
            'baseUrl' : 'https://api.mercadopago.com/v1'
        };

        /**
         * @private
         */
        Utils.clear = function (value){
          return ("" + value).trim().replace(/\s+|-/g, "");
        };

        Utils.paramsForm = function (form){
            var values = {},
                data = form.querySelectorAll('[data-checkout]');

                Array.prototype.forEach.call(data, function(el){
                    var attr = el.getAttribute('data-checkout'),
                        index = el.selectedIndex;

                    if (el.nodeName === 'SELECT' && index !== null && index !== -1) {
                        values[attr] = el.options[index].value;
                    } else {
                        values[attr] = el.value;
                    }

                });
            return values;
        };

        Utils.isEmpty = function (obj) {

            var hasOwnProperty = Object.prototype.hasOwnProperty;

            // null and undefined are "empty"
            if (obj == null){
                return true;  
            } 

            // Assume if it has a length property with a non-zero value
            // that that property is correct.
            if (obj.length > 0){
                return false;
            }
            if (obj.length === 0){
                return true;
            }

            // Otherwise, does it have any properties of its own?
            // Note that this doesn't handle
            // toString and valueOf enumeration bugs in IE < 9
            for (var key in obj) {
                if (hasOwnProperty.call(obj, key)){
                    return false;
                }
            }

            return true;
        };

        exports.utils = Utils;

    }(_exports));

    
    (function (exports){
        
        /**
        * AJAX native    
        * @private
        */
        
        function AJAX(options){
            var useXDomain = !!window.XDomainRequest;

            var req = useXDomain ? new XDomainRequest() : new XMLHttpRequest()
            var data; 

            options.url += (options.url.indexOf("?") >= 0 ? "&" : "?") + "referer="+escape(Mercadopago.referer);

            options.requestedMethod = options.method;

            if (useXDomain && options.method == "PUT") {
                options.method = "POST";
                options.url += "&_method=PUT";
            }

            req.open(options.method, options.url, true);

            req.timeout = options.timeout || 1000;

            if (window.XDomainRequest) {
                req.onload = function(){
                    data = JSON.parse(req.responseText);
                    if (typeof options.success === "function") {
                        options.success(options.requestedMethod === 'POST' ? 201 : 200, data);
                    }
                    if (typeof options.complete === "function") {
                        options.complete(options.requestedMethod === 'POST' ? 201 : 200, data);
                    }
                };
                req.onerror = req.ontimeout = function(){
                    if(typeof options.error === "function"){
                        options.error(400,{user_agent:window.navigator.userAgent, error : "bad_request", cause:[]});
                    }
                    if(typeof options.complete === "function"){
                        options.complete(400,{user_agent:window.navigator.userAgent, error : "bad_request", cause:[]});
                    }
                };
                req.onprogress = function() {};
            } else {
                req.setRequestHeader('Accept','application/json');

                if(options.contentType){
                    req.setRequestHeader('Content-Type', options.contentType);
                }else{
                    req.setRequestHeader('Content-Type', 'application/json');
                }

                req.onreadystatechange = function() {
                    if (this.readyState === 4){
                        if (this.status >= 200 && this.status < 400){
                          // Success!
                            data = JSON.parse(this.responseText);
                            if (typeof options.success === "function") {
                                options.success(this.status, data);
                            }
                            if (typeof options.complete === "function") {
                                options.complete(this.status, data);
                            }
                        } else if(this.status >= 400){
                            data = JSON.parse(this.responseText);
                            if (typeof options.error === "function") {
                                options.error(this.status, data);
                            }
                            if (typeof options.complete === "function") {
                                options.complete(this.status, data);
                            }
                        } else {
                            if (typeof options.error === "function") {
                                options.error(503, {});
                            }
                            if (typeof options.complete === "function") {
                                options.complete(503, {});
                            }
                        }
                    }
                };    
            }

            if(options.method === 'GET' || options.data == null || options.data == undefined){
                req.send();    
            }else{
                req.send(JSON.stringify(options.data));
            }
        }

        exports['request'].AJAX = AJAX;

    }(_exports));


    /**
    * Track errors
    * @private
    */
    (function(Mercadopago,_exports){
        
        var request = _exports['request'],
            Utils = _exports['utils'];

        function trackErrors(data){
        
            var url = Utils.baseUrl + "/payment_methods/track_error?public_key=" + Mercadopago.key + "&js_version=" + Mercadopago.version;
            
            request.AJAX({
                url: url,
                method : "POST",
                data:data,
                timeout : 5000
            });
        }

        _exports['trackErrors'] = trackErrors;

    }(Mercadopago, _exports));

    /**
    * Payment methods functions
    *
    */
    (function(Mercadopago, _exports){
        
        var Utils = _exports['utils'],
            trackErrors = _exports['trackErrors'],
            request = _exports['request'],
            guessedPaymentMethods = {},
            guessedInstallments = {},
            acceptedPaymentMethods = [],
            acceptedCardIssuers = {},
            paymentMethods = {};
        

        paymentMethods.validateBinPattern = function validateBinPattern(cn,config){
            var bin = cn.slice(0, 6);
            return config && config.bin && (bin.match(config.bin.pattern)?true:false) && ( !config.bin.exclusion_pattern || !bin.match(config.bin.exclusion_pattern));
        };

        paymentMethods.setPaymentMethods = function setPaymentMethods(pm){
            acceptedPaymentMethods = pm;
        };

        paymentMethods.getPaymentMethods = function getPaymentMethods(){
            return acceptedPaymentMethods;
        }; 

        function searchPaymentMethods(object,callback){
            var acceptedPM = paymentMethods.getPaymentMethods(),
                paymentMethod,
                i = 0,
                id = object.bin || object.payment_method_id,
                _status = 200,
                _response;
               

            function searchPm(allPmt){
                
                if (object.bin) {
                    paymentMethod = [];
                    for(pm in allPmt){
                        if(!object.payment_type_id || (object.payment_type_id && object.payment_type_id ==  allPmt[pm].payment_type_id)){
                            for (c in allPmt[pm].settings){

                                if(paymentMethods.validateBinPattern(object.bin,allPmt[pm].settings[c])){
                                    paymentMethod[i++] = allPmt[pm];
                                }
                            }
                        }
                    }
                }else if(object.payment_method_id) {
                    paymentMethod = allPmt.filter(function(pm){
                                        return pm.id==object.payment_method_id;
                                    });    
                }

                if(paymentMethod && paymentMethod.length>0){
                    guessedPaymentMethods[id] = paymentMethod;
                }

                if(paymentMethod && paymentMethod.length==0){
                    _status = 400;
                    _response = {
                        "message": "payment method not found",
                        "error": "bad_request",
                        "status": 400,
                        "cause": []
                    };
                }else{
                    _response = paymentMethod;
                }
                typeof callback === 'function' ? callback(_status,_response) : null;
            
            }

            if (acceptedPM.length>0) {
                searchPm(acceptedPM);
            }
            else{
                paymentMethods.getAllPaymentMethods(function(status,response){
                    if(status === 200){
                        searchPm(response);  
                    }else{
                        typeof callback === 'function' ? callback(status,response) : null;
                    }
                });
            }

        }

        paymentMethods.getPaymentMethod = function getPaymentMethod(object,callback){
            
            var id = object.bin || object.payment_method_id;

            if(!id){
                return typeof callback == "function" ?  callback(400, {status:400, error : "bad_request", cause:{"code": "2000","description": "the payment_method_id or bin are required"}}, object) : null; 
            }
 
            if(object.bin){
                object.bin = Utils.clear(object.bin).replace(/[^0-9]/g, "").slice(0,6);
            }
            if(guessedPaymentMethods && guessedPaymentMethods[id]){
                return (typeof callback == "function" ?  callback(200 , guessedPaymentMethods[id]) : null);
            }
            return searchPaymentMethods(object,callback);
        };

        paymentMethods.getAllPaymentMethods = function getAllPaymentMethods(callback){

            var url = Utils.baseUrl + '/payment_methods?public_key=' + Mercadopago.key + '&js_version=' + Mercadopago.version;
               
            if (document.querySelector('html').getAttribute('lang')) {
                url += '&locale=' + document.querySelector('html').getAttribute('lang');
            }

            request.AJAX({
                method : 'GET',
                url : url,
                timeout: 10000,
                success : function successAllPMT(status,response){
                    paymentMethods.setPaymentMethods(response);
                    typeof callback == "function" ?  callback(status, response) : null;
                },
                error : function(status,response){
                    trackErrors({status:status, type:'getAllPaymentMethods', data:response});
                    typeof callback == "function" ?  callback(status, response) : null;
                }
            });
        };

        paymentMethods.getInstallments = function getInstallments(object,callback){
            var url =  Utils.baseUrl + "/payment_methods/installments?public_key=" + Mercadopago.key + "&js_version="+ Mercadopago.version,
                id = object.bin || object.payment_method_id,
                query = "";
            
            if(object.bin){
                query += "&bin=" + object.bin;
            }
            if(object.payment_method_id){
                query += "&payment_method_id=" + object.payment_method_id;
            }
            if(object.issuer_id){
                query += "&issuer.id=" + object.issuer_id;
            }
            if(object.payment_type_id){
                query += "&payment_type_id=" + object.payment_type_id;
            }
            if(object.amount){
                query += "&amount=" + object.amount;
            }
            if(object.differential_pricing_id){
                query += "&differential_pricing_id=" + object.differential_pricing_id;
            }
            if (document.querySelector('html').getAttribute('lang')) {
                query += "&locale=" + document.querySelector('html').getAttribute('lang');
            }
            url += query;

            if(guessedInstallments && guessedInstallments[query]){
                return (typeof callback === 'function' ?  callback(200 , guessedInstallments[query]) : null);
            }

            request.AJAX({
                method : "GET",
                url : url,
                timeout : 10000,
                error : function(status, response) {
                        trackErrors({status:status, type:"getInstallments", data:response});
                        typeof callback == "function" ?  callback(status, response, object) : null;
                },
                success: function successGetInstallments(status, response) {
                        
                        if(status === 200 && response.length>0){
                            if(id){
                                guessedInstallments[id] = response;    
                            }
                            if(query){
                                guessedInstallments[query] = response;    
                            }    
                        }
                        
                        typeof callback == "function" ?  callback(status, response, object) : null;
                }
            });
        };

        paymentMethods.getIssuers = function(id,callback){

            var url = Utils.baseUrl + "/payment_methods/card_issuers?public_key=" + Mercadopago.key + "&js_version=" + Mercadopago.version; 
            
            if(id !== null || id !== undefined){
                url += "&payment_method_id=" + id;
            }

            if(acceptedCardIssuers[id]){
                return typeof callback === 'function' ? callback(200, acceptedCardIssuers[id]) : null;
            }

            request.AJAX({
                method : "GET",
                url : url,
                timeout : 10000,
                error : function(status, response) { 
                    trackErrors({status:status, type:"cardIssuers", data:response});
                    typeof callback === 'function' ?  callback(status, response) : null;
                },
                success: function successIssuers(status, response) {
                    if(status === 200){
                        acceptedCardIssuers[id] = response;    
                    }
                    typeof callback === 'function' ?  callback(status, response) : null;
                }
            });

        };

        _exports['paymentMethods'] = paymentMethods;

        for(exports in paymentMethods){
            Mercadopago[exports] = paymentMethods[exports];    
        }

    }(Mercadopago,_exports));
    

    (function(Mercadopago, _exports){

        var Utils = _exports['utils'],
            trackErrors = _exports['trackErrors'],
            request = _exports['request'],
            paymentMethods = _exports['paymentMethods'],
            _Card = {
                'tokenName' : 'card',
                'whitelistedAttrs' : ["cardNumber", "securityCode", "cardExpirationMonth", "cardExpirationYear","cardExpiration", "cardIssuerId"],
                'identificationTypes' : [],
                'requiredParamsCodes' : {"cardholderName": {'code': "221", 'description': "parameter cardholderName can not be null/empty"},
                    "docNumber" : {'code': "214", 'description': "parameter docNumber can not be null/empty"},
                    "docType" : {'code': "212", 'description': "parameter docType can not be null/empty"},
                    "cardNumber": { "code" : "205", 'description': "parameter cardNumber can not be null/empty"},
                    "securityCode": { 'code': "224",'description': "parameter securityCode can not be null/empty"},
                    "cardExpirationMonth": { 'code': "208", 'description': "parameter cardExpirationMonth can not be null/empty"},
                    "cardExpirationYear": {'code': "209", 'description': "parameter cardExpirationYear can not be null/empty"},
                    "cardIssuerId": {'code': "220", 'description': "parameter cardIssuerId can not be null/empty"}
                },
                'invalidParamsCode' : { "cardholderName": { 'code': "316", 'description': "invalid parameter cardholderName"},
                    "docNumber" : {'code': "324", 'description': "invalid parameter docNumber"},
                    "docType" : {  'code': "322", 'description': "invalid parameter docType"},
                    "cardNumber": { 'code': "E301", 'description': "invalid parameter cardNumber" },
                    "securityCode": { 'code': "E302", 'description': "invalid parameter securityCode"},
                    "cardExpirationMonth":  {'code': "325", 'description': "invalid parameter cardExpirationMonth"},
                    "cardExpirationYear": {'code': "326", 'description': "invalid parameter cardExpirationYear"}
                }
            };

        function getIdentificationTypes(callback){

            function successIdentificationTypes(status,response){
                
                if(typeof callback === 'function'){
                     callback(status,response);
                }else if(status==200){
                    var selectorTypes =  document.querySelector('select[data-checkout=docType]'),
                        addEvent = false;
                    
                    if(selectorTypes){
                        
                        selectorTypes.options.length = 0;

                        for (var i=0; i < response.length; i++) {
                            
                            var el = response[i],
                                option = new Option(el.name,el.id);
                    
                            selectorTypes.options.add(option);
                        }
                    }
                }
            }

            if (_Card.identificationTypes.length>=1) {
                successIdentificationTypes(200,_Card.identificationTypes);
            }
            else{
                request.AJAX({
                    method : 'GET',
                    timeout: 5000,
                    url : Utils.baseUrl + '/identification_types?public_key=' + Mercadopago.key,
                    success : function(status,response){
                                if(status==200){
                                    _Card.identificationTypes = response;
                                }
                                successIdentificationTypes(status,response);
                            },
                    error : function(status,response){
                        if (status!==404) {
                            trackErrors({status:status, type:'getIdentificationTypes', data:response});
                        }else{
                            var list = [ document.querySelector('select[data-checkout=docType]'),
                                        document.querySelector('input[data-checkout=docNumber]'),
                                        document.querySelector('label[for=docType]'),
                                        document.querySelector('label[for=docNumber]')
                                    ];
                            for (i in list) {
                                if (list[i]) {
                                    list[i].style.display = "none";    
                                }
                            }
                        }
                        typeof callback === 'function' ?  callback(status,response) : null;
                    }
                });
            }
        }

        function validateLuhn(num){
            var digit, digits, odd, sum, _i, _len;
            odd = true;
            sum = 0;
            digits = (num + '').split('').reverse();
            for (_i = 0, _len = digits.length; _i < _len; _i++) {
                digit = digits[_i];
                digit = parseInt(digit, 10);
                if ((odd = !odd)) {
                  digit *= 2;
                }
                if (digit > 9) {
                  digit -= 9;
                }
                sum += digit;
            }
            return sum % 10 === 0;
        }
           
        function validateCardNumber(cardNumber, pm, callback) {
            cardNumber = Utils.clear(cardNumber);
            
            if(callback == undefined && (typeof pm === 'function')){
                callback = pm;
            }
            
            var pmtData = {
                'bin':cardNumber,
                'internal_validate':true
            };

            if(typeof pm !== 'function'){
                pmtData['payment_method_id'] = pm;
            }

            paymentMethods.getPaymentMethod(pmtData,
               function(status,data){
                    var result = false;
                    if(status == 200){
                        for(var j=0; j < data.length && !result; j++){
                            config = data[j].settings;
                            for (var i=0; config && i < config.length && !result; i++){
                                result = cardNumber.length == config[i].card_number.length && paymentMethods.validateBinPattern(cardNumber,config[i]) && ( config[i].card_number.validation == "none" ||  validateLuhn(cardNumber));
                            }
                        }
                    }
                    typeof callback == "function" ? callback(status,result) : null;
                }
            );
        }

        function validateSecurityCode(securityCode, pm, callback) {
            securityCode = Utils.clear(securityCode);

            if (securityCode && !(/^\d+$/.test(securityCode))){
                return typeof callback === 'function' ? callback(200,false) : null;
            }
            paymentMethods.getPaymentMethod({"bin": pm, "internal_validate":true},
                function(status,data){
                    var result = true;
                    if(status == 200){
                        var config = data[0] ? data[0].settings : [];
                        for (var i=0; config && i < config.length && result ; i++){
                            result = !config[i].security_code.length || securityCode.length == config[i].security_code.length || (config[i].security_code.mode == 'optional' && !securityCode.length);
                        }
                    }
                    return typeof callback == "function" ? callback(status,result) : null;
            });
        }

        function validateAdditionalInfoNeeded(params, errors, callback){
            var index = errors.length;

            paymentMethods.getPaymentMethod({'bin':params['cardNumber'],'internal_validate':true},
                function(status,data){
                   
                    if(status == 200){
                        var additionalInfoNeeded = data[0] ? data[0].additional_info_needed : [];

                        for (var j=0; j<additionalInfoNeeded.length; j++) {
                            switch (additionalInfoNeeded[j]){
                                case "cardholder_name":
                                    if(!params["cardholderName"] || params["cardholderName"]===""){
                                        errors[index++] = _Card.requiredParamsCodes["cardholderName"];
                                    }else if(!validateCardholderName(params["cardholderName"])){
                                        errors[index++] = _Card.invalidParamsCode["cardholderName"];
                                    }   
                                    break;
                                case "cardholder_identification_type": 
                                    if(!params["docType"] || params["docType"]===""){
                                        errors[index++] = _Card.requiredParamsCodes["docType"];
                                    }else if(_Card.identificationTypes && !_Card.identificationTypes.filter(function(dt){return dt.id == params["docType"];})){
                                        errors[index++] = _Card.invalidParamsCode["docType"];
                                    }
                                    break;
                                case "cardholder_identification_number":  
                                    if(!params["docNumber"] || params["docNumber"]===""){
                                        errors[index++] = _Card.requiredParamsCodes["docNumber"];
                                    }else if(!validateIdentification(params["docType"],params["docNumber"])){
                                        errors[index++] = _Card.invalidParamsCode["docNumber"];
                                    }
                                    break;
                            }
                        }
                    }
                    typeof callback == "function" ? callback(status, errors) : null;

                });
        }

        function validateCardholderName(cardholderName){
            var regExPattern = "^[a-zA-ZÃ£ÃƒÃ¡ÃÃ Ã€Ã¢Ã‚Ã¤Ã„áº½áº¼Ã©Ã‰Ã¨ÃˆÃªÃŠÃ«Ã‹Ä©Ä¨Ã­ÃÃ¬ÃŒÃ®ÃŽÃ¯ÃÃµÃ•Ã³Ã“Ã²Ã’Ã´Ã”Ã¶Ã–Å©Å¨ÃºÃšÃ¹Ã™Ã»Ã›Ã¼ÃœÃ§Ã‡â€™Ã±Ã‘ .']*$";
            return (cardholderName.match(regExPattern)?true:false);
        }
        
        function validateIdentification(type,number){
            if(_Card.identificationTypes.length===0){
                return true;
            }
            number = Utils.clear(number);
            var dt = _Card.identificationTypes.length===0? null : _Card.identificationTypes.filter(function(_dt){return _dt.id == type;})[0];
            dt = dt || null;
            number = number || null;
            return dt!==null && number!==null && dt.min_length <=  number.length && number.length <= dt.max_length;
        }

        function validateExpiryDate(month, year) {
            var currentTime, 
                expiry;
            
            month = ('' + month).trim();

            if(year == undefined){
                if (month.split('/').length==1){ 
                    return false;
                }
                year = month.split('/')[1];
                month = month.split('/')[0];
            }
            
            year = ('' + year).trim();
            
            if(year.length==2){
                year='20' + year;
            }
           
            if(!(/^\d+$/.test(month))){
                return false;
            }
            if(!(/^\d+$/.test(year))){
                return false;
            }
            if (!(parseInt(month, 10) <= 12)) {
                return false;
            }
            expiry = new Date(year, month);
            currentTime = new Date;
            expiry.setMonth(expiry.getMonth() - 1);
            expiry.setMonth(expiry.getMonth() + 1, 1);
            return expiry > currentTime;
        }
    
        function validate(params, callback){
            var attr,
                index = 0,
                errors = [],
                callApi = false;
            
            if (params['cardId'] && params['cardId'] !== '' && params["cardId"] !== '-1') {
                callback(errors);
                return;
            }

            if (params['cardExpiration'] && (!params['cardExpirationMonth'] || !params['cardExpirationYear'])) {
                params['cardExpirationMonth'] = params['cardExpiration'].split('/')[0];
                params['cardExpirationYear'] = params['cardExpiration'].split('/')[1];
            }else{
                params['cardExpiration'] = params['cardExpirationMonth'] + '/' + params['cardExpirationYear'];
            }

            if (params['cardExpirationYear'] && params['cardExpirationYear'].length==2) {
                params['cardExpirationYear'] = ('20' + params['cardExpirationYear']);
            }
            
            params['docNumber'] = Utils.clear(params['docNumber']);

            for(var i = 0; i < _Card.whitelistedAttrs.length; i++) {
                attr = _Card.whitelistedAttrs[i];
                if(attr == 'cardNumber' || attr == 'securityCode'){
                    params[attr] = Utils.clear(params[attr]);
                }
                if((!params[attr] || params[attr]==='') && attr!=='cardIssuerId' && attr!=='securityCode'){
                    errors[index++] = _Card.requiredParamsCodes[attr];
                }   
            }

            if(!Mercadopago.validateExpiryDate(params['cardExpirationMonth'],params['cardExpirationYear'])){
                errors[index++] = _Card.invalidParamsCode['cardExpirationMonth'];
                errors[index++] = _Card.invalidParamsCode['cardExpirationYear'];
            }
          
            
            validateCardNumber(params['cardNumber'],
                function(status,result){
                    if(!result){
                        errors[index++] = _Card.invalidParamsCode['cardNumber'];
                    }
                    validateSecurityCode(params['securityCode'],params['cardNumber'],
                        function(status,result){
                        if(!result){
                            errors[index++] = _Card.invalidParamsCode['securityCode'];
                        }

                        validateAdditionalInfoNeeded(params, errors, 
                            function(status, errors){
                                callback(errors);
                        });
                    });
            });
        }
        
        Mercadopago.validateLuhn = validateLuhn;

        Mercadopago.validateCardNumber = validateCardNumber;

        Mercadopago.validateSecurityCode = validateSecurityCode;

        Mercadopago.validateCardholderName = validateCardholderName;
        
        Mercadopago.validateIdentification = validateIdentification;

        Mercadopago.validateExpiryDate = validateExpiryDate;

        Mercadopago.getIdentificationTypes = getIdentificationTypes;
        
        _Card.validate = validate;

        _exports['card'] = _Card;

    }(Mercadopago, _exports));


    (function (Mercadopago, _exports) {

        var Utils = _exports['utils'],
            Card = _exports['card'],
            trackErrors = _exports['trackErrors'],
            request = _exports['request'];

        /**
         * mappingCard
         * @private
         */
        function mappingCard(data){
            var tokenData = {};

            if(Mercadopago.deviceProfileId){
                tokenData["device"] = { "meli" : { "session_id" : Mercadopago.deviceProfileId } };
            }

            if(data.cardId && data.cardId !== "" && data.cardId !== "-1") {
                tokenData["card_id"] = data.cardId;
                tokenData["security_code"] = data.securityCode;
                return tokenData;    
            }

            tokenData["card_number"] = data.cardNumber;
            tokenData["security_code"] = data.securityCode;
            tokenData["expiration_month"] = parseInt(data.cardExpirationMonth,10);
            tokenData["expiration_year"] = parseInt(data.cardExpirationYear,10);
            tokenData["cardholder"] = {"name" : data.cardholderName};
            tokenData["cardholder"]["identification"] = { "type": (data.docType===""||data.docType===undefined)?null:data.docType,
                                                    "number": (data.docNumber===""||data.docNumber===undefined)?null:data.docNumber
                                                    };
            return tokenData;
        }

        /**
        * CardToken
        * @private
        */
        var CardToken = {};

        CardToken.request = function CardTokenRequest(_method, data, callback) {
            var url = Utils.baseUrl + "/card_tokens";
            var _body = data.card ? mappingCard(data.card) : {};

            callback = typeof callback == "function" ? callback : function(){};

            if (_method != "POST" && _method != "PUT") {
                throw new Error("Method not allowed.");
            }

            if (_method == "PUT") {
                url += '/' + Mercadopago.tokenId;
            }

            url += '?public_key=' + Mercadopago.key + '&js_version=' + Mercadopago.version;

            request.AJAX({
                method: _method,
                url : url,
                data: _body,
                timeout : 10000,
                error: function(status, response) {
                    trackErrors({status:status, type:"cardForm", data:response});
                },
                success: function(status,response){
                    Mercadopago.tokenId = response.id;
                },
                complete: callback
            }); 
        };

        CardToken.new = function CardTokenNew(callback) {
            CardToken.request("POST", {}, (function(){
                return function() {
                    Mercadopago.createDeviceProfile(callback)
                };
            }()));
        };

        CardToken.update = function CardTokenUpdate(data, callback) {
            CardToken.request("PUT", data, callback);
        }

        CardToken.create = function CardTokenCreate(data, callback) {
            CardToken.new((function() {
                return function() {
                    CardToken.update(data, callback);
                };
            }()));
        }

        _exports.CardToken = CardToken;

        /**
         * post
         * @private
         */
        function post(dataObj, callback) {
            if (Mercadopago.tokenId) {
                _exports.CardToken.update(dataObj, callback);
            } else {
                _exports.CardToken.create(dataObj, callback);
            }
        }

        /**
         * post
         * @public
         */
        function formatData(data, whitelistedAttrs, callback) {

            if(data && data.jquery){
                data = data[0];
            }

            if(data instanceof HTMLFormElement || data.nodeType !== undefined && data.nodeType === document.ELEMENT_NODE){
                data = Utils.paramsForm(data);
            } 

            if(Utils.isEmpty(data)){
                callback(data);
            }else{
                Card.validate(data, function(validateResult){

                    if (validateResult.length) {
                        data = validateResult;

                        trackErrors({
                            'status': 400,
                            'type': 'validateForm',
                            'data': data
                        });
                    }
                    callback(data);
                });
            }
        }

        /**
         * create
         * @public
         */
        function create(params, callback) {

            /** validate key*/
            if (!Mercadopago.key || typeof Mercadopago.key != "string") { 
                throw new Error("You did not set a valid publishable key. Call Mercadopago.setPublishableKey() with your public_key.");
            }
            if (/\s/g.test(Mercadopago.key)) { 
                throw new Error("Your key is invalid, as it contains whitespaces.");
            }

            var data = document.querySelectorAll('[data-checkout]');

            /* SSL */
            /* "file:" protocol support - Remove it when Hybrid Mobile apps problem is solved */
            // if(window.location.protocol != 'file:' && window.location.protocol != 'https:' && data && data.length > 0 && !(/^TEST/.test(Mercadopago.key))){
            //     trackErrors({status:200, type:"validateReferer", data: { referer: window.location.host, user_agent: window.navigator.userAgent, public_key: Mercadopago.key}});
            //     throw new Error("Your payment cannot be processed because the website contains credit card data and is not using a secure connection.SSL certificate is required to operate.");
            // }

            /* validate PCI DSS */
            function validateDSS(){
               var j = 0,
                   invalidNames = [];

                for(var i=0; data && i < data.length; i++){
                    var el = data[i];
                    if(el.name !==null && el.name !== undefined && el.name !== "" && (el.getAttribute('data-checkout') == "cardNumber" || el.getAttribute('data-checkout') == "securityCode")){
                        invalidNames[j++] = el.getAttribute('data-checkout');
                    }
                }
                if(invalidNames.length > 0){
                    trackErrors({status:200, type:"DSS-" + window.location.host , data: { inputNames: invalidNames, user_agent: window.navigator.userAgent, public_key: Mercadopago.key}});
                }
            }

            validateDSS();

            params.card.public_key = Mercadopago.key;

            post(params, callback);
        }

        function createToken(data, callback) {
            var params = {};

            function complete(response){
                params[Card.tokenName] = response;
                if(!params[Card.tokenName][0]){
                    return create(params, callback);
                }else{
                    return callback(400,{error : "bad_request", message: "invalid parameters", cause: params[Card.tokenName]});
                }
            }
            formatData(data, Card.whitelistedAttrs, complete);
        }
        
        Mercadopago.createToken = createToken;
            
    }(Mercadopago, _exports));
  
    (function(Mercadopago, _exports){

        /** Device Profile
        */
        var deviceProfileCallback = null;
        var deviceProfileCallbackTimer = null;
        _exports.creatingDevice = false;
        function sessionIdListener (event) {
            if (event.origin != "https://mldp.mercadopago.com") {
                return;
            }
            clearTimeout(deviceProfileCallbackTimer);

            var data = null;
            try {
                data = JSON.parse(event.data);
            } catch (e) {}

            if (data.session_id == Mercadopago.deviceProfileId) {
                return;
            }

            Mercadopago.deviceProfileId = data.session_id;
            _exports.creatingDevice = false;

            if (deviceProfileCallback) {
                deviceProfileCallback();
            }

            getTHM(data.session_id);
        };

        function createDeviceProfile(callback){

            if (!Mercadopago.tokenId) {
                return;
            }

            _exports.creatingDevice = true;
            
            var existent = document.querySelector("iframe#device_profile");
            if (existent) {
                var removed = existent.parentElement.removeChild(existent);
                try {
                    delete removed;
                } catch (e) {/* don't worry if cannot delete, is still removed */}
            } else {
                if (window.addEventListener) {
                    window.addEventListener ("message", sessionIdListener, false);
                } else if (window.attachEvent) {
                    window.attachEvent ("onmessage", sessionIdListener);
                }
            }

            var iframe = document.createElement('iframe');
            iframe.id = "device_profile";

            deviceProfileCallback = (typeof callback == "function") ? callback : null;
            if (deviceProfileCallback) {
                deviceProfileCallbackTimer = setTimeout(function() {
                    _exports.creatingDevice = false;
                    deviceProfileCallback();
                }, 3000);
            }

            iframe.style.display = "none";
            iframe.src = "https://mldp.mercadopago.com/device_profile/widget?public_key=" + Mercadopago.key + "&session_id=" + Mercadopago.tokenId;
            document.body.appendChild(iframe);
        }

        /** THM pixel
        */
        function getTHM(sessionId){
            /* Initialize pixel iframe */            
            var existent = document.querySelector("iframe#thm_mp_cntnr");
            if (existent) {
                var removed = existent.parentElement.removeChild(existent);
                try {
                    delete removed;
                } catch (e) {/* don't worry if cannot delete, is still removed */}
            }

            var cntnr = document.createElement("iframe");
            cntnr.id = "thm_mp_cntnr";
            cntnr.setAttribute("width", "0");
            cntnr.setAttribute("height", "0");
            cntnr.setAttribute("frameborder", "0");
            cntnr.style.visibility = "hidden";

            document.querySelector("body").appendChild(cntnr);

            cntnr.contentDocument.open();
            cntnr.contentDocument.write("<!doctype html><html><body></body></html>");
            cntnr.contentDocument.close();
            /* END - Initialize pixel iframe */

            var script = document.createElement("script");
            script.id = "thm_loader";
            script.setAttribute("type", "text/javascript");
            script.setAttribute("src", "https://content.mercadopago.com/fp/check.js?org_id=jk96mpy0&session_id=" + sessionId);

            cntnr.contentDocument.body.appendChild(script);
        }

        Mercadopago.createDeviceProfile = createDeviceProfile;

    }(Mercadopago, _exports));

    (function(Mercadopago, _exports){

        function initMercadopago(){

            if(Mercadopago.initialized === true){
                return;
            }

            _exports.CardToken.new();

            if(Mercadopago.getPaymentMethods().length===0) {
                Mercadopago.getAllPaymentMethods();
            }

            Mercadopago.initialized = true;
        }

        function clearSession(){
            Mercadopago.tokenId = null;
            Mercadopago.deviceProfileId = null;
        }

        Mercadopago.clearSession = clearSession;

        Mercadopago.initMercadopago = initMercadopago;

    }(Mercadopago, _exports));

    this.Mercadopago = Mercadopago;
    
   
}).call();