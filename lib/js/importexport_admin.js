// importexport_admin.js - put all admin js in here

$(document).ready(function() {

    $('.import-export #import_export_type').on('change', function() {

        if ( $(this).val()==$(this).data('current')) {
            $('.import-export-section').show();
            $('.import-export #change-type').prop('disabled', true)
                .addClass('ui-button-disabled ui-state-disabled');

        } else {
            $('.import-export-section').hide();
            $('.import-export #change-type').prop('disabled', false)
                .removeClass('ui-button-disabled, ui-state-disabled');
        }
    });


    // update field_map source selection 
    function updateSourceSelection() {
        var $sourceFields = $('.import-export .field_map_item');
        var limitSelection = $('.import-export #limit_source_selection').prop('checked');
        if (limitSelection) {
            // get all selected fields values
            var selectedFields = [];
            $sourceFields.find('option:selected').each(function() {
                if ($(this).val()!='') selectedFields.push($(this).val());
            });
            // disable selected fields in all other selects
            $sourceFields.each(function() {
                var selected = $(this).val();
                $(this).find('option').each(function() {
                    if ($.inArray($(this).val(), selectedFields) > -1 && 
                        $(this).val() != selected) {
                            $(this).prop('disabled', true);
                    }
                });
            });

        } else { // do not limit selection
            $sourceFields.find('option').prop('disabled', false);
        }
    }
    // update source selection when limit_source_selection is changed or any source field is changed
    $('.import-export #limit_source_selection, .import-export .field_map_item').on('change', function(e) {
        updateSourceSelection();
    });


    // imp-exp-clear-selection button
    $('.import-export .imp-exp-clear-selection').on('click', function(e) {
        e.preventDefault();
        $(this).closest('tr').find('.field_map_item').val('');
        updateSourceSelection();
    });

    

});