mQuery(function() {
    CustomObjects.handleTabOnShow();
    CustomObjects.formOnLoad();
});

CustomObjects = {

    handleTabOnShow: function() {
        mQuery('a.custom-object-tab[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            let customObjectId = mQuery(e.target).attr('data-custom-object-id');
            let contactId = mQuery('input#leadId').val();
            CustomObjects.initLinkInput(customObjectId, contactId);
            CustomObjects.reloadItemsTable(customObjectId, contactId);
        });
    },

    reloadItemsTable: function(customObjectId, contactId) {
        CustomObjects.getItemsForObject(customObjectId, contactId, function(response) {
            CustomObjects.refreshTabContent(customObjectId, response.newContent);
        });
    },

    initLinkInput: function(customObjectId, contactId) {
        let selector = CustomObjects.createTabSelector(customObjectId, '[data-toggle="typeahead"]');
        let input = mQuery(selector);

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
                url: url+'?filter=%QUERY&contactId='+input.attr('data-contact-id'),
                wildcard: '%QUERY',
                filter: function(response) {
                    return response.items;
                },
            }
        });

        customItems.initialize();
          
        input.typeahead({
            minLength: 0,
            highlight: true
        }, {
            name: 'custom-items-'+customObjectId,
            display: 'value',
            source: customItems.ttAdapter()
        }).bind('typeahead:selected', function(e, selectedItem) {
            if (!selectedItem || !selectedItem.id) return;
            CustomObjects.linkContactWithCustomItem(contactId, selectedItem.id, function() {
                CustomObjects.reloadItemsTable(customObjectId, contactId);
                input.val('');
            });
        });
    },

    linkContactWithCustomItem: function(contactId, customItemId, callback) {
        mQuery.ajax({
            type: 'POST',
            url: mauticBaseUrl+'s/custom/item/'+customItemId+'/link/contact/'+contactId+'.json',
            data: {contactId: contactId},
            success: function (response) {
                callback(response);
            },
        });
    },

    getItemsForObject: function(customObjectId, contactId, callback) {
        mQuery.ajax({
            type: 'GET',
            url: mauticBaseUrl+'s/custom/object/'+customObjectId+'/item',
            data: {contactId: contactId},
            success: function (response) {
                callback(response);
            },
        });
    },

    refreshTabContent: function(customObjectId, content) {
        let selector = CustomObjects.createTabSelector(customObjectId, '.custom-item-list');
        mQuery(selector).html(content);
        Mautic.onPageLoad(selector);
    },

    createTabSelector: function(customObjectId, suffix) {
        return '#custom-object-'+customObjectId+'-container '+suffix;
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
                containment: '#mauticforms_fields .drop-here',
                stop: function(e, ui) {
                    // Restore original overflow
                    mQuery('body').css(bodyOverflow);
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
                    // Restore original overflow
                    mQuery('body').css(bodyOverflow);
                    mQuery(ui.item).attr('style', '');

                    mQuery.ajax({
                        type: "POST",
                        url: mauticAjaxUrl + "?action=form:reorderActions",
                        data: mQuery('#mauticforms_actions').sortable("serialize") + "&formId=" + mQuery('#mauticform_sessionId').val()
                    });
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

        Mautic.initHideItemButton('#mauticforms_fields');
        Mautic.initHideItemButton('#mauticforms_actions');
    },
};



