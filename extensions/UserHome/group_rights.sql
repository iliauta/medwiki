-- Table: group_rights
-- Table: groups
CREATE TABLE IF NOT EXISTS groups (
    group_id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL UNIQUE
);

-- Table: user_group_membership
CREATE TABLE IF NOT EXISTS user_group_membership (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    UNIQUE KEY (user_id, group_id),
    FOREIGN KEY (group_id) REFERENCES groups(group_id)
);

-- Category tree support: add parent_id to category table
ALTER TABLE category
    ADD COLUMN parent_id INT DEFAULT NULL AFTER cat_id;

-- Table: category_group_rights
CREATE TABLE IF NOT EXISTS rights (
    right_id INT AUTO_INCREMENT PRIMARY KEY,
    right_name VARCHAR(255) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS category_group_rights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cat_id INT NOT NULL,
    group_id INT NOT NULL,
    right_id INT NOT NULL,
    FOREIGN KEY (cat_id) REFERENCES category(cat_id),
    FOREIGN KEY (group_id) REFERENCES groups(group_id),
    FOREIGN KEY (right_id) REFERENCES rights(right_id)
);

CREATE TABLE IF NOT EXISTS group_rights (
    gr_group VARCHAR(255) NOT NULL,
    gr_right VARCHAR(255) NOT NULL,
    PRIMARY KEY (gr_group, gr_right)
);

-- Example data
INSERT INTO group_rights (gr_group, gr_right) VALUES
    ('user', 'read'),
    ('editor', 'edit'),
    ('sysop', 'delete'),
    ('sysop', 'edit'),
    ('user', 'edit');
