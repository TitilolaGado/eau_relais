# Bilan de validation — EauRelais V1

Date : 19 octobre 2026.

## Vérifications exécutées

| Vérification | Résultat |
|---|---|
| PHPUnit sur SQLite en mémoire | 16 tests réussis, 118 assertions. |
| Même suite avec la connexion MySQL sur MariaDB 10.11.14 | 16 tests réussis, 118 assertions. |
| Migrations sur MariaDB | Exécutées avec succès. |
| Installation et chargement des données fictives sur SQLite | Exécutés avec succès. |
| File hors connexion et IndexedDB simulée | 5 tests réussis. |
| Compilation Vite des écrans Vue | Réussie. |
| Génération de la coquille PWA et du service worker | Réussie. |
| Résolution Composer et verrouillage des dépendances | Réussis ; aucune alerte de sécurité signalée par Composer lors de cette résolution. |

## Couverture des tests serveur

- Pages publiques et pages métier protégées.
- Répétition d’un signalement avec le même UUID : une seule ligne en base.
- Refus d’un secret incorrect et absence des coordonnées privées dans le suivi public.
- Parcours signalement → vérification → affectation → réparation → confirmation publique.
- Refus de confirmation par le rôle technicien.
- Refus d’accès d’un gestionnaire d’une autre commune et d’un technicien non affecté.
- Validation des transitions, dates, consentement GPS, types et tailles des pièces jointes.
- Délais sans observations affichables comme absents et calculs sur des dates connues.
- Réouverture, effacement de la confirmation et révision de l’état du point d’eau.
- Export sans téléphone, catalogue public sans comptes rendus d’entretien privés.
- Création et modification d’un technicien par l’administrateur ; refus pour le gestionnaire.
- Réponses privées marquées `no-store`.
- Protection contre le chargement du jeu de démonstration en production.
- Association d’un signalement à un point d’eau uniquement dans sa localité.

## Couverture de la file hors connexion

- Aucune suppression sans confirmation de réception.
- Conservation du même UUID et du même secret après une réponse réseau perdue.
- Poursuite des autres demandes en cas de validation invalide.
- Arrêt temporaire en cas d’indisponibilité serveur.
- Conservation puis suppression d’un brouillon ; synchronisation sans double envoi simultané ; suppression des contacts locaux après réception et conservation du seul reçu.

## Vérifications encore nécessaires

Aucun navigateur de contrôle compatible n’était disponible pour cette réalisation. La vérification visuelle sur ordinateur et téléphone, l’installation PWA réelle, les permissions de caméra/GPS et les essais de coupure du réseau sur appareil n’ont donc pas été exécutés. Les tests IndexedDB utilisent `fake-indexeddb` : ils vérifient la logique de stockage, pas le comportement de tous les navigateurs.

La compatibilité base a été testée avec MariaDB via le pilote MySQL de Laravel ; un serveur Oracle MySQL 8 n’a pas été exécuté dans cet environnement.

Cette livraison n’a pas été déployée sur un serveur public. Les notifications externes, la cartographie, les paiements et les capteurs ne font pas partie du MVP. Les rappels d’entretien nécessitent la tâche planifiée décrite dans le README.

Le pilote doit également préciser les personnes responsables, les délais cibles, les modalités de référencement des techniciens et le financement des réparations. Ces éléments organisationnels ne sont pas remplacés par l’application.
