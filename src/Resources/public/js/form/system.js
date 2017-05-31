"use strict";

define([
        'underscore',
        'oro/translator',
        'routing',
        'pim/form',
        'pim/fetcher-registry',
        'pim/formatter/choices/base',
        'pim/initselect2',
        'text!textmaster/template/system/group/configuration'
    ],
    function (_,
              __,
              Routing,
              BaseForm,
              FetcherRegistry,
              ChoicesFormatter,
              initSelect2,
              template) {
        return BaseForm.extend({
            events: {
                'change .texmaster-config': 'updateModel'
            },
            isGroup: true,
            label: __('textmaster.configuration.tab.label'),
            template: _.template(template),

            /**
             * {@inheritdoc}
             */
            render: function () {
                this.$el.html(this.template({
                    apisecret: this.getFormData()['pim_textmaster___api_secret'] ?
                        this.getFormData()['pim_textmaster___api_secret'].value : '',
                    apikey: this.getFormData()['pim_textmaster___api_key'] ?
                        this.getFormData()['pim_textmaster___api_key'].value : '',
                    attributes: this.getFormData()['pim_textmaster___attributes'] ?
                        this.getFormData()['pim_textmaster___attributes'].value : '',
                    autolaunch: this.getFormData()['pim_textmaster___autolaunch'] ?
                        this.getFormData()['pim_textmaster___autolaunch'].value == true : false
                }));

                var searchOptions = {
                    options: {
                        types: [
                            'pim_catalog_text',
                            'pim_catalog_textarea'
                        ]
                    }
                };

                FetcherRegistry.getFetcher('attribute').search(searchOptions)
                    .then(function (attributes) {
                        var choices = _.chain(attributes)
                            .filter(function (attribute) {
                                return attribute.localizable;
                            })
                            .map(function (attribute) {
                                var attributeGroup = ChoicesFormatter.formatOne(attribute.group);
                                var attributeChoice = ChoicesFormatter.formatOne(attribute);
                                attributeChoice.group = attributeGroup;

                                return attributeChoice;
                            })
                            .value();
                        initSelect2.init(this.$('input.select-field'), {
                            data: choices,
                            multiple: true,
                            containerCssClass: 'input-xxlarge'
                        });
                    }.bind(this));

                this.$('.switch').bootstrapSwitch();

                this.delegateEvents();

                return BaseForm.prototype.render.apply(this, arguments);
            },

            /**
             * Update model after value change
             *
             * @param {Event}
             */
            updateModel: function (event) {
                var name = event.target.name;
                var data = this.getFormData();
                var newValue = event.target.value;
                if ('checkbox' == $(event.target).attr('type')) {
                    newValue = $(event.target).prop('checked') ? true : false;
                }
                if (name in data) {
                    data[name].value = newValue;
                } else {
                    data[name] = {value: newValue};
                }
                this.setData(data);
            }
        });
    }
);
