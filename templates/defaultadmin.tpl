<script type="text/javascript">
$(document).ready(function(){
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
</script>

{$formstart}
<div class="warning">{$mod->Lang('warn_wp_import')}</div>
<div class="warning">{$mod->Lang('warn_longtime')}</div>

<fieldset>
  <legend>{$mod->Lang('wpsettings')}:</legend>
  <div class="pageoverflow m_bottom_15">
    <p class="pageinput">*{$mod->Lang('root_path')}:</p>
    <p class="pageinput">
      <input type="text" name="{$actionid}root_path"  size="50" value="{$wp.root_path}"/>
      <input type="submit" name="{$actionid}wp_check" value="{$mod->Lang('check')}"/>
      <br/>{$mod->Lang('info_root_path', $this_path)}
    </p>
  </div>
  <div class="pageoverflow m_bottom_15">
    <p class="pageinput">*{$mod->Lang('root_url')}:</p>
    <p class="pageinput">
      <input type="text" name="{$actionid}root_url" size="50" value="{$wp.root_url}"/>
      <br/>{$mod->Lang('info_root_url', 'test')}
    </p>
  </div>
  <div class="pageoverflow m_bottom_15">
    <p class="pageinput">*{$mod->Lang('db_host')}:</p>
    <p class="pageinput">
      <input type="text" name="{$actionid}db_host" value="{$wp.db_host}"/>
    </p>
  </div>
  <div class="pageoverflow m_bottom_15">
    <p class="pageinput">*{$mod->Lang('db_user')}:</p>
    <p class="pageinput">
      <input type="text" name="{$actionid}db_user" value="{$wp.db_user}"/>
    </p>
  </div>
  <div class="pageoverflow m_bottom_15">
    <p class="pageinput">*{$mod->Lang('db_pass')}:</p>
    <p class="pageinput">
      <input type="text" name="{$actionid}db_pass" value="{$wp.db_pass}"/>
    </p>
  </div>
  <div class="pageoverflow m_bottom_15">
    <p class="pageinput">*{$mod->Lang('db_name')}:</p>
    <p class="pageinput">
      <input type="text" name="{$actionid}db_name" value="{$wp.db_name}"/>
    </p>
  </div>
  <div class="pageoverflow m_bottom_15">
    <p class="pageinput">*{$mod->Lang('db_prefix')}:</p>
    <p class="pageinput">
      <input type="text" name="{$actionid}db_prefix" value="{$wp.db_prefix}"/>
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pageinput">
      <input type="submit" name="{$actionid}wp_test" value="{$mod->Lang('test')}"/>
    </p>
  </div>

</fieldset>

{if isset($db_tested)}
<fieldset>
  <legend>{$mod->Lang('options')}:</legend>
  <div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('clear_news_posts')}:</p>
    <p class="pageinput">
      <select id="clear_news_posts" name="{$actionid}clear_news_posts">
        {cge_yesno_options selected=$opts.clear_news_posts|default:1}
      </select>
      <br/>{$mod->Lang('info_clear_news_posts')}
    </p>
  </div>
  <div class="pageoverflow" id="cont_clear_news_fielddefs" style="padding-left: 3em;">
    <p class="pagetext">{$mod->Lang('clear_news_fielddefs')}:</p>
    <p class="pageinput">
      <select id="clear_news_fielddefs" name="{$actionid}clear_news_fielddefs">
        {cge_yesno_options selected=$opts.clear_news_fielddefs|default:0}
      </select>
      <br/>{$mod->Lang('info_clear_news_fielddefs')}
    </p>
  </div>
  <div class="pageoverflow" id="cont_clear_news_categories" style="padding-left: 3em;">
    <p class="pagetext">{$mod->Lang('clear_news_categories')}:</p>
    <p class="pageinput">
      <select id="clear_news_categories" name="{$actionid}clear_news_categories">
        {cge_yesno_options selected=$opts.clear_news_categories|default:0}
      </select>
      <br/>{$mod->Lang('info_clear_news_categories')}
    </p>
  </div>
{* Short term hack only *}
{*
  <div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('import_categories')}:</p>
    <p class="pageinput">
      {cge_yesno_options prefix=$actionid name=import_categories selected=$opts.import_categories}
      <br/>{$mod->Lang('info_import_categories')}
    </p>
  </div>
*}

{* Short term hack only *}
    <div class="pageoverflow">
        <p class="pagetext">Default News Category:</p>
        <p class="pageinput">
            <select name="{$actionid}default_category">
                {html_options options=$default_categories selected=$opts.default_category}
            </select><br/>
            All imported posts will be set to this category. NB: Short term fix to get Importer to work faster!!!
        </p>
    </div>

  <div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('import_posts')}:</p>
    <p class="pageinput">
      {cge_yesno_options prefix=$actionid name=import_posts selected=$opts.import_posts id="import_posts"}
      <br/>{$mod->Lang('info_import_posts')}
    </p>
  </div>

  <div id="section_posts">
    <div class="pageoverflow" style="padding-left: 3em;">
      <p class="pagetext">{$mod->Lang('import_images')}:</p>
      <p class="pageinput">
        {cge_yesno_options prefix=$actionid name=import_images selected=$opts.import_images id="import_images"}
        <br/>{$mod->Lang('info_import_images')}
      </p>
    </div>
    <div id="section_images">
        <div class="pageoverflow" style="padding-left: 6em;">
            <p class="pagetext">{$mod->Lang('image_paths')}:</p>
            <p class="pageinput">
                <textarea name="{$actionid}image_paths" rows="2">{$opts.image_paths}</textarea>
                <br/>{$mod->Lang('info_image_paths')}
            </p>
        </div>
        <div class="pageoverflow" style="padding-left: 6em;">
            <p class="pagetext">{$mod->Lang('image_dest')}:</p>
            <p class="pageinput">
                <input type="text" name="{$actionid}image_dest" value="{$opts.image_dest}"/>
                <br/>{$mod->Lang('info_image_dest')}
            </p>
        </div>
            <div class="pageoverflow" style="padding-left:3em;">
            <p class="pagetext">Import Post Thumbnails to:</p>
            <p class="pageinput">
                <select name="{$actionid}import_thumbnails">
                    {html_options options=$all_field_defs selected=$opts.import_thumbnails}
                </select><br/>
                Imported post thumnails will be added to this custom field. NB: Leave empty to not import thumbnails.
            </p>
            </div>
    </div>{* #section_images *}
  </div>{* #section_posts *}



    <div class="pageoverflow">
        <p class="pagetext">{$mod->Lang('author_default')}:</p>
        <p class="pageinput">
          	<select name="{$actionid}default_author">
	            {html_options options=$default_authors selected=$opts.default_author}
	        </select>
        <br/>{$mod->Lang('info_author_default')}
        </p>
    </div>
    {*
    <div class="pageoverflow">
        <p class="pagetext">{$mod->Lang('import_users')}:</p>
        <p class="pageinput">
        {cge_yesno_options prefix=$actionid name=import_users selected=$opts.import_users id="import_users"}
        <br/>{$mod->Lang('info_import_users')}
        </p>
    </div> 
    <div class="pageoverflow" id="section_users" style="padding-left: 3em;">
        <p class="pagetext">{$mod->Lang('user_pw')}:</p>
        <p class="pageinput">
        <input type="text" name="{$actionid}user_pw" value="{$opts.user_pw}"/>
        <br/>{$mod->Lang('info_user_pw')}
        </p>
    </div>
    *}

  <div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('enable_postmeta_mapping')}:</p>
    <p class="pageinput">
      <select name="{$actionid}enable_postmeta_mapping" id="enable_postmeta">
      {cge_yesno_options selected=$opts.enable_postmeta_mapping}
      </select>
    </p>
  </div>

</fieldset>


<fieldset id="fs_postmeta">
  <legend>{$mod->Lang('legend_postmeta_mapping')}:</legend>
  <div class="information">{$mod->Lang('info_postmeta_mapping')}</div>

  <table id="postmeta_map" class="pagetable">
    <thead>
      <tr>
        <th style="width: 50%;">{$mod->Lang('col_wpkey')}:</th>
	<th style="width: 44%;">{$mod->Lang('col_fldname')}:</th>
	<th style="width: 3%; text-align: right;" class="pageicon">
	  <a class="add_row">{cgimage image="icons/system/newobject.gif" alt=$mod->Lang('title_add_row')}</a>
	</th>
	<th style="text-align: right;" class="pageicon"></td>
      </tr>
    </thead>
    <tbody>
      {if isset($opts.postmeta_mapping) && count($opts.postmeta_mapping)}
        {foreach $opts.postmeta_mapping as $wpkey => $fldname}
        <tr {if $fldname@first}id="postmeta_skeleton"{/if}>
          <td>
  	    <select name="{$actionid}wp_postmeta[wpkey][]">
	      {html_options options=$wp_metakeys selected=$wpkey}
	    </select>
	  </td>
	  <td>
	    <input type="text" name="{$actionid}wp_postmeta[fldname][]" value="{$fldname}"/>
	  </td>
	  <td></td>
	  <td>
	    <a class="delete_row">{cgimage class="systemicon icon_deleterow" image="icons/system/delete.gif" alt=$mod->Lang('title_delete_row')}</a>
	  </td>
        </tr>
	{/foreach}
      {else}
        <tr id="postmeta_skeleton">
          <td>
  	    <select name="{$actionid}postmeta[wpkey][]">
	      {html_options options=$wp_metakeys}
	    </select>
	  </td>
	  <td>
	    <input type="text" name="{$actionid}postmeta[fldname][]"/>
	  </td>
	  <td></td>
	  <td>
	    <a class="delete_row">{cgimage class="systemicon icon_deleterow" image="icons/system/delete.gif" alt=$mod->Lang('title_delete_row')}</a>
	  </td>
        </tr>
      {/if}
    </tbody>
  </table>
</fieldset>

<div class="pageoverflow">
  <p class="pageinput">
    <input type="submit" name="{$actionid}wp_import" value="{$mod->Lang('import')}"/>
  </p>
</div>
{/if}

{$formend}