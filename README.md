# Oyst 1-Click plugin for Magento

[![Build Status](https://travis-ci.org/oystparis/oyst-1click-magento.svg?branch=master)](https://travis-ci.org/oystparis/oyst-1click-magento)
[![Latest Stable Version](https://img.shields.io/badge/latest-0.2.2-green.svg)](https://github.com/oystparis/oyst-1click-magento/releases)
[![Magento = 1.7.x.x](https://img.shields.io/badge/magento-1.7-blue.svg)](#)
[![Magento = 1.8.x.x](https://img.shields.io/badge/magento-1.8-blue.svg)](#)
[![Magento = 1.9.x.x](https://img.shields.io/badge/magento-1.9-blue.svg)](#)
[![Magento = 1.12.x.x](https://img.shields.io/badge/magento-1.12-blue.svg)](#)
[![Magento = 1.13.x.x](https://img.shields.io/badge/magento-1.13-blue.svg)](#)
[![Magento = 1.14.x.x](https://img.shields.io/badge/magento-1.14-blue.svg)](#)
[![PHP >= 5.3](https://img.shields.io/badge/php-%3E=5.3-green.svg)](#)

You can sign up for an Oyst account at https://admin.free-pay.com.

This is the Oyst 1-Click plugin for Magento 1.x.
The plugin supports the Magento Community and Enterprise edition.

We commit all our new features directly into our GitHub repository.
But you can also request or suggest new features or code changes yourself!

## Installation

After all install method:
- Go to `Admin Panel` -> `System` -> `Cache Management` and flush all caches ;
- Logout of `Admin Panel` and login again ;
- Go to `System` -> `Configuration` -> `1-Click` and specify your configuration settings.

After this your website is ready to accept payments through 1-Click.

### Install using Composer

Add the repository
```
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/oystparis/oyst-1click-magento"
    }
  ]
```
and script block to your ```composer.json```.
```
  "scripts": {
    "post-install-cmd": [
      "./.modman/oyst-1click-magento/lib/Oyst/get-sdk.sh"
    ],
    "post-update-cmd": [
      "./.modman/oyst-1click-magento/lib/Oyst/get-sdk.sh"
    ]
  }
```
Then run the composer installer: `composer require oyst/oyst-1click-magento`

### Install using modman

This is the preferred installation method, unless installing manually.
```
$ modman init
$ modman clone https://github.com/oystparis/oyst-1click-magento
$ ./lib/Oyst/get-sdk.sh
```

### Manual installation

#### By cloning this repository

* Clone this repository
* Copy the `app` folder into your Magento codebase
* Copy the `js` folder into your Magento codebase
* Copy the `lib` folder into your Magento codebase
* Copy the `skin` folder into your Magento codebase
* Exec `./lib/Oyst/get-sdk.sh`

#### By downloading an official releases

* [Click here to see and download the official releases of the Oyst 1-Click module](https://github.com/oystparis/oyst-1click-magento/releases)
* Unzip
* Copy the contents of folder inside your Magento root directory.

## Support

You can create issues on our Magento Repository or if you have some specific problems for your account you can contact <a href="mailto:plugin@oyst.com">plugin@oyst.com</a> as well.
