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

if (isset($_POST['deleteUser']))
{
    jsonExit([
        'ok' => false
    ]);
}
else if (isset($_POST['save']))
{
    $user->setUsername(trim(_post('username')));
    $user->setName(trim(_post('name')));
    $user->setEmail(trim(_post('email')));
    $user->setPassword(trim(_post('password')));
    if (!$userRepo->canSaveUser($user, $err)) {
        jsonExit([
            'ok' => false,
            'error' => $err,
        ]);
    }
    $userRepo->updateUserProperties($user);
    jsonExit([
        'ok' => true
    ]);
}
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    jsonExit([
        'ok' => false
    ]);
}


$_u = function(string $key) use($user) {
    print htmlspecialchars($user->{$key});
}


?>

<h4> <?php _e('set_edituser');?> </h4>

<div class="mtt-settings-table">

<p><a href="<?php mtturl('controlpanel/delete-user', ['id'=>$user->id]); ?>" class="mtt-settings-button"><?php _e('btn_deleteuser'); ?></a></p>

<form action="<?php mtt_settings_page_url(); ?>" method="post" data-ok-reload="yes">
<input type="hidden" name="id" value="<?php $_u('id'); ?>">

<div class="tr">
  <div class="th"><?php _e('username');?></div>
  <div class="td">
    <input name="username" value="<?php $_u('username'); ?>" class="in350">
  </div>
</div>

<div class="tr">
  <div class="th"><?php _e('name');?></div>
  <div class="td">
    <input name="name" value="<?php $_u('name'); ?>" class="in350"> <br>
  </div>
</div>

<div class="tr">
  <div class="th"><?php _e('email');?></div>
  <div class="td">
    <input type="email" name="email" value="<?php $_u('email'); ?>" class="in350">
  </div>
</div>

<div class="tr">
  <div class="th"><?php _e('new_password');?></div>
  <div class="td">
    <input type="password" name="password" value="" class="in350" autocomplete="new-password">
  </div>
</div>

<div class="tr form-bottom-buttons">
  <button type="submit"><?php _e('set_save'); ?></button>
</div>

</form>

</div>

