<?php
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class SpecialUserDashboard extends SpecialPage {
    public function __construct() {
        parent::__construct('UserHome');
    }

    public function execute($subPage) {
        $out = $this->getOutput();
        $out->setPageTitle('Available Pages and Sections');

        // Get all pages
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $res = $dbr->select(
            'page',
            ['page_title', 'page_namespace'],
            [],
            __METHOD__
        );

        $out->addWikiTextAsContent("== Pages ==\n");
        foreach ($res as $row) {
            $title = Title::makeTitle($row->page_namespace, $row->page_title);
            $out->addWikiTextAsContent("* [[" . $title->getPrefixedText() . "]]");
        }

        // List categories tree for current user
        $out->addWikiTextAsContent("== Category Tree ==\n");
        $user = $this->getUser();
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $res = $dbr->select(
            'category',
            ['cat_title'],
            [],
            __METHOD__
        );

        foreach ($res as $row) {
            $catTitle = Title::makeTitle(NS_CATEGORY, $row->cat_title);
            // Check if user can read this category using PermissionManager
            $permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
            if ($permissionManager->userCan('read', $user, $catTitle)) {
                $out->addWikiTextAsContent("* [[" . $catTitle->getPrefixedText() . "]]");
            }
        }

        // Optionally, you can use CategoryTree extension for better visualization
    }
}

$wgExtensionMessagesFiles[] = __DIR__ . '/UserHome.alias.php';


