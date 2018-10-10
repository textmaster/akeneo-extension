'use strict';

define([
    'jquery',
    'underscore',
    'oro/translator',
    'pim/form',
    'textmaster/template/form/dashboard',
    'textmaster/form/dashboard/status',
    'pim/fetcher-registry',
    'routing',
    'oro/datagrid-builder',
    'oro/pageable-collection',
    'pim/datagrid/state',
    'require-context'
], function (
    $,
    _,
    __,
    BaseForm,
    template,
    statusTemplate,
    FetcherRegistry,
    Routing,
    datagridBuilder,
    PageableCollection,
    DatagridState,
    requireContext
) {
    return BaseForm.extend({

        /**
         * {@inheritdoc}
         */
        render: function () {

            this.$el.empty().html(
                _.template(template)()
            );

            this.renderDocumentStatuses();
            this.renderGrid();

            this.delegateEvents();

            return this;
        },

        renderDocumentStatuses() {
            const fetcher = FetcherRegistry.getFetcher('textmaster-dashboard-status-data');
            const widgetContainer = this.$el.find('#textmaster-status-widget').find('.AknCompletenessPanel');

            fetcher.clear();

            fetcher.fetchAll().then(function (data) {
                if (data.error) {
                    console.error(data.error);
                } else {
                    _.each(data, function (statusData) {
                        widgetContainer.append(statusTemplate.render(statusData));
                    }.bind(this));
                }
            });
        },

        /**
         * Render grid.
         */
        renderGrid: function () {
            const urlParams = {
                alias: 'document-grid'
            };

            const dataGridState = DatagridState.get(urlParams.alias, ['filters']);
            if (null !== dataGridState.filters) {
                const collection = new PageableCollection();

                const filters = collection.decodeStateData(dataGridState.filters);

                collection.processFiltersParams(urlParams, filters, urlParams.alias + '[_filter]');
            }

            const gridName = urlParams.alias;
            $.get(Routing.generate('pim_datagrid_load', urlParams)).then(function (response) {
                const metadata = response.metadata;

                this.$('#grid-' + gridName).data({metadata: metadata, data: JSON.parse(response.data)});

                const gridModules = metadata.requireJSModules;
                gridModules.push('pim/datagrid/state-listener');
                gridModules.push('oro/datafilter-builder');
                gridModules.push('oro/datagrid/pagination-input');

                const resolvedModules = [];
                _.each(gridModules, function (module) {
                    resolvedModules.push(requireContext(module));
                });
                datagridBuilder(resolvedModules)
            }.bind(this));
        }
    });
});

