/* global define */
define(['oro/datagrid/model-action'],
    function(ModelAction) {
        'use strict';

        /**
         * Navigate action. Changes window location to url, from getLink method
         *
         * @export  oro/datagrid/navigate-action
         * @class   oro.datagrid.NavigateAction
         * @extends oro.datagrid.ModelAction
         */
        return ModelAction.extend({

            /**
             * If `true` then created launcher will be complete clickable link,
             * If `false` redirection will be delegated to execute method.
             *
             * @property {Boolean}
             */
            useDirectLauncherLink: true,

            /**
             * Execute redirect
             */
            execute: function() {
                window.open(this.getLink(), '_blank');
            }
        });
    }
);