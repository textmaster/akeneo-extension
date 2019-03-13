'use strict';

define([
    'underscore',
    'textmaster/template/form/dashboard/status'
], function (
    _,
    template
) {
    return ({

        /**
         * {@inheritdoc}
         */
        render: function (statusParams) {
            return _.template(template)({
                name: statusParams['name'],
                rate: statusParams['rate']
            });
        }
    });
});

