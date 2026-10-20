# Modèle et indicateurs

## Relations

Une commune contient plusieurs localités. Une localité contient des points d’eau et des signalements. Un incident peut concerner un point non encore répertorié et recevoir plusieurs interventions. Chaque intervention possède ses pièces et peut recevoir des photos. Les historiques appartiennent à l’incident et conservent l’acteur quand il est connecté. Les entretiens appartiennent au point d’eau, avec un responsable et une date prévue.

Le compte qui saisit est enregistré séparément du nom du déclarant. Les déclarants sans compte accèdent au suivi via une référence et un secret dont seul le hachage est conservé côté serveur. Le même UUID client et le même secret sont conservés lors des nouvelles tentatives d’envoi ; une contrainte unique en base empêche la duplication, y compris en cas de tentatives concurrentes.

## Statuts

- Signalé → Vérification, Non retenu ou Doublon.
- Vérification → Planifié, Non retenu ou Doublon.
- Planifié → Réparé ou Vérification.
- Réparé → Confirmé ou Vérification.
- Confirmé, Non retenu ou Doublon → Vérification en cas de réouverture motivée.

La planification nécessite une intervention. Le passage à Réparé exige un compte rendu. La confirmation passe par un point d’entrée dédié : le technicien ne peut pas confirmer sa propre réparation avec son accès professionnel.

## Calculs

Les filtres de période portent sur la **date de création de l’incident**, et non sur la date des interventions. Les filtres géographiques s’appliquent à l’intérieur du périmètre autorisé.

| Indicateur | Calcul |
|---|---|
| Incidents ouverts | Statut autre que confirmé, non retenu ou doublon. |
| Prise en charge | Moyenne en heures entre création et premier changement de statut, pour les incidents ayant cette date ; effectif affiché. |
| Remise en service | Moyenne en heures entre création et confirmation pour les incidents actuellement confirmés ; effectif affiché. |
| Réouverture | La confirmation est effacée ; le délai de la prochaine confirmation repart de la création initiale. |
| Points hors service | État actuel déclaré des points dans la zone autorisée ; indépendant de la période de création des incidents. |
| Blocages | Incidents ayant un blocage général ou au moins une intervention avec un blocage non vide, y compris les incidents déjà clos dans la période. |
| Récurrences | Plusieurs incidents sur le même point dans la période ; signalements classés doublon exclus. |
| Entretiens | Prochains entretiens non terminés, triés par date, avec les retards ; indépendant du filtre de période des incidents. |

Les valeurs sans observation suffisante sont `null`, affichées « — », jamais artificiellement zéro.

L’export reprend les incidents filtrés et autorisés. Il omet les noms et téléphones des déclarants et neutralise les cellules pouvant être interprétées comme une formule de tableur.
