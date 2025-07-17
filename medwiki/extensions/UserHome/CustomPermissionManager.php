<?php
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\User\UserIdentity;
use MediaWiki\Title\Title;

class CustomPermissionManager extends PermissionManager {
    /**
     * Check if the user has the required right by looking up user_groups table
     */
    public function userCan( $action, UserIdentity $user, Title $title, $options = [] ) {
        $userId = $user->getId();
        if ( !$userId ) {
            return false;
        }

        // Always allow logged-in users to read the UserHome special page
        if (
            $action === 'read' &&
            $title->isSpecialPage() &&
            $title->getDBkey() === 'UserHome'
        ) {
            return true;
        }

        // ...existing code for other permission checks...
        $dbr = \MediaWiki\MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
        $groupRes = $dbr->select(
            'user_groups',
            ['ug_group'],
            ['ug_user' => $userId],
            __METHOD__
        );
        $userGroups = [];
        foreach ( $groupRes as $row ) {
            $userGroups[] = $row->ug_group;
        }
        if ( !$userGroups ) {
            return false;
        }
        $rightRes = $dbr->select(
            'group_rights',
            ['gr_group'],
            [
                'gr_group' => $userGroups,
                'gr_right' => $action
            ],
            __METHOD__
        );
        foreach ( $rightRes as $row ) {
            return true;
        }
        return false;
    }
}
