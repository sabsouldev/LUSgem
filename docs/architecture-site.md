# Site officiel - Les Univers Singuliers

## Arborescence Symfony compatible

```
config/
  packages/
    security.yaml
  routes.yaml
src/
  Controller/
    AdminController.php
    MemberController.php
    PublicController.php
    SecurityController.php
  Entity/
    User.php
templates/
  admin/
    adherents.html.twig
    dashboard.html.twig
    messages.html.twig
    publications.html.twig
  member/
    dashboard.html.twig
    newsletter.html.twig
    planning.html.twig
    propositions.html.twig
    reports.html.twig
  partials/
    _banner.html.twig
    _footer.html.twig
    _header.html.twig
  public/
    activites.html.twig
    blog.html.twig
    charte.html.twig
    contact.html.twig
    fonctionnement.html.twig
    home.html.twig
    mooks.html.twig
  security/
    login.html.twig
  base.html.twig
public/
  assets/
    fonts/
      Blogger-Sans.otf
      Blogger-Sans-Bold.otf
      LeagueSpartan-Bold.otf
    images/
      banner-officielle.png
      gommette-1.png
      gommette-2.png
      gommette-3.png
      gommette-4.png
      mook-1.jpg
    styles/
      app.css
  uploads/
    planning/
      .gitkeep
    comptes-rendus/
      .gitkeep
    mooks/
      .gitkeep
docs/
  architecture-site.md
```

## Structure des pages publiques

- Accueil: banniere officielle + section complete "Qui sommes-nous ?" + objectifs du site.
- Fonctionnement: roles des adherents, animateurs (2 ETP), bureau, mediateur, rappel du cadre.
- Charte: communication respectueuse, equite, regles, gestion des conflits, responsabilite individuelle.
- Activites: texte de contexte + grille d'activites (musique, debats, cuisine, ateliers creatifs, QI Gong, balades, jardinage, jeux de societe).
- Mooks: definition, MOOK 1 complet, MOOK 2 pret a completer, boutons consulter/telecharger.
- Blog/Newsletter: articles institutionnels, newsletter mensuelle, informations importantes.
- Contact: coordonnees a completer + formulaire securise (token CSRF).

## Espace adherent (prive)

- URL prefixee `/adherent/*`.
- Acces protege par `ROLE_MEMBER`.
- Connexion uniquement via comptes crees par administrateur.
- Rubriques:
  - Vie du GEM (tableau de bord)
  - Planning hebdomadaire (PDF)
  - Comptes rendus (historique PDF)
  - Newsletter
  - Propositions (dont message confidentiel au bureau)

## Administration

- URL prefixee `/admin/*`.
- Acces protege par `ROLE_ADMIN`.
- Fonctions prevues:
  - Creation comptes adherents et gestion des roles
  - Mise a jour planning PDF
  - Publication comptes rendus
  - Gestion mooks
  - Publication newsletter et blog
  - Consultation messages de contact

## Regles d'acces implementees

Dans `config/packages/security.yaml`:

- `^/adherent` => `ROLE_MEMBER`
- `^/admin` => `ROLE_ADMIN`

Inscription publique absente volontairement. Connexion via `/connexion` uniquement.

## Integration identite graphique

- Polices integrees localement:
  - Titraille: League Spartan (gras)
  - Texte: Blogger
- Couleurs appliquees:
  - Bleu fonce `#1350A2`
  - Bleu clair `#69A7F8`
  - Jaune `#FFDF57`
  - Orange `#FA7824`
- Banniere officielle en haut de la page d'accueil.
- Gommettes integrees comme elements decoratifs discrets (faible opacite, sans surcharge).

## Recommandations UX TSA appliquees

- Navigation stable et previsible (menus constants sur toutes les pages).
- Hierarchie visuelle simple (cartes, titres clairs, sections courtes).
- Contrastes eleves et reperes typographiques constants.
- Aucune animation rapide ou clignotante.
- Formulaires courts, libelles explicites.
- Cadre textuel non infantilisant et institutionnel.
