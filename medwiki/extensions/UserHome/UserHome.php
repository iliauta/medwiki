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

        // You can add logic for sections/categories here

        $out->addWikiTextAsContent("== Sections ==\n");
        $out->addWikiTextAsContent("* Add your section listing logic here.");

        // $out->addWikiTextAsContent("...");
    }
}

$wgExtensionMessagesFiles[] = __DIR__ . '/UserHome.alias.php';


