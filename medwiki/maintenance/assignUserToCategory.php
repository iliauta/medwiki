<?php
// MediaWiki maintenance script bootstrap
require_once __DIR__ . '/Maintenance.php';

function assignUserToCategory($dbw, $userName, $categoryTitle, $rightName = 'read') {
    // Get user_idch
    // Normalize username: underscores to spaces, first letter uppercase
    if (!is_numeric($userName)) {
        $userName = str_replace('_', ' ', $userName);
        $userName = ucfirst($userName);
    }
    $where = is_numeric($userName)
        ? ['user_id' => (int)$userName]
        : ['user_name' => $userName];
    $userRow = $dbw->newSelectQueryBuilder()
        ->select('user_id')
        ->from('user')
        ->where($where)
        ->caller(__METHOD__)->fetchRow();
    if (!$userRow || !isset($userRow->user_id)) {
        echo "ERROR: User not found!\n";
        return false;
    }
    $userId = $userRow->user_id;

    // Get category_id
    $catRow = $dbw->newSelectQueryBuilder()
        ->select('cat_id')
        ->from('category')
        ->where(['cat_title' => $categoryTitle])
        ->caller(__METHOD__)->fetchRow();
    if (!$catRow || !isset($catRow->cat_id)) {
        echo "ERROR: Category not found!\n";
        return false;
    }
    $catId = $catRow->cat_id;

    // Get group for category
    $groupName = str_replace(' ', '_', strtolower($categoryTitle));
    $groupRow = $dbw->query("SELECT group_id FROM groups WHERE group_name = '" . $dbw->strencode($groupName) . "'")->fetchObject();
    if (!$groupRow || !isset($groupRow->group_id)) {
        echo "ERROR: Group not found!\n";
        return false;
    }
    $groupId = $groupRow->group_id;

    // Assign user to group
    // Use raw SQL to avoid prefix issues
    $sql = "INSERT INTO user_group_membership (user_id, group_id) VALUES ('" . (int)$userId . "', '" . (int)$groupId . "')";
    $dbw->query($sql);

    echo "User $userName assigned to category $categoryTitle for $rightName.\n";
    return true;
}

// Main entry point for command line usage
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../includes/WebStart.php';
    $services = MediaWiki\MediaWikiServices::getInstance();
    $dbw = $services->getConnectionProvider()->getPrimaryDatabase();

    $userName = $argv[1] ?? null;
    $categoryTitle = $argv[2] ?? null;
    $rightName = $argv[3] ?? 'read';
    if (!$userName || !$categoryTitle) {
        echo "Usage: php assignUserToCategory.php <UserName> <CategoryTitle> [read|edit]\n";
        exit(1);
    }
    $result = assignUserToCategory($dbw, $userName, $categoryTitle, $rightName);
    if ($result) {
        echo "Success!\n";
    } else {
        echo "Failed!\n";
    }
}
