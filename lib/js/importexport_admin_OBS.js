// importexport_admin_OBS.js - OBSOLETE - just kept for reference
$(document).ready(function() {
  $('#clear_news_posts').on('change',function(){
    var v = $(this).val();
    if( v == 0 ) {
      $('#cont_clear_news_fielddefs').hide();
      $('#cont_clear_news_categories').hide();
    }
    else {
      $('#cont_clear_news_fielddefs').show();
      $('#cont_clear_news_categories').show();
    }
  });
  $('#clear_news_posts').trigger('change'); // for document load.

  $('#import_posts').on('change',function(){
    var v = $(this).val();
    if( v == 0 ) {
      $('#section_posts').hide();
    }
    else {
      $('#section_posts').show();
    }
  });
  $('#import_posts').trigger('change');

  $('#import_images').on('change',function(){
    var v = $(this).val();
    if( v == 0 ) {
      $('#section_images').hide();
    }
    else {
      $('#section_images').show();
    }
  });
  $('#import_images').trigger('change');

  $('#import_users').on('change',function(){
    var v = $(this).val();
    if( v == 0 ) {
      $('#section_users').hide();
    }
    else {
      $('#section_users').show();
    }
  });
  $('#import_users').trigger('change');

  $('#enable_postmeta').on('change',function(){
    var v = $(this).val()
    if( v == 0 ) {
      $('#fs_postmeta').hide();
    }
    else {
      $('#fs_postmeta').show();
    }
  })
  $('#enable_postmeta').trigger('change');

  $('a.add_row').click(function(){
    var $el = $('tr#postmeta_skeleton').clone();
    $el.removeAttr('id');
    $('table#postmeta_map > tbody').append($el);
  });
  $('body').on('click','a.delete_row',function(e){
    e.preventDefault();
    if( $(this).closest('tr').attr('id') == 'postmeta_skeleton' ) {
      alert('cannot delete this row');
    }
    else {
      $(this).closest('tr').remove();
    }
  });

});