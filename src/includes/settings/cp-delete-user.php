<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

$userId = $_SERVER['REQUEST_METHOD'] === 'POST' ? (int)_post('id') : (int)_get('id');
$userRepo = new UserRepo(DBConnection::instance());
$user = $userRepo->findUserById($userId);
if (!$user) {
    echo "User with ID $userId not found";
    return;
}

if (isset($_POST['save']))
{
    $userRepo->deleteUserById($user->id);
    jsonExit([
        'ok' => true
    ]);
}

?>

<h4> <?php _e('set_deleteuser');?> </h4>

<form action="<?php mtt_settings_page_url(); ?>" method="post" data-ok-redirect="<?php mtturl('controlpanel/users'); ?>">
<input type="hidden" name="id" value="<?php echo $user->id; ?>">

<div class="mtt-settings-table">

<p><?php _e('warnDeleteUser'); ?></p>

<div class="tr form-bottom-buttons">
  <button type="submit"><?php _e('action_delete'); ?></button>
</div>

</div>
</form>
