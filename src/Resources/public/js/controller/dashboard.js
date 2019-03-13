'use strict';

define(['pim/controller/front', 'pim/form-builder'],
    function (BaseController, FormBuilder) {
        return BaseController.extend({
            renderForm: function () {
                return FormBuilder.build('pim-textmaster-dashboard-index').then((form) => {
                    this.on('pim:controller:can-leave', function (event) {
                        form.trigger('pim_enrich:form:can-leave', event);
                    });

                    form.setElement(this.$el).render();

                    return form;
                });
            }
        });
    }
);