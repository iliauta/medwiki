<?php
namespace MediaWiki\Category;

use MediaWiki\Output\CustomOutputPage;

class CustomCategoryPage extends CategoryPage {
    public function showAccessibleCategories( int $userId ) {
        echo "CustomCategoryPage::showAccessibleCategories called for user $userId"; // Debugging output
        error_log("CustomCategoryPage::showAccessibleCategories called for user $userId"); // PHP error log

        $outputPage = new CustomOutputPage( $this->getContext() );
        $categories = $outputPage->getAccessibleCategoriesForUser( $userId );

        foreach ( $categories as $catTitle ) {
            echo htmlspecialchars( $catTitle ) . "aaaaaaa<br>";
        }
    }
}