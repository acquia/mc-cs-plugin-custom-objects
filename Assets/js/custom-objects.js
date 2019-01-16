mQuery(function() {
    CustomObjects.handleTabOnShow();
});

CustomObjects = {

    handleTabOnShow: function() {
        mQuery('a.custom-object-tab[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            let customObjectId = mQuery(e.target).attr('data-custom-object-id');
            let contactId = mQuery('input#leadId').val();
            CustomObjects.getItemsForObject(customObjectId, contactId, function(response) {
                CustomObjects.refreshTabContent(customObjectId, response.newContent);
            });
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
        let tabContent = mQuery('#custom-object-'+customObjectId+'-container .custom-item-list');
        tabContent.html(content);
    },

}
