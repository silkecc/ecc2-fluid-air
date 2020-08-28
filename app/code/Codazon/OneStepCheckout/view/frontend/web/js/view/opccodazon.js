/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
], function (ko, $, Component, quote) {
    'use strict';
    return Component.extend({
        visible: ko.observable(!quote.isVirtual()),
        className: ko.computed(function () {
            if(quote.isVirtual()){
                return "col2";
            }else{
                return "col";
            }
        }),
    });
});
