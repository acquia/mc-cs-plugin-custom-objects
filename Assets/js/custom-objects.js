mQuery(function() {
    CustomObjects.handleTabOnShow();
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
        let url = mauticBaseUrl+'s/custom/object/'+customObjectId+'/item.json';
        let customItems = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value', 'id'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: url+'?filter=%QUERY',
                wildcard: '%QUERY'
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

}
