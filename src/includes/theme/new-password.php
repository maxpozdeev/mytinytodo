<?php declare(strict_types=1);

$email = _get('email');
$code = _get('code');

$msg = htmlspecialchars(checkArguments($email, $code));

function checkArguments(string $email, string $code): string
{
    if ($email === '' || $code === '') {
        if (MTT_DEBUG) {
            error_log("Link has empty code or email");
        }
        return __('invalidOrExpiredResetLink');
    }
    $data = [];
    $db = DBConnection::instance();
    $r = $db->sqa("SELECT pwtoken FROM {$db->prefix}users WHERE email=?", [$email]);
    if (!$r || !validateWebToken($code, $r['pwtoken'], $data, false)) {
        if (MTT_DEBUG) {
            error_log("User has no pwtoken or webtoken is invalid");
        }
        return __('invalidOrExpiredResetLink');
    }
    if (time() > $data['exp']) {
        if (MTT_DEBUG) {
            error_log("Link has expired webtoken");
        }
        return __('invalidOrExpiredResetLink');
    }
    return '';
}

if ($msg !== '') {
    echo <<<EOD
<div id="page_auth">
  <div id="authmsg" class="show info">$msg</div>
</div>
EOD;
    return;
}

$email = htmlspecialchars($email);
$code = htmlspecialchars($code);

?>

<!-- Page: Reset Password Link-->
<div id="page_auth">
  <div class="auth-header">
    <h3><?php _e('newpassword_h'); ?></h3>
    <div><?php _e('newpassword_d'); ?></div>
  </div>
  <div id="authmsg">&nbsp;</div>
  <div id="authform">
    <form id="login_form" onsubmit="return false">
    <input type="hidden" name="email" value="<?php echo $email; ?>">
    <input type="hidden" name="code" value="<?php echo $code; ?>">
    <fieldset>
    <div class="auth-content">
      <div class="h"><?php _e('new_password');?></div>
      <div><input type="password" name="newpassword" id="newpassword" class="form-input" required autocomplete="new-password"></div>
      <div class="h"><?php _e('confirm_new_password');?></div>
      <div><input type="password" name="newpassword2" id="newpassword2" class="form-input" required autocomplete="new-password"></div>
    </div>
    <div class="form-bottom-buttons">
      <button type="submit" id="auth_submit" disabled><?php _e('btn_reset_password'); ?></button>
    </div>
    </fieldset>
    <div class="overlay mtt-hidden"></div>
    </form>
  </div>
</div>
<!-- End of Page: Login -->

<script type="text/javascript">
function mttPasswordEdited(e) {
  const pw1 = document.getElementById('newpassword').value;
  const pw2 = document.getElementById('newpassword2').value;
  const btnSubmit = document.getElementById('auth_submit');
  if (pw1 !== '' && pw1 === pw2) {
    btnSubmit.removeAttribute('disabled');
  }
  else {
    btnSubmit.setAttribute('disabled','disabled');
  }
}
document.getElementById('mtt').classList.add('page-newpassword');
document.getElementById('newpassword').onkeyup = mttPasswordEdited;
document.getElementById('newpassword2').onkeyup = mttPasswordEdited;
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
    mytinytodo.db.request( 'newPassword', {
        email: form.email.value,
        code: form.code.value,
        newpassword: form.newpassword.value,
        newpassword2: form.newpassword2.value,
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
