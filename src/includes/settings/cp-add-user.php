<?php

if (!defined('MTT_PAGE')) {
    die("Unexpected usage");
}

if (isset($_POST['save']))
{
    try {
        $user = User::create( _post('username'), _post('name'), _post('email') );
        $user->setPassword(_post('password')); //empty password allowed
    }
    catch (InvalidArgumentException $e) {
        jsonExit( ['ok' => false, 'error' => $e->getMessage()] );
    }
    $userRepo = new UserRepo(DBConnection::instance());
    if (!$userRepo->canSaveUser($user, $err)) {
        jsonExit( ['ok' => false, 'error' => $err] );
    }
    $userRepo->saveUser($user);
    $t = [ 'ok' => true, 'saved' => 1, 'redirect' => get_mtturl('controlpanel/users') ];
    jsonExit($t);
}

?>

<h4> <?php _e('set_adduser');?> </h4>

<form action="<?php mtt_settings_page_url(); ?>" method="post">
<div class="mtt-settings-table">

<div class="tr">
  <div class="th"><?php _e('username');?></div>
  <div class="td">
    <input name="username" value="" class="in350">
  </div>
</div>

<div class="tr">
  <div class="th"><?php _e('name');?></div>
  <div class="td">
    <input name="name" value="" class="in350"> <br>
  </div>
</div>

<div class="tr">
  <div class="th"><?php _e('email');?></div>
  <div class="td">
    <input type="email" name="email" value="" class="in350">
  </div>
</div>

<div class="tr">
  <div class="th"><?php _e('password');?></div>
  <div class="td">
    <input type="password" name="password" value="" class="in350" autocomplete="new-password">
  </div>
</div>

<div class="tr form-bottom-buttons">
  <button type="submit"><?php _e('set_save'); ?></button>
</div>

</div>
</form>
