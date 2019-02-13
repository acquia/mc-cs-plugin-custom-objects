// Init stuff on refresh:
mQuery(function() {
    CustomObjects.formOnLoad();

    CustomObjects.onCampaignEventModalLoaded(function() {
        CustomObjects.initCustomItemTypeaheadsOnCampaignEventForm();
    })
});

CustomObjects = {

    onCampaignEventModalLoaded(callback) {
        mQuery(document).ajaxComplete(function(event, request, settings) {
            if (settings.type === 'GET' && settings.url.indexOf('s/campaigns/events') >= 0) {
                callback(event, request, settings);
            }
        })
    },

    updateFormFieldOptions(fieldSelectHtml) {
        let fieldSelect = mQuery(fieldSelectHtml);
        let operators = JSON.parse(fieldSelect.find(':selected').attr('data-operators'));
        let operatorSelect = mQuery('#campaignevent_properties_operator');
        let selectedOperator = operatorSelect.find(':selected').attr('value');
        operatorSelect.empty();

        for (var operatorKey in operators) {
            let option = mQuery('<option/>').attr('value', operatorKey).text(operators[operatorKey]);
            if (operatorKey == selectedOperator) {
                option.attr('selected', true);
            }
            operatorSelect.append(option);
        }

        operatorSelect.trigger("chosen:updated");
    },

    // Called from tab content HTML:
    initContactTabForCustomObject(customObjectId) {
        let contactId = mQuery('input#leadId').val();
        let selector = CustomObjects.createTabSelector(customObjectId, '[data-toggle="typeahead"]');
        let input = mQuery(selector);
        CustomObjects.initCustomItemTypeahead(input, customObjectId, contactId, function(selectedItem) {
            CustomObjects.linkContactWithCustomItem(contactId, selectedItem.id, function() {
                CustomObjects.reloadItemsTable(customObjectId, contactId);
                input.val('');
            });
        });
        CustomObjects.reloadItemsTable(customObjectId, contactId);
    },

    initCustomItemTypeaheadsOnCampaignEventForm() {
        let typeaheadInputs = mQuery('input[data-toggle="typeahead"]');
        typeaheadInputs.each(function(i, nameInputHtml) {
            let nameInput = mQuery(nameInputHtml);
            let customObjectId = nameInput.attr('data-custom-object-id');
            let idInput = mQuery(nameInput.attr('data-id-input-selector'));
            CustomObjects.initCustomItemTypeahead(nameInput, customObjectId, null, function(selectedItem) {
                idInput.val(selectedItem.id);
                CustomObjects.displaySelectedItemInfo(nameInput, idInput);
                CustomObjects.addIconToInput(nameInput, 'check');
            });
            nameInput.on('blur', function() {
                if (!nameInput.val()) {
                    idInput.val('');
                    CustomObjects.displaySelectedItemInfo(nameInput, idInput);
                    CustomObjects.removeIconFromInput(nameInput);
                }
            });
            if (idInput.val()) {
                CustomObjects.displaySelectedItemInfo(nameInput, idInput);
                CustomObjects.addIconToInput(nameInput, 'check');
            }
        })
    },

    addIconToInput(input, icon) {
        CustomObjects.removeIconFromInput(input);
        let id = input.attr('id')+'-input-icon';
        let formGroup = input.closest('.form-group');
        let iconEl = mQuery('<span/>').addClass('fa fa-'+icon+' form-control-feedback');
        let ariaEl = mQuery('<span/>').addClass('sr-only').text('('+icon+')').attr('id', id);
        if (icon === 'spinner') {
            iconEl.addClass('fa-spin');
        }
        formGroup.addClass('has-feedback');
        input.attr('aria-describedby', id);
        formGroup.append(iconEl);
        formGroup.append(ariaEl);
    },

    removeIconFromInput(input) {
        let formGroup = input.closest('.form-group');
        formGroup.find('.form-control-feedback').remove();
        formGroup.find('.sr-only').remove();
        input.removeAttr('aria-describedby');
        formGroup.removeClass('has-feedback');
    },

    displaySelectedItemInfo(nameInput, idInput) {
        let formGroup = nameInput.closest('.form-group');
        formGroup.find('.selected-message').remove();

        if (idInput.val()) {
            let selectedMessage = nameInput.attr('data-selected-message');
            selectedMessage = selectedMessage.replace('%id%', idInput.val());
            let selectedMessageEl = mQuery('<span/>').addClass('selected-message text-success').text(selectedMessage);
            formGroup.append(selectedMessageEl);
        }
    },

    reloadItemsTable(customObjectId, contactId) {
        CustomObjects.getItemsForObject(customObjectId, contactId, function(response) {
            CustomObjects.refreshTabContent(customObjectId, response.newContent);
        });
    },

    initCustomItemTypeahead(input, customObjectId, contactId, onSelectCallback) {
        // Initialize only once
        if (input.attr('data-typeahead-initialized')) {
            return;
        }

        input.attr('data-typeahead-initialized', true);
        let url = input.attr('data-action');
        let customItems = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value', 'id'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: url+'?filter=%QUERY&contactId='+contactId,
                wildcard: '%QUERY',
                ajax: {
                    beforeSend: function() {
                        CustomObjects.addIconToInput(input, 'spinner');
                    },
                    complete: function(){
                        CustomObjects.removeIconFromInput(input);
                    }
                },
                filter: function(response) {
                    return response.items;
                },
            }
        });

        customItems.initialize();

        input.typeahead({
            minLength: 0,
            highlight: true,
        }, {
            name: 'custom-items-'+customObjectId+'-'+contactId,
            display: 'value',
            source: customItems.ttAdapter()
        }).bind('typeahead:selected', function(e, selectedItem) {
            if (!selectedItem || !selectedItem.id) return;
            onSelectCallback(selectedItem);
        });
    },

    linkContactWithCustomItem(contactId, customItemId, callback) {
        mQuery.ajax({
            type: 'POST',
            url: mauticBaseUrl+'s/custom/item/'+customItemId+'/link/contact/'+contactId+'.json',
            data: {contactId: contactId},
            success: function (response) {
                callback(response);
            },
        });
    },

    getItemsForObject(customObjectId, contactId, callback) {
        mQuery.ajax({
            type: 'GET',
            url: mauticBaseUrl+'s/custom/object/'+customObjectId+'/item',
            data: {contactId: contactId},
            success: function (response) {
                callback(response);
            },
        });
    },

    refreshTabContent(customObjectId, content) {
        let selector = CustomObjects.createTabSelector(customObjectId, '.custom-item-list');
        mQuery(selector).html(content);
        Mautic.onPageLoad(selector);
    },

    createTabSelector(customObjectId, suffix) {
        return '#custom-object-'+customObjectId+'-container '+suffix;
    },

    /**
     * Custom object form events
     */
    formOnLoad: function () {
        CustomObjects.formInitCFAdder();
        CustomObjects.formInitSortable();
        mQuery('.panel').each(function (i, panel) {
            CustomObjects.formInitPanel(panel);
        });
    },

    /**
     * Init CF adding feature
     */
    formInitCFAdder: function() {
        mQuery('select.form-builder-new-component').change(function (e) {
            mQuery(this).find('option:selected');
            CustomObjects.formShowModal(mQuery(this).find('option:selected'));
            // Reset the dropdown
            mQuery(this).val('');
            mQuery(this).trigger('chosen:updated');
        });
    },

    /**
     * Init CF sorting feature
     */
    formInitSortable: function () {
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
                    CustomObjects.formRecalculateCFOrder();
                }
            });

            Mautic.initFormFieldButtons();
        }
    },

    /**
     * Recalculate CF order
     */
    formRecalculateCFOrder: function() {
        mQuery('.drop-here').find('[id*=order]').each(function(i, selector) {
            mQuery(selector).val(i)
                .parent().attr('id', 'customField_' + i);
        });
    },

    /**
     * Init CF panel events (except sortable)
     * @param panel
     */
    formInitPanel: function(panel) {
        CustomObjects.formInitModal(panel);
        CustomObjects.formInitDeleteFieldButton(panel);
    },

    /**
     * Init ajax modal on .panel element
     * @param panel
     */
    formInitModal: function(panel) {
        mQuery(panel).find("[data-toggle='ajaxmodal']")
            .off('click.ajaxmodal')
            .on('click.ajaxmodal', function (event) {

                event.preventDefault();
                // Mautic.ajaxifyModal(this, event);
                CustomObjects.formShowModal(mQuery(this));
            });

        CustomObjects.formInitSortable();
    },

    formShowModal: function(element) {
        let target = element.attr('data-target');
        if (element.attr('href')) {
            var route = element.attr('href');
            var edit = true;
        } else {
            var route = element.attr('data-href');
            var edit = false;
        }

        Mautic.showModal(target);

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
                        CustomObjects.formConvertDataToModal(element);
                    }
                    Mautic.stopIconSpinPostEvent();
                },
                error: function (request, textStatus, errorThrown) {
                    Mautic.processAjaxError(request, textStatus, errorThrown);
                    Mautic.stopIconSpinPostEvent();
                },
                complete: function () {
                    Mautic.stopModalLoadingBar(target);
                    CustomObjects.formBindSaveFromModal(target);
                }
            });
        });
    },

    formBindSaveFromModal(target) {
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
                            CustomObjects.saveCustomFieldPanel(response, target);
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
     * Transfer CF data from CO form to modal
     * @param panel
     */
    formConvertDataToModal: function (panel) {
        mQuery(panel).find('input').each(function (i, input) {
            let id = mQuery(input).attr('id');
            let name = id.slice(id.lastIndexOf('_') + 1, id.length);
            mQuery('#objectFieldModal').find('#custom_field_' + name).val(mQuery(input).val());
        });
    },

    /**
     * Init CF delete button
     * @param panel
     */
    formInitDeleteFieldButton: function(panel) {
        mQuery(panel).find('[data-hide-panel]')
            .unbind('click')
            .click(function(e) {
                e.preventDefault();
                let panel = mQuery(this).closest('.panel');
                panel.hide('fast');
                panel.find('[id*=deleted]').val(1);
            });
    },

    /**
     * Create custom field from
     * \MauticPlugin\CustomObjectsBundle\Controller\CustomField\SaveController::saveAction
     */
    saveCustomFieldPanel: function(response, target) {
        let content = mQuery(response.content);
        let fieldOrderNo = 0;

        if (content.find('#custom_field_id').val()) {
            // Custom field has id, this was edit
            fieldOrderNo = mQuery(content).find('[id*=order]').val();
            content = CustomObjects.formConvertDataFromModal(content, fieldOrderNo);
            mQuery('form[name="custom_object"] [id*=order][value="' + fieldOrderNo +'"]').parent().replaceWith(content);
        } else {
            // New custom field without id
            fieldOrderNo = mQuery('.panel').length - 2;
            content = CustomObjects.formConvertDataFromModal(content, fieldOrderNo);
            mQuery('.drop-here').prepend(content);
            CustomObjects.formRecalculateCFOrder();
            fieldOrderNo = 0;
        }

        mQuery(target).hide();
        mQuery('body').removeClass('modal-open');
        mQuery('.modal-backdrop').remove();

        CustomObjects.formInitModal(mQuery('[id*=order][value="' + fieldOrderNo +'"]').parent());
    },

    /**
     * Transfer modal data to CO form
     * @param panel CF panel content
     * @param fieldIndex numeric index of CF in form
     * @returns html content of panel
     */
    formConvertDataFromModal: function (panel, fieldIndex) {
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
