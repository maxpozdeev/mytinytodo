<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class AuthController extends ApiController {

    function postAction($action)
    {
        switch ($action) {
            case 'login':   return $this->login();         break;
            case 'logout':  return $this->logout();        break;
            case 'session': return $this->createSession(); break;
            default:              return ['total' => 0]; // error 400 ?
        }
    }

    private function login()
    {
        check_token();
        $t = array('logged' => 0);
        if (!need_auth()) {
            $t['disabled'] = 1;
            return $t;
        }
        $password = $this->req->jsonBody['password'] ?? '';
        if ( isPasswordEqualsToHash($password, Config::get('password')) ) {
            updateSessionLogged(true);
            $t['token'] = update_token();
            $t['logged'] = 1;
        }
        return $t;
    }

    private function logout()
    {
        check_token();
        updateSessionLogged(false);
        update_token();
        session_regenerate_id(true);
        $t = array('logged' => 0);
        return $t;
    }

    private function createSession()
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
