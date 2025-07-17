-- List all tables
SHOW TABLES;

-- Show structure of a specific table (example: medusers)
DESCRIBE medusers;

-- Show structure of all tables
SELECT table_name, column_name, data_type
FROM information_schema.columns
WHERE table_schema = 'mediawiki_dev';