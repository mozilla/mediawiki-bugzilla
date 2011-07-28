{* SMARTY *}

<table class="bugzilla ui-helper-reset">
    <thead>
        <tr>
            <th>ID</th>
            <th>Summary</th>
            <th>Status</th>
            <th>Priority</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$bugs item=bug}
            <tr>
                <td>{$bug->id|escape}</td>
                <td>{$bug->summary|escape}</td>
                <td>{$bug->status|escape}</td>
                <td>{$bug->priority|escape}</td>
            </tr>
        {/foreach}
    </tbody>
</table>
