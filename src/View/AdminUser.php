<?php
/**
 * Shared Media Tagger
 * User Admin View
 *
 * @var Attogram\SharedMedia\Tagger\TaggerAdmin $smt
 * @var array $users
 */
declare(strict_types = 1);


?>
<form method="POST">
<div class="box white">
    <p>
        <b><?= count($users) ?></b> Users
    </p>
    <table border="1">
        <tr>
            <td>&nbsp;</td>
            <td>ID</td>
            <td>Tags</td>
            <td>Last</td>
            <td>IP/Host</td>
            <td>User Agent</td>
        </tr>
        <?php foreach ($users as $user) { ?>
        <tr>
            <td>
                <input type="checkbox" name="d<?= $user['id'] ?>" />
            </td>
            <td>
                <?= $user['id'] ?>
            </td>
            <td>
                <?= $user['tagCount'] ?>
            </td>
            <td class="nobr">
                <small><?= $user['last'] ?></small>
            </td>
            <td>
                <small><?= $user['ipAndHost'] ?></small>
            </td>
            <td>
                <small><?= $user['user_agent'] ?></small>
            </td>
        </tr>
        <?php } ?>
    </table>
    <p>
        <input type="submit" value="Delete selected users" />
    </p>
</div>
</form>
