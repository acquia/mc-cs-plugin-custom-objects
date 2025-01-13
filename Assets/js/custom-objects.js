// Init stuff on refresh:
mQuery(function() {
    CustomObjects.onCampaignEventModalLoaded(function() {
        CustomObjects.initCustomItemTypeaheadsOnCampaignEventForm();
    })
});

CustomObjects = {
    activeModal: {},

    onCampaignEventModalLoaded(callback) {
        mQuery(document).ajaxComplete(function(event, request, settings) {
            if (settings.type === 'POST' && settings.url.indexOf('s/campaigns/events') >= 0) {
                callback(event, request, settings);
            }
        })
    },

    initCustomFieldConditions() {
        let form = mQuery('form[name=campaignevent]');
        let fieldSelect = form.find('#campaignevent_properties_field');
        let operatorSelect = form.find('#campaignevent_properties_operator');

        CustomObjects.updateFormFieldOptions(fieldSelect, operatorSelect);

        fieldSelect.on('change', function() {
            CustomObjects.updateFormFieldOptions(fieldSelect, operatorSelect);
        });

        operatorSelect.on('change', function() {
            CustomObjects.updateFormFieldOptions(fieldSelect, operatorSelect);
        });
    },

    updateFormFieldOptions(fieldSelect, operatorSelect) {
        let valueField = mQuery('#campaignevent_properties_value');
        let selectedField = fieldSelect.find(':selected');
        let operators = JSON.parse(selectedField.attr('data-operators'));
        let options = JSON.parse(selectedField.attr('data-options'));
        let selectedOperator = operatorSelect.find(':selected').attr('value');
        let isEmptyOperator = selectedOperator === 'empty' || selectedOperator === '!empty';
        let valueFieldAttrs = {
            'class': valueField.attr('class'),
            'id': valueField.attr('id'),
            'name': valueField.attr('name'),
            'autocomplete': valueField.attr('autocomplete'),
            'value': valueField.val()
        };
        const fieldType = selectedField.attr('data-field-type');

        operatorSelect.empty();

        for (let operatorKey in operators) {
            let option = mQuery('<option/>').attr('value', operatorKey).text(operators[operatorKey]);
            if (operatorKey == selectedOperator) {
                option.attr('selected', true);
            }
            operatorSelect.append(option);
        }

        Mautic.destroyChosen(valueField);

        let newValueField = mQuery('<input/>').attr('type', 'text');

        if (!mQuery.isEmptyObject(options) && !isEmptyOperator) {
            newValueField = mQuery('<select/>');
            for (let optionValue in options) {
                newValueField.append(
                    mQuery("<option></option>")
                        .attr('value', optionValue)
                        .attr('selected', valueField.val() === optionValue)
                        .text(options[optionValue])
                );
            }
        }

        if (fieldType === 'date') {
            newValueField.datetimepicker({
                timepicker: false,
                format: 'Y-m-d',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollMonth: false,
                scrollInput: false,
                closeOnDateSelect: true
            });
        } else if (fieldType === 'datetime') {
            newValueField.datetimepicker({
                format: 'Y-m-d H:i',
                lazyInit: true,
                validateOnBlur: false,
                allowBlank: true,
                scrollMonth: false,
                scrollInput: false
            });
        }

        if (isEmptyOperator) {
            newValueField.val(null);
            newValueField.attr('value', '');
            newValueField.attr('disabled', 'disabled');
        } else {
            newValueField.val(valueField.val());
        }

        newValueField.attr(valueFieldAttrs);
        valueField.replaceWith(newValueField);

        if (valueField.is('select')) {
            // I would love this to work, but Chosen doesn't want to initialize on this select...
            Mautic.activateChosenSelect(valueField);
        }
        operatorSelect.trigger("chosen:updated");
    },

    initCustomItemTypeaheadsOnCampaignEventForm() {
        let typeaheadInputs = mQuery('input[data-toggle="typeahead"]');
        typeaheadInputs.each(function(i, nameInputHtml) {
            let nameInput = mQuery(nameInputHtml);
            let customObjectId = nameInput.attr('data-custom-object-id');
            let idInput = mQuery(nameInput.attr('data-id-input-selector'));
            CustomObjects.initCustomItemTypeahead(nameInput, customObjectId, function(selectedItem) {
                idInput.val(selectedItem.id);
                CustomObjects.addIconToInput(nameInput, 'check');
            });
            nameInput.on('blur', function() {
                if (!nameInput.val()) {
                    idInput.val('');
                    CustomObjects.removeIconFromInput(nameInput);
                }
            });
            if (idInput.val()) {
                CustomObjects.addIconToInput(nameInput, 'check');
            }
        })
    },

    addIconToInput(input, icon, spinIt) {
        CustomObjects.removeIconFromInput(input);
        let id = input.attr('id')+'-input-icon';
        let formGroup = input.closest('.form-group');
        let iconEl = mQuery('<span/>').addClass('fa fa-'+icon+' form-control-feedback');
        let ariaEl = mQuery('<span/>').addClass('sr-only').text('('+icon+')').attr('id', id);
        if (spinIt) {
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

    reloadItemsTable(customObjectId, currentEntityId, currentEntityType, tabId) {
        CustomObjects.reloadItems(customObjectId, currentEntityId, currentEntityType, 0, '#'+tabId+'-container .custom-item-list');
    },

    initCustomItemTypeahead(input, customObjectId, onSelectCallback) {
        // Initialize only once
        if (input.attr('data-typeahead-initialized')) {
            return;
        }

        input.attr('data-typeahead-initialized', true);
        let url = input.attr('data-action');
        let separator = (url.indexOf('?') >= 0) ? '&' : '?';
        let customItems = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value', 'id'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: url+separator+'filter=%QUERY',
                wildcard: '%QUERY',
                ajax: {
                    beforeSend: function() {
                        Mautic.startPageLoadingBar();
                    },
                    complete: function() {
                        Mautic.stopPageLoadingBar();
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
            name: 'custom-items-'+customObjectId,
            display: 'value',
            source: customItems.ttAdapter()
        }).bind('typeahead:selected', function(e, selectedItem) {
            if (!selectedItem || !selectedItem.id) return;
            onSelectCallback(selectedItem);
        });
    },

    linkCustomItemWithEntity(elHtml, event, customObjectId, currentEntityType, currentEntityId, tabId, relationshipObjectId) {
        event.preventDefault();
        const $element = mQuery(elHtml);
        const action = $element.attr('data-action');
        const modalListSelector = '#' + $element.closest('.page-list').attr('id');

        if (relationshipObjectId) {
            CustomObjects.activeModal = {
                customObjectId: customObjectId,
                currentEntityId: currentEntityId,
                currentEntityType: currentEntityType,
                tabId: tabId,
                modalListSelector: modalListSelector
            };
            Mautic.loadAjaxModal('#MauticSharedModal', action, 'GET');
        } else {
            CustomObjects.customItemLinkAction(action, customObjectId, currentEntityType, currentEntityId, function () {
                CustomObjects.reloadItems(customObjectId, currentEntityId, currentEntityType, 1, modalListSelector)
                CustomObjects.reloadItemsTable(customObjectId, currentEntityId, currentEntityType, tabId);
            });
        }
    },

    unlinkCustomItemFromEntity(elHtml, event, customObjectId, currentEntityType, currentEntityId, tabId) {
        event.preventDefault();

        CustomObjects.customItemLinkAction(mQuery(elHtml).attr('data-action'), customObjectId, currentEntityType, currentEntityId, function () {
            CustomObjects.reloadItemsTable(customObjectId, currentEntityId, currentEntityType, tabId);
        });
    },

    customItemLinkAction(url, customObjectId, currentEntityType, currentEntityId, callback) {
        mQuery.ajax({
            type: 'POST',
            url: url,
            showLoadingBar: true,
            success: callback
        });
    },

    reloadItems(customObjectId, currentEntityId, currentEntityType, lookup, selector) {
        mQuery.ajax({
            type: 'GET',
            url: mauticBaseUrl+'s/custom/object/'+customObjectId+'/item',
            data: {
                filterEntityId: currentEntityId,
                filterEntityType: currentEntityType,
                tmpl: 'list',
                lookup: lookup
            },
            success: function (response) {
                mQuery(selector).html(response.newContent);
                Mautic.onPageLoad(selector);
            },
            showLoadingBar: true
        });
    }
};

Mautic.customItemLinkFormLoad = function(response) {
    let target = mQuery('#MauticSharedModal');
    let content = mQuery(response.newContent);

    target.find('.modal-title').html(content.find('.page-header h3').html());
    content.find('.page-header').remove();
    content.find('.box-layout > .bg-auto').removeClass('bg-auto');
    // Remove name field as we auto-generate relationship item names
    content.find('#custom_item_name').closest('.row').remove();
    // Remove cancel & apply buttons
    content.find('.btn-cancel').remove();
    content.find('.btn-apply').remove();
    target.find('.modal-body').html(content);
    Mautic.onPageLoad('#MauticSharedModal', response, true);
};

Mautic.customItemLinkFormPostSubmit = function(response) {
    mQuery('body').removeClass('noscroll');
    mQuery('#MauticSharedModal').modal('hide');

    Mautic.customObjectsCleanUpFormModal();
};

/**
 * For setup of CustomObjects.activeModal property when loading
 * the ajax modal from the edit link.
 *
 * @param el
 */
Mautic.customObjectsSetUpLinkFormModalFromEditLink = function(el) {
    let element = mQuery(el);

    CustomObjects.activeModal = {
        customObjectId: element.attr('data-custom-object-id'),
        currentEntityId: element.attr('data-current-entity-id'),
        currentEntityType: element.attr('data-current-entity-type'),
        tabId: element.attr('data-tab-id')
    };
};

Mautic.customObjectsCleanUpFormModal = function() {
    const activeModal = CustomObjects.activeModal;

    if (activeModal.modalListSelector) {
        CustomObjects.reloadItems(
            activeModal.customObjectId,
            activeModal.currentEntityId,
            activeModal.currentEntityType,
            1,
            activeModal.modalListSelector
        );
    }

    CustomObjects.reloadItemsTable(
        activeModal.customObjectId,
        activeModal.currentEntityId,
        activeModal.currentEntityType,
        activeModal.tabId
    );

    CustomObjects.activeModal = {};
};
