"use strict";

define(
    [
        'underscore',
        'oro/translator',
        'routing',
        'pim/form',
        'pim/fetcher-registry',
        'pim/formatter/choices/base',
        'pim/initselect2',
        'textmaster/template/system/group/configuration'
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

            configure() {
                this.trigger('tab:register', {
                    code: this.code,
                    label: this.label
                });

                return BaseForm.prototype.configure.apply(this, arguments);
            },

            /**
             * {@inheritdoc}
             */
            render() {
                this.$el.html(this.template({
                    apisecret: this.getFormData()['pim_textmaster___api_secret'] ?
                        this.getFormData()['pim_textmaster___api_secret'].value : '',
                    apikey: this.getFormData()['pim_textmaster___api_key'] ?
                        this.getFormData()['pim_textmaster___api_key'].value : '',
                    attributes: this.getFormData()['pim_textmaster___attributes'] ?
                        this.getFormData()['pim_textmaster___attributes'].value : ''
                }));

                const searchOptions = {
                    options: {
                        limit: 200,
                        types: [
                            'pim_catalog_text',
                            'pim_catalog_textarea'
                        ]
                    }
                };

                FetcherRegistry.getFetcher('attribute').search(searchOptions)
                    .then(function (attributes) {
                        const choices = _.chain(attributes)
                            .filter(function (attribute) {
                                return attribute.localizable;
                            })
                            .map(function (attribute) {
                                return ChoicesFormatter.formatOne(attribute);
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
            }
            ,

            /**
             * Update model after value change
             *
             * @param {Event} event
             */
            updateModel: function (event) {
                const name = event.target.name;
                const data = this.getFormData();
                let newValue = event.target.value;
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
)
;
