<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

function listUsers()
{
    $userRepo = new UserRepo(DBConnection::instance());
    $users = $userRepo->findUsers();
    foreach ($users as $u) {
        $editUrl = routerMakeUrl('controlpanel/edit-user', ['id'=> (int)$u->id]);
        echo "<div class=tr>".
            "<div class=th><a href='$editUrl'>{$u->username}</a></div>".
            "<div class=td>{$u->name}</div>".
            "<div class=td>{$u->email}</div>".
            "<div class=td>  </div>".
            "</div>\n";
    }
}

?>

<h4> <?php _e('set_users');?> </h4>

<form action="<?php mtt_settings_page_url(); ?>" method="post">
<div class="mtt-settings-table">

<div class="tr">
  <div class="td"> <a href="<?php mtturl('controlpanel/add-user'); ?>" class="mtt-settings-button">Add new user</a> </div>
</div>

<?php listUsers(); ?>

</div>
</form>
