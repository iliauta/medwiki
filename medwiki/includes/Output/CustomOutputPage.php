<?php
namespace MediaWiki\Output;

class CustomOutputPage extends OutputPage {
    public function getAccessibleCategoriesForUser( int $userId ): array {
        $dbr = \MediaWiki\MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase();
        $categories = [];

        $sql = "
            SELECT c.cat_title
            FROM category c
            INNER JOIN category_group_rights cgr ON cgr.cat_id = c.cat_id
            INNER JOIN user_group_membership ugm ON ugm.group_id = cgr.group_id AND ugm.user_id = $userId
            INNER JOIN rights r ON r.right_id = cgr.right_id
            WHERE r.right_name IN ('read', 'edit')
            ORDER BY c.cat_title ASC
        ";
        $res = $dbr->query( $sql, __METHOD__ );

        while ( $row = $res->fetchObject() ) {
            $categories[] = $row->cat_title;
        }

        return $categories;
    }
}