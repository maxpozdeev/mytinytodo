<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

function listUsers()
{
    $db = DBConnection::instance();
    $userRepo = new UserRepo($db);
    $users = $userRepo->findUsers();
    $lists = [];
    foreach ($userRepo->countLists() as $r) {
        $lists[(int)$r['id']] = $r;
    }
    $tasks = [];
    foreach ($userRepo->countTasks() as $r) {
        $tasks[(int)$r['id']] = $r;
    }

    foreach ($users as $u) {
        $editUrl = routerMakeUrl('controlpanel/edit-user', ['id'=> (int)$u->id]);
        $cntLists = $lists[$u->id]['count'] ?? 0;
        $cntTasks = $tasks[$u->id]['count'] ?? 0;
        echo "<tr>".
            "<td><a href='$editUrl'>{$u->username}</a></td>".
            "<td>{$u->name}</td>".
            "<td>{$u->email}</td>".
            "<td> $cntLists / $cntTasks </td>".
            "<td></td>".
            "</tr>\n";
    }
}

?>

<h4> <?php _e('set_users');?> </h4>

<form action="<?php mtt_settings_page_url(); ?>" method="post">
<div class="mtt-settings-table">

<p><a href="<?php mtturl('controlpanel/add-user'); ?>" class="mtt-settings-button"><?php _e('btn_adduser'); ?></a></p>

<table style="width:100%;">
  <tr>
    <th><?php _e('username'); ?></th>
    <th><?php _e('name'); ?></th>
    <th><?php _e('email'); ?></th>
    <th><?php _e('lists'); ?> / <?php _e('tasks'); ?></th>
    <th></th>
  </tr>

  <?php listUsers(); ?>

</table>

</div>
</form>
