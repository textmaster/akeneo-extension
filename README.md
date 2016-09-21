# Textmaster extension for Akeneo PIM

## Description

The Textmaster Akeneo extension allows you to easily translate your Akeneo product content using the Textmaster service.

## Installation

First step is to require the sources:
```
composer require akeneo-labs/textmaster-bundle @stable
```

Register your bundle in the `AppKernel.php`

```
$bundles[] = new \Pim\Bundle\TextmasterBundle\TextmasterBundle();
```

Update the database schema:

```
rm app/cache/* -rf
app/console doctrine:schema:update --force
```

Then we need to add a new mass edit batch job:

```
app/console akeneo:batch:create-job 'Textmaster Connector' textmaster_start_projects mass_edit textmaster_start_projects '[]' 'Start TextMaster project'
```


### Parameters

You can configure your TextMaster plugin in the dedicated screen: `System >> Configuration >> TextMaster`

In this screen you will be able to set:

- you API credentials : `API key` and `API secret`
- the attributes you want to translate
