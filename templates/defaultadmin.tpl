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
    @param string $addclass - additional class for the input tag/button    
    @param string $inputproperties - additional properties for the input tag
    @param string $uiicon (optional) - icon for the submit button, exclude prefix 'ui-icon-'
    @param string $inline - (optional) output div container start & end, to inline multiple inputs
                          - not set (default - not inline), inline_start, inline_end, inline 
                        see: https://api.jqueryui.com/theming/icons/
*}
{function name=ImpExp_render_field option=''}
    {$inputclass=$option->addclass|default:''}
    {if $option->required}{$inputclass="$inputclass required"}{/if}
    {if empty($option->inline) || $option->inline=='inline_start'}
        <div class="pageoverflow {$option->divclass}">
        {if !in_array($option->inputtype, ['checkbox','submit','cancel'])}
            <p class="pageinput">{$option->label}{if $option->required}*{/if}:</p>
        {/if}
            <p class="pageinput">
    {/if}    

    {if $option->inputtype=='text'}
            <input id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" type="text" value="{$option->value}" size="{$option->size}" class="{$inputclass}" />

    {elseif $option->inputtype=='textarea'}
            <textarea id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" class="{$inputclass}" rows="{$option->rows}">{$option->value}</textarea>

    {elseif $option->inputtype=='select'}
            <select id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" class="{$inputclass}">
                {html_options options=$option->options selected=$option->value}
            </select>

    {elseif $option->inputtype=='checkbox'}
            <input type="hidden" name="{$actionid}{$option->fieldname}" value="0">
            <label><input id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" type="checkbox" value="1" {if $option->value}checked{/if} class="{$inputclass}"/>{$option->label}</label>

    {elseif $option->inputtype=='file_xml'}{* xml only for now *}
            <input type="file" id="{$option->fieldname}" name="{$actionid}{$option->fieldname}" size="{$option->size}" accept=".xml" class="{$inputclass}" />

    {elseif $option->inputtype=='field_map'}{* uses data from $import_export class *}
        {$limit_source_selection=$import_export->config_options['limit_source_selection']->value}
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

    {elseif $option->inputtype=='submit'}
            <button type="submit" id="{$option->fieldname}" name="{$actionid}submit" value="continue" class="{$inputclass} ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" role="button" aria-disabled="false">
                {if !empty($option->uiicon)}<span class="ui-button-icon-primary ui-icon ui-icon-{$option->uiicon}"></span>{/if}
                <span class="ui-button-text">{$option->label}</span>
            </button>


    {elseif $option->inputtype=='cancel'}
            <button type="submit" id="{$option->fieldname}-cancel" name="{$actionid}submit" value="cancel" class="{$inputclass} ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" role="button" aria-disabled="false">
                <span class="ui-button-icon-primary ui-icon ui-icon-circle-close"></span>
                <span class="ui-button-text">{$option->label}</span>
            </button>
    {/if}

    {if empty($option->inline) || $option->inline=='inline_end'}
            </p>
        {if !in_array($option->inputtype, ['checkbox','submit','cancel'])}
            <p class="pageinput">{$option->label}{if $option->required}*{/if}:</p>
        {/if}
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
            <div class="progress-bar bg-warning" role="progressbar" style="width: {$import_export->progress}%" aria-valuenow="{$import_export->progress}" aria-valuemin="0" aria-valuemax="100" data-url="{cms_action_url action=admin_ajax_data do=get_progress}" data-activate-url="{cms_action_url action=admin_ajax_data do=ajax_process}" data-key="{$import_export->ajax_key}">{$import_export->progress}%</div>
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


