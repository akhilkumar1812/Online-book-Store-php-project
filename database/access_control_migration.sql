-- Run once against an existing onlinebookstore database.
ALTER TABLE users
    ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER password;

-- Promote an existing trusted account after verifying its email address.
-- Replace the address below before running this statement.
-- UPDATE users SET role = 'admin' WHERE email = 'your-admin@example.com';
