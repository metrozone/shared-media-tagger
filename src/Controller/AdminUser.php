<?php
declare(strict_types = 1);

namespace Attogram\SharedMedia\Tagger\Controller;

use Attogram\SharedMedia\Tagger\Tools;

/**
 * Class AdminUser
 */
class AdminUser extends ControllerBase
{
    protected function display()
    {
        $this->smt->title = 'User Admin';
        $this->smt->includeHeader();
        $this->smt->includeTemplate('MenuSmall');
        $this->smt->includeAdminMenu();

        // Delete users
        if (!empty($_POST)) {
            $this->deleteUsers();
        }

        // Get all users
        $users = $this->smt->database->getUsers();

        // Set combo ipAndHost, Set User Tagging Count
        foreach ($users as $id => $user) {
            $users[$id]['ipAndHost'] = $user['ip'];
            if ($user['ip'] != $user['host']) {
                $users[$id]['ipAndHost'] .= '<br />' . $user['host'];
            }

            $users[$id]['tagCount'] = $this->smt->database->getUserTagCount($user['id']);
        }

        /** @noinspection PhpIncludeInspection */
        include($this->getView('AdminUser'));

        $this->smt->includeFooter();
    }

    private function deleteUsers()
    {
        foreach ($_POST as $name => $value) {
            if ($name[0] !== 'd') {
                continue;
            }
            $this->deleteUser(ltrim($name, 'd'));
        }
    }

    /**
     * @param int|string $userId
     */
    private function deleteUser($userId)
    {
        if (empty($userId) || !Tools::isPositiveNumber($userId)) {
            Tools::error('Invalid User ID: ' . $userId);

            return;
        }
        Tools::debug('Deleting User # ' . $userId);

        $bind = [':user_id' => $userId];

        // Delete user tagging
        $sql = 'DELETE FROM tagging WHERE user_id = :user_id';
        $res = $this->smt->database->queryAsBool($sql, $bind);
        if (!$res) {
            Tools::error('ERROR: failed to delete user tagging');
        }

        // Delete user
        $sql = 'DELETE FROM user WHERE id = :user_id';
        $res = $this->smt->database->queryAsBool($sql, $bind);
        if (!$res) {
            Tools::error('ERROR: failed to delete user');
        }
    }
}
