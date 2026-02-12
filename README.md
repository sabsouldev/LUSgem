# Les Univers Singuliers - Site officiel GEM (Symfony)

Site institutionnel et espace adherent pour **Les Univers Singuliers** (GEM autiste de Perigueux), developpe avec Symfony.

Le projet contient:
- une partie publique (presentation institutionnelle),
- un espace adherent securise,
- un espace administration (comptes, publications, blog, suivi adherents),
- une identite visuelle integree (banniere, couleurs, typographies).

## Stack technique
- PHP `>= 8.2`
- Symfony `7.4`
- Doctrine ORM + Migrations
- Base de donnees: **MariaDB 10.4.32**
- Twig

## Prerequis
- PHP 8.2+
- Composer
- MariaDB 10.4.32

## Installation
1. Installer les dependances:
```bash
composer install
```

2. Configurer la base dans `.env.local`:
```dotenv
DATABASE_URL="mysql://USER:PASSWORD@127.0.0.1:3306/lusgem?serverVersion=mariadb-10.4.32&charset=utf8mb4"
```

3. Creer la base et lancer les migrations:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

4. Creer un compte administrateur:
```bash
php bin/console app:user:create-admin admin@lusgem.local MotDePasseFort123!
```

5. Lancer le projet (au choix):
```bash
symfony server:start
```
ou
```bash
php -S 127.0.0.1:8000 -t public
```

## Authentification et roles
- Pas d'inscription publique.
- Les comptes sont crees uniquement par l'administration.
- Roles:
  - `ROLE_MEMBER`: acces espace adherent
  - `ROLE_ADMIN`: acces administration
- Connexion: `/connexion`
- Deconnexion: `/deconnexion`

## Fonctionnalites

### Partie publique
- Accueil, fonctionnement, charte, activites, mooks, blog/newsletter (institutionnel), contact.
- Route mook principale: `/mooks` (legacy `/moocks` conservee).

### Espace adherent (`/adherent/*`)
- Dashboard "Vie du GEM"
- Planning hebdomadaire (PDF)
- Comptes-rendus (PDF)
- Newsletter
- Propositions (formulaire + suivi des dernieres propositions)
- Blog adherents en lecture (6 derniers + filtres categorie/date)

### Administration (`/admin/*`)
- Dashboard admin
- Gestion des adherents:
  - creation de compte,
  - edition de fiche (coordonnees),
  - champs administratifs (`cotisation`, `attestation`, `notes`, champs personnalises),
  - reinitialisation mot de passe,
  - consultation des propositions et statut,
  - export CSV du tableau adherents.
- Gestion des publications:
  - planning PDF,
  - comptes-rendus PDF,
  - mooks PDF,
  - newsletters.
- CRUD blog adherents (reserve admin) avec medias:
  - PDF, images, videos, audio/podcasts.

## Stockage des donnees

### Base SQL (Doctrine)
- Table `user` (authentification et roles).

### Fichiers JSON (`var/data`)
- `blog-posts.json`
- `newsletters.json`
- `member-profiles.json`
- `propositions.json`

### Fichiers uploades (`public/uploads`)
- `planning/`
- `comptes-rendus/`
- `mooks/`
- `blog-media/`

## Routes principales
- Public:
  - `/`
  - `/fonctionnement`
  - `/charte-cadre`
  - `/activites`
  - `/mooks`
  - `/blog-newsletter`
  - `/contact`
- Securite:
  - `/connexion`
  - `/deconnexion`
- Adherent:
  - `/adherent/vie-gem`
  - `/adherent/blog`
  - `/adherent/propositions`
- Admin:
  - `/admin`
  - `/admin/adherents`
  - `/admin/adherents/export.csv`
  - `/admin/publications`
  - `/admin/blog`

## Commandes utiles
```bash
# Lister les routes
php bin/console debug:router

# Verifier syntaxe Twig
php bin/console lint:twig templates

# Verifier le container
php bin/console lint:container

# Creer/mettre a jour admin
php bin/console app:user:create-admin <email> <mot_de_passe>
```

## Structure de reference
- `docs/architecture-site.md`
- `docs/database.md`
