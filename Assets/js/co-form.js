// Init stuff on refresh:
mQuery(function() {
    CustomObjectsForm.onLoad();
});

CustomObjectsForm = {

    /**
     * Custom object form events
     */
    onLoad: function () {
        CustomObjectsForm.initAdder();
        CustomObjectsForm.initSortable();
        mQuery('.panel').each(function (i, panel) {
            CustomObjectsForm.initPanel(panel);
        });
    },

    /**
     * Init CF adding feature
     */
    initAdder: function() {
        mQuery('select.form-builder-new-component').change(function (e) {
            mQuery(this).find('option:selected');
            CustomObjectsForm.showModal(mQuery(this).find('option:selected'));
            // Reset the dropdown
            mQuery(this).val('');
            mQuery(this).trigger('chosen:updated');
        });
    },

    /**
     * Init CF sorting feature
     */
    initSortable: function () {
        if (mQuery('#mauticforms_fields .drop-here')) {
            // Make the fields sortable
            mQuery('#mauticforms_fields .drop-here').sortable({
                items: '.panel',
                cancel: '',
                helper: function(e, ui) {
                    // Before sorting
                    ui.children().each(function() {
                        mQuery(this).width(mQuery(this).width());
                    });

                    return ui;
                },
                scroll: true,
                axis: 'y',
                containment: '#mauticforms_fields .drop-here',
                stop: function(e, ui) {
                    mQuery(ui.item).attr('style', '');
                    CustomObjectsForm.recalculateOrder();
                }
            });

            Mautic.initFormFieldButtons();
        }
    },

    /**
     * Recalculate CF order
     */
    recalculateOrder: function() {
        mQuery('.drop-here').find('[id*=order]').each(function(i, selector) {
            mQuery(selector).val(i)
                .parent().attr('id', 'customField_' + i);
        });
    },

    /**
     * Init CF panel events (except sortable)
     * @param panel
     */
    initPanel: function(panel) {
        CustomObjectsForm.initEditFieldButton(panel);
        CustomObjectsForm.initDeleteFieldButton(panel);
    },

    /**
     * Init ajax modal on .panel element
     * @param panel
     */
    initEditFieldButton: function(panel) {
        mQuery(panel).find('button.btn-edit')
            .unbind('click')
            .bind('click', function (event) {
                event.preventDefault();
                CustomObjectsForm.showModal(mQuery(this));
            });

        CustomObjectsForm.initSortable();
    },

    /**
     * Init CF delete button
     * @param panel
     */
    initDeleteFieldButton: function(panel) {
        mQuery(panel).find('[data-hide-panel]')
            .unbind('click')
            .click(function(e) {
                e.preventDefault();
                let panel = mQuery(this).closest('.panel');
                panel.hide('fast');
                panel.find('[id*=deleted]').val(1);
            });
    },

    showModal: function(element) {
        let target = element.attr('data-target');
        if (element.attr('href')) {
            var route = element.attr('href');
            var edit = true;
        } else {
            var route = element.attr('data-href');
            var edit = false;
        }

        mQuery('body').addClass('noscroll');
        mQuery(target).find('.loading-placeholder').removeClass('hide');
        mQuery(target).modal('show');

        // Fill modal with form loaded via ajax
        mQuery(target).on('shown.bs.modal', function() {
            // Fill modal with form loaded via ajax
            mQuery.ajax({
                url: route,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response) {
                        Mautic.processModalContent(response, target);
                    }
                    if (edit) {
                        let panel = element.closest('.panel');
                        CustomObjectsForm.convertDataToModal(panel);
                    }
                    Mautic.stopIconSpinPostEvent();
                },
                error: function (request, textStatus, errorThrown) {
                    Mautic.processAjaxError(request, textStatus, errorThrown);
                    Mautic.stopIconSpinPostEvent();
                },
                complete: function () {
                    Mautic.stopModalLoadingBar(target);
                    CustomObjectsForm.initSaveModal(target);
                    CustomObjectsForm.initCancelModal();
                }
            });
        });

        mQuery(target).on('hidden.bs.modal', function () {
            mQuery('body').removeClass('noscroll');
            Mautic.resetModal(target);
        });
    },

    initCancelModal() {
        mQuery('button.btn-cancel')
            .unbind('click')
            .bind('click', function() {
                mQuery('#objectFieldModal').modal('hide');
            });
    },

    initSaveModal(target) {
        Mautic.startIconSpinOnEvent();

        mQuery(target).find('button.btn-save')
            .unbind('click')
            .bind('click', function() {
                let form = mQuery('form[name="custom_field"]');
                let route = form.attr('action');

                mQuery.ajax({
                    url: route,
                    type: 'POST',
                    dataType: 'json',
                    data: form.serialize(),
                    success: function (response) {
                        if (response.closeModal) {
                            // Valid post, lets create panel
                            CustomObjectsForm.saveToPanel(response, target);
                        } else {
                            // Rerender invalid form
                            Mautic.processModalContent(response, target);
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                        Mautic.stopIconSpinPostEvent();
                    },
                });
            });
    },

    /**
     * Create custom field from
     * \MauticPlugin\CustomObjectsFormBundle\Controller\CustomField\SaveController::saveAction
     */
    saveToPanel: function(response, target) {
        let content = mQuery(response.content);
        let fieldOrderNo = 0;

        if (content.find('#custom_field_id').val()) {
            // Custom field has id, this was edit
            fieldOrderNo = mQuery(content).find('[id*=order]').val();
            content = CustomObjectsForm.convertDataFromModal(content, fieldOrderNo);
            mQuery('form[name="custom_object"] [id*=order][value="' + fieldOrderNo +'"]').parent().replaceWith(content);
        } else {
            // New custom field without id
            fieldOrderNo = mQuery('.panel').length - 2;
            content = CustomObjectsForm.convertDataFromModal(content, fieldOrderNo);
            mQuery('.drop-here').prepend(content);
            CustomObjectsForm.recalculateOrder();
            fieldOrderNo = 0;
        }

        mQuery(target).modal('hide');
        mQuery('body').removeClass('modal-open');
        mQuery('.modal-backdrop').remove();

        let panel = mQuery('#customField_' + fieldOrderNo);
        CustomObjectsForm.initPanel(panel);
    },

    /**
     * Transfer CF data from CO form to modal
     * @param panel DOM element with .panel class
     */
    convertDataToModal: function (panel) {
        mQuery(panel).find('input').each(function (i, input) {
            let id = mQuery(input).attr('id');
            let name = id.slice(id.lastIndexOf('_') + 1, id.length);
            mQuery('#objectFieldModal').find('#custom_field_' + name).val(mQuery(input).val());
        });
    },

    /**
     * Transfer modal data to CO form
     * @param panel CF panel content
     * @param fieldIndex numeric index of CF in form
     * @returns html content of panel
     */
    convertDataFromModal: function (panel, fieldIndex) {
        mQuery(panel).find('input').each(function(i, input) {
            let id = mQuery(input).attr('id');
            id = id.slice(id.lastIndexOf('_') + 1, id.length);
            let name = 'custom_object[customFields][' + fieldIndex + '][' + id + ']';
            mQuery(input).attr('name', name);
            id = 'custom_object_custom_fields_' + fieldIndex + '_' + id;
            mQuery(input).attr('id', id);
        });
        return panel;
    },
};
