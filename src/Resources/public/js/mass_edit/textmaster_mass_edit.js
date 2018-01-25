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
        'pim/initselect2'
    ],
    function (
        _,
        BaseOperation,
        template,
        UserContext,
        FetcherRegistry,
        ChoicesFormatter,
        initSelect2
    ) {
        return BaseOperation.extend({
            template: _.template(template),

            events: {
                'change .textmaster-field': 'updateModel'
            },

            render() {
                const data = Object.assign(
                    {},
                    {
                        name: '',
                        apiTemplates: []
                    },
                    this.getFormData().actions[0]
                );

                this.$el.html(this.template(data));
                this.initApiTemplates();

                return this;
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
                const data = this.getFormData();
                data.actions[0] = Object.assign(
                    {},
                    {
                        name: '',
                        apiTemplates: [],
                        username: UserContext.get('username')
                    },
                    data.actions[0],
                    {[field]: value}
                );
                this.setData(data);
            },

            initApiTemplates: function () {
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
            }
        });
    }
);
