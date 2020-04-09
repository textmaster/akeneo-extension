'use strict';
/**
 * Mass edit operation: send to TextMaster
 *
 * @author    JM Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2017 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
        'underscore',
        'pim/mass-edit-form/product/operation',
        'textmaster/template/mass_edit',
        'pim/user-context',
        'pim/fetcher-registry',
        'pim/formatter/choices/base',
        'pim/initselect2',
        'datepicker',
        'pim/date-context',
        'pim/formatter/date',
        'oro/messenger'
    ],
    function (
        _,
        BaseOperation,
        template,
        UserContext,
        FetcherRegistry,
        ChoicesFormatter,
        initSelect2,
        Datepicker,
        DateContext,
        DateFormatter,
        messenger
    ) {
        let attributeOptions = [
            {
                id: 'defaultAttributes',
                text: 'Default attributes (set in System -> TextMaster -> Settings)'
            },
            {
                id: 'customSelection',
                text: 'Custom selection'
            },
            {
                id: 'updatedInDateRange',
                text: 'Default attributes updated in a date range'
            },
        ];
        const attributeOptionDefault = attributeOptions[0].id;
        const attributeOptionCustom = attributeOptions[1].id;
        const attributeOptionDateRange = attributeOptions[2].id;

        const fields = {
            name: {
                label: 'Project Name',
                defaultValue: '',
                schema: {
                    isRequired: true,
                },
                errorContainerSelector: '#name-error',
            },
            apiTemplates: {
                label: 'API Templates',
                defaultValue: [],
                schema: {
                    isRequired: true,
                },
                errorContainerSelector: '#api-templates-error',
            },
            attributeOption: {
                label: 'Attribute Option',
                defaultValue: attributeOptionDefault,
                schema: {},
            },
            personalizedAttributes: {
                label: 'Attributes',
                defaultValue: [],
                schema: {
                    isRequired: true,
                },
                errorContainerSelector: '#personalized-attributes-error',
            },
            dateRangeStartsAt: {
                label: 'From Date',
                defaultValue: '',
                schema: {
                    isRequired: true,
                },
                errorContainerSelector: '#start-date-error',
            },
            dateRangeEndsAt: {
                label: 'To Date',
                defaultValue: '',
                schema: {
                    isRequired: true,
                },
                errorContainerSelector: '#end-date-error',
            },
        };

        const defaultValues = {
            dateRangeStartsAtFormatted: '',
            dateRangeEndsAtFormatted: '',
            username: UserContext.get('username'),
        };

        Object.keys(fields).map(function (fieldName) {
            defaultValues[fieldName] = fields[fieldName].defaultValue;
        });

        return BaseOperation.extend({
            template: _.template(template),

            events: {
                'change .textmaster-field': 'updateModel',
                'change #textmaster-attribute-option': 'onAttributeOptionChange',
            },

            render() {
                const data = Object.assign({}, defaultValues, this.getFormData().actions[0]);

                this.$el.html(this.template(data));
                this.initApiTemplates();
                this.initAttributeOptions();
                this.renderExtraFields(data.attributeOption);

                return this;
            },

            validate() {
                const formData = this.getFormData().actions[0];
                let isValid = true;

                if (!formData) {
                    messenger.notify('error', 'Please fill the information!');
                    return $.Deferred().resolve(false);
                }

                const notifyFieldError = function (fieldName) {
                    isValid = false;
                    messenger.notify('error', `Field ${fields[fieldName].label} is not valid.`);
                };

                if (!this.validateField('name', formData.name)) {
                    notifyFieldError('name');
                }

                if (!this.validateField('apiTemplates', formData.apiTemplates)) {
                    notifyFieldError('apiTemplates');
                }

                if (formData.attributeOption === attributeOptionCustom) {
                    if (!this.validateField('personalizedAttributes', formData.personalizedAttributes)) {
                        notifyFieldError('personalizedAttributes');
                    }
                } else if (formData.attributeOption === attributeOptionDateRange) {
                    const isStartDateValid = this.validateField('dateRangeStartsAt', formData.dateRangeStartsAt);
                    const isEndDateValid = this.validateField('dateRangeEndsAt', formData.dateRangeEndsAt);

                    if (!isStartDateValid) {
                        notifyFieldError('dateRangeStartsAt');
                    }

                    if (!isEndDateValid) {
                        notifyFieldError('dateRangeEndsAt');
                    }

                    if (isStartDateValid && isEndDateValid && formData.dateRangeEndsAt < formData.dateRangeStartsAt) {
                        isValid = false;
                        messenger.notify('error', '"End Date" must not be before "Start Date"');
                    }
                }

                return $.Deferred().resolve(isValid);
            },

            onAttributeOptionChange(event) {
                this.renderExtraFields(event.target.value);
            },

            renderExtraFields(attributeOption) {
                if (attributeOption === attributeOptionCustom) {
                    this.initAttributesSelection();
                    this.hideDateRangeSelection();
                } else if (attributeOption === attributeOptionDefault) {
                    this.hideAttributeSelection();
                    this.hideDateRangeSelection();
                } else if (attributeOption === attributeOptionDateRange) {
                    this.hideAttributeSelection();
                    this.renderDateFields();
                }
            },

            /**
             * {@inheritDoc}
             */
            updateModel(event) {
                const target = event.target;
                this.setValue(target.name, target.value);
            },

            /**
             * Replace actions[0] with an updated version.
             * #immutability
             *
             * @param {String} field Name of the input field
             * @param {string} value Value of the input field
             */
            setValue(field, value) {
                this.validateField(field, value);

                const data = this.getFormData();
                data.actions[0] = Object.assign({}, defaultValues, data.actions[0], {[field]: value});
                this.setData(data);
            },

            validateField(fieldName, value) {
                let isValid = true;

                if (fields[fieldName]) {
                    const $errorContainer = this.$(fields[fieldName].errorContainerSelector);

                    if (fields[fieldName].schema.isRequired && value.length === 0) {
                        isValid = false;
                        $errorContainer.text('This field is required.');
                    } else {
                        $errorContainer.text('');
                    }
                }

                return isValid;
            },

            initApiTemplates() {
                const fetcher = FetcherRegistry.getFetcher('textmaster-api-templates');

                fetcher.fetchAll()
                    .then(apiTemplates => {
                        const choices = _.chain(apiTemplates)
                            .map(apiTemplate => {
                                return {
                                    id: apiTemplate.id,
                                    text: `[${apiTemplate.language_from} to ${apiTemplate.language_to}] ${apiTemplate.name}`
                                };
                            })
                            .value();
                        initSelect2.init(this.$('#textmaster-api-templates'), {
                            data: choices,
                            multiple: true,
                            containerCssClass: 'input-xxlarge'
                        });
                    });
            },

            initAttributeOptions() {
                initSelect2.init(this.$('#textmaster-attribute-option'), {
                    minimumResultsForSearch: -1, // disable search box
                    data: attributeOptions,
                    multiple: false,
                    containerCssClass: 'input-xxlarge'
                })
            },

            hideAttributeSelection() {
                const $field = this.$('#textmaster-personalized-attributes');
                const $fieldContainer = $field.closest('.AknFieldContainer');
                $fieldContainer.hide();
            },

            hideDateRangeSelection() {
                const $container = this.$('.date-range-fields-container');
                $container.hide();
            },

            initAttributesSelection() {
                const $field = this.$('#textmaster-personalized-attributes');
                const $fieldContainer = $field.closest('.AknFieldContainer');
                const fetcher = FetcherRegistry.getFetcher('textmaster-default-attributes');

                fetcher.getJSON(fetcher.options.urls.list)
                    .then(function (attributes) {
                        const choices = _.chain(attributes)
                            .map(function (attributeCode) {
                              return {id: attributeCode, text: attributeCode};
                            })
                            .value();

                        initSelect2.init($field, {
                            data: choices,
                            multiple: true,
                            containerCssClass: 'input-xxlarge'
                        });

                        const formData = this.getFormData().actions[0];
                        if (formData.personalizedAttributes.length === 0) {
                            $field.val(choices.map(choice => choice.id).join(','));
                        }

                        $field.trigger('change');
                        $fieldContainer.show();
                    }.bind(this));
            },

            renderDateFields() {
                const $container = this.$('.date-range-fields-container');
                $container.show();

                const datepickerOptions = {
                    format: DateContext.get('date').format,
                    defaultFormat: DateContext.get('date').defaultFormat,
                    language: DateContext.get('language'),
                };

                Datepicker.init(this.$('.datetimepicker'), datepickerOptions)
                    .on('changeDate', function (e) {
                        const $datetimepicker = this.$(e.currentTarget);
                        const $input = $datetimepicker.find('.datepicker-field');
                        const dateStringToDisplay = $input.val();
                        const dateStringToSubmit = DateFormatter.format(
                            dateStringToDisplay,
                            datepickerOptions.format, // current format
                            'yyyy-MM-dd' // target format
                        );

                        this.setValue($input.prop('name'), dateStringToSubmit);
                        this.setValue($input.prop('name') + 'Formatted', dateStringToDisplay);
                    }.bind(this));
            }
        });
    }
);
