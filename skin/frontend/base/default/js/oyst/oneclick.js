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
 * OneClick Customs JS
 */
"use strict";

wgxpath.install();

var mutationObserver = null;

/**
 * Main function to init 1-Click
 *
 * @param {String} oneClickUrl  Backend conf oneclick payment url
 */
function oystOneClick(config) {
    if (config.addtocartButtonsClass) {
        smartButtonData(config.addtocartButtonsClass);
    }

    window.__OYST__ = window.__OYST__ || {};
    window.__OYST__.getOneClickURL = function (cb, opts) {
        opts = opts || {};

        ready(function () {
            var form = new FormData();
            form.append("preload", opts.preload);
            if (config.isCheckoutCart) {
                form.append("isCheckoutCart", config.isCheckoutCart);
            }

            if (config.addToCartProductFormId) {
                var isErrorsInForm = null;

                if (!opts.preload) {
                    if (config.isProductAddtocartFormValidate) {
                        var formTmp = new VarienForm(config.addToCartProductFormId);
                        isErrorsInForm = !formTmp.validator.validate();
                    }

                    if ("function" === typeof isCustomProductAddtocartFormValid) {
                        // [Hook] This function allow anyone to use custom form validator, return as to be a boolean
                        isErrorsInForm = !isCustomProductAddtocartFormValid();
                    }

                    if (isErrorsInForm) {
                        cb(isErrorsInForm, null);
                        return;
                    }

                    var addToCartFormData = {};
                    Object.entries($(config.addToCartProductFormId).serialize(true)).each(function (element) {
                        var keyParts = element[0].split("[");
                        if (keyParts.length == 1) {
                            addToCartFormData[element[0]] = element[1];
                        } else {
                            if (!addToCartFormData[keyParts[0]]) {
                                addToCartFormData[keyParts[0]] = {};
                            }
                            addToCartFormData[keyParts[0]][keyParts[1].replace("]", "")] = element[1];
                        }
                    });
                    form.append("add_to_cart_form", JSON.stringify(addToCartFormData));
                }
            }

            var settings = {
                async: true,
                cacheControl: "no-cache",
                data: form,
                method: "POST",
                mimeType: "multipart/form-data",
                url: config.oneClickUrl
            };

            var xhr = new XMLHttpRequest();
            xhr.open(settings.method, settings.url, settings.async);
            xhr.setRequestHeader("cache-control", settings.cacheControl);
            xhr.onload = function () {
                if (200 === xhr.status) {
                    var data = JSON.parse(xhr.responseText);
                    var isErrorsInForm = null;
                    var messageProductViewElem = document.getElementById("messages_product_view");
                    // If not the preload of button run form validation to know if they are errors
                    if (!opts.preload) {
                        if (data && data.has_error && data.message) {
                            isErrorsInForm = true;
                            if ("function" === typeof customMessagesProductView) {
                                // This function allow anyone to display custom message
                                customMessagesProductView(data);
                            } else {
                                if (messageProductViewElem) {
                                    messageProductViewElem.parentNode.removeChild(messageProductViewElem);
                                }

                                appendNotificationMsg(data.message, "error-msg", "product-view");
                            }
                        }
                    }

                    cb(isErrorsInForm, data.url);

                    if (messageProductViewElem) {
                        messageProductViewElem.parentNode.removeChild(messageProductViewElem);
                    }
                }
            };
            xhr.send(form);

            initMutationObserver();
        });
    };

    var allowOystRedirectSelf = false;
    window.addEventListener('message', function(event){
        if (event.data.type == 'ORDER_COMPLETE') {
           allowOystRedirectSelf = false;
        }

        if (event.data.type == 'ORDER_CANCEL') {
           allowOystRedirectSelf = true;
        }

        if (event.data.type == 'MODAL_CLOSE' && allowOystRedirectSelf) {
           window.location.reload(false);
        }
    });
}

/**
 * Wait for DOM ready
 *
 * @param fn
 */
function ready(fn) {
    if (document.attachEvent ? "complete" === document.readyState : "loading" !== document.readyState) {
        fn();
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}

function appendNotificationMsg(message, notificationType, target) {
    var child = document.createElement("span");
    child.innerHTML = message;

    var parent = document.createElement("li");
    parent.append(child);

    child = document.createElement("ul");
    child.append(parent);

    parent = document.createElement("li");
    parent.className = notificationType;
    parent.append(child);

    child = document.createElement("ul");
    child.className = "messages";
    child.append(parent);

    parent = document.createElement("div");
    parent.id = "messages_product_view";
    parent.append(child);

    document.getElementsByClassName(target)[0].before(parent);
}

/**
 * Move dom element as first child
 *
 * @param {HTMLElement} parentElement
 * @param {HTMLElement} newFirstChildElement
 */
function prependChild(parentElement, newFirstChildElement) {
    parentElement.insertBefore(newFirstChildElement, parentElement.firstChild);
}

/**
 * Move OneClick button in first position in add to cart buttons list
 */
function oneClickButtonPickToFirstAddToCartButtons(addtocartButtonsClass) {
    ready(function () {
        try {
            var addToCartButtons = document.getElementsByClassName(addtocartButtonsClass)[0];
            var oystOneClickButton = document.getElementById("oyst-1click-button-wrapper");
            prependChild(addToCartButtons, oystOneClickButton);
        } catch (e) {
            console.error(addtocartButtonsClass + " class not found.");
        }
    });
}

/**
 * Apply add to cart button size on OneClick button
 */
function smartButtonData(addtocartButtonsClass) {
    if (document.getElementById("oyst-1click-button").getAttribute("data-smart")) {
        try {
            var addtocartButtons = document.getElementsByClassName(addtocartButtonsClass);
            var addtocartButton = addtocartButtons[0].getElementsByClassName("button btn-cart");

            document.getElementById("oyst-1click-button").setAttribute("data-height", addtocartButton[0].getHeight() + "px");
            document.getElementById("oyst-1click-button").setAttribute("data-width", addtocartButton[0].getWidth() + "px");
        } catch (e) {
            console.error(addtocartButtonsClass + " class not found.");
        }
    }
}

function isElemVisible(elem) {
    var result = true;

    if (!elem.visible()) {
        result = false;
    } else {
        elem.ancestors().each(function (ancestor) {
            if (!ancestor.visible()) {
                result = false;
            }
        }.bind(this));
    }

    return result;
}

function insertButtonByXPath() {
    var placeToAppendButton = document.getElementById("oyst-1click-button-wrapper").getAttribute("data-place-to-append");
    var breakException = {};

    try {
        placeToAppendButton.split(/\s*,\s*/).forEach(function (xpathExpression) {
            var el = document.evaluate(xpathExpression, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;

            if (null !== el && isElemVisible(el)) {
                var oneClickButtonWrapper = document.getElementById("oyst-1click-button-wrapper");

                if (!el.querySelector("#oyst-1click-button-wrapper")) {
                    el.appendChild(oneClickButtonWrapper);
                }

                // Manage only one button
                throw breakException;
            }
        });
    } catch (e) {
        if (e !== breakException) throw e;
    }
}

function initMutationObserver() {
    if (mutationObserver) {
        mutationObserver.disconnect();
    }

    mutationObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (isElemVisible(mutation.target)) {
                insertButtonByXPath();
                initMutationObserver();
            }
        });
    });

    var targetNode = document.documentElement;
    var mutationOptions = {
        attributes: true,

        attributeOldValue: true,
        characterData: true,

        characterDataOldValue: true,
        childList: true,

        subtree: true,
        attributeFilter: ["style"]
    };

    mutationObserver.observe(targetNode, mutationOptions);
}
