/**
 * This file is part of Oyst_OneClick for Magento.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author Oyst <plugin@oyst.com> <@oyst>
 * @category Oyst
 * @package Oyst_OneClick
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * OneClick RedirectCart JS
 */
"use strict";

/**
 * Redirect from cart
 *
 * @param {String} url Loading page url
 * @param {int} oystParam cart id
 */
function RedirectCart(url, oystParam) {
    var self = this;

    this.url = url;
    this.oystParam = oystParam;
    this.form = new FormData();
    this.xhr = new XMLHttpRequest();
    this.data = {};

    this.prepare = function () {
        if (!self.form.get("oystParam")) {
            self.form.append("oystParam", self.oystParam);
        }

        self.xhr.open("POST", self.url, true);
        self.xhr.setRequestHeader("cache-control", "no-cache");
    };

    this.nullResponse = function () {
        if (200 === self.xhr.status) {
            self.data = JSON.parse(self.xhr.responseText);
            self.send();
        }
    };

    this.redirectSuccess = function () {
        if (200 === self.xhr.status) {
            self.data = JSON.parse(self.xhr.responseText);
            window.location.href = self.data;
        }
    };

    this.send = function () {
        setTimeout(function () {
            if (!self.data.check_order_url) {
                self.xhr.onload = self.nullResponse;
            } else {
                self.url = self.data.check_order_url;
                self.oystParam = self.data.oyst_order_id;
                self.xhr.onload = self.redirectSuccess;
            }

            self.prepare();
            self.xhr.send(self.form);
        }, 5000);
    };
}
