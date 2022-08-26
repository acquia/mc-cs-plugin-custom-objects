// Custom Object create/edit form handling is here
// Init stuff on refresh:

Mautic.customObjectOnLoad = function() {
    CustomObjectsForm.onLoad();
};

CustomObjectsForm = {

    /**
     * Custom object form events
     */
    onLoad: function () {
        CustomObjectsForm.initAdder();
        CustomObjectsForm.initPanels();
    },

    initPanels: function() {
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
     * Init CF panel events (except sortable)
     * @param panel
     */
    initPanel: function(panel) {
        CustomObjectsForm.initEditFieldButton(panel);
        CustomObjectsForm.initDeleteFieldButton(panel);
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
                if (panel.find('input[type="hidden"][id*=_id]').val() === '') {
                    // New unsaved custom field.
                    panel.remove();
                    CustomObjectsForm.recalculateOrder();
                } else {
                    panel.find('[id*=deleted]').val(1);
                }
            });
    },

    showModal: function(element) {
        let panel = element.closest('.panel');
        let target = element.attr('data-target');
        let panelCount = mQuery('.drop-here').children().length;
        if (element.attr('href')) {
            // Panel id in format customField_1
            let panelId = panel.attr('id');
            panelId = panelId.slice(panelId.lastIndexOf('_') + 1, panelId.length);
            var route = element.attr('href') + '&panelId=' + panelId + '&panelCount=' + panelCount;
            var edit = true;
        } else {
            // Tell backend how many fields are present in the form
            var route = element.attr('data-href') + '&panelCount=' + panelCount;
            var edit = false;
        }

        mQuery('body').addClass('noscroll');
        mQuery(target).find('.loading-placeholder').removeClass('hide');
        mQuery(target).modal('show');

        // Fill modal with form loaded via ajax
        mQuery(target)
            .off('shown.bs.modal')
            .on('shown.bs.modal', function() {
            // Fill modal with form loaded via ajax
            mQuery.ajax({
                url: route,
                type: 'GET',
                dataType: 'json',
                success: function (response) {
                    if (response) {
                        CustomObjectsForm.refreshModalContent(response, target);
                        if (edit) {
                            CustomObjectsForm.convertDataToModal(panel);
                        }
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
                    mQuery('#objectFieldModal [role="tabpanel"] [role="presentation"]').click(function() {
                        CustomObjectsForm.handleModalDefaultValueOptions();
                    });
                }
            });
        });

        mQuery(target).off('hidden.bs.modal').on('hidden.bs.modal', function () {
            mQuery('body').removeClass('noscroll');
            Mautic.resetModal(target);
            CustomObjectsForm.initPanels();
        });
    },

    initCancelModal() {
        mQuery('#objectFieldModal button.btn-cancel')
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
                CustomObjectsForm.handleModalDefaultValueOptions();

                let form = mQuery('form[name="custom_field"]');
                let route = form.attr('action');

                mQuery(target).find('button.btn-save').attr('disabled', 'disabled');
                mQuery(target).find('button.btn-cancel').attr('disabled', 'disabled');

                mQuery.ajax({
                    url: route,
                    type: 'POST',
                    dataType: 'json',
                    data: form.serialize(),
                    success: function (response) {
                        if (response.closeModal) {
                            // Valid post, lets create panel
                            CustomObjectsForm.saveToPanel(response);
                        } else {
                            // Rerender invalid form
                            CustomObjectsForm.refreshModalContent(response, target);
                            CustomObjectsForm.initSaveModal(target);
                        }
                    },
                    error: function (request, textStatus, errorThrown) {
                        Mautic.processAjaxError(request, textStatus, errorThrown);
                        Mautic.stopIconSpinPostEvent();
                        mQuery(target).find('button.btn-save').removeAttr('disabled');
                        mQuery(target).find('button.btn-cancel').removeAttr('disabled');
                        CustomObjectsForm.initSaveModal(target);
                    },
                });
            });
    },

    /**
     * Update default value options from Modal properties panel to general tab default value settings.
     * Everything happens in modal
     */
    handleModalDefaultValueOptions: function() {
        let type = mQuery('#custom_field_type').val();

        if (!CustomObjectsForm.isSelectableField(type) || type === 'country') {
            return;
        }

        let choiceDefinition = mQuery('#objectFieldModal').find('#sortable-custom_field_options');
        if (choiceDefinition === undefined) {
            return; // No options
        }

        let options = '';

        let selectedValues = CustomObjectsForm.getSelectableValuesFromModal(type);

        switch (type) {
            // Add empty value option
            case 'select' :
            case 'multiselect':
                let placeholder = mQuery('#objectFieldModal #custom_field_params_placeholder').val();

                if (placeholder !== '') {
                    mQuery('#objectFieldModal #custom_field_defaultValue').attr('data-placeholder', placeholder);
                }

                options = options + '<option value=""></option>';
                break;
            case `radio_group` :
                options = options + mQuery('#custom_field_defaultValue input:eq(0)').get(0).outerHTML
                    + mQuery('#custom_field_defaultValue label:eq(0)').get(0).outerHTML;
                break;
        }

        // Transfer options
        let i = 0;

        choiceDefinition.find('.sortable').each(function() {
            let row = mQuery(this).find('input');
            let label = mQuery(row[0]).val();
            let value = mQuery(row[1]).val();

            let checked = selectedValues.indexOf(value) > -1 ? ' checked="checked"' : '';
            let selected = selectedValues.indexOf(value) > -1 ? ' selected="selected"' : '';
            switch (type) {
                case 'checkbox_group':
                    options = options + '<div class="checkbox"><label><input type="checkbox" id="custom_field_defaultValue_' +
                        i + '" name="custom_field[defaultValue][]" class="form-control" autocomplete="false" value="' +
                        value + '"' + checked + '>' + label + '</label></div>';
                    break;
                case 'select' :
                case 'multiselect':
                    options = options + '<option value="' + value + '"' + selected + '>' + label + '</option>';
                    break;
                case 'radio_group':
                    options = options + '<input type="radio" id="custom_field_defaultValue_' +
                        i + '" name="custom_field[defaultValue]" autocomplete="false" value="' + value + '"' + checked + '>' +
                        '<label for="custom_field_defaultValue_' + i + '">' + label + '</label>';
                    break;
            }

            i = i + 1;
        });

        // Put it to thee right DOM node
        let target = mQuery('#custom_field_defaultValue');

        if (type === 'checkbox_group') {
            target = mQuery('#objectFieldModal .default-value .choice-wrapper')
        }

        target.html(options).trigger('chosen:updated');
    },

    /**
     * @param type
     * @returns {boolean}
     */
    isSelectableField: function(type) {
        return type === 'checkbox_group' ||
            type === 'select' ||
            type === 'multiselect' ||
            type === 'radio_group' ||
            type === 'country';
    },

    /**
     * Get selected default values from multiselects in modal
     * @param type
     * @returns {Array}
     */
    getSelectableValuesFromModal: function(type) {

        let selector = '';

        if (type === 'multiselect' || type === 'select') {
            selector = '#custom_field_defaultValue option:selected';
        } else {
            selector = 'input[id*="custom_field_defaultValue_"]:checked'
        }

        let selectedValues = [];

        mQuery(selector).each(function() {
            selectedValues.push(mQuery(this).val());
        });

        return selectedValues;
    },

    /**
     * Load modal with stuff from response
     * @param response
     * @param target
     */
    refreshModalContent(response, target) {
        Mautic.stopIconSpinPostEvent();

        if (response.target) {
            // Replace content
            mQuery(response.target).html(response.newContent);
        } else if (response.newContent) {
            // Load the content
            if (mQuery(target + ' .loading-placeholder').length) {
                mQuery(target + ' .loading-placeholder').addClass('hide');
                mQuery(target + ' .modal-body-content').html(response.newContent);
                mQuery(target + ' .modal-body-content').removeClass('hide');
            } else {
                mQuery(target + ' .modal-body').html(response.newContent);
            }
        }

        // Activate content specific stuff
        Mautic.onPageLoad(target, response, true);

        mQuery('#custom_field_isUniqueIdentifier_1').on('change', function() {
            CustomObjectsForm.setChoiceRequiredVal(true, 'required')
            mQuery('#objectFieldModal .chosen-required .choice-wrapper').find('label').attr('disabled', true);
        });
        mQuery('#custom_field_isUniqueIdentifier_0').on('change', function() {
            mQuery('#objectFieldModal .chosen-required .choice-wrapper').find('label').removeAttr('disabled');
        });
    },

    /**
     * Transfer CF data from CO form to modal
     * @param panel DOM element with .panel class
     */
    convertDataToModal: function (panel) {

        // Value could be different kind of type (input/textarea/..)
        let panelId = CustomObjectsForm.getPanelId(panel);
        let defaultValueIdSelector = '#custom_object_customFields_' + panelId + '_defaultValue';
        let type = mQuery('#custom_object_customFields_' + panelId + '_type').val();

        mQuery(panel).find('input').each(function (i, input) {

            let value = mQuery(input).val();
            let id = mQuery(input).attr('id');

            if (id !== undefined) {
                let propertyName = id.slice(id.lastIndexOf('_') + 1, id.length);
                let target = '#custom_field_' + propertyName;

                if (propertyName === 'params') {
                    let params = JSON.parse(value);

                    for(key in params){
                        let target = '#custom_field_params_' + key;
                        mQuery('#objectFieldModal').find(target).val(params[key]);
                    }

                } else if (propertyName === 'options') {
                    let options = JSON.parse(value);

                    let content = mQuery('#sortable-custom_field_options').html('');
                    let prototype = mQuery('#custom_field_options_list a[data-prototype]').attr('data-prototype');

                    let order = 0;

                    for(let option in options){
                        let html = prototype.replace(/__name__/g, order.toString());
                        html = mQuery(html);

                        html.find("input[id*='label']").val(options[option]['label']);
                        html.find("input[id*='value']").val(options[option]['value']);

                        content.append(html);

                        order = order + 1;
                    }
                } else if (-1 !== ['required', 'showInCustomObjectDetailList', 'showInContactDetailList', 'isUniqueIdentifier'].indexOf(propertyName)) {
                    CustomObjectsForm.setChoiceRequiredVal(value, propertyName);
                } else {
                    mQuery('#objectFieldModal').find(target).val(value);
                }
            }
        });

        if (CustomObjectsForm.isSelectableField(type)) {
            CustomObjectsForm.convertSelectableDataToModal(panel, type);
        } else {
            mQuery('#custom_field_defaultValue').val(mQuery(defaultValueIdSelector).val());
        }
    },

    /**
     * Convert selectable default values to modal
     * @param panel
     * @param type
     */
    convertSelectableDataToModal: function(panel, type) {

        let options = '';
        switch(type){
            case 'checkbox_group':
                options = mQuery(panel).find('.choice-wrapper').clone();
                mQuery(options).find('input').each(function(){
                        mQuery(this)
                            .attr('id', 'custom_field_defaultValue_' + mQuery(this).val())
                            .attr('name', 'custom_field[defaultValue]');
                    }
                );
                mQuery('#objectFieldModal #general .choice-wrapper').replaceWith(options);
                break;
            case 'country':
            case 'select':
            case 'multiselect':
                let panelDefaultValue = mQuery(panel).find('[id*=_defaultValue]');
                let placeholder = panelDefaultValue.attr('data-placeholder');

                if (placeholder !== undefined) {
                    mQuery('#objectFieldModal #custom_field_defaultValue').attr('data-placeholder', placeholder);
                }

                options = panelDefaultValue.find('option').clone();
                mQuery('#objectFieldModal #general #custom_field_defaultValue')
                    .html('').prepend(options).trigger("chosen:updated");
                break;
            case 'radio_group':
                options = mQuery(panel).find('.choice-wrapper').clone();
                mQuery(options).children().first().attr('id', 'custom_field_defaultValue')
                    .children('input').each(function(){
                        mQuery(this)
                            .attr('id', 'custom_field_defaultValue_' + mQuery(this).val())
                            .attr('name', 'custom_field[defaultValue]');
                    }
                );
                mQuery('#objectFieldModal #general .choice-wrapper').replaceWith(options);
                break;
        }
    },

    /**
     * Create/edit custom field from modal and transfer data to CO panel hidden fields
     * \MauticPlugin\CustomObjectsFormBundle\Controller\CustomField\SaveController::saveAction
     */
    saveToPanel: function(response) {

        let panelSelector = '#customField_' + response.panelId;

        let panel = mQuery(panelSelector);

        if (response.isNew) {
            mQuery('.drop-here').prepend(response.content);
            panel = mQuery('.drop-here').children().first();
        } else {
            mQuery(panel).replaceWith(response.content);
            panel = mQuery(panelSelector);
        }

        mQuery('#objectFieldModal').modal('hide');
        mQuery('body').removeClass('modal-open');
        mQuery('.modal-backdrop').remove();

        CustomObjectsForm.initPanel(panel);
        CustomObjectsForm.recalculateOrder();
    },

    /**
     * Find closest panel and get his id
     *
     * @param panel
     */
    getPanelId: function (panel) {
        let panelId = panel.attr('id');
        panelId = panelId.slice(panelId.lastIndexOf('_') + 1, panelId.length);

        return panelId;
    },

    /**
     * @param value
     * @param name
     */
    setChoiceRequiredVal: function(value, name) {
        let element = mQuery('#objectFieldModal .chosen-' + name + ' .choice-wrapper');
        let no = element.find('label').eq(0);
        let yes = element.find('label').eq(1);

        if (value) {
            yes.removeClass('btn-default').addClass('btn-success active');
            no.removeClass('btn-danger active').addClass('btn-default');
            yes.find('input').attr('checked', 'checked');
            no.find('input').removeAttr('checked');
        } else {
            yes.removeClass('btn-success active').addClass('btn-default');
            no.removeClass('btn-default').addClass('btn-danger active');
            yes.find('input').removeAttr('checked');
            no.find('input').attr('checked', 'checked');
        }
    }
};
