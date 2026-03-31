
<!-- Page: Login -->
<div id="page_login">
  <div id="authmsg">&nbsp;</div>
  <div id="authform">
    <form id="login_form" onsubmit="return false">
    <fieldset>
    <div class="auth-content">
      <div class="h"><?php _e('username');?></div>
      <div><input name="username" id="username" class="form-input" autocapitalize="off" autocorrect="off" required autofocus autocomplete="username"></div>
      <div class="h"><?php _e('password');?></div>
      <div><input type="password" name="password" id="password" class="form-input" autocomplete="current-password"></div>
      <div><a href="<?php mtturl('reset');?>">Forgot password?</a></div>
    </div>
    <div class="form-bottom-buttons">
      <button type="submit"><?php _e('btn_login'); ?></button>
    </div>
    </fieldset>
    <div class="overlay mtt-hidden"></div>
    </form>
  </div>
</div>
<!-- End of Page: Login -->

<script type="text/javascript">
document.getElementById('mtt').classList.add('page-login');
document.getElementById('login_form').onsubmit = function(e) {
    e.preventDefault();
    const form = this;
    const authmsg = document.getElementById('authmsg');
    const fieldset = document.querySelector('#login_form fieldset');
    mytinytodo.db.errorCallback = function(msg) {
        fieldset.removeAttribute('disabled');
        form.classList.remove('mtt-overlay');
        form.password.focus();
        authmsg.textContent = msg;
        authmsg.classList.add('show');
    }
    authmsg.classList.remove('show');
    fieldset.setAttribute('disabled', '');
    form.classList.add('mtt-overlay');
    mytinytodo.db.request( 'login', {
        username: form.username.value,
        password: form.password.value
    }, function(json, isError) {
        fieldset.removeAttribute('disabled');
        form.classList.remove('mtt-overlay');
        form.password.focus();
        if (json.logged) {
            window.location = mytinytodo.mttUrl;
        }
        else {
            authmsg.textContent = json.error;
            authmsg.classList.add('show');
        }
    });
};
</script>
