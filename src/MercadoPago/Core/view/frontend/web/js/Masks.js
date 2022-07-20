(function () {

  window.vObj;
  window.vFun;

  // input mask
  window.maskInput = function (o, f) {
    vObj = o;
    vFun = f;
    setTimeout(execmascara(), 1);
  }

  // eslint-disable-next-line no-unused-vars
  window.execmascara = function () {
    vObj.value = vFun(vObj.value);
  }

  // eslint-disable-next-line no-unused-vars
  window.mdate = function (v) {
    v = v.replace(/\D/g, '');
    v = v.replace(/(\d{2})(\d)/, '$1/$2');
    v = v.replace(/(\d{2})(\d{2})$/, '$1$2');
    return v;
  }

  // eslint-disable-next-line no-unused-vars
  window.minteger = function (v) {
    return v.replace(/\D/g, '');
  }

  // eslint-disable-next-line no-unused-vars -- hugo
  window.mintegerletter = function (v) {
    return v.replace(/[^A-Za-z0-9]+/g, '');
  }

  // eslint-disable-next-line no-unused-vars
  window.mcc = function (v) {
    v = v.replace(/\D/g, '');
    v = v.replace(/^(\d{4})(\d)/g, '$1 $2');
    v = v.replace(/^(\d{4})\s(\d{4})(\d)/g, '$1 $2 $3');
    v = v.replace(/^(\d{4})\s(\d{4})\s(\d{4})(\d)/g, '$1 $2 $3 $4');
    return v;
  }

  window.mcep = function (v) {
    v = v.replace(/\D/g, '');
    v = v.replace(/^(\d{5})(\d)/g, '$1-$2');
    return v;
  }


  // eslint-disable-next-line no-unused-vars
  window.mcpf = function (v) {
    v = v.replace(/\D/g, '');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d)/, '$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    return v;
  }

  // eslint-disable-next-line no-unused-vars
  window.mcnpj = function (v) {
    v = v.replace(/\D/g, '');
    v = v.replace(/^(\d{2})(\d)/, '$1.$2');
    v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
    v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
    v = v.replace(/(\d{4})(\d)/, '$1-$2');
    return v;
  }
}).call(this);