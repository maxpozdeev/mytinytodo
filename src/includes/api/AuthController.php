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
        }
        return $t;
    }

    private function logout(): ?array
    {
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

}
