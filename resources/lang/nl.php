<?php
/**
 * Dutch texts (base language). Everything the user gets to see lives here:
 * views, error messages, confirmations, labels, JavaScript messages.
 *
 * Usage in PHP:
 *   Lang::get('task.created');
 *   Lang::get('admin.demo_loaded', ['projects' => 3]);   // :projects in the text
 *   Lang::choice('logs.lines', 5);                        // logs.lines.one / .other
 * Usage in views (escaped):  <?= t('ui.dashboard.title') ?>
 *
 * Adding a language: copy this file to resources/lang/<code>.php (two
 * letters), translate the values and adjust 'meta'. The language appears
 * in the language picker automatically. Missing keys fall back to Dutch.
 * Keep the :placeholders (e.g. :name) exactly the same as in this file.
 */
return [

    // Data about the language itself (the name is shown in the language picker).
    'meta' => [
        'name'       => 'Nederlands',
        'short'      => 'NL',
        'html'       => 'nl',
        'decimal'    => ',',
        'thousands'  => '.',
        'date_short' => 'd-m-Y',            // PHP date() format
        'date_long'  => ':day :month :year',
    ],

    'months' => [
        1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april', 5 => 'mei', 6 => 'juni',
        7 => 'juli', 8 => 'augustus', 9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december',
    ],

    'common' => [
        'yes' => 'ja',
        'no'  => 'nee',
    ],

    'roles' => [
        'user' => 'Gebruiker',
        'admin'     => 'Admin',
        'owner'  => 'Eigenaar',
    ],

    'status' => [
        'todo'  => 'Te doen',
        'doing' => 'Bezig',
        'done'  => 'Klaar',
    ],

    'priority' => [
        1 => 'Hoogst',
        2 => 'Hoog',
        3 => 'Normaal',
        4 => 'Laag',
        5 => 'Laagst',
    ],

    // ---------------------------------------------------------------------
    // Messages set by the controllers
    // ---------------------------------------------------------------------

    'auth' => [
        'email_required'     => 'E-mailadres is verplicht.',
        'password_required'  => 'Wachtwoord is verplicht.',
        'unknown_user'       => 'Onbekende of inactieve gebruiker.',
        'wrong_password'     => 'Wachtwoord is onjuist.',
        'firstname_required' => 'Voornaam is verplicht.',
        'lastname_required'  => 'Achternaam is verplicht.',
        'email_invalid'      => 'Vul een geldig e-mailadres in.',
        'password_min'       => 'Wachtwoord moet minimaal 8 tekens zijn.',
        'password_mismatch'  => 'De wachtwoorden komen niet overeen.',
        'email_taken'        => 'Er bestaat al een account met dit e-mailadres.',
        'registered'         => 'Account aangemaakt — je kunt nu inloggen.',
    ],

    'profile' => [
        'current_required' => 'Huidig wachtwoord is verplicht.',
        'new_min'          => 'Nieuw wachtwoord moet minimaal 8 tekens zijn.',
        'mismatch'         => 'De nieuwe wachtwoorden komen niet overeen.',
        'same_as_old'      => 'Het nieuwe wachtwoord moet anders zijn dan het huidige.',
        'changed'          => 'Wachtwoord succesvol gewijzigd.',
        'current_wrong'    => 'Huidig wachtwoord is onjuist.',
    ],

    'project' => [
        'name_required'       => 'Projectnaam is verplicht.',
        'name_max'            => 'Projectnaam mag maximaal 120 tekens bevatten.',
        'invalid'             => 'Ongeldig project.',
        'created'             => 'Project aangemaakt.',
        'exists'              => 'Er bestaat al een project met die naam.',
        'updated'             => 'Project bijgewerkt.',
        'not_found'           => 'Project niet gevonden.',
        'deleted'             => 'Project verwijderd.',
        'delete_failed'       => 'Kon het project niet verwijderen.',
        'last_one'            => 'Je kunt je enige project niet verwijderen. Maak eerst een nieuw project aan.',
        'default_title'       => 'Mijn eerste project',
        'default_description' => 'Standaardproject — automatisch aangemaakt',
    ],

    'task' => [
        'title_required' => 'Titel is verplicht.',
        'choose_project' => 'Kies een project.',
        'invalid'        => 'Ongeldige taak.',
        'created'        => 'Taak toegevoegd.',
        'exists'         => 'Er bestaat al een taak met die naam in dit project.',
        'updated'        => 'Taak bijgewerkt.',
        'deleted'        => 'Taak verwijderd.',
        'delete_failed'  => 'Kon de taak niet verwijderen.',
    ],

    'admin' => [
        'owner_only'         => 'Deze actie is alleen beschikbaar voor de eigenaar.',
        'role_updated'       => 'Rol bijgewerkt.',
        'role_failed'        => 'Kon de rol niet wijzigen.',
        'no_self_deactivate' => 'Je kunt je eigen account niet deactiveren.',
        'user_activated'     => 'Gebruiker geactiveerd.',
        'user_deactivated'   => 'Gebruiker gedeactiveerd.',
        'status_failed'      => 'Kon de status niet wijzigen.',
        'demo_loaded'        => 'Demodata geladen: :projects nieuw project(en), :tasks nieuwe taak/taken, :skipped overgeslagen (bestonden al).',
        'demo_failed'        => 'Demodata kon niet worden geladen. Zie het foutlog voor details.',
    ],

    'errors' => [
        'not_found_title' => '404 — Pagina niet gevonden',
        'not_found_text'  => 'De URL :path bestaat niet.',
        'server_title'    => 'Er is iets misgegaan',
        'server_text'     => 'Probeer het later opnieuw. De fout is vastgelegd in het logbestand.',
        'server_json'     => 'Interne serverfout',
        'not_logged_in'   => 'Niet ingelogd',
        'invalid_input'   => 'Ongeldige invoer',
    ],

    // ---------------------------------------------------------------------
    // Text in the views (src/Views/)
    // ---------------------------------------------------------------------

    'ui' => [

        'layout' => [
            'hi'         => 'Hoi, :name',
            'dashboard'  => 'Dashboard',
            'profile'    => 'Mijn profiel',
            'admin'      => 'Admin panel',
            'logout'     => 'Uitloggen',
            'login'      => 'Inloggen',
            'register'   => 'Account aanmaken',
            'built_with' => 'Gebouwd met PHP, MySQL en SCSS',
            'language'   => 'Taal',
            'theme_to_dark'  => 'Donkere modus aanzetten',
            'theme_to_light' => 'Lichte modus aanzetten',
        ],

        'login' => [
            'title'         => 'Welkom terug',
            'subtitle'      => 'Log in om je dashboard te openen.',
            'email'         => 'E-mailadres',
            'password'      => 'Wachtwoord',
            'submit'        => 'Inloggen',
            'no_account'    => 'Nog geen account?',
            'register_link' => 'Registreer hier',
        ],

        'register' => [
            'title'        => 'Account aanmaken',
            'subtitle'     => 'Maak een account aan om je projecten en taken te beheren.',
            'firstname'    => 'Voornaam',
            'infix'        => 'Tussenvoegsel',
            'lastname'     => 'Achternaam',
            'email'        => 'E-mailadres',
            'password'     => 'Wachtwoord',
            'confirm'      => 'Bevestig wachtwoord',
            'submit'       => 'Account aanmaken',
            'have_account' => 'Al een account?',
            'login_link'   => 'Log hier in',
        ],

        'forbidden' => [
            'title' => '403 — Geen toegang',
            'text'  => 'Je hebt geen rechten om deze pagina te bekijken.',
            'back'  => 'Terug naar dashboard',
        ],

        'dashboard' => [
            'page_title'          => 'Dashboard',
            'title'               => 'Mijn taken',
            'subtitle'            => 'Sleep taken tussen kolommen om hun status te wijzigen.',
            'manage_projects'     => 'Projecten beheren',
            'new_task'            => '+ Nieuwe taak',
            'filter_project'      => 'Project',
            'all_projects'        => 'Alle projecten',
            'filter_priority'     => 'Prioriteit',
            'all_priorities'      => 'Alle prioriteiten',
            'clear_filters'       => 'Filters wissen',
            'empty_column'        => 'Geen taken in deze kolom.',
            'delete'              => 'Verwijderen',
            'close'               => 'Sluiten',
            'confirm_task_delete' => 'Taak verwijderen?',
            'task_modal_title'    => 'Nieuwe taak',
            'edit_task_title'     => 'Taak bewerken',
            'field_title'         => 'Titel',
            'field_description'   => 'Beschrijving',
            'field_project'       => 'Project',
            'field_priority'      => 'Prioriteit',
            'field_deadline'      => 'Deadline',
            'cancel'              => 'Annuleren',
            'add_task'            => 'Taak toevoegen',
            'save'                => 'Opslaan',
            'projects_title'      => 'Projecten beheren',
            'no_projects'         => 'Nog geen projecten. Maak er een aan hieronder.',
            'edit'                => 'Bewerken',
            'confirm_project_delete' => 'Project ":name" verwijderen? Alle taken worden ook verwijderd.',
            'last_project_hint'   => 'Dit is je enige project. Maak eerst een nieuw project aan voordat je het kunt verwijderen.',
            'no_tasks'            => 'Geen taken',
            'progress'            => ':done / :total taken voltooid — :percent%',
            'new_project'         => 'Nieuw project aanmaken',
            'project_name'        => 'Projectnaam',
            'project_placeholder' => 'bijv. Website redesign',
            'description_placeholder' => 'Optionele omschrijving...',
            'create_project'      => 'Project aanmaken',
            'edit_project_title'  => 'Project bewerken',
        ],

        'profile' => [
            'page_title'      => 'Mijn profiel',
            'title'           => 'Mijn profiel',
            'subtitle'        => 'Jouw accountgegevens in één overzicht.',
            'back'            => '← Dashboard',
            'name'            => 'Naam',
            'email'           => 'E-mail',
            'role'            => 'Rol',
            'member_since'    => 'Lid sinds',
            'change_password' => 'Wachtwoord wijzigen',
            'current'         => 'Huidig wachtwoord',
            'new'             => 'Nieuw wachtwoord',
            'confirm'         => 'Nieuw wachtwoord bevestigen',
            'cancel'          => 'Annuleren',
            'save'            => 'Opslaan',
        ],

        'admin' => [
            'page_title'        => 'Admin panel',
            'title'             => 'Admin panel',
            'owner_subtitle'    => 'Volledig beheer over alle gebruikers, rollen en projecten.',
            'admin_subtitle'    => 'Leesrechten op alle gebruikers en projecten.',
            'load_demo'         => 'Demodata laden',
            'confirm_demo'      => 'Voorbeeldprojecten en -taken toevoegen aan jouw account?',
            'logs'              => 'Logs',
            'back'              => '← Dashboard',
            'stat_users'        => 'Actieve gebruikers',
            'stat_inactive'     => 'Inactieve gebruikers',
            'stat_projects'     => 'Actieve projecten',
            'stat_tasks'        => 'Actieve taken',
            'stat_done'         => 'Voltooid systeem-breed',
            'tab_users'         => 'Gebruikers',
            'tab_projects'      => 'Projecten',
            'th_name'           => 'Naam',
            'th_email'          => 'E-mail',
            'th_role'           => 'Rol',
            'th_projects'       => 'Projecten',
            'th_status'         => 'Status',
            'th_actions'        => 'Acties',
            'th_project'        => 'Project',
            'th_owner'          => 'Eigenaar',
            'th_tasks'          => 'Taken',
            'th_progress'       => 'Voortgang',
            'th_created'        => 'Aangemaakt',
            'you'               => 'jij',
            'active'            => 'Actief',
            'inactive'          => 'Inactief',
            'deleted'           => 'Verwijderd',
            'change_role'       => 'Rol wijzigen',
            'deactivate'        => 'Deactiveren',
            'activate'          => 'Activeren',
            'confirm_deactivate' => 'Gebruiker :name deactiveren?',
            'confirm_activate'   => 'Gebruiker :name activeren?',
        ],
    ],

    // Messages in JavaScript (public/js/app.js) via window.I18N
    'js' => [
        'task_update_failed' => 'De taak kon niet worden bijgewerkt. Vernieuw de pagina en probeer opnieuw.',
        'network_error'      => 'Netwerkfout - probeer het opnieuw.',
        'empty_column'       => 'Geen taken in deze kolom.',
        'empty_filter'       => 'Geen taken met dit filter.',
        'no_tasks'           => 'Geen taken',
        'progress'           => ':done / :total taken voltooid — :percent%',
    ],

    // ---------------------------------------------------------------------
    // Activity log
    // ---------------------------------------------------------------------

    // 'actions' is nested so that Lang::get('activity.actions.' . $action) works with codes like "task.moved".
    'activity' => [
        'actions' => [
            'auth' => [
                'login'        => 'Ingelogd',
                'login_failed' => 'Mislukte inlogpoging',
                'logout'       => 'Uitgelogd',
                'register'     => 'Account aangemaakt',
            ],
            'task' => [
                'created' => 'Taak aangemaakt',
                'updated' => 'Taak bijgewerkt',
                'moved'   => 'Taak verplaatst',
                'deleted' => 'Taak verwijderd',
            ],
            'project' => [
                'created' => 'Project aangemaakt',
                'updated' => 'Project bijgewerkt',
                'deleted' => 'Project verwijderd',
            ],
            'profile' => [
                'password' => 'Wachtwoord gewijzigd',
            ],
            'admin' => [
                'role_changed' => 'Rol gewijzigd',
                'user_toggled' => 'Gebruiker status gewijzigd',
                'demo_loaded'  => 'Demodata geladen',
            ],
        ],
        'categories' => [
            'auth'    => 'Inloggen & accounts',
            'task'    => 'Taken',
            'project' => 'Projecten',
            'profile' => 'Profiel',
            'admin'   => 'Beheer',
        ],
    ],

    // Admin → Logs
    'logs' => [
        'title'          => 'Logs',
        'subtitle'       => 'Alleen lezen. Bestanden staan in storage/logs/ en worden na :days dagen automatisch opgeruimd.',
        'back'           => '← Admin panel',
        'empty_activity' => 'Er is nog geen activiteit vastgelegd. Log in, maak een taak aan of verplaats een taak; dan verschijnt hier de eerste regel.',
        'empty_app'      => 'Geen foutmeldingen gevonden. Dat is goed nieuws.',
        'unreadable'     => 'Dit logbestand kon niet worden gelezen.',
        'no_results'     => 'Geen regels gevonden met deze filters.',
        'truncated'      => 'Dit bestand is erg groot. Alleen het laatste deel (ca. 3 MB) wordt getoond.',
        'lines'          => ['one' => ':count regel', 'other' => ':count regels'],
        'filtered'       => '(gefilterd)',
        'details'        => 'Details',
        'unknown_user'   => 'Onbekend',
        'types' => [
            'activity' => 'Activiteit',
            'app'      => 'Fouten & systeem',
        ],
        'levels' => [
            'error'   => 'Fout',
            'warning' => 'Waarschuwing',
            'info'    => 'Info',
            'debug'   => 'Debug',
        ],
        'filter' => [
            'date'     => 'Datum',
            'category' => 'Categorie',
            'level'    => 'Niveau',
            'search'   => 'Zoeken',
            'all'      => 'Alles',
            'apply'    => 'Filter',
            'reset'    => 'Reset',
        ],
        'placeholder' => [
            'activity' => 'Naam, actie, titel, IP …',
            'app'      => 'Foutmelding, bestand, URL …',
        ],
        'th' => [
            'time'    => 'Tijd',
            'user'    => 'Gebruiker',
            'action'  => 'Actie',
            'details' => 'Details',
            'ip'      => 'IP',
            'level'   => 'Niveau',
            'message' => 'Melding',
        ],
        'pager' => [
            'newer' => '← Nieuwer',
            'older' => 'Ouder →',
            'page'  => 'Pagina :page van :pages',
        ],
        // Names of the data fields in the activity log
        'data' => [
            'task_id'           => 'taak-id',
            'title'             => 'titel',
            'project_id'        => 'project-id',
            'email'             => 'e-mail',
            'reason'             => 'reden',
            'to'              => 'naar',
            'target_user_id' => 'doelgebruiker-id',
            'new_role'        => 'nieuwe rol',
            'active'            => 'actief',
            'projects'         => 'projecten',
            'tasks'             => 'taken',
            'skipped'      => 'overgeslagen',
        ],
        // Reasons for a failed login attempt
        'reasons' => [
            'unknown_user'   => 'onbekende gebruiker',
            'inactive_user'  => 'gebruiker inactief',
            'wrong_password' => 'onjuist wachtwoord',
        ],
    ],

];
