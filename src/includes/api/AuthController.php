<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class AuthController extends ApiController {

    function postAction($action)
    {
        switch ($action) {
            case 'login':   $this->response->data = $this->login();         break;
            case 'logout':  $this->response->data = $this->logout();        break;
            case 'session': $this->response->data = $this->createSession(); break;
            case 'resetPassword': $this->response->data = $this->resetPassword(); break;
            case 'newPassword':   $this->response->data = $this->newPassword(); break;
            default:        $this->response->data = ['total' => 0]; // error 400 ?
        }
    }

    private function login(): ?array
    {
        $t = array('logged' => 0);
        if (!need_auth()) {
            $t['disabled'] = 1;
            return $t;
        }
        $username = $this->req->jsonBody['username'] ?? '';
        $password = $this->req->jsonBody['password'] ?? '';

        $db = DBConnection::instance();
        $u = $db->sqa("SELECT id,username,pwhash FROM {$db->prefix}users WHERE username=?", [$username]);
        if (!$u)
            return $t;

        if ( isPasswordEqualsToHash($password, $u['pwhash'] ) ) {
            updateSessionLogged(true, $u);
            $t['token'] = update_token();
            $t['logged'] = 1;
            $t['ok'] = true;
        }
        else {
            $t['ok'] = false;
            $t['error'] = __('invalidUsernameOrPassword', true);
        }
        return $t;
    }

    private function logout(): ?array
    {
        if (!need_auth()) {
            $t['disabled'] = 1;
            return $t;
        }
        updateSessionLogged(false);
        update_token();
        session_regenerate_id(true);
        $t = array('logged' => 0);
        return $t;
    }

    private function createSession(): ?array
    {
        $t = array();
        if (!need_auth()) {
            $t['disabled'] = 1;
            return $t;
        }
        if (access_token() == '') {
            update_token();
        }
        $t['token'] = access_token();
        $t['session'] = session_id();
        return $t;
    }

    private function resetPassword(): ?array
    {
        $t = array('ok' => false);
        if (!need_auth()) {
            $t['disabled'] = 1;
            return $t;
        }

        $t['ok'] = true;
        $t['msg'] = __("resetSentIfEmailExists", true);

        $email = (string)($this->req->jsonBody['email'] ?? '');

        $db = DBConnection::instance();
        $userId = (int) (new UserRepo($db))->findUserIdByEmail($email);

        //TODO: write the code

        return $t;
    }

    private function newPassword(): ?array
    {
        $t = array('ok' => false);
        if (!need_auth()) {
            $t['disabled'] = 1;
            return $t;
        }

        $email = (string)($this->req->jsonBody['email'] ?? '');
        $code = (string)($this->req->jsonBody['code'] ?? '');
        $pw1 = (string)($this->req->jsonBody['newpassword'] ?? '');
        $pw2 = (string)($this->req->jsonBody['newpassword2'] ?? '');

        if ($email === '' || $code === '' || $pw1 === '' || $pw2 === '') {
            $t['ok'] = false;
            $t['error'] = "No arguments";
        }

        $t['ok'] = true;
        $t['msg'] = __("new_password_set", true);


        $db = DBConnection::instance();


        //TODO: write the code

        return $t;
    }

}
