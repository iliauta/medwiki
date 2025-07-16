CREATE TABLE user_group_rights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    page_or_section VARCHAR(255) NOT NULL,
    can_access TINYINT(1) NOT NULL DEFAULT 0,
    include_subsections TINYINT(1) NOT NULL DEFAULT 0
);
