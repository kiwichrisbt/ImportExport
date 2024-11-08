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


    // Ajax Processing & Progress Bar for Import/Export
    if ($('.import-export .progress-bar').length) {
        var $progress_bar = $('.import-export .progress-bar');

        startAjaxProcessing($progress_bar);
        
        // startProgressBar($progress_bar);
    }

    // start ajax processing
    function startAjaxProcessing($progress_bar) {
        var activate_url = $progress_bar.data('activate-url');
        if (activate_url) {
            $.ajax({
                url: activate_url,
                dataType: "json",
                data: {},
                success: function(data) {
                    if (data.success) {
                        updateProgressBar(data.progress);
                        // if data.messages is set and not empty updateMessages(data.messages)
                        if (data.messages) updateMessages(data.messages);
                        if (data.errors) updateErrors(data.errors);

                    } else {
                        updateErrors('Processing error occurred - '+data.error);
                    }
                },
                error: function() {
                    updateErrors('Processing error occurred');
                }
            });
        }
    }

    // start ajax progress bar - don't use jquery UI progressbar
    function startProgressBar($progress_bar) {    
        var interval_time = 1000;       // 1 second
        var max_repeat_progress = 20;   // if no progress for x iterations, stop
        var repeat_progress = 0;
        var previous_progress = null;
        var ajax_url = $progress_bar.data('url');
        if (ajax_url) {
            var progress_bar = $('.import-export .progress-bar').progressbar({
                value: false
            });
            var progress_interval = setInterval(function() {
                if (repeat_progress >= max_repeat_progress-1) { // stop if no progress
                    clearInterval(progress_interval);
                    updateErrors('No progress');

                } else {
                    $.ajax({
                        url: ajax_url,
                        dataType: "json",
                        data: {},
                        success: function(data) {
                            if (data.progress && data.progress>=0 && data.progress<=100) {
                                updateProgressBar(data.progress);
                                if (data.progress == previous_progress) {
                                    repeat_progress++;
                                } else {
                                    previous_progress = data.progress;
                                    repeat_progress = 0;
                                }
                                if (data.progress == 100) {
                                    clearInterval(progress_interval); // done
                                    updateMessages('Import/Export completed');
                                }
                            } else {
                                clearInterval(progress_interval);
                                updateErrors('ProgressBar Error occurred - '+data.progress);
                            }
                        },
                        error: function() {
                            clearInterval(progress_interval);
                            updateErrors('ProgressBar Error occurred');
                        }
                    });
                }
            }, interval_time);
        }
    }

    // update progress bar - with either percentage or text
    function updateProgressBar(progress) {
        var $progress_bar = $('.import-export .progress-bar');
        if (progress == parseInt(progress)) progress = progress + '%';
        $progress_bar.css('width', progress)
                     .attr('aria-valuenow', progress)
                     .html(progress);
        if (progress == "100%") {
            $progress_bar.removeClass('active').addClass('done');
        }
    }

    // updateMessages - 
    function updateMessages(messages) {  // can be string or array of strings
        var $message_container = $('.import-export .pagemcontainer').removeClass('hidden');
        if (typeof messages=='string') messages = [messages];
        for (var i=0; i<messages.length; i++) {
            $message_container.append('<p class="pagemessage">'+messages[i]+'</p>');
        }
    }

    // updateErrors
    function updateErrors(errors) {  // can be string or array of strings
        var $error_container = $('.import-export .pageerrorcontainer').removeClass('hidden');
        if (typeof errors=='string') errors = [errors];
        for (var i=0; i<errors.length; i++) {
            $error_container.append('<p class="pageerror">'+errors[i]+'</p>');
        }
    }
    
    

});