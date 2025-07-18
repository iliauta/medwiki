<?php
use MediaWiki\MediaWikiServices;

class RedirectAfterLogin {
    public static function onUserLoginComplete( $user, &$inject_html, $direct ) {
        error_log("RedirectAfterLogin: UserLoginComplete called for user ID " . $user->getId());
        // Log to browser console (will only work if output not sent yet)
        echo "<script>console.log('RedirectAfterLogin: UserLoginComplete called for user ID " . $user->getId() . "');</script>";

        // Redirect to your categories page
        $url = '/includes/Category/showCategoriesForUser.php';
        header("Location: $url");
        exit;
    }
}