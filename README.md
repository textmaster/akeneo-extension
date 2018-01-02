# Textmaster extension for Akeneo PIM

[![Build Status](https://travis-ci.org/textmaster/akeneo-extension.svg?branch=master)](https://travis-ci.org/textmaster/akeneo-extension)

Also available on the Akeneo marketplace: https://marketplace.akeneo.com/

## Description

The Textmaster Akeneo extension allows you to easily translate your Akeneo product contents to a large quantity of languages with a simple mass edit process.

## Requirements

| Akeneo Textmaster extension | Akeneo PIM Community Edition |
|:---------------------------:|:----------------------------:|
| v1.2.*                      | v1.7.*                       |
| v1.1.*                      | v1.6.*                       |
| v1.0.*                      | v1.5.*                       |

You also need a Textmaster account to have some API credentials and access to the Textmaster's customer interface.

#### Create a Textmaster account

Creating your account on https://textmaster.com is totally free. You can access the register form by clicking on the "Login" button or by following [this link](https://textmaster.com/sign_up).

## How it works

![mass edit screen](doc/img/mass-edit-01.png)

The translation request is done by a very simple mass edit process:

- Select your products in the grid and choose the "translate with Textmaster" mass edit operation.
- Choose your source language and the many target languages you want translation for.
- Send your products to Textmaster in just one click
- You can then connect to your Textmaster client interface to choose more options, like translation memory, preferred Textmasters, etc. Your products will be translated in the PIM as soon as they are in Textmaster

## Installation

First step is to require the sources:
```
composer require textmaster/akeneo-extension 1.2.*
```

Register your bundle in the `AppKernel::registerProjectBundles`:

```
new \Pim\Bundle\TextmasterBundle\PimTextmasterBundle();
```

Then we need to add a new mass edit batch job:

```
app/console akeneo:batch:create-job 'Textmaster Connector' 'textmaster_start_projects' 'mass_edit' 'textmaster_start_projects' '{}' 'Start TextMaster project'
```

We must put the mass edit form template at the right place:

```
mkdir -p app/Resources/PimEnrichBundle/views/MassEditAction/product/configure
cp vendor/textmaster/akeneo-extension/src/Resources/views/MassEditAction/configure/textmaster_start_projects.html.twig app/Resources/PimEnrichBundle/views/MassEditAction/product/configure/
```

Update the database schema and regenerate your cache and assets:

```
rm app/cache/* -rf
app/console doctrine:schema:update --force
rm -rf app/cache/* web/bundles/* web/css/* web/js/* ; app/console pim:install:assets
```

Finally, you must set a `cron` to retrieve the translated contents from Textmaster:
```
0 * * * * /home/akeno/pim/app/console pim:textmaster:retrieve-translations >> /tmp/textmaster.log
```

This command checks for translated content once every hour. We do not recommend to check more often than every hour to not overload the Textmaster servers.

### Parameters

You can configure your TextMaster plugin in the dedicated screen: `System >> Configuration >> TextMaster`

![configuration screen](doc/img/configuration-01.png)

In this screen you will be able to set:

- you API credentials : `API key` and `API secret`
- the attributes you want to translate

## Video demo

A live demonstration is available on this short video: https://www.youtube.com/watch?v=9WkyQFwoWWo

