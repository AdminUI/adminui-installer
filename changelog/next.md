## Version 4.0.0

- **BreakingChange**: Move minimum PHP version to 8.4
- **Security**: Registering the first admin is now refused (403) when AdminUI is not installed or an admin already exists, checked before validation and again under a lock. Previously only the registration page checked, so the endpoint could create extra Super Admins
- **Improvement**: The installer UI has been rebuilt as a Vue single-page app (Vite, Tailwind), replacing the Blade and petite-vue views. The install page, registration, and the already-installed, already-registered, not-installed, no-database and maintenance screens are all included
- **Improvement**: Console output for the long install steps (update dependencies, publish resources, run migrations, seed database) now streams to the install page while each step runs, and the install resumes from the last completed step
- **Improvement**: The compiled assets now ship inside the package and are served by the installer itself, so the install page works straight after `composer require` with no `vendor:publish` step
- **Improvement**: Once an admin account exists, the register screen shows a link to the admin login instead of the form
- **Fix**: Maintenance mode (`artisan down --render`) renders the installer's maintenance screen again
