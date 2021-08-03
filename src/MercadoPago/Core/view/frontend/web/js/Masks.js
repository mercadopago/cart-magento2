var vObj, vFun;

// input mask
function maskInput (o, f) {
  vObj = o;
  vFun = f;
  setTimeout(execmascara(), 1);
}

// eslint-disable-next-line no-unused-vars
function execmascara () {
  vObj.value = vFun(vObj.value);
}

// eslint-disable-next-line no-unused-vars
function mdate (v) {
  v = v.replace(/\D/g, '');
  v = v.replace(/(\d{2})(\d)/, '$1/$2');
  v = v.replace(/(\d{2})(\d{2})$/, '$1$2');
  return v;
}

// eslint-disable-next-line no-unused-vars
function minteger (v) {
  return v.replace(/\D/g, '');
}

// eslint-disable-next-line no-unused-vars
function mcc (v) {
  v = v.replace(/\D/g, '');
  v = v.replace(/^(\d{4})(\d)/g, '$1 $2');
  v = v.replace(/^(\d{4})\s(\d{4})(\d)/g, '$1 $2 $3');
  v = v.replace(/^(\d{4})\s(\d{4})\s(\d{4})(\d)/g, '$1 $2 $3 $4');
  return v;
}

function mcep (v) {
  v = v.replace(/\D/g, '');
  v = v.replace(/^(\d{5})(\d)/g, '$1-$2');
  return v;
}


// eslint-disable-next-line no-unused-vars
function mcpf (v) {
  v = v.replace(/\D/g, '');
  v = v.replace(/(\d{3})(\d)/, '$1.$2');
  v = v.replace(/(\d{3})(\d)/, '$1.$2');
  v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  return v;
}

// eslint-disable-next-line no-unused-vars
function mcnpj (v) {
  v = v.replace(/\D/g, '');
  v = v.replace(/^(\d{2})(\d)/, '$1.$2');
  v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
  v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
  v = v.replace(/(\d{4})(\d)/, '$1-$2');
  return v;
}
