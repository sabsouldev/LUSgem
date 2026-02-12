# Configuration BDD - Les Univers Singuliers

- SGBD cible: MariaDB 10.4.32
- Encodage: utf8mb4
- Collation: utf8mb4_unicode_ci

## DATABASE_URL Symfony

Utiliser le format suivant dans `.env.local`:

`DATABASE_URL="mysql://USER:PASSWORD@HOST:3306/lusgem?serverVersion=mariadb-10.4.32&charset=utf8mb4"`

Exemple local:

`DATABASE_URL="mysql://lusgem_user:motdepasse@127.0.0.1:3306/lusgem?serverVersion=mariadb-10.4.32&charset=utf8mb4"`

## Notes

- Les comptes adherents sont geres par l'administration (pas d'inscription en ligne).
- La table `user` (entite `App\Entity\User`) est prevue pour l'authentification.
- Creer les migrations puis migrer:
  - `php bin/console make:migration`
  - `php bin/console doctrine:migrations:migrate`
