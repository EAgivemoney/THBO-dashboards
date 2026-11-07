# THBO-dashboards

A simple PHP/MySQL dashboard with a Kanban board (to do / in progress / done) for projects and tasks. The styling is built entirely with **SCSS**. The interface is available in Dutch, English, German, Spanish and French.

## Features

* Kanban board with drag & drop
* Create, edit and soft-delete tasks
* Priorities and deadlines for tasks
* Project management (the last remaining project cannot be deleted)
* Registration and login
* Profile page with password change
* Admin panel with user management, an activity log and an error log viewer
* Language picker (nl, en, de, es, fr) with translated texts, dates and demo data
* Stored procedures, SQL views and functions
* SCSS architecture with separate components

## Tech stack

* **PHP 8.1+** (strict types, PSR-4 autoloading, PDO)
* **MySQL / MariaDB** (see `sql/database.sql`)
* **SCSS** (compiles to `public/css/main.css`)
* **Vanilla JS** for drag & drop on the Kanban board

## Folder structure

```text
THBO-dashboards/
├── config/             # Database/env configuration
├── public/             # Document root — point WAMP here
│   ├── index.php       # Front controller
│   ├── .htaccess       # mod_rewrite rules
│   ├── js/app.js       # Drag & drop, modals, filters
│   └── css/main.css    # Compiled CSS (output)
├── resources/          # Source files and data (no application code)
│   ├── scss/           # SCSS source files
│   │   ├── abstracts/  # Variables, mixins
│   │   ├── base/       # Reset, global
│   │   ├── components/ # Buttons, forms, alerts, modal
│   │   ├── layout/     # Top bar, language picker
│   │   ├── pages/      # Auth, dashboard, admin
│   │   └── main.scss   # Entry point
│   ├── lang/           # Translations: nl.php (base), de.php, en.php, es.php, fr.php
│   └── seed/           # Demo data per language: demo.nl.json, demo.en.json, ...
├── sql/database.sql    # Schema + lookup data + procedures
├── src/                # All application code (PHP)
│   ├── Controllers/
│   ├── Core/           # Database, Router, Logger, Activity, LogReader, Lang, Seeder
│   ├── Models/
│   └── Views/
├── storage/logs/       # app-*.log (errors) and activity-*.log (activity)
└── .env                # Configuration (DB credentials)
```

## Installation under WAMP

1. **Place the project** in `C:\wamp64\www\THBO-dashboards\` (or wherever your `www` folder is).
2. **Import the database**:

   * Open phpMyAdmin (`http://localhost/phpmyadmin`).
   * Go to the **SQL** tab and paste the contents of `sql/database.sql`. The script creates the `thbodb` database itself.
   * Warning: the script starts with `DROP DATABASE IF EXISTS thbodb`, so importing it again wipes all existing data.
3. **Configure `.env`**:

   ```
   DB_HOST=127.0.0.1
   DB_DATABASE=thbodb
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   (With the default WAMP MySQL the password is empty.)

   Optional settings:

   ```
   APP_DEBUG=true             # show error details in the browser
   APP_LANG=nl                # default language when the visitor has not chosen one
   LOG_LEVEL=debug            # debug | info | warning | error
   LOG_RETENTION_DAYS=30      # log files older than this are deleted automatically
   LOG_TIMEZONE=Europe/Amsterdam
   ```
4. **Enable `mod_rewrite`** in WAMP:

   * Left-click the WAMP icon → **Apache** → **Apache modules** → tick `rewrite_module`.
   * Restart the Apache service.
5. **Open** `http://localhost/THBO-dashboards/public/` in your browser. You are redirected to the login page.
6. **Create an account** through *Register*. When you open the dashboard and have no project yet, a default project is created for you (through the stored procedure `SP_ProjectAanmaken`). If that name is still taken by a deleted project, a number is added, e.g. "Mijn eerste project (2)".

## Languages, resources, logs and demo data

`resources/` only contains source files and data. The code that uses them always lives in `src/`.

| File / folder                       | Used by                | Purpose                                                  |
| ----------------------------------- | ---------------------- | -------------------------------------------------------- |
| `resources/scss/`                   | `npm run sass`         | Compiled to `public/css/main.css`                        |
| `resources/lang/<code>.php`         | `src/Core/Lang.php`    | All texts of the site (views, messages, JavaScript)      |
| `resources/seed/demo.<code>.json`   | `src/Core/Seeder.php`  | Sample projects and tasks per language                   |

### Languages

The site is available in Dutch, English, German, Spanish and French. Visitors choose with the language picker (globe, top right). The choice is stored in a cookie for one year, also after logging out. Without a choice, `APP_LANG` from `.env` applies, otherwise Dutch.

* **Changing a text:** edit the sentence in `resources/lang/<code>.php`. In views: `<?= t('ui.dashboard.title') ?>`, in PHP: `Lang::get('task.created')`, with placeholders: `Lang::get('admin.demo_loaded', ['tasks' => 5])`.
* **Adding a language:** copy `nl.php` to `resources/lang/<code>.php` (two letters), translate the values and adjust the `meta` block (name, date format, separators, month names). Optionally add `resources/seed/demo.<code>.json`. The language shows up in the picker automatically, sorted alphabetically by name. Keys that are missing fall back to Dutch, so keep the `:placeholders` (e.g. `:name`) exactly as in `nl.php`.
* **Note:** what users enter themselves (projects, tasks) and data stored in the database stay in the language they were saved in.

### Demo data, logs and error handling

* **Demo data:** log in as owner, open the admin panel and click *Load demo data*. The demo data of the chosen language is added to your account. Clicking again in the same language does not create duplicates; in another language that language's version is added.
* **Logs:** `Logger` writes errors to `storage/logs/app-DATE.log`, `Activity` writes user actions to `activity-DATE.log`. View them via Admin panel → Logs (read only, with filters and search). Old files are deleted after `LOG_RETENTION_DAYS` days.

## Building the SCSS

Once:

```bash
npm install
```

One-off build:

```bash
npx sass resources/scss:public/css --no-source-map
```

While developing (auto-recompile):

```bash
npm run sass
```

## Routes

| Method | Path                  | What it does                              |
| ------ | --------------------- | ----------------------------------------- |
| GET    | `/`                   | Redirect to `/login` or `/dashboard`      |
| GET    | `/login`              | Login form                                |
| POST   | `/login`              | Handle login                              |
| GET    | `/register`           | Registration form                         |
| POST   | `/register`           | Handle registration                       |
| GET    | `/logout`             | Log out                                   |
| GET    | `/dashboard`          | Kanban board                              |
| POST   | `/tasks`              | Create a task                             |
| POST   | `/tasks/update`       | Update a task                             |
| POST   | `/tasks/status`       | Change status (AJAX, JSON payload)        |
| POST   | `/tasks/delete`       | Soft-delete a task                        |
| POST   | `/projects`           | Create a project                          |
| POST   | `/projects/update`    | Update a project                          |
| POST   | `/projects/delete`    | Delete a project (not the last one)       |
| GET    | `/profile`            | Profile page                              |
| POST   | `/profile/password`   | Change password                           |
| GET    | `/admin`              | Admin panel                               |
| GET    | `/admin/logs`         | View the activity log and error log       |
| POST   | `/admin/demo`         | Load demo data (owner only)               |
| POST   | `/admin/user/role`    | Change a role (owner only)                |
| POST   | `/admin/user/toggle`  | Activate/deactivate a user (owner only)   |
| POST   | `/language`           | Switch language (sets a cookie)           |

## Database

The complete schema definition is in `sql/database.sql`, including:

* Tables `Rol`, `TaakStatus`, `Gebruiker`, `Project`, `Taak`
* View `VW_ProjectOverzicht`
* Function `FN_ProjectVoortgang`
* Stored procedure `SP_ProjectAanmaken`

The table, column, view, function and procedure names in the database are Dutch (the original schema). The PHP code is entirely English: every query in `src/Models/` maps the Dutch column names to English keys with an alias (e.g. `Titel AS title`). Outside the SQL strings the Dutch names do not appear.

## Code conventions

* All variables, functions, classes, file names, routes, form fields, CSS classes and comments are in English.
* Everything the user sees lives in `resources/lang/<code>.php`.
* Model rows have English `snake_case` keys (`full_name`, `is_active`, `created_at`, ...).

## Security

* PDO prepared statements
* Session-based authentication
* Authorization checks for admin functionality
* Server-side validation
* Soft-delete system for tasks and projects
* Output escaping in views; the language switch and the log viewer only accept values from fixed lists

## Development notes

* Routing goes through a custom Router class
* All requests go through `public/index.php`
* Views are rendered server-side with PHP
* Styling uses a modular SCSS structure
* JavaScript is used for drag & drop, modals and filters

## License

This project is intended for educational purposes at THBO.