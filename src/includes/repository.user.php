<?php declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2025 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

class UserRepo
{
    protected AbstractDatabase $db;

    function __construct(AbstractDatabase $db)
    {
        $this->db = $db;
    }

    /**
     * Search user id by its username.
     * Return null if user does not exists.
     * @param string $username
     * @return null|int
     */
    public function findUserIdByUsername(string $username): ?int
    {
        $r = $this->db->sq("SELECT id FROM {$this->db->prefix}users WHERE username=?", [$username]);
        if (is_null($r))
            return null;
        return (int)$r;
    }

    /**
     * Search user id by its e-mail.
     * Return null if user does not exists.
     * @param string $email
     * @return null|int
     */
    public function findUserIdByEmail(string $email): ?int
    {
        $r = $this->db->sq("SELECT id FROM {$this->db->prefix}users WHERE email=?", [$email]);
        if (is_null($r))
            return null;
        return (int)$r;
    }


    /**
     *
     * @return array<User>
     */
    public function findUsers(): array
    {
        $a = [];
        $q = $this->db->dq("SELECT * FROM {$this->db->prefix}users ORDER BY id");
        while ($r = $q->fetchAssoc()) {
            $a[] = User::fromArray($r);
        }
        return $a;
    }

    /**
     *
     * @param int $id
     * @return null|User
     */
    public function findUserById(int $id): ?User
    {
        $r = $this->db->sqa("SELECT * FROM {$this->db->prefix}users WHERE id = ?", [$id]);
        if ($r)
            return User::fromArray($r);
        return null;
    }

    /**
     *
     * @param string $username
     * @return null|User
     */
    public function findUserByUsername(string $username): ?User
    {
        $r = $this->db->sqa("SELECT * FROM {$this->db->prefix}users WHERE username=?", [$username]);
        if ($r) {
            return User::fromArray($r);
        }
        return null;
    }

    /**
     * Check that a user can be created or updated with a unique username and e-mail
     * that are not already registered with another user.
     * @param User $user
     * @param string|null $error  Receives a human-readable error message on failure.
     * @return bool  True when the username and e-mail are free for this user.
     */
    public function canSaveUser(User $user, ?string &$error = null): bool
    {
        $existingId = $this->findUserIdByUsername((string) $user->username);
        if ($existingId !== null && $existingId !== $user->id) {
            $error = __2('alreadyTakenByAnotherAccount', __('username'), $user->username);
            return false;
        }

        $existingId = $this->findUserIdByEmail((string) $user->email);
        if ($existingId !== null && $existingId !== $user->id) {
            $error = __2('alreadyTakenByAnotherAccount', __('email'), $user->email);
            return false;
        }

        return true;
    }

    /**
     * Create or update a user in the database.
     * @param User $user
     * @return void
     */
    public function saveUser(User $user)
    {
        $a = $user->toArray();
        if ($user->id === null) {
            # Create new record
            $this->db->dq("INSERT INTO {$this->db->prefix}users (username,name,email,extra,pwhash,pwtoken,last_visit) VALUES (?,?,?,?,?,?,?)",
                [$user->username, $user->name, $user->email, $a['extra'], $user->pwhash, $user->pwtoken, $user->lastVisit]);
            $user->id = (int) $this->db->lastInsertId();
        }
        else {
            # Update record
            $this->db->dq("UPDATE {$this->db->prefix}users SET username=?,name=?,email=?,extra=?,pwhash=?,pwtoken=?,last_visit=? WHERE id = ". (int)$user->id,
                [$user->username, $user->name, $user->email, $a['extra'], $user->pwhash, $user->pwtoken, $user->lastVisit]);
        }
    }

    /**
     * Saves only changed field of a user
     * @param User $user
     * @return int
     * @throws InvalidArgumentException
     */
    public function updateUserProperties(User $user): int
    {
        $fv = $user->toArray(true);
        if (count($fv) == 0) {
            return 0;
        }

        $fields = [];
        $values = [];
        foreach ($fv as $field => $value) {
            if (!preg_match("/^[a-zA-Z0-9_]+$/", $field))
                throw new InvalidArgumentException("Unexpected table field name: $field");
            $fields[] = "$field=?";
            $values[] = $value;
        }

        $sqlSet = implode(',', $fields);
        $values[] = $user->id;

        $this->db->ex("UPDATE {$this->db->prefix}users SET $sqlSet WHERE id=?", $values);
        $affected = $this->db->affected();
        return $affected;
    }
}
