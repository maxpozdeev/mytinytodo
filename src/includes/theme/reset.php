
<!-- Page: Reset -->
<div id="page_auth">
  <div class="auth-header">
    <h3><?php _e('resetpassword_h'); ?></h3>
    <div><?php _e('resetpassword_d'); ?></div>
  </div>
  <div id="authmsg">&nbsp;</div>
  <div id="authform">
    <form id="login_form" onsubmit="return false">
    <fieldset>
    <div class="auth-content">
      <div class="h"><?php _e('email');?></div>
      <div><input name="email" class="form-input" autocapitalize="off" autocorrect="off" required autofocus autocomplete="email"></div>
    </div>
    <div class="form-bottom-buttons">
      <button type="submit"><?php _e('btn_send_reset'); ?></button>
    </div>
    </fieldset>
    <div class="overlay mtt-hidden"></div>
    </form>
  </div>
</div>
<!-- End of Page: Login -->

<script type="text/javascript">
document.getElementById('mtt').classList.add('page-reset');
document.getElementById('login_form').onsubmit = function(e) {
    e.preventDefault();
    const form = this;
    const authmsg = document.getElementById('authmsg');
    const fieldset = document.querySelector('#login_form fieldset');
    mytinytodo.db.errorCallback = function(msg) {
        fieldset.removeAttribute('disabled');
        form.classList.remove('mtt-overlay');
        authmsg.textContent = msg;
        authmsg.classList.add('show');
    }
    authmsg.classList.remove('show');
    fieldset.setAttribute('disabled', '');
    form.classList.add('mtt-overlay');
    mytinytodo.db.request( 'resetPassword', {
        email: form.email.value,
    }, function(json, isError) {
        fieldset.removeAttribute('disabled');
        form.classList.remove('mtt-overlay');
        if (json.ok) {
          authmsg.textContent = json.msg;
          authmsg.classList.add('show', 'info');
          document.getElementById('authform').style.display = 'none';
          document.querySelector('#page_auth .auth-header').style.visibility = 'hidden';
        }
        else {
            authmsg.textContent = json.error;
            authmsg.classList.add('show', 'error');
        }
    });
};
</script>
