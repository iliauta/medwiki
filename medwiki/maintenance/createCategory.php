<?php
// MediaWiki maintenance script bootstrap
require_once __DIR__ . '/Maintenance.php';

function createCategoryWithGroup($dbw, $categoryTitle) {
    // 1. Create category
    echo "Creating category: $categoryTitle\n";
    $dbw->newInsertQueryBuilder()
        ->insertInto('category')
        ->row([
            'cat_title' => $categoryTitle,
            'cat_pages' => 0,
            'cat_subcats' => 0,
            'cat_files' => 0
        ])
        ->onDuplicateKeyUpdate()
        ->uniqueIndexFields(['cat_title'])
        ->set([
            'cat_pages' => 0,
            'cat_subcats' => 0,
            'cat_files' => 0
        ])
        ->caller(__METHOD__)->execute();

    // 2. Get category ID
    $catRow = $dbw->newSelectQueryBuilder()
        ->select('cat_id')
        ->from('category')
        ->where(['cat_title' => $categoryTitle])
        ->caller(__METHOD__)->fetchRow();
    if (!$catRow || !isset($catRow->cat_id)) {
        echo "ERROR: Category not found after insert!\n";
        return false;
    }
    $catId = $catRow->cat_id;
    echo "Category ID: $catId\n";

    // 3. Create group using raw SQL
    $groupName = str_replace(' ', '_', strtolower($categoryTitle));
    echo "Creating group: $groupName\n";
    $dbw->query("INSERT INTO groups (group_name) VALUES ('" . $dbw->strencode($groupName) . "')");

    // 4. Get group ID using raw SQL
    $groupRow = $dbw->query("SELECT group_id FROM groups WHERE group_name = '" . $dbw->strencode($groupName) . "'")->fetchObject();
    if (!$groupRow || !isset($groupRow->group_id)) {
        echo "ERROR: Group not found after insert!\n";
        return false;
    }
    $groupId = $groupRow->group_id;
    echo "Group ID: $groupId\n";

    // 5. Ensure rights exist and link them
    $rights = ['read', 'edit'];
    foreach ($rights as $rightName) {
        echo "Ensuring right exists: $rightName\n";
        $rightRow = $dbw->query("SELECT right_id FROM rights WHERE right_name = '" . $dbw->strencode($rightName) . "'")->fetchObject();
        if (!$rightRow) {
            echo "Inserting right: $rightName\n";
            $dbw->query("INSERT INTO rights (right_name) VALUES ('" . $dbw->strencode($rightName) . "')");
            $rightRow = $dbw->query("SELECT right_id FROM rights WHERE right_name = '" . $dbw->strencode($rightName) . "'")->fetchObject();
        }
        if ($rightRow && isset($rightRow->right_id)) {
            echo "Linking category $catId, group $groupId, right {$rightRow->right_id}\n";
            $dbw->query("INSERT INTO category_group_rights (cat_id, group_id, right_id) VALUES ('" . (int)$catId . "', '" . (int)$groupId . "', '" . (int)$rightRow->right_id . "')");
        } else {
            echo "ERROR: Could not get right_id for $rightName\n";
        }
    }
    echo "Category, group, and rights creation complete.\n";
    return true;
}

// Main entry point for command line usage
if (php_sapi_name() === 'cli') {
    // Bootstrap MediaWiki
    require_once __DIR__ . '/../includes/WebStart.php';
    $services = MediaWiki\MediaWikiServices::getInstance();
    $dbw = $services->getConnectionProvider()->getPrimaryDatabase();

    $categoryTitle = $argv[1] ?? null;
    if (!$categoryTitle) {
        echo "Usage: php createCategory.php <CategoryTitle>\n";
        exit(1);
    }
    $result = createCategoryWithGroup($dbw, $categoryTitle);
    if ($result) {
        echo "Success!\n";
    } else {
        echo "Failed!\n";
    }
}