{* defaultadmin.tpl - v1.0 - 10Oct24 

    This template should be extended by child import/export templates
*}


{*  function ImpExp_render_field
    Used to output a standard html wrapper and input for an admin page. 
    Alternatively just create a your own html with input if something more custom is required

    @param string $fieldname (required) - parameter name, excluding the $actionid prefix
    @param string $value - parameter value
    @param string $inputtype - text, textarea, select, checkbox, file_xml, submit, 
                                cancel+submit, field_map
    @param string $label - label text
    @param boolean $required - whether the field is required
    @param array $options - array of options for select or checkbox
    @param integer $size - size of input field
    @param integer $rows - number of rows for textarea
    @param string $divclass - class name for the div
    @param string $inputproperties - additional properties for the input tag
    @param string $uiicon (optional) - icon for the submit button, exclude prefix 'ui-icon-'
                        see: https://api.jqueryui.com/theming/icons/
*}
{function name=ImpExp_render_field option=''}
{* {function name=ImpExp_render_field fieldname='' value='' inputtype='text' label='' required=false options='' size=80 rows=3 divclass='' inputproperties='' uiicon=''} *}

    {if $option->inputtype=='text'}
        <div class="pageoverflow {$option->divclass}">
            <p class="pageinput">{$option->label}{if $option->required}*{/if}:</p>
            <p class="pageinput">
                <input id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" type="text" value="{$option->value}" size="{$option->size}" {if $option->required}class="required"{/if} />
            </p>
        </div>

    {elseif $option->inputtype=='textarea'}
        <div class="pageoverflow {$option->divclass}">
            <p class="pageinput">{$option->label}{if $option->required}*{/if}:</p>
            <p class="pageinput">
                <textarea id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" {if $option->required}class="required"{/if} rows="{$option->rows}">{$option->value}</textarea>
            </p>
        </div>

    {elseif $option->inputtype=='select'}
        <div class="pageoverflow {$option->divclass}">
            <p class="pageinput">{$option->label}{if $option->required}*{/if}:</p>
            <p class="pageinput">
                <select id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" {if $option->required}class="required"{/if}>
                    {html_options options=$option->options selected=$option->value}
                </select>
            </p>
        </div>

    {elseif $option->inputtype=='checkbox'}
        <div class="pageoverflow {$option->divclass}">
            <p class="pageinput">
                <input type="hidden" name="{$actionid}{$option->fieldname}" value="0">
                <label><input id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" type="checkbox" value="1" {if $option->value}checked{/if} />{$option->label}</label>
            </p>
        </div>

    {elseif $option->inputtype=='file_xml'}{* xml only for now *}
        <div class="pageoverflow {$option->divclass}">
            <p class="pageinput">{$option->label}{if $option->required}*{/if}:</p>
            <p class="pageinput">
                <input type="file" id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" size="{$option->size}" accept=".xml" {if $option->required}class="required"{/if} />
            </p>
        </div>

    {elseif $option->inputtype=='field_map'}{* uses data from $import_export class *}
        {$limit_source_selection=$import_export->config_options['limit_source_selection']->value}
        <div class="pageoverflow {$option->divclass}">
            <p class="pageinput">{$option->label}{if $option->required}*{/if}:</p>

            <div class="table-responsive import-export">
                <table class="field-map-table  table-layout">
                    <thead>
                        <tr class="field-map-header">
                            <th class="panel panel-left"></th>
                            <th class="panel panel-destination">{$import_export->destination_name} {$mod->Lang('field_map_destination_fields')}</th>
                            <th class="panel panel-source">{$import_export->source_name} {$mod->Lang('field_map_source_fields')}</th>
                            <th class="panel panel-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach $import_export->field_map as $fieldname => $field}{* destination *}
                        <tr class="field-map-item">
                            <td class="panel panel-left">{$field@iteration}</td>
                            <td class="panel panel-destination">{$fieldname}{if $field->required}*{/if}</td>
                            <td class="panel panel-source">
                                <select name="{$actionid}field_map_item[{$fieldname}]" class="field_map_item">
                                    <option value="">{$mod->Lang('label_select_source')}</option>
                                {foreach $import_export->source_fields as $key => $value}
                                    <option value="{$key}" {if $key==$field->source_field}selected{elseif $limit_source_selection && in_array($key, $selected_sources)}disabled{/if}>{$key}</option>
                                {/foreach}
                                </select>
                            </td>
                            <td class="panel panel-right">
                                <button class="imp-exp-clear-selection imp-exp-btn imp-exp-btn-default imp-exp-icon-only" title="{$mod->Lang('clear_selection')}" role="button" aria-disabled="false"><span class="imp-exp-icon-trash-can-regular"></span></button>
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>      
            </div>
        </div>


    {elseif $option->inputtype=='submit' || $option->inputtype=='cancel+submit'}
        <div class="pageoverflow {$option->divclass}">
            <p class="pageinput">
            {if $option->inputtype=='cancel+submit'}
                <button type="submit" id="{$option->fieldname}-cancel" name="{$actionid}submit" value="cancel" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" role="button" aria-disabled="false">
                    <span class="ui-button-icon-primary ui-icon ui-icon-circle-close"></span>
                    <span class="ui-button-text">{$mod->Lang('cancel')}</span>
                </button>
            {/if}
                <button type="submit" id="{$option->fieldname}" name="{$actionid}submit" value="continue" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" role="button" aria-disabled="false">
                    {if !empty($option->uiicon)}<span class="ui-button-icon-primary ui-icon ui-icon-{$option->uiicon}"></span>{/if}
                    <span class="ui-button-text">{$option->label}</span>
                </button>
            </p>
        </div>
    {/if}
{/function}


{form_start action='defaultadmin' class="import-export" extraparms=$extraparms}

    {* select Type of Import Export *}
    <div class="pageoverflow m_bottom_25">
        <p class="pageinput"><b>{$mod->Lang('selected_import_export')}</b>:</p>
        <p class="pageinput">
            <select id="import_export_type" name="{$actionid}import_export_type" data-current="{$selected_type}">
                {html_options options=$input_output_type_options selected=$selected_type}
            </select>           
            <button type="submit" id="change-type" name="{$actionid}submit" value="change_type" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary ui-button-disabled ui-state-disabled" role="button" aria-disabled="true" disabled>
                <span class="ui-button-icon-primary ui-icon ui-icon-circle-check"></span>
                <span class="ui-button-text">{$mod->Lang('change_type')}</span>
            </button>
        </p>
    </div>

    {* Step Headings *}
    {if isset($import_export->steps)}
    <div class="steps pageoverflow m_bottom_25">
        <div class="step-headings progress">
        {foreach $import_export->steps as $step_number => $heading}
            <div class="step-bar {if $import_export->step==$step_number}active{elseif $import_export->step>$step_number}done{/if}" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">{$step_number}. {$mod->Lang($heading)}</div>
        {/foreach}
        </div>
    </div>
    {/if}
    {* Progress Bar (optional) *}
    {if isset($import_export->progress)}
    <div class="steps pageoverflow m_bottom_25">
        <p class="pageinput">{$mod->Lang('import_progress')}:</p>
        <div class="progress">
            <div class="progress-bar bg-warning" role="progressbar" style="width: {$import_export->progress}%" aria-valuenow="{$import_export->progress}" aria-valuemin="0" aria-valuemax="100" data-url="{cms_action_url action=admin_ajax_data do=get_progress}" data-activate-url="{cms_action_url action=admin_ajax_data do=start_ajax_processing key=$import_export->ajax_key}">{$import_export->progress}%</div>
        </div>
    </div>
    {/if}


    {* Messages & Errors - hide if empty*}
    {if isset($import_export->messageManager)}
        {$messages=$import_export->messageManager->getMessages()}
        <div class="pagemcontainer message success no-slide m_bottom_25 {if empty($messages)}hidden{/if}">
        {foreach $messages as $message}
            <p class="pagemessage">{$message}</p>
        {/foreach}
        </div>
        {$errors=$import_export->messageManager->getErrors()}
        <div class="pageerrorcontainer error no-slide m_bottom_25 {if empty($errors)}hidden{/if}">
        {foreach $errors as $error}
            <p class="pageerror">{$error}</p>
        {/foreach}
        </div>
    {/if}

{block name=step_content}
{*  
    child templates should extend this block to add their own content
*}
{/block}

{form_end}








{* 
<pre style="width:90%;">
For now leave the following in place & ignore - remove later
</pre>

<fieldset class="import-export-section">
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
<fieldset class="import-export-section">
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
  </div> *}



{* Short term hack only *}
    {* <div class="pageoverflow">
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
    </div>{ * #section_images * }
  </div>#section_posts * }
*}


    {* <div class="pageoverflow">
        <p class="pagetext">{$mod->Lang('author_default')}:</p>
        <p class="pageinput">
          	<select name="{$actionid}default_author">
	            {html_options options=$default_authors selected=$opts.default_author}
	        </select>
        <br/>{$mod->Lang('info_author_default')}
        </p>
    </div> *}
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

  {* <div class="pageoverflow">
    <p class="pagetext">{$mod->Lang('enable_postmeta_mapping')}:</p>
    <p class="pageinput">
      <select name="{$actionid}enable_postmeta_mapping" id="enable_postmeta">
      {cge_yesno_options selected=$opts.enable_postmeta_mapping}
      </select>
    </p>
  </div>

</fieldset>


<fieldset id="fs_postmeta import-export-section">
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


<div class="pageoverflow import-export-section">
  <p class="pageinput">
    <input type="submit" name="{$actionid}wp_import" value="{$mod->Lang('import')}"/>
  </p>
</div>
{/if} *}

