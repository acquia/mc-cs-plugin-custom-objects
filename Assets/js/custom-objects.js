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

    initDeleteFieldButton: function() {
        mQuery('#mauticforms_fields').find('[data-hide-panel]').click(function(e) {
            e.preventDefault();
            let panel = mQuery(this).closest('.panel');
            panel.hide('fast');
            panel.find('.cf-deleted').val('1');
        });
    },

    formOnLoad: function (container) {
        mQuery('select.form-builder-new-component').change(function (e) {
            mQuery(this).find('option:selected');
            Mautic.ajaxifyModal(mQuery(this).find('option:selected'));
            // Reset the dropdown
            mQuery(this).val('');
            mQuery(this).trigger('chosen:updated');
        });



        if (mQuery('#mauticforms_fields')) {
            //make the fields sortable
            mQuery('#mauticforms_fields').sortable({
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

                    mQuery.ajax({
                        type: "POST",
                        url: mauticAjaxUrl + "?action=form:reorderFields",
                        data: mQuery('#mauticforms_fields').sortable("serialize", {attribute: 'data-sortable-id'}) + "&formId=" + mQuery('#mauticform_sessionId').val()
                    });
                }
            });

            Mautic.initFormFieldButtons();
        }

        if (mQuery('#mauticforms_actions')) {
            //make the fields sortable
            mQuery('#mauticforms_actions').sortable({
                items: '.panel',
                cancel: '',
                helper: function(e, ui) {
                    ui.children().each(function() {
                        mQuery(this).width(mQuery(this).width());
                    });

                    // Fix body overflow that messes sortable up
                    bodyOverflow.overflowX = mQuery('body').css('overflow-x');
                    bodyOverflow.overflowY = mQuery('body').css('overflow-y');
                    mQuery('body').css({
                        overflowX: 'visible',
                        overflowY: 'visible'
                    });

                    return ui;
                },
                scroll: true,
                axis: 'y',
                containment: '#mauticforms_actions .drop-here',
                stop: function(e, ui) {
                    // Sorting done
                    // Restore original overflow
                    mQuery('body').css(bodyOverflow);
                    mQuery(ui.item).attr('style', '');
                }
            });

            mQuery('#mauticforms_actions .mauticform-row').on('dblclick.mauticformactions', function(event) {
                event.preventDefault();
                mQuery(this).find('.btn-edit').first().click();
            });
        }

        if (mQuery('#mauticform_formType').length && mQuery('#mauticform_formType').val() == '') {
            mQuery('body').addClass('noscroll');
        }

        CustomObjects.initDeleteFieldButton();
    },
};



