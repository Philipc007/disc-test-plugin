# Refonte Scoring DISC — Alignement sur méthode D4D

## Contexte

L'algorithme actuel utilise une normalisation min-max intra-personnelle (voir SPECS.md section "Calcul des Scores DISC"). Cela produit **mécaniquement** un score de 100 pour le dominant et 0 pour le plus faible à chaque test. Ce n'est pas un bug — c'est la conséquence directe de la formule min-max. Mais c'est un problème de crédibilité : des dirigeants familiers du DISC trouveront ça amateur.

## Objectif

Aligner le scoring du plugin DISC WordPress sur la méthode utilisée dans le Diagnostic 4D du Dirigeant (notre produit premium). Le Quick Scan DISC et le D4D doivent partager la même logique de scoring pour former un écosystème cohérent.

## Nouvelle logique de scoring

### Étape 1 — Scoring brut par axe (MODIFIÉ)

Pour chaque réponse (28 questions) :

```
scores[most_like]  += 2      // "Le plus moi"
scores[least_like] -= 1      // "Le moins moi"
// NOUVEAU : les deux dimensions NON choisies
scores[neutre_1]   += 0.5
scores[neutre_2]   += 0.5
```

**Propriété ipsative** : la somme par question est toujours +2 (2 - 1 + 0.5 + 0.5 = 2).
**Somme totale** sur 28 questions : 28 × 2 = 56, répartis entre les 4 axes.

### Étape 2 — Conversion en scores relatifs (%)

```
Pour chaque axe :
  score_relatif = round((score_brut / somme_des_4_scores_bruts) * 100)
```

**Les 4 scores relatifs totalisent 100%.**

Exemple avec scores bruts D=22, I=16, S=10, C=8 (somme = 56) :
- D = round(22/56 × 100) = 39%
- I = round(16/56 × 100) = 29%
- S = round(10/56 × 100) = 18%
- C = round(8/56 × 100) = 14%

→ Résultat nuancé, lisible, crédible. Pas de 0 ni de 100 mécanique.

### Cas edge : somme des scores bruts = 0

Théoriquement impossible avec le +0.5 sur les neutres (la somme est toujours 56). Mais par sécurité, si `somme_des_4_scores_bruts == 0`, afficher 25% pour chaque axe.

## Détermination du profil (MODIFIÉ)

Les seuils actuels (>= 60) n'ont plus de sens avec des scores en %. Nouvelle logique :

### Profil dominant

```
Trier les 4 scores relatifs par ordre décroissant :
  score_1 (plus élevé) → dimension_1
  score_2              → dimension_2
  score_3              → dimension_3
  score_4 (plus bas)   → dimension_4
```

### Profil simple vs double

```
ecart_1_2 = score_1 - score_2

Si ecart_1_2 > SEUIL_DOUBLE (défaut : 5) :
  profil = dimension_1                    // Ex: "D"
Sinon :
  profil = dimension_1 + dimension_2      // Ex: "DI"
```

### Profil équilibré

```
ecart_type = écart-type des 4 scores relatifs

Si ecart_type < SEUIL_EQUILIBRE (défaut : 4) :
  profil = "DISC"
```

### Profil triple (optionnel)

```
ecart_2_3 = score_2 - score_3

Si ecart_1_2 <= SEUIL_DOUBLE ET ecart_2_3 <= SEUIL_DOUBLE :
  profil = dimension_1 + dimension_2 + dimension_3   // Ex: "DIS"
```

### Seuils configurables

```php
// À ajouter dans les constantes ou en option WordPress
define('DISC_SEUIL_DOUBLE', 5);     // Écart max pour profil double (en points %)
define('DISC_SEUIL_EQUILIBRE', 4);  // Écart-type max pour profil équilibré
```

### Profils supportés (inchangé)

Les 12+ profils existants restent valides :
- Simples : D, I, S, C
- Doubles : DI, DS, DC, IS, IC, SC
- Triples : DIS (et autres si détectés)
- Équilibré : DISC

## Fichiers à modifier

### 1. `includes/class-disc-frontend.php`

**Méthode `handle_contact_submission()`** :

- Modifier le calcul des scores bruts : ajouter +0.5 aux deux dimensions non choisies pour chaque réponse
- Remplacer la normalisation min-max par la formule scores relatifs (%)
- Stocker les scores relatifs en BDD (champs score_d, score_i, score_s, score_c) — ce sont des entiers 0-100 qui représentent maintenant des pourcentages

### 2. `includes/class-disc-renderer.php`

**Méthode `determine_profile_type()`** :

- Remplacer la logique `>= 60` par la nouvelle logique basée sur les écarts
- Implémenter la détection profil double (écart <= 5)
- Implémenter la détection profil équilibré (écart-type < 4)
- Documenter les seuils dans un commentaire en tête de méthode

### 3. `includes/class-disc-email.php`

**Template email** :

- Remplacer le label "Score" par "Tendance" dans le tableau des résultats
- Ajouter sous le graphique : *"Les valeurs représentent la répartition de vos tendances comportementales. Elles totalisent 100%."*
- Les scores affichés doivent inclure le signe % (ex: "D : 39%")

### 4. `assets/js/frontend.js`

**Fonction `displayResults()` et `createChart()`** :

- Afficher les scores avec le signe %
- Remplacer "Score" par "Tendance" dans les labels
- Configurer l'axe du graphique Chart.js :
  - **NE PAS fixer le max à 100** (puisque les scores totalisent 100%, aucun axe ne devrait dépasser ~50-60% en pratique)
  - Laisser Chart.js auto-scaler ou fixer un max à 60 pour une meilleure lisibilité
  - Alternative : fixer max à 100 si tu préfères la stabilité visuelle (au choix)

### 5. Webhook payload (`class-disc-frontend.php`)

- Les scores envoyés au CRM sont les scores relatifs (%)
- Adapter les seuils de tags CRM : un score >= 30% (au lieu de 60) indique une dimension significative dans cette nouvelle échelle
- Mettre à jour l'exemple dans SPECS.md

## Tests à effectuer

### Profils types à vérifier

**Test 1 — Profil très marqué (D pur)** :
Répondre D en "most_like" à 20+ questions sur 28.
→ Attendu : D très élevé (~40-45%), les autres bien en dessous, profil "D"

**Test 2 — Profil double (DI)** :
Alterner D et I en "most_like" de manière équilibrée.
→ Attendu : D et I proches (~30% chacun), écart < 5, profil "DI"

**Test 3 — Profil équilibré** :
Distribuer les choix uniformément entre D, I, S, C.
→ Attendu : 4 scores proches de 25%, écart-type < 4, profil "DISC"

### Checklist

- [ ] Le dominant n'est plus systématiquement à 100
- [ ] Le plus faible n'est plus systématiquement à 0
- [ ] Les 4 scores affichés totalisent 100% (±1% arrondi)
- [ ] Profil simple correctement détecté
- [ ] Profil double correctement détecté
- [ ] Profil équilibré correctement détecté
- [ ] Les 12 descriptions de profils s'affichent toujours correctement
- [ ] Graphique Chart.js lisible et proportionné
- [ ] Email reçu avec bon vocabulaire ("Tendance") et signe %
- [ ] Webhook payload correct sur webhook.site
- [ ] Tags CRM cohérents avec les nouveaux seuils

## Résumé des changements vs algorithme actuel

| Aspect | Avant (v1.0) | Après (v1.2) |
|--------|-------------|-------------|
| Scoring brut | +2 / -1 / 0 | +2 / -1 / +0.5 / +0.5 |
| Somme par question | +1 | +2 (ipsatif) |
| Normalisation | Min-max intra-personnelle | Scores relatifs (% du total) |
| Échelle affichée | 0-100 (artificiel) | 0-100% (somme = 100%) |
| Dominant mécanique à 100 | Oui (toujours) | Non |
| Plus faible mécanique à 0 | Oui (toujours) | Non |
| Seuil profil double | score >= 60 | écart top2 <= 5% |
| Seuil profil équilibré | aucun des 4 >= 60 | écart-type < 4 |
| Vocabulaire | "Score" | "Tendance" |

## Notes importantes

- **Base de données** : vider les résultats existants avant déploiement (les anciens scores ne sont pas compatibles)
- **Backward compatibility** : aucune nécessaire, on est en phase de test
- **SPECS.md** : mettre à jour la section "Algorithmes Clés > Calcul des Scores DISC" après implémentation
- **Cette logique est alignée avec le Diagnostic 4D du Dirigeant** (notre produit premium) qui utilise le même format ipsatif +2/-1/+0.5
