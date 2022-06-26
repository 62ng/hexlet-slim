<?php

namespace An\HexletSlim;

class RepoInFiles
{
    // $databasePath = __DIR__ . '/../database';
    // $userDatabasePath = "{$databasePath}/users/" . $user['id'] . ".txt";

    public function __construct()
    {
        session_start();
        if (!array_key_exists('users', $_SESSION)) {
            $_SESSION['users'] = [];
        }
    }

    public function all()
    {
        // $fileNames = scandir($databasePath . '/users');
        // foreach ($fileNames as $fileName) {
        //     if (strlen($fileName) < 3) {
        //         continue;
        //     }
        //     $users[] = json_decode(file_get_contents($databasePath . '/users/' . $fileName), true);
        // }

        return array_values($_SESSION['users']);
    }

    public function find(string $id)
    {
        // $userDatabasePath = "{$databasePath}/users/{$args['id']}.txt";
        // $user = json_decode(file_get_contents($userDatabasePath), true);
        if (!isset($_SESSION['users'][$id])) {
            throw new \Exception("Wrong user id: {$id}");
        }

        return $_SESSION['users'][$id];
    }

    public function destroy(string $id)
    {
        unset($_SESSION['users'][$id]);
    }

    public function save(array &$item)
    {
        // $size = file_put_contents($userDatabasePath, json_encode($user));
        if (empty($item['name']) || empty($item['email'])) {
            $json = json_encode($item);
            throw new \Exception("Wrong data: {$json}");
        }
        if (!isset($item['id'])) {
            $item['id'] = uniqid();
        }
        $_SESSION['users'][$item['id']] = $item;

        return $item['id'];
    }
}
