# THBO-dashboards

Een eenvoudig PHP/MySQL dashboard met een Kanban-bord (todo / doing / done) voor projecten en taken. Styling is volledig met **SCSS** opgebouwd.

## Features

* Kanban-bord met drag & drop
* Taken aanmaken, wijzigen en soft-deleten
* Prioriteiten en deadlines voor taken
* Projectbeheer
* Registreren en inloggen
* Profielbeheer + wachtwoord wijzigen
* Admin panel met gebruikersbeheer
* Stored procedures, SQL views en functions
* SCSS architectuur met gescheiden componenten

## Tech stack

* **PHP 8.1+** (strict types, PSR-4 autoloading, PDO)
* **MySQL / MariaDB** (zie `sql/database.sql`)
* **SCSS** (compileert naar `public/css/main.css`)
* **Vanilla JS** voor drag & drop op het Kanban-bord

## Mappenstructuur

```text
THBO-dashboards/
├── config/             # Database/env configuratie
├── public/             # Document root — wijs WAMP hierheen
│   ├── index.php       # Front controller
│   ├── .htaccess       # mod_rewrite regels
│   ├── js/app.js       # Drag & drop + modal
│   └── css/main.css    # Gecompileerde CSS (output)
├── resources/scss/     # SCSS bronbestanden
│   ├── abstracts/      # Variables, mixins
│   ├── base/           # Reset, global
│   ├── components/     # Buttons, forms, alerts, modal
│   ├── layout/         # Topbar
│   ├── pages/          # Auth, dashboard
│   └── main.scss       # Entry point
├── sql/database.sql    # Schema + lookup data + procedures
├── src/
│   ├── Controllers/
│   ├── Core/           # Database, Router
│   ├── Models/
│   └── Views/
├── storage/logs/
└── .env                # Configuratie (DB-credentials)
```

## Installatie onder WAMP

1. **Plaats het project** in `C:\wamp64\www\THBO-dashboards\` (of waar je `www` ook staat).
2. **Importeer de database**:

   * Open phpMyAdmin (`http://localhost/phpmyadmin`).
   * Ga naar tab **SQL** en plak de inhoud van `sql/database.sql`. Het script maakt zelf de database `thbodb` aan.
3. **Configureer `.env`**:

   ```
   DB_HOST=127.0.0.1
   DB_DATABASE=thbodb
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   (Voor de standaard WAMP MySQL is het wachtwoord leeg.)
4. **Zet `mod_rewrite` aan** in WAMP:

   * Linker-klik op het WAMP-icoon → **Apache** → **Apache modules** → vink `rewrite_module` aan.
   * Herstart de Apache service.
5. **Open** `http://localhost/THBO-dashboards/public/` in je browser. Je wordt doorgestuurd naar de loginpagina.
6. **Maak een account aan** via *Registreren* — bij de eerste login wordt automatisch een standaardproject voor je aangemaakt (via de stored procedure `SP_ProjectAanmaken`).

## SCSS bouwen

Eenmalig:

```bash
npm install
```

Eenmalige build:

```bash
npx sass resources/scss/main.scss public/assets/css/main.css --no-source-map
```

Tijdens ontwikkelen (auto-recompile):

```bash
npm run sass
```

## Routes

| Methode | Pad                   | Wat het doet                           |
| ------- | --------------------- | -------------------------------------- |
| GET     | `/`                   | Redirect naar `/login` of `/dashboard` |
| GET     | `/login`              | Loginformulier                         |
| POST    | `/login`              | Login afhandelen                       |
| GET     | `/register`           | Registreren                            |
| POST    | `/register`           | Registratie afhandelen                 |
| GET     | `/logout`             | Uitloggen                              |
| GET     | `/dashboard`          | Kanban-bord                            |
| POST    | `/tasks`              | Nieuwe taak aanmaken                   |
| POST    | `/tasks/update`       | Taak wijzigen                          |
| POST    | `/tasks/status`       | Status wijzigen (AJAX, JSON-payload)   |
| POST    | `/tasks/delete`       | Taak soft-deleten                      |
| POST    | `/projects`           | Nieuw project aanmaken                 |
| POST    | `/projects/update`    | Project wijzigen                       |
| POST    | `/projects/delete`    | Project verwijderen                    |
| GET     | `/profiel`            | Profielpagina                          |
| POST    | `/profiel/wachtwoord` | Wachtwoord wijzigen                    |
| GET     | `/admin`              | Admin dashboard                        |

## Database

De volledige schema-definitie staat in `sql/database.sql` — inclusief:

* Tabellen `Rol`, `TaakStatus`, `Gebruiker`, `Project`, `Taak`
* View `VW_ProjectOverzicht`
* Function `FN_ProjectVoortgang`
* Stored procedure `SP_ProjectAanmaken`

De PHP-code maakt direct gebruik van de Nederlandstalige tabel- en kolomnamen.

## Security

* PDO prepared statements
* Sessiegebaseerde authenticatie
* Autorisatiechecks voor admin-functionaliteiten
* Server-side validatie
* Soft delete systeem voor taken

## Development notes

* Routing verloopt via een custom Router class
* Alle requests lopen via `public/index.php`
* Views worden server-side gerenderd met PHP
* Styling gebruikt een modulaire SCSS-structuur
* JavaScript wordt gebruikt voor drag & drop en modals

## License

Dit project is bedoeld voor educatieve doeleinden binnen THBO.
