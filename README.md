# 🧩 Les Univers Singuliers — Site officiel du GEM

Site institutionnel et espace adhérent pour **Les Univers Singuliers**, Groupe d'Entraide Mutuelle autiste de Périgueux.

Développé avec **Symfony 7.4** par [VaryaCode](https://varyacode.fr).

---

## 🎯 Le projet

Un site sur mesure pour une association qui avait besoin :
- d'une **vitrine institutionnelle** claire et accessible,
- d'un **espace adhérent sécurisé** (planning, blog, propositions),
- d'un **back-office d'administration** (gestion des membres, publications, contenus).

> Pas de CMS générique — un outil construit pour les besoins réels du terrain.

---

## 🛠 Stack technique

| Couche | Technologies |
|---|---|
| **Back-end** | PHP 8.2+ · Symfony 7.4 · Doctrine ORM |
| **Base de données** | MariaDB |
| **Templates** | Twig |

---

## 🏗 Architecture

Le site se compose de trois espaces avec des niveaux d'accès distincts :

| Espace | Description |
|---|---|
| **Public** | Présentation du GEM, activités, charte, mooks, contact |
| **Adhérent** | Dashboard, planning PDF, comptes-rendus, blog, propositions |
| **Administration** | Gestion des adhérents, publications, blog avec médias |

L'authentification est gérée par Symfony Security — pas d'inscription publique, les comptes sont créés par l'administration.

---

## ✨ Points notables

- **Export CSV** des adhérents pour le suivi administratif
- **Upload et gestion de médias** : PDF, images, vidéos, podcasts
- **Système de propositions** permettant aux adhérents de soumettre des idées
- **Blog interne** avec filtres par catégorie et par date
- **Identité visuelle intégrée** (bannière, couleurs, typographies de l'association)

---

## 📸 Aperçu

*Captures d'écran à venir.*

---

<p align="center"><sub>Développé par <a href="https://varyacode.fr">VaryaCode</a> — 2025</sub></p>
