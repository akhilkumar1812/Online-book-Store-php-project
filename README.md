# Online book Store php project
 It's a online book store where subscribed users can download books

## Access control setup

- Import `database/onlinebookstore.sql` for a new database. For an existing database, run `database/access_control_migration.sql` once before deploying these PHP changes.
- Promote a trusted account to administrator by editing the commented `UPDATE users ...` statement in the migration file and running it with that account's email. Public sign-up can create regular accounts only.
- Legacy plain-text passwords in an existing database are upgraded to password hashes when the account next signs in. New registrations store password hashes immediately.
- The `pdf/.htaccess` rule requires Apache 2.4 with `.htaccess` overrides enabled. Keep the `pdf` directory outside any public web root on other web servers, or add an equivalent deny rule, since direct web access would bypass PHP session checks.
- Book downloads now require login and use a book ID. Admin add, edit, and delete actions require an administrator session and a CSRF token.
