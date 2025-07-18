<?php
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class SpecialUserDashboard extends SpecialPage {
    public function __construct() {
        parent::__construct('UserHome');
    }

    public function execute($subPage) {
        $user = $this->getUser();
        // Redirect if user is logged in and not already on UserHome
        $title = $this->getPageTitle();
        if ($user && $user->isRegistered() && $title->getPrefixedText() !== 'Special:UserHome') {
            header('Location: /index.php/Special:UserHome');
            exit;
        }

        $out = $this->getOutput();
        //$out->setPageTitle('Available Pages and Sections');
        $out->setPageTitle('Available Categories');

        // Get all pages
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $res = $dbr->select(
            'page',
            ['page_title', 'page_namespace'],
            [],
            __METHOD__
        );

        $out->addWikiTextAsContent("== Pages ==\n");
        // foreach ($res as $row) {
        //     $title = Title::makeTitle($row->page_namespace, $row->page_title);
        //     $out->addWikiTextAsContent("* [[" . $title->getPrefixedText() . "]]");
        // }

        // List categories tree for current user
        $out->addWikiTextAsContent("== Category Tree ==\n");
        $user = $this->getUser();
        $userId = $user ? $user->getId() : 0;
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);

        // Use raw SQL to get only categories the user can read or edit
        $sql = "
            SELECT c.cat_title, parent.cat_title AS parent_cat_title
            FROM medcategory c
            LEFT JOIN medcategory parent ON c.parent_id = parent.cat_id
            INNER JOIN category_group_rights cgr ON cgr.cat_id = c.cat_id
            INNER JOIN user_group_membership ugm ON ugm.group_id = cgr.group_id AND ugm.user_id = $userId
            INNER JOIN rights r ON r.right_id = cgr.right_id
            WHERE r.right_name IN ('read', 'edit')
            GROUP BY c.cat_title, parent.cat_title
            ORDER BY parent.cat_title ASC, c.cat_title ASC
        ";
        $res = $dbr->query( $sql, __METHOD__ );

        // Build a parent => [subcategories] array
        $categoriesTree = [];
        while ( $row = $res->fetchObject() ) {
            $catTitleRaw = isset($row->cat_title) ? trim($row->cat_title) : '';
            $parentCatTitleRaw = isset($row->parent_cat_title) ? trim($row->parent_cat_title) : '';
            if ($catTitleRaw !== '' && strlen($catTitleRaw) > 0) {
                $categoriesTree[$parentCatTitleRaw][] = $catTitleRaw;
            }
        }

        // Output the tree
        foreach ($categoriesTree as $parentNameRaw => $subcats) {
            // If parentNameRaw is empty or parent id is -1, treat as root
            $isRoot = ($parentNameRaw === '' || $parentNameRaw === '-1');
            $parentName = !$isRoot ? ucfirst($parentNameRaw) : '';
            if (!$isRoot) {
                $out->addHTML('<div style="font-weight:bold; margin-top:10px;">' . htmlspecialchars($parentName) . '</div>');
            }
            foreach ($subcats as $catTitleRaw) {
                $catTitle = Title::makeTitle(NS_CATEGORY, $catTitleRaw);
                $name = ucfirst($catTitle->getText());
                $margin = $isRoot ? '0px' : '30px';
                $out->addHTML('<div style="margin-left:' . $margin . ';"><a href="' . htmlspecialchars($catTitle->getLocalURL()) . '">' . htmlspecialchars($name) . '</a></div>');
            }
        }

        // $categoryLinks = [];
        // foreach ($res as $row) {
        //     $catTitle = Title::makeTitle(NS_CATEGORY, $row->cat_title);
        //     $permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
        //     if ($permissionManager->userCan('read', $user, $catTitle)) {
        //         $categoryLinks[] = $catTitle;
        //     }
        // }
        // if ($categoryLinks) {
        //    $out->addCategoryLinks($categoryLinks);
        // }

        // Optionally, you can use CategoryTree extension for better visualization
    }
}

$wgExtensionMessagesFiles[] = __DIR__ . '/UserHome.alias.php';

//Programming comments
// This Code used to show loinks in bottom of page. So we can use it to add some links on bottom of the page 
//$out->addWikiTextAsContent("* [[" . $catTitle->getPrefixedText() . "]]);

// $out->addCategoryLinks($categoryLinks);
// This does very similar thing to the above, but uses a different method   
//Programming comments

