# Oyst 1-Click plugin for Magento

[![Build Status](https://travis-ci.org/oystparis/oyst-1click-magento.svg?branch=master)](https://travis-ci.org/oystparis/oyst-1click-magento)
[![Latest Stable Version](https://img.shields.io/badge/latest-1.0.0-green.svg)](https://github.com/oystparis/oyst-1click-magento/releases)
[![Magento = 1.7.x.x](https://img.shields.io/badge/magento-1.7-blue.svg)](#)
[![Magento = 1.8.x.x](https://img.shields.io/badge/magento-1.8-blue.svg)](#)
[![Magento = 1.9.x.x](https://img.shields.io/badge/magento-1.9-blue.svg)](#)
[![Magento = 1.12.x.x](https://img.shields.io/badge/magento-1.12-blue.svg)](#)
[![Magento = 1.13.x.x](https://img.shields.io/badge/magento-1.13-blue.svg)](#)
[![Magento = 1.14.x.x](https://img.shields.io/badge/magento-1.14-blue.svg)](#)
[![PHP >= 5.3](https://img.shields.io/badge/php-%3E=5.3-green.svg)](#)

You can sign up for an Oyst account at https://backoffice.oyst.com/signup.

This is the Oyst 1-Click plugin for Magento 1.x.
The plugin supports the Magento Community and Enterprise edition.

We commit all our new features directly into our GitHub repository.
But you can also request or suggest new features or code changes yourself!

## Installation

### Via Magento Downloader

1. Download the last package [Oyst_OneClick-x.x.x.tgz](https://github.com/oystparis/oyst-1click-magento/releases) from the official releases ;
2. Login into the backend, go to `System` — `Cache Management` and __enable__ all types of cache ;
3. Go to `System` — `Tools` — `Compilation` and make sure compilation is disabled. It should display “_Compiler Status: Disabled_” on that page ;
4. Go to `System` — `Magento Connect` — `Magento Connect Manager` and upload your file ;
5. Go to `System` — `Cache Management` page under Magento backend and click “_Flush Cache Storage_” button. After this action, the extension is installed ;
6. If you need to enable compilation, you can do it now at `System` — `Tools` — `Compilation` ;
7. Please log out of the backend and log in again, so Magento can refresh permissions.

### Via FTP/SFTP/SSH

1. Download the last package [Oyst_OneClick-x.x.x.tgz](https://github.com/oystparis/oyst-1click-magento/releases) from the official releases and unzip ;
2. Login into the backend, go to `System` — `Cache Management` and __enable__ all types of cache ;
3. Go to `System` — `Tools` — `Compilation` and make sure compilation is disabled. It should display “_Compiler Status: Disabled_” on that page ;
4. Connect to your website source folder with FTP/SFTP/SSH and upload all the extension files and folders of the extension package to the root folder of your Magento installation:
> /!\ Please use the “__Merge__” upload mode. Do not replace the whole folders, but merge them. This way your FTP/SFTP client will only add new files. This mode is used by default by most of FTP/SFTP clients software. For MacOS it’s recommended to use Transmit.
5. Go to `System` — `Cache Management` page under Magento backend and click “_Flush Cache Storage_” button. After this action, the extension is installed ;
6. If you need to enable compilation, you can do it now at `System` — `Tools` — `Compilation` ;
7. Please log out of the backend and log in again, so Magento can refresh permissions.

### By cloning this repository

1. Login into the backend, go to `System` — `Cache Management` and __enable__ all types of cache ;
2. Go to `System` — `Tools` — `Compilation` and make sure compilation is disabled. It should display “_Compiler Status: Disabled_” on that page ;
3. Clone this repository ;
4. Copy the `app`, `js`, `lib`, `skin` folders into the root folder of your Magento ;
5. Exec `./lib/Oyst/get-sdk.sh`.

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

## Support

You can create issues on our Magento Repository or if you have some specific problems for your account you can contact <a href="mailto:plugin@oyst.com">plugin@oyst.com</a> as well.
