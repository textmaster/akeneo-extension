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
                        briefing: '',
                        attributes: [],
                        fromLocale: UserContext.get('catalogLocale'),
                        toLocales: [],
                        category: null
                    },
                    this.getFormData().actions[0]
                );

                this.$el.html(this.template(data));
                this.initLocales(data.fromLocale);
                this.initCategories();

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
                        briefing: '',
                        fromLocale: UserContext.get('catalogLocale'),
                        toLocales: [],
                        category: null,
                        username: UserContext.get('username')
                    },
                    data.actions[0],
                    {[field]: value}
                );
                this.setData(data);
            },

            /**
             * @param {String} fromLocale
             */
            initLocales(fromLocale) {
                const localeFetcher = FetcherRegistry.getFetcher('locale');

                localeFetcher.fetchActivated()
                    .then(locales => {
                        const choices = _.chain(locales)
                            .map(function (locale) {
                                return {
                                    id: locale.code,
                                    text: locale.label
                                };
                            })
                            .value();
                        initSelect2.init(this.$('#textmaster-locale-from'), {
                            data: choices,
                            multiple: false,
                            containerCssClass: 'input-xxlarge'
                        }).select2('val', fromLocale);
                        initSelect2.init(this.$('#textmaster-locales-to'), {
                            data: choices,
                            multiple: true,
                            containerCssClass: 'input-xxlarge'
                        });
                    });
            },

            initCategories() {
                const fetcher = FetcherRegistry.getFetcher('textmaster-categories');
                const defaultCategory = this.config.defaultCategory;

                fetcher.fetchAll()
                    .then(categories => {
                        const choices = _.chain(categories)
                            .map((label, code) => {
                                return {
                                    id: code,
                                    text: `${code} - ${label}`
                                };
                            })
                            .value();
                        initSelect2.init(this.$('#textmaster-category'), {
                            data: choices,
                            multiple: false,
                            containerCssClass: 'input-xxlarge'
                        }).select2('val', defaultCategory);
                    });
            }
        });
    }
);
