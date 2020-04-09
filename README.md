# Textmaster extension for Akeneo PIM

[![Build Status](https://travis-ci.org/textmaster/akeneo-extension.svg?branch=master)](https://travis-ci.org/textmaster/akeneo-extension)

Also available on the Akeneo marketplace: https://marketplace.akeneo.com/

## Description

The Textmaster Akeneo extension allows you to easily translate your Akeneo product contents to a large quantity of languages with a simple mass edit process.

## NOTES 
- Please know that this module is not compatible with other TextMaster modules. If you want to use another TextMaster module, make sure to use another TextMaster account. 
- The TextMaster module with Akeneo is not compatible with the feature ‘Organization’ we recently released. It is, however, in our roadmap. We invite you to consult it or contact us if you have any request. 


## Requirements

| Akeneo Textmaster extension | Akeneo PIM Community Edition |
|:---------------------------:|:----------------------------:|
| v4.0.*                      | v4.0.*                       |
| v3.2.*                      | v3.2.*                       |
| v3.0.*                      | v3.0.*                       |
| v2.4.*                      | v2.3.*                       |
| v2.3.*                      | v2.3.*                       |
| v2.2.*                      | v2.2.*                       |
| v2.1.*                      | v2.1.*                       |
| v2.0.*                      | v2.0.*                       |
| v1.3.*                      | v1.7.*                       |
| v1.2.*                      | v1.7.*                       |
| v1.1.*                      | v1.6.*                       |
| v1.0.*                      | v1.5.*                       |


You also need a Textmaster account to have some API credentials and access to the Textmaster's customer interface.

### Create a Textmaster account

You can create a sandbox account for testing purpose at app.textmasterstaging.com

When you are ready, you can create your account on https://textmaster.com It's totally free. You can access the register form by clicking on the "Login" button or by following [this link](https://textmaster.com/sign_up).

### Create one or more API templates

This extension uses Textmaster API templates.
You must have at least one API template before using this extension.

## How it works

![mass edit screen](doc/img/mass-edit-01.png)

The translation request is done by a very simple mass edit process:

- Select your products in the grid and choose the "translate with Textmaster" mass edit operation.
- Choose the API template used for this translation project. [API templates are explained in this documentation](doc/resources/API_EN_v2.pdf)
- Send your products to Textmaster in just one click
- You can then connect to your Textmaster client interface to choose more options, like translation memory, preferred Textmasters, etc. Your products will be translated in the PIM as soon as they are in Textmaster

You can check translation progress with the dashboard :

![dashboard screen](doc/img/dashboard-01.png)

## Installation

First step is to require the sources:
```
composer require textmaster/akeneo-extension ~3.0
```

Register the bundle in `AppKernel::registerProjectBundles`:

```
new \Pim\Bundle\TextmasterBundle\PimTextmasterBundle()
```

Then we need to add a new mass edit batch job:

```
bin/console akeneo:batch:create-job 'Textmaster Connector' 'textmaster_start_projects' "mass_edit" 'textmaster_start_projects'
```

Make sure you have native Akeneo script `bin/console akeneo:batch:job-queue-consumer-daemon` running.

You can also add `&` to the end of the command to make it run in the background: `bin/console akeneo:batch:job-queue-consumer-daemon &`

Add the new routes used by the extension to the global router. Add the following route to your configuration:

```
textmaster:
    resource: "@PimTextmasterBundle/Resources/config/routing.yml"
```

Optional : Add parameters into app/config/parameters.yml to use textmaster sandbox :

```
parameters:
    ...
    textmaster.base_uri.api: 'https://api.textmasterstaging.com/v1'
    textmaster.base_uri.app: 'https://app.textmasterstaging.com'
```

Update the database schema and regenerate your cache and assets:

```
rm -rf var/cache/* web/bundles/* web/js/* web/css/*
bin/console doctrine:schema:update --force --env=prod
bin/console p:i:a --env=prod
bin/console a:i --env=prod
node yarn run webpack
find ./ -type d -exec chmod 755 {} \;
find ./ -type f -exec chmod 644 {} \;
```

Set a `cron` to synchronize projects & documents between Akeneo and Textmaster:
```
0 * * * * /path/to/bin/console pim:textmaster:processing >> /tmp/textmaster.log
```

This command checks for translated content once every hour and outputs log to `/tmp/textmaster.log`. We do not recommend to check more often than every hour to not overload the Textmaster servers.
Note that you should select products that have non-empty translatable attributes and the attributes are added in `System >> Configuration >> TextMaster`.

To see projects that are registered on TextMaster, run:
```
bin/console pim:textmaster:list-projects
```

### Parameters

You can configure your TextMaster plugin in the dedicated screen: `System >> Configuration >> TextMaster`

![configuration screen](doc/img/configuration-01.png)

In this screen you will be able to set:

- you API credentials : `API key` and `API secret`
- the attributes you want to translate

## Screenshots

![Select products](doc/img/01-select-products.png)

![Select Textmaster action](doc/img/02-select-action.png)

![Configure the project](doc/img/03-configure-project.png)

![Execution details](doc/img/04-execution-details.png)

## Video demo

A live demonstration for the 1.2 version of this extension is available on this short video:
https://www.youtube.com/watch?v=9WkyQFwoWWo
