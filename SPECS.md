# Spécifications Techniques - Plugin DISC Test WordPress

## Vue d'ensemble

Plugin WordPress pour administrer un test DISC psychométrique comme lead magnet B2B pour dirigeants et managers d'entreprises.

**Version** : 1.1.0
**Status** : MVP complet — en déploiement
**Stack** : WordPress 5.8+, PHP 7.4+, MySQL 5.7+, JavaScript ES6, Chart.js 3.9.1, QuickChart.io

## Architecture

### Base de données

#### Table `wp_disc_questions`
```sql
- id (bigint, PK, auto_increment)
- question_order (int, indexed) - Ordre d'affichage
- statement_d (text) - Affirmation Dominance
- statement_i (text) - Affirmation Influence  
- statement_s (text) - Affirmation Stabilité
- statement_c (text) - Affirmation Conformité
- created_at (datetime)
```

#### Table `wp_disc_results`
```sql
- id (bigint, PK, auto_increment)
- session_token (varchar(64), unique, indexed)
- email (varchar(255), indexed)
- first_name (varchar(100))
- last_name (varchar(100))
- company (varchar(255))
- position (varchar(255))
- score_d (int) - Score Dominance 0-100
- score_i (int) - Score Influence 0-100
- score_s (int) - Score Stabilité 0-100
- score_c (int) - Score Conformité 0-100
- profile_type (varchar(10), indexed) - Ex: "DI", "SC"
- consistency_score (decimal(5,2)) - Score cohérence 0-100
- average_response_time (decimal(8,2)) - Secondes
- total_time (int) - Secondes
- ip_address (varchar(45))
- user_agent (text)
- consent_given (tinyint)
- consent_timestamp (datetime)
- completed_at (datetime, indexed)
- created_at (datetime)
```

#### Table `wp_disc_responses`
```sql
- id (bigint, PK, auto_increment)
- result_id (bigint, indexed, FK)
- question_id (bigint, indexed)
- most_like (varchar(1)) - 'D', 'I', 'S', ou 'C'
- least_like (varchar(1)) - 'D', 'I', 'S', ou 'C'
- response_time (decimal(8,2)) - Secondes
- created_at (datetime)
```

#### Table `wp_disc_audit_logs`
```sql
- id (bigint, PK, auto_increment)
- event_type (varchar(50), indexed)
- user_id (bigint)
- session_token (varchar(64))
- ip_address (varchar(45), indexed)
- details (text, JSON)
- created_at (datetime, indexed)
```

### Classes PHP

#### DISC_Database
**Responsabilité** : Gestion de toutes les opérations BDD

**Méthodes principales** :
- `create_tables()` - Crée les 4 tables
- `insert_default_questions()` - Insère les 28 questions DISC
- `get_questions()` - Récupère toutes les questions
- `save_result($data)` - Enregistre un résultat
- `save_responses($token, $responses)` - Enregistre les réponses détaillées
- `get_all_results($limit, $offset)` - Liste paginée
- `get_statistics()` - Métriques globales
- `log_event($type, $details, $token)` - Audit trail

**Sécurité** :
- Utilise `$wpdb->prepare()` pour TOUTES les requêtes
- Validation des types avec casting explicite
- Indexes sur colonnes de recherche

#### DISC_Security
**Responsabilité** : Couche de sécurité globale

**Méthodes principales** :
- `verify_ajax_nonce()` - Vérifie les nonces AJAX
- `get_client_ip()` - IP sécurisée (proxy-aware)
- `check_rate_limit()` - 3 tests/heure/IP via transients
- `generate_session_token()` - Token unique 64 chars
- `validate_contact_data($data)` - Validation formulaire
- `calculate_consistency_score($responses)` - Anti-triche
- `validate_response_times($times)` - Détection anomalies
- `encrypt_email($email)` - AES-256-CBC
- `decrypt_email($encrypted)` - Déchiffrement

**Clé d'encryption** :
```php
// À ajouter dans wp-config.php
define('DISC_ENCRYPTION_KEY', 'votre_cle_32_caracteres');
```

#### DISC_Renderer
**Responsabilité** : Génération HTML (approche hybride)

**Méthodes principales** :
- `render_test($atts)` - Fonction centrale appelée par shortcode ET bloc
- `get_profile_description($type, $scores)` - Descriptions des 12 profils
- `determine_profile_type($scores)` - Calcul du profil dominant

**Profils supportés** :
- Simples : D, I, S, C
- Doubles : DI, DS, DC, IS, IC, SC
- Triples : DIS
- Équilibré : DISC

**HTML généré** :
- Écran démarrage avec bénéfices
- 28 questions avec navigation progressive
- Barre de progression
- Formulaire contact RGPD
- Écran résultats avec graphique Chart.js

#### DISC_Frontend
**Responsabilité** : Gestion interactions utilisateur

**Méthodes principales** :
- `handle_response_submission()` - AJAX réponse question (logging)
- `handle_contact_submission()` - AJAX formulaire + calcul scores

**Workflow soumission** :
1. Vérifie nonce et rate limit
2. Valide données contact
3. Calcule scores DISC (normalisation 0-100)
4. Calcule cohérence et temps
5. Détermine profil
6. Enregistre BDD
7. Envoie email
8. Trigger hook WordPress
9. Retourne résultats JSON

**Hook intégration CRM** :
```php
do_action('disc_test_completed', $contact_data, $scores, $profile_type);
```

#### DISC_Email
**Responsabilité** : Envoi emails automatiques

**Méthodes principales** :
- `send_results_email($contact, $scores, $profile)` - Email HTML

**Template email** :
- Header avec gradient
- Badge profil
- Scores en tableau
- Description profil
- Forces principales
- Conseils développement
- Footer avec contact

#### DISC_Admin
**Responsabilité** : Interface administration WordPress

**Pages créées** :
- **Résultats** : Liste tous les participants avec filtres
- **Statistiques** : Dashboard avec métriques
- **Questions** : Visualisation des 28 questions
- **Paramètres** : Configuration plugin

**Métriques affichées** :
- Total tests
- Tests 30 derniers jours
- Distribution profils (graphique)
- Cohérence moyenne
- Temps moyen

### Frontend JavaScript

#### Fichier `frontend.js`

**Variables globales** :
- `sessionToken` - UUID généré côté client
- `currentQuestionIndex` - Position dans le test
- `responses[]` - Stockage réponses
- `questionStartTime` - Tracking temps
- `testStartTime` - Durée totale

**Fonctions principales** :
```javascript
startTest()                          // Lance le test
handleNextQuestion()                 // Validation + navigation
handlePreviousQuestion()             // Retour arrière
handleRadioChange($radio)            // Gestion exclusivité choix
submitContactForm()                  // AJAX soumission finale
displayResults(data)                 // Affiche profil + graphique
createChart(scores)                  // Chart.js barres horizontales
validateEmail(email)                 // Regex validation
shareOnLinkedIn()                    // Popup partage
showLoader() / hideLoader()          // UX pendant AJAX
```

**Validation côté client** :
- Email format (regex)
- Champs requis non vides
- "Le plus" ≠ "Le moins" pour chaque question
- Longueur min prénom/nom (2 chars)
- Consentement RGPD coché

**AJAX endpoints** :
```javascript
discTest.ajaxUrl                     // admin-ajax.php
discTest.nonce                       // Nonce sécurité
discTest.strings                     // Traductions
```

### Styles CSS

#### `frontend.css`
**Design mobile-first** responsive

**Composants** :
- `.disc-test-container` - Container principal max-width 800px
- `.disc-screen` - Écrans avec transitions fadeIn
- `.disc-progress-bar` - Barre gradient animée
- `.disc-question` - Card question avec ombre
- `.disc-statement` - Affirmations avec hover
- `.disc-choice` - Radio buttons stylés (vert "plus" / rouge "moins")
- `.disc-profile-badge` - Badge profil avec gradient
- `.disc-chart-container` - Container graphique 300px height
- `.disc-loading-overlay` - Overlay fullscreen avec spinner

**Couleurs** :
- Primary : `#667eea` → `#764ba2` (gradient)
- Dominance : `#dc2626` (rouge)
- Influence : `#eab308` (jaune)
- Stabilité : `#22c55e` (vert)
- Conformité : `#3b82f6` (bleu)

**Breakpoints** :
- Desktop : > 768px
- Tablet : 768px
- Mobile : < 480px

#### `admin.css`
Dashboard WordPress avec cards statistiques en grid

#### `block-editor.css`
Styles preview bloc Gutenberg

### Shortcode

**Usage** :
```
[disc_test]
[disc_test showTitle="true" buttonText="Démarrer" redirectUrl=""]
```

**Attributs** :
- `showTitle` (bool, défaut: true) - Affiche titre intro
- `buttonText` (string, défaut: "Commencer le test") - Texte bouton
- `redirectUrl` (string, défaut: "") - Redirection post-test

### Bloc Gutenberg

**Block type** : `disc-test/test-block`

**Attributs** : Identiques au shortcode

**Render** : Server-side via `DISC_Renderer::render_test()`

**Editor UI** :
- InspectorControls (barre latérale)
- ToggleControl pour showTitle
- TextControl pour buttonText
- TextControl (url) pour redirectUrl
- Preview placeholder dans l'éditeur

## Algorithmes Clés

### Calcul des Scores DISC

**Entrées** : 28 réponses avec "most_like" et "least_like"

**Logique** :
```
Pour chaque réponse :
  scores[most_like] += 2
  scores[least_like] -= 1

Normalisation 0-100 :
  min = min(scores)
  max = max(scores)
  range = max - min
  
  Pour chaque dimension :
    scores[dim] = ((scores[dim] - min) / range) * 100
```

**Résultat** : 4 scores entre 0 et 100

### Détermination du Profil

**Logique** :
```
Trier scores par ordre décroissant

Si score_1 >= 60 :
  profil = dimension_1
  
  Si score_2 >= 60 :
    profil += dimension_2
Sinon :
  profil = "DISC" (équilibré)
```

**Exemples** :
- D=85, I=72, S=45, C=30 → Profil "DI"
- D=92, I=45, S=40, C=35 → Profil "D"
- D=55, I=58, S=52, C=50 → Profil "DISC"

### Score de Cohérence

**But** : Détecter les réponses incohérentes (triche/inattention)

**Paires de questions validées** :
```
(1, 13) - Leadership/Direction
(2, 17) - Défis/Audace
(3, 24) - Focus objectifs/Efficacité
(5, 10) - Positivité
(6, 15) - Contrôle/Résultats
(8, 22) - Action/Initiative
(11, 23) - Risques/Compétition
(14, 26) - Communication ferme
(16, 20) - Chaleur sociale
(19, 25) - Relations/Rapport
```

**Logique** :
```
Pour chaque paire (q1, q2) :
  Si (q1.most_like == q2.most_like) OU
     (q1.least_like == q2.least_like) :
    consistent++

score = (consistent / total_paires) * 100
```

**Interprétation** :
- ≥ 70% : Cohérent (vert)
- 50-69% : Acceptable (orange)
- < 50% : Suspect (rouge + warning)

### Validation Temps de Réponse

**Seuils** :
- Trop rapide : < 2 secondes
- Trop lent : > 180 secondes (3 minutes)

**Warnings si** :
- > 30% des questions trop rapides
- > 5 questions trop lentes

## Hooks WordPress

### Actions
```php
disc_test_completed($contact_data, $scores, $profile_type)
```
Déclenché après enregistrement résultat, avant email.

**Use case** : Intégration CRM, webhooks, analytics

### Filtres
Aucun filtre exposé actuellement (ajout possible si besoin)

## Sécurité

### CSRF Protection
- Nonces sur tous les formulaires
- `wp_nonce_field()` génération
- `wp_verify_nonce()` vérification

### XSS Protection
**Entrées** :
- `sanitize_email()` pour emails
- `sanitize_text_field()` pour textes courts
- `sanitize_textarea_field()` pour longs textes

**Sorties** :
- `esc_html()` pour texte
- `esc_attr()` pour attributs HTML
- `esc_url()` pour URLs
- `esc_js()` pour JavaScript inline

### SQL Injection
- `$wpdb->prepare()` systématique
- Casting types explicite
- Jamais de concaténation directe

### Rate Limiting
**Méthode** : WordPress Transients
**Règle** : 3 tests maximum par heure par IP
**Stockage** : `disc_rate_limit_{md5(ip)}`
**TTL** : HOUR_IN_SECONDS (3600s)

### RGPD
- Checkbox consentement obligatoire
- Timestamp du consentement stocké
- Lien vers politique confidentialité
- Emails chiffrés en BDD (AES-256-CBC)
- Possibilité suppression données (manuel admin)

## Performance

### Base de Données
- Indexes sur : email, profile_type, completed_at, ip_address, event_type
- Pagination avec LIMIT/OFFSET
- Pas de JOIN complexes

### Assets
- Chart.js depuis CDN (cache browser)
- CSS/JS avec version query string (cache busting)
- Chargement conditionnel admin assets

### Caching
Pas de cache implémenté (compatible avec plugins cache WordPress)

## Internationalisation

**Text domain** : `disc-test`
**Fonction** : `__()` et `_e()`
**Fichiers** : `/languages/` (vide, à créer si traductions)

**Langues** : Français uniquement actuellement

## Tests

### Tests Manuels Requis
1. ✅ Activation plugin sans erreur
2. ✅ Création des 4 tables
3. ✅ Insertion 28 questions
4. ✅ Affichage shortcode
5. ✅ Navigation 28 questions
6. ✅ Validation "le plus" ≠ "le moins"
7. ✅ Soumission formulaire contact
8. ✅ Calcul scores correct
9. ✅ Affichage graphique Chart.js
10. ✅ Envoi email automatique
11. ✅ Stockage BDD résultats
12. ✅ Page admin résultats accessible
13. ✅ Statistiques calcul correct
14. ✅ Rate limiting fonctionne (4ème test bloqué)
15. ✅ Responsive mobile

### Tests de Charge
Non effectués (recommandé avant lancement public)

## Déploiement

### Prérequis WordPress
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+
- allow_url_fopen activé (Chart.js CDN)

### Installation
1. Upload ZIP via admin WordPress
2. Activer le plugin
3. Configurer DISC_ENCRYPTION_KEY dans wp-config.php
4. Configurer politique confidentialité
5. Tester SMTP pour emails
6. Créer landing page avec shortcode

### Configuration SMTP Recommandée
Plugin : WP Mail SMTP ou similaire
Raison : Meilleure délivrabilité emails

## Fonctionnalités Implémentées (v1.1)

- ✅ Export CSV (Date+heure, tous champs, BOM UTF-8, séparateur `;` pour Excel FR)
- ✅ Webhook CRM — POST JSON non-bloquant vers URL configurable dans les paramètres
- ✅ Graphique dans l'email — image statique via QuickChart.io (compatible tous clients email)
- ✅ Renvoi d'email depuis l'admin par résultat (avec confirmation + log audit)
- ✅ Édition des questions depuis l'admin
- ✅ Partage LinkedIn — modale copier/coller (API LinkedIn ne supporte plus le pré-remplissage)
- ✅ Tags CRM dans le webhook — configurables dans les paramètres (à venir)

## Limitations Connues

1. **Pas de PDF** - Email HTML + graphique image (phase 1)
2. **Bloc Gutenberg non compilé** - Nécessite npm build (optionnel)
3. **Pas de traductions** - Français uniquement
4. **Pas de cache** - Compatible mais pas implémenté
5. **Admin.js vide** - Pas de JS admin pour l'instant

## Roadmap

### v1.2 (Post-lancement)
- Tags CRM configurables dans les paramètres
- Génération PDF résultats
- Compilation bloc Gutenberg

### v1.2
- Traductions EN/ES
- Comparaison d'équipe
- Badge LinkedIn personnalisé
- Analytics avancés

### v2.0
- Version SaaS multi-sites
- API REST
- Intégrations natives (HubSpot, Salesforce)
- Rapports avancés avec charts


## Architecture Future & Vision Évolutive

### Vision Stratégique (Horizon 6-12 mois)

Le plugin DISC Test actuel est conçu comme un **MVP monolithique** (un seul test, un seul algorithme, une seule langue). La vision à moyen terme est de transformer ce plugin en un **framework modulaire de tests psychométriques** permettant de gérer plusieurs types de tests, algorithmes et langues depuis le même moteur.

### Objectifs de la Modularisation Future

**Business** :
- Offre freemium : DISC Express (5 min) gratuit → DISC Standard (10 min) → DISC Pro (50 min) payant
- Verticales métier : DISC RH, DISC Commercial, DISC Management
- Expansion internationale : FR, EN, ES
- Licensing : Vendre le framework à d'autres coachs/consultants

**Technique** :
- Réutiliser le moteur de capture de leads pour tous les tests
- Ajouter de nouveaux tests sans dupliquer le code
- Gérer plusieurs algorithmes de scoring
- Support multi-langue natif

### Architecture Cible (v2.0)

```
Moteur de Test Psychométrique (Core)
├── Test Engine (réutilisable)
│   ├── Lead Capture
│   ├── Question Rendering
│   ├── Progress Tracking
│   └── Results Display
├── Test Kits (modules interchangeables)
│   ├── disc-express/
│   │   ├── config.json (10 questions, 5 min)
│   │   ├── questions-fr.json
│   │   ├── questions-en.json
│   │   ├── algorithm.php
│   │   └── profiles.json
│   ├── disc-standard/          ← MVP actuel
│   │   ├── config.json (28 questions, 10 min)
│   │   ├── questions-fr.json
│   │   ├── algorithm.php
│   │   └── profiles.json
│   └── disc-pro/
│       ├── config.json (50 questions + secteur)
│       └── ...
├── Algorithms (pluggables)
│   ├── disc-classic.php (+2/-1 scoring)
│   ├── disc-weighted.php (pondération avancée)
│   └── custom-algorithms/
└── Languages
    └── i18n standard WordPress
```

### Structure Base de Données Future

```sql
-- Table des kits de tests (v2.0)
CREATE TABLE wp_disc_test_kits (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT COMMENT 'Durée estimée en minutes',
    question_count INT,
    algorithm VARCHAR(50),
    config LONGTEXT COMMENT 'JSON: questions, profils, options',
    pricing ENUM('free', 'premium') DEFAULT 'free',
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Modification table résultats (v2.0)
ALTER TABLE wp_disc_results 
ADD COLUMN test_kit_id VARCHAR(50) DEFAULT 'disc-standard' AFTER id,
ADD COLUMN test_version VARCHAR(20) DEFAULT '1.0' AFTER test_kit_id,
ADD KEY test_kit_id (test_kit_id);

-- Table des questions devient générique (v2.0)
ALTER TABLE wp_disc_questions
ADD COLUMN test_kit_id VARCHAR(50) DEFAULT 'disc-standard' AFTER id,
ADD KEY test_kit_id (test_kit_id);
```

### Principes de Conception pour Faciliter l'Évolution

#### 1. Séparation des Préoccupations

**À respecter dès maintenant (v1.0)** :
- ✅ Questions dans une table séparée (déjà fait)
- ✅ Algorithme dans des méthodes isolées (déjà fait)
- ✅ Descriptions de profils dans des structures de données (déjà fait)

**À améliorer progressivement** :
- 🔄 Externaliser les questions dans un fichier JSON (facilite ajout langues)
- 🔄 Encapsuler l'algorithme dans une interface abstraite
- 🔄 Rendre les profils configurables par test

#### 2. Configuration vs Hard-coding

**Actuellement** : 
- Questions en BDD ✅
- Algorithme hardcodé dans le code PHP ⚠️
- Profils hardcodés dans le code PHP ⚠️

**Objectif v2.0** :
- Questions en JSON par kit/langue
- Algorithmes en classes pluggables
- Profils en JSON par kit/langue

#### 3. Extensibilité

**Hooks WordPress à exposer (v2.0)** :
```php
// Filtrer les questions avant affichage
apply_filters('disc_test_questions', $questions, $test_kit_id);

// Filtrer l'algorithme de calcul
apply_filters('disc_test_algorithm', $algorithm, $test_kit_id);

// Filtrer les profils
apply_filters('disc_test_profiles', $profiles, $test_kit_id);

// Action après génération résultats
do_action('disc_test_results_generated', $result, $test_kit_id);
```

### Plan de Migration v1.0 → v2.0

#### Phase 1 : Préparation (v1.1 - 1-2 jours)

**Changements mineurs compatibles v1.0** :
- Ajouter `test_version` dans wp_disc_results
- Créer dossier `/data/test-kits/disc-standard/`
- Exporter questions actuelles en JSON
- Documenter l'algorithme actuel

**Impact** : Zéro sur fonctionnement actuel, pose les bases pour v2.0

#### Phase 2 : Abstraction du Moteur (v1.5 - 1 semaine)

**Refactoring sans changement fonctionnel** :
- Créer `class-test-engine.php` (moteur générique)
- Migrer logique de `DISC_Renderer` vers le moteur
- Créer interface `Test_Algorithm`
- Implémenter `DISC_Classic_Algorithm` (actuel)

**Impact** : Aucun pour l'utilisateur final, prêt pour multi-tests

#### Phase 3 : Multi-kits (v2.0 - 1-2 semaines)

**Nouveaux composants** :
- Table `wp_disc_test_kits`
- Interface admin "Kits de Tests"
- Loader dynamique de kits
- Sélecteur de kit dans shortcode : `[disc_test kit="disc-express"]`

**Impact** : Activation de plusieurs tests simultanés

### Décisions d'Architecture pour v1.0 (MVP Actuel)

#### Ce qu'on FAIT maintenant :
- ✅ Garder l'architecture actuelle simple et fonctionnelle
- ✅ Lancer rapidement pour valider le concept
- ✅ Éviter la sur-ingénierie prématurée

#### Ce qu'on PRÉVOIT pour faciliter v2.0 :
- 🔄 Commenter clairement les zones qui seront modularisées
- 🔄 Utiliser des noms de variables/fonctions génériques (ex: `test_type` plutôt que `disc_type`)
- 🔄 Structurer le code en pensant "test" plutôt que "DISC uniquement"

#### Ce qu'on ÉVITE :
- ❌ Hardcoder "DISC" partout (préférer "test", "assessment", etc.)
- ❌ Couples forts entre composants (favoriser injection de dépendances)
- ❌ Logique métier dans les vues (séparer présentation/logique)

### Critères de Déclenchement pour v2.0

**NE PAS commencer la modularisation AVANT d'avoir** :
- [ ] 100+ tests complétés en production
- [ ] Feedback terrain sur le besoin d'autres versions
- [ ] Demande concrète pour d'autres langues
- [ ] Validation du ROI du MVP actuel

**COMMENCER la modularisation SI** :
- ✅ Demandes fréquentes pour version courte/longue
- ✅ Opportunité de licensing identifiée
- ✅ Expansion internationale validée
- ✅ Budget dev disponible (3-4 semaines)

### Exemples de Kits Futurs

**DISC Express** (Lead Magnet agressif)
- 10 questions à choix forcé
- 3-5 minutes
- Profils simplifiés (4 types purs : D, I, S, C)
- Gratuit, CTA fort vers DISC Standard

**DISC Standard** (MVP actuel)
- 28 questions à choix forcé
- 8-10 minutes
- 12 profils détaillés
- Gratuit, CTA vers D4D/Coaching

**DISC Pro** (Premium)
- 50 questions + contexte métier
- 15-20 minutes
- 24 profils ultra-détaillés
- Payant (47-97€)
- Rapport PDF téléchargeable

**DISC RH** (Verticale)
- 28 questions orientées recrutement
- Scoring adapté aux soft skills
- Profils avec recommandations RH

**Leadership 360** (Nouveau test)
- Évaluation multi-sources
- Autre algorithme de calcul
- Réutilise le même moteur

### Notes pour les Développeurs

**Si vous travaillez sur le MVP actuel (v1.0)** :
- Concentrez-vous sur faire fonctionner le test DISC Standard
- Ne sur-architecturez pas pour l'instant
- Mais gardez en tête la vision modulaire dans vos choix de nommage

**Si vous travaillez sur v2.0 (future)** :
- Lisez d'abord cette section complète
- Étudiez le code v1.0 pour comprendre ce qui doit être abstrait
- Suivez le plan de migration Phase par Phase
- Testez la rétrocompatibilité à chaque étape

### Ressources et Références

**Patterns architecturaux pertinents** :
- Strategy Pattern (pour algorithmes interchangeables)
- Factory Pattern (pour création de kits de tests)
- Plugin Architecture (pour extensibilité)

**Inspirations** :
- WooCommerce (produits configurables)
- Gravity Forms (formulaires modulaires)
- Advanced Custom Fields (champs personnalisables)

### Estimation Effort Total

| Phase | Difficulté | Temps Dev | Temps Test | Impact MVP |
|-------|-----------|-----------|------------|------------|
| v1.0 MVP | Faible | ✅ Fait | 1 jour | Production |
| v1.1 Préparation | Faible | 1-2 jours | 2h | Aucun |
| v1.5 Abstraction | Moyenne | 1 semaine | 2 jours | Aucun |
| v2.0 Multi-kits | Élevée | 2 semaines | 3 jours | Nouveaux tests |

**Total v1.0 → v2.0** : 3-4 semaines de développement

### Validation Business Avant v2.0

**Métriques à atteindre avec v1.0** :
- [ ] 100+ leads capturés
- [ ] Taux de completion > 70%
- [ ] Cohérence moyenne > 65%
- [ ] Taux email ouvert > 40%
- [ ] 10+ demandes explicites pour version courte/longue

**ROI attendu v2.0** :
- Acquisition : +50% de leads (version express)
- Conversion : +20% vers offres premium (parcours progressif)
- Expansion : Nouveaux marchés (EN/ES)
- Revenus : Licensing à d'autres coachs

---

## Conclusion pour Claude Code

**En développant le MVP actuel** :
- ✅ Priorisez la simplicité et la rapidité
- ✅ Mais pensez "test générique" dans vos noms de variables
- ✅ Commentez les zones qui seront modularisées
- ✅ Documentez les algorithmes clairement

**Cette vision est un guide, pas une contrainte immédiate.**

Le succès de v1.0 validera (ou invalidera) le besoin de v2.0.

## Support & Maintenance

**Logs** : `wp_disc_audit_logs` table
**Debug** : `WP_DEBUG` WordPress standard
**Errors** : Gérés via try-catch + logs

**Contact** : Issues GitHub du repo
