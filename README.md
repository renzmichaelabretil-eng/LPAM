# Leyte Provincial Athletic Meet

PHP/MySQL live medal tracker with a public results portal and role-ready admin console.

For local development, put the MySQL settings in `config.local.php`. Define
`DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` as PHP constants.

For Hostinger, put the same settings in `lpam-config.php` one directory above
the deployed site root, or provide the four settings as environment variables.
Import the schema into the hosting database separately if the database has not
already been initialized. Keep SQL dumps outside the web root and set a unique
administrator password before making the site public.
