define([
  'Magento_SalesRule/js/action/set-coupon-code',
  'Magento_SalesRule/js/action/cancel-coupon',
  'Magento_SalesRule/js/model/coupon',
], function (setCouponCodeAction, cancelCouponAction, coupon) {
  'use strict';

  var couponCode = coupon.getCouponCode();
  var isApplied = coupon.getIsApplied();

  var mixin = {
    apply: function () {
      if (this.validate()) {
        setCouponCodeAction(couponCode(), isApplied);
        mpDeleteCardForm();
      }
    },
    cancel: function () {
      if (this.validate()) {
        couponCode('');
        cancelCouponAction(isApplied);
        mpDeleteCardForm();
      }
    },
  }
  return function (origin) {

    return origin.extend(mixin);
  };
});
