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
 * OneClick RedirectFromOyst JS
 */
"use strict";

/**
 * Redirect from Oyst
 *
 * @param {String} url Loading page url
 */
function RedirectFromOyst(url) {
    var self = this;

    this.url = url;
    this.xhr = new XMLHttpRequest();
    this.data = null;

    this.prepare = function () {
        self.xhr.open("GET", self.url, true);
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
            if (null === self.data) {
                self.xhr.onload = self.nullResponse;
            } else {
                self.xhr.onload = self.redirectSuccess;
            }

            self.prepare();
            self.xhr.send();
        }, 5000);
    };
}
