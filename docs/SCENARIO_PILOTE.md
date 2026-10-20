# Scénario de présentation — EauRelais

## Préparer

Installer le projet et charger les données fictives selon le README. Prévoir deux sessions de navigateur distinctes pour éviter de mélanger les connexions. Utiliser un nouvel incident pour le parcours complet.

## 1. Une école signale

- Ouvrir l’accueil, puis « Signaler une panne ».
- Choisir une localité et un point d’eau d’école.
- Décrire : « La pompe est bloquée, les élèves ne peuvent plus puiser de l’eau. »
- Ajouter un contact fictif, envoyer et télécharger le reçu.
- Expliquer la différence entre enregistrement local et réception par le serveur.

## 2. Le gestionnaire prend en charge

- Se connecter comme gestionnaire et ouvrir le nouvel incident.
- Passer en « Vérification en cours » et expliquer la priorité.
- Planifier une intervention avec la technicienne disponible.
- Montrer l’historique et la notification d’affectation.

## 3. La technicienne intervient

- Se connecter comme technicienne.
- Ouvrir l’incident affecté et son compte rendu.
- Accepter, saisir un diagnostic, les travaux, une pièce et la date réelle.
- Cocher la réparation terminée et enregistrer.
- Le statut devient « Réparé — confirmation attendue ».

## 4. L’école confirme

- Sans session professionnelle, ouvrir « Suivre mon signalement ».
- Utiliser la référence et le code du reçu.
- Ajouter un commentaire puis confirmer « L’eau est revenue ».
- Montrer la remise en service et la mise à jour du tableau de bord.

## 5. Tester une réparation infructueuse

Sur un autre incident réparé, choisir « Le problème persiste ». Le signalement retourne en vérification ; la date de confirmation est effacée et le fonctionnement redevient à vérifier.

## 6. Tester le réseau limité sur un vrai appareil

- Ouvrir une première fois le formulaire en ligne, attendre la préparation des ressources.
- Couper la connexion puis recharger la page.
- Saisir un brouillon ; le reprendre puis soumettre.
- Vérifier l’état « En attente d’envoi ».
- Rétablir le réseau, garder l’application ouverte et réessayer si nécessaire.
- Vérifier une seule ligne en base, malgré plusieurs tentatives.

Ces essais réels sur navigateur et téléphone doivent compléter les tests automatisés. Ne pas confondre la simulation IndexedDB de la suite de tests avec un essai radio ou une vérification visuelle sur appareil.
