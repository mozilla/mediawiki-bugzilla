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
        <?php foreach($response->bugs as $bug): ?>
            <tr>
                <td>
                    <a href="https://bugzilla.mozilla.org/show_bug.cgi?id=<?php echo $bug->id ?>">
                        <?php echo $bug->id ?>
                    </a>
                </td>
                <td><a href="<?php echo $bug->url ?>"><?php echo $bug->summary ?></a></td>
                <td><?php echo $bug->status ?></td>
                <td><?php echo $bug->priority ?></td>
            </tr>
        <?php endforeach; ?>    
    </tbody>
</table>
