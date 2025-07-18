<?php
require_once __DIR__ . '/../../includes/WebStart.php'; // filepath: c:\source\medwiki\dev_1\medwiki\includes\Category\showCategoriesForUser.php

use MediaWiki\Category\CustomCategoryPage;
use MediaWiki\Context\RequestContext;

echo "Script loaded"; // Should appear on page

$user = RequestContext::getMain()->getUser();
$userId = $user ? $user->getId() : 0;

$categoryPage = new CustomCategoryPage(/* pass context if needed */);
$categoryPage->showAccessibleCategories($userId);