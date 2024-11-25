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
        ajaxProcessing();   // automatically start when the progress bar is present
    }
    $('.import-export #cancel_ajax-cancel').on('click', function(e) {
        var $imp_exp = $('.import-export');
        if ($imp_exp.hasClass('ajax-cancelled') || $imp_exp.hasClass('failed') || $imp_exp.hasClass('completed')) {
            $imp_exp.removeClass('ajax-cancelled');
            // allow normal cancel - to go back to the start
        } else {
            e.preventDefault();
            $imp_exp.addClass('cancel-ajax');
        }
    });
    // get the saved key data and restart the processing at the attachment stage
    $('.import-export #submit_retry_attachments').on('click', function(e) {
        e.preventDefault();
        var $imp_exp = $('.import-export');
        var retry_data = JSON.parse(sessionStorage.getItem('retry_data'));
        sessionStorage.removeItem('retry_data');
        if (retry_data) {
            $imp_exp.removeClass('failed completed');
            $imp_exp.find('.pageerrorcontainer .pageerror').remove();
            $imp_exp.find('.pageerrorcontainer').addClass('hidden');
            updateMessages('Retrying attachments...');
            ajaxProcessing(retry_data.key, 'attachment');
        }
    });

    // start ajax processing
    function ajaxProcessing(key, retry=null) {
        var $progress_bar = $('.import-export .progress-bar');
        var max_repeat_progress = 10;   // if no progress for x iterations, stop
        var key = key ? key : $progress_bar.data('key'); // if key undefined or null, get from data attribute
        var ajax_data = { 'key': key };
        if (retry) ajax_data['retry'] = retry;
        $.ajax({
            url: $progress_bar.data('activate-url'),
            dataType: "json",
            data: ajax_data,
            success: function(data) {
                if ( data==null || data.status==null || data.status=='error' ) {
                    updateErrors('Processing error occurred.');
                    $('.import-export').addClass('failed');


                } else {
                    var $imp_exp = $('.import-export');
                    var repeat_progress = parseInt(sessionStorage.getItem('repeat_progress')) ?? 0;
                    var previous_progress = parseInt(sessionStorage.getItem('progress')) ?? 0;
                    updateProgressBar(data.progress);
                    if (data.messages && data.messages.length) updateMessages(data.messages);
                    if (data.errors && data.errors.length) updateErrors(data.errors);
                    if (data.feedback && Object.keys(data.feedback).length) updateFeedback(data.feedback);

                    if (data.status!='completed') {     // continue processing
                        if ($imp_exp.hasClass('cancel-ajax')) {
                            $imp_exp.removeClass('cancel-ajax').addClass('ajax-cancelled');
                            updateErrors('Processing cancelled');

                        } else if (data.progress == previous_progress && repeat_progress >= max_repeat_progress) { 
                            updateErrors('No progress - after '+max_repeat_progress+' iterations');

                        } else {    // continue processing
                            repeat_progress = (data.progress == previous_progress) ? repeat_progress+1 : 0;
                            sessionStorage.setItem('repeat_progress', repeat_progress);
                            ajaxProcessing(data.key);
                        }
                    }
                    if (data.status=='completed') {
                        $imp_exp.addClass('completed');
                        if ( (data.feedback?.['file-error-count'] ?? 0) > 0 ) {
                            $('#submit_retry_attachments').removeClass('hidden');
                            sessionStorage.setItem('retry_data', JSON.stringify(data));
                        }
                        $('#cancel_ajax-cancel').addClass('hidden');
                        $('#submit_completed').removeClass('hidden');
                    }
                }
            },
            error: function( jqXHR, textStatus, errorThrown ) {
                updateErrors('Processing error occurred ('+textStatus+') - '+errorThrown);
                $('.import-export').addClass('failed');
            }
        });
        $('#cancel_ajax-cancel').removeClass('hidden');
        $('#submit_retry_attachments, #submit_completed').addClass('hidden');
    }

    // update progress bar - with either percentage or text & save in session
    function updateProgressBar(progress) {
        var $progress_bar = $('.import-export .progress-bar');
        sessionStorage.setItem('progress', progress);   // save for next iteration (initeger)
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

    // updateFeedback feedback is an object of feedbackClass => data
    //  - feedback elements should have class $('.import-export .feedback.feedbackClass')
    function updateFeedback(feedback) {
        for (var feedbackClass in feedback) {
            var $feedbackElement = $('.import-export .feedback.'+feedbackClass).last();
            if ($feedbackElement.length) {
                $feedbackElement.html(feedback[feedbackClass]);
            }
        }
    }
    
    

});