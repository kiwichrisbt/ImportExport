{* wp_xml_to_LISE.tpl - v1.0 - 15Oct24 *}
{extends file="module_file_tpl:ImportExport;defaultadmin.tpl"} 

{block name=step_content}
    {if !empty($import_export)}
    <fieldset class="import-export-section">
        <legend>{$import_export->type_name}:</legend>

    {if $import_export->step==1}
        {foreach $import_export->config_options as $fieldname => $option}
            {if $option->step==1}
                {call name=ImpExp_render_field option=$option}
            {/if}
        {/foreach}

    {elseif $import_export->step==2}
        {foreach $import_export->config_options as $fieldname => $option}
            {if $option->step==2}
                {call name=ImpExp_render_field option=$option}
            {/if}
        {/foreach}

    {elseif $import_export->step==3}    
        {foreach $import_export->config_options as $fieldname => $option}
            {if $option->step==3}
                {call name=ImpExp_render_field option=$option}
            {/if}
        {/foreach}

    {/if}

    </fieldset>
    {/if}
{/block}