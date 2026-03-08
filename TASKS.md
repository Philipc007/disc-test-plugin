# TASKS - Plugin DISC Test WordPress

## 🚀 Phase 1 : MVP Fonctionnel (PRIORITÉ IMMÉDIATE)

### Fichiers Manquants à Créer

- [x] **assets/js/admin.js** - Optionnel MVP, laissé vide intentionnellement

### Fonctionnalités à Finaliser

- [x] **Export CSV** ✅
  - `handle_export()` dans `DISC_Admin` via hook `admin_init`
  - Format : Date+heure, Prénom, Nom, Email, Entreprise, Poste, Profil, D/I/S/C, Cohérence, Temps
  - BOM UTF-8, séparateur `;`, sécurisé par nonce

- [x] **Webhook CRM** ✅
  - POST JSON non-bloquant (`blocking=false`) dans `DISC_Frontend`
  - URL depuis `get_option('disc_crm_webhook')`
  - Payload : contact + profil + scores + cohérence + timestamp ISO

- [x] **Tags CRM dans le webhook** ✅
  - Tags en anglais pour compatibilité internationale
  - Tag fixe : `disc` (tous les leads)
  - Tag profil : `disc-di`, `disc-d`, etc.
  - Tag dimensions dominantes (score >= 60) : `disc-d`, `disc-i`, `disc-s`, `disc-c`
  - Tag qualité : `disc-consistent` (≥70%) / `disc-suspect` (<50%)
  - Préfixe configurable depuis **Paramètres** du plugin

### Corrections Audit Sécurité (2026-03-08)

- [x] **Fix 1 — Consentement RGPD** ✅ : `isset()` remplacé par vérification de la valeur réelle (`absint() === 1`)
- [x] **Fix 2 — Log pollution** ✅ : allowlist d'événements AJAX + traitement conditionnel `test_started` vs `question_answered`
- [x] **Fix 3 — SSRF** ✅ : `wp_remote_post()` → `wp_safe_remote_post()` pour le webhook CRM
- [x] **Fix 4 — Chiffrement email** ✅ : `encrypt_email()` réellement appelée dans `save_result()` (était manquante)
- [x] **Fix 5 — DB schema** ✅ : `consent_given DEFAULT 0` (était DEFAULT 1)
- [x] **Fix 6 — Déchiffrement admin** ✅ : `decrypt_email()` dans affichage, export CSV, renvoi email
- [x] **Fix 7 — XSS confirm()** ✅ : `esc_js()` complet + `decrypt_email()` dans bouton "Renvoyer email"
- [x] **Fix 8 — Email subject option** ✅ : `disc_email_subject` depuis `get_option()` avec placeholder `{profil}`
- [x] **Fix 9 — XSS showError()** ✅ : `.html()` jQuery → `.empty().append($('<p>', {text: message}))`
- [x] **Fix 10 — Gutenberg block.js** ✅ : vérification `file_exists()` avant enregistrement `editor_script`

### Tests Critiques

- [ ] **Test End-to-End complet**
  - Installation plugin
  - Activation sans erreur
  - Affichage shortcode [disc_test]
  - Parcourir 28 questions
  - Soumettre formulaire contact
  - Vérifier email reçu
  - Vérifier données en BDD
  - Vérifier admin résultats
  - Vérifier admin statistiques

- [ ] **Test Sécurité**
  - Tenter SQL injection dans formulaire
  - Vérifier rate limiting (3 tests)
  - Tester sans nonce valide
  - Vérifier encryption email en BDD

- [ ] **Test Responsive**
  - iPhone (375px)
  - iPad (768px)
  - Desktop (1920px)
  - Tester navigation questions mobile
  - Tester graphique sur mobile

### Configuration Pré-Lancement

- [ ] **wp-config.php**
  ```php
  define('DISC_ENCRYPTION_KEY', 'générer_clé_32_chars');
  ```

- [ ] **SMTP Configuration**
  - Installer WP Mail SMTP
  - Configurer serveur SMTP
  - Tester envoi email

- [ ] **Page de Confidentialité**
  - Créer/vérifier page confidentialité WordPress
  - Mentionner collecte données DISC
  - Expliquer utilisation emails

- [ ] **Landing Page**
  - Créer page dédiée test DISC
  - Optimiser titre/description SEO
  - Ajouter preuve sociale si dispo
  - Shortcode [disc_test]

## 📊 Phase 2 : Intégrations CRM (APRÈS TESTS MVP)

### Mautic Integration

- [ ] **Webhook automatique**
  - Endpoint Mautic dans paramètres
  - Envoi auto après test
  - Mapping champs : email, first_name, last_name, company, position

- [ ] **Tags par profil**
  - Tag "DISC-D" si Dominance élevé
  - Tag "DISC-I" si Influence élevé
  - Tag "DISC-S" si Stabilité élevé
  - Tag "DISC-C" si Conformité élevé
  - Tag "DISC-{profil}" ex: "DISC-DI"

- [ ] **Segments Mautic**
  - Segment par profil DISC
  - Segment cohérence < 60% (à recontacter)
  - Segment temps rapide (leads chauds)

### EspoCRM Integration

- [ ] **Créer champ personnalisé** "Profil DISC" dans EspoCRM
- [ ] **Créer champ personnalisé** "Scores DISC" (D,I,S,C)
- [ ] **Webhook EspoCRM**
- [ ] **Mapping automatique**

### Kanbox LinkedIn

- [ ] **Taguer contacts** ayant fait le test
- [ ] **Créer segment** "A fait DISC"
- [ ] **Exclure** de prospection froide

## 🎨 Phase 3 : Optimisations UX (APRÈS INTÉGRATIONS)

### Génération PDF

- [ ] **Bibliothèque TCPDF**
  - Installer via Composer ou inclure
  - Template PDF élégant
  - Logo LIBERMOUV
  - Graphique des scores
  - Description profil complète
  - Conseils développement

- [ ] **Lien téléchargement**
  - Token sécurisé dans email
  - Expiration après 7 jours
  - Compteur téléchargements

### Améliorations Graphique

- [ ] **Animation entrée** barres Chart.js
- [ ] **Tooltip personnalisé** avec contexte
- [ ] **Légende interactive**
- [ ] **Export image** du graphique

### Partage Social

- [ ] **Améliorer partage LinkedIn**
  - Texte pre-filled optimisé
  - Image OG personnalisée par profil
  - Tracking des partages

- [ ] **Badge personnalisé**
  - Image "Mon profil : DI" downloadable
  - Watermark LIBERMOUV
  - Formats LinkedIn/Twitter

### Email Marketing

- [ ] **Séquence post-test**
  - J+0 : Email résultats
  - J+2 : Conseils selon profil
  - J+5 : Invitation webinar D4D
  - J+7 : Témoignages dirigeants
  - J+10 : Offre coaching

- [ ] **Personnalisation par profil**
  - Template email adapté D/I/S/C
  - Études de cas profil-spécifiques
  - Call-to-action personnalisés

## 📈 Phase 4 : Analytics & Optimisation (CONTINU)

### Tracking

- [ ] **Google Analytics Events**
  - Event "Test Started"
  - Event "Test Completed"
  - Event "Question {n}" (abandon rate)
  - Event "Email Submitted"
  - Event "Result Viewed"

- [ ] **Conversion Tracking**
  - Taux de completion test
  - Taux soumission formulaire
  - Taux ouverture email
  - Taux clic vers webinar

- [ ] **Heatmaps** (Hotjar ou similaire)
  - Où les gens abandonnent
  - Temps par question
  - Clics sur CTA

### A/B Testing

- [ ] **Variantes landing page**
  - Titre : "Découvrez votre profil" vs "Quel type de leader êtes-vous ?"
  - Bouton : "Commencer" vs "Démarrer le test" vs "Découvrir mon profil"
  - Preuve sociale : avec/sans nombre de participants

- [ ] **Variantes formulaire**
  - Champs : Min (email/nom) vs Complet (+ entreprise/poste)
  - Moment : Avant questions vs Après questions
  - Design : Simple vs Élaboré

- [ ] **Variantes email**
  - Sujet email
  - Longueur description
  - CTA vers webinar

### Optimisations Performance

- [ ] **Cache questions** (WordPress Object Cache)
- [ ] **Lazy load** Chart.js
- [ ] **Minify CSS/JS** en production
- [ ] **CDN pour assets** statiques
- [ ] **Database query optimization**


# Section à AJOUTER dans TASKS.md

**Où l'ajouter** : Après la section "Phase 4 : Analytics & Optimisation", créer une nouvelle section

---

## 🔮 Phase 5 : Architecture Modulaire (FUTURE - Après validation MVP)

> ⚠️ **NE PAS COMMENCER** avant d'avoir validé le MVP avec 100+ tests en production

### Pré-requis pour Démarrer la Phase 5

- [ ] **Validation Métrique** : 100+ tests complétés
- [ ] **Validation Business** : Taux completion > 70%, cohérence > 65%
- [ ] **Validation Marché** : Demandes concrètes pour autres versions/langues
- [ ] **Validation ROI** : Le MVP génère des leads qualifiés
- [ ] **Budget Dev** : 3-4 semaines de développement disponibles

### v1.1 - Préparation Légère (1-2 jours) ✨

> Cette phase peut être faite MAINTENANT sans impacter le MVP

#### Base de Données

- [ ] **Ajouter champ version tracking**
  ```sql
  ALTER TABLE wp_disc_results 
  ADD COLUMN test_version VARCHAR(20) DEFAULT '1.0' AFTER id,
  ADD COLUMN test_kit_id VARCHAR(50) DEFAULT 'disc-standard' AFTER test_version;
  ```

- [ ] **Ajouter indexes pour performance future**
  ```sql
  ALTER TABLE wp_disc_results ADD KEY test_kit_id (test_kit_id);
  ALTER TABLE wp_disc_questions ADD KEY test_kit_id (test_kit_id);
  ```

#### Structure Fichiers

- [ ] **Créer dossier data/**
  ```
  disc-test/
  └── data/
      └── test-kits/
          └── disc-standard/
              ├── config.json
              ├── questions-fr.json
              ├── algorithm.php
              └── profiles-fr.json
  ```

- [ ] **Exporter questions actuelles en JSON**
  - Script PHP pour exporter depuis BDD → JSON
  - Format : `{"id": 1, "d": "...", "i": "...", "s": "...", "c": "..."}`

- [ ] **Exporter profils actuels en JSON**
  - Extraire de `DISC_Renderer::get_profile_description()`
  - Format : `{"DI": {"title": "...", "description": "...", ...}}`

#### Documentation

- [ ] **Documenter l'algorithme actuel**
  - Formule de scoring (+2/-1)
  - Normalisation 0-100
  - Détermination profil
  - Calcul cohérence

- [ ] **Commenter les zones à modulariser**
  - Marquer dans le code : `// TODO v2.0: Modularize`
  - Classes concernées : Renderer, Frontend, Database

### v1.5 - Abstraction du Moteur (1 semaine)

> Refactoring sans changement fonctionnel pour l'utilisateur

#### Nouvelle Architecture Core

- [ ] **Créer class-test-engine.php**
  - Moteur générique de test
  - Render questions
  - Calculate scores
  - Display results

- [ ] **Créer interface Test_Algorithm**
  ```php
  interface Test_Algorithm {
      public function calculate_scores($responses);
      public function determine_profile($scores);
      public function get_consistency_score($responses);
  }
  ```

- [ ] **Implémenter DISC_Classic_Algorithm**
  - Migrer logique depuis DISC_Frontend
  - Implémenter l'interface
  - Tests unitaires

- [ ] **Créer class-test-kit-loader.php**
  - Charge config depuis JSON
  - Valide structure
  - Cache les données

#### Refactoring Classes Existantes

- [ ] **DISC_Renderer → Test_Renderer**
  - Généraliser le rendu HTML
  - Accepter un test_kit en paramètre
  - Backward compatible avec shortcode actuel

- [ ] **DISC_Frontend → Test_Frontend**
  - Déléguer calcul à Test_Algorithm
  - Support multi-kits
  - Backward compatible

- [ ] **DISC_Database → Test_Database**
  - Méthodes génériques par test_kit
  - get_questions($test_kit_id)
  - save_result($test_kit_id, $data)

#### Tests de Régression

- [ ] **Tester rétrocompatibilité totale**
  - Ancien shortcode `[disc_test]` fonctionne
  - Résultats identiques
  - Emails identiques
  - Admin identique

- [ ] **Tests unitaires moteur**
  - PHPUnit pour Test_Engine
  - Tests algorithme DISC Classic
  - Tests loader JSON

### v2.0 - Multi-Kits Complet (2 semaines)

> Activation de plusieurs tests simultanés

#### Base de Données

- [ ] **Créer table wp_disc_test_kits**
  ```sql
  CREATE TABLE wp_disc_test_kits (
      id VARCHAR(50) PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      description TEXT,
      duration INT,
      question_count INT,
      algorithm VARCHAR(50),
      config LONGTEXT,
      pricing ENUM('free', 'premium'),
      active TINYINT(1),
      created_at DATETIME
  );
  ```

- [ ] **Migrer données DISC Standard**
  - Insérer kit "disc-standard"
  - Associer questions existantes
  - Associer résultats existants

#### Nouveaux Kits de Tests

- [ ] **DISC Express (5 min, 10 questions)**
  - Créer questions-fr.json (10 questions)
  - Algorithme simplifié
  - 4 profils de base (D, I, S, C)
  - Config : free, 5 min

- [ ] **DISC Pro (15 min, 50 questions)**
  - Créer questions-fr.json (50 questions)
  - Algorithme pondéré
  - 24 profils détaillés
  - Config : premium, 15 min, 47€

#### Interface Admin

- [ ] **Menu "Kits de Tests"**
  - Liste des kits installés
  - Activer/Désactiver
  - Statistiques par kit

- [ ] **Page "Ajouter un Kit"**
  - Upload JSON ou ZIP
  - Validation structure
  - Preview avant activation

- [ ] **Statistiques par Kit**
  - Filtrer résultats par test_kit_id
  - Comparer performance entre kits

#### Shortcode Étendu

- [ ] **Support attribut "kit"**
  ```
  [disc_test kit="disc-express"]
  [disc_test kit="disc-standard"]
  [disc_test kit="disc-pro"]
  ```

- [ ] **Backward compatibility**
  - `[disc_test]` = `[disc_test kit="disc-standard"]`

#### Pricing & Gating

- [ ] **Intégration Stripe (pour kits premium)**
  - Paiement avant accès résultats
  - Webhook Stripe
  - Email avec accès payant

- [ ] **Freemium Logic**
  - DISC Express : gratuit, CTA fort
  - DISC Standard : gratuit, CTA moyen
  - DISC Pro : payant, rapport complet

### v2.1 - Internationalisation (1 semaine)

#### Support Multi-Langue

- [ ] **Structure i18n**
  ```
  data/test-kits/disc-standard/
  ├── questions-fr.json
  ├── questions-en.json
  ├── questions-es.json
  ├── profiles-fr.json
  ├── profiles-en.json
  └── profiles-es.json
  ```

- [ ] **Détection langue utilisateur**
  - WordPress locale
  - Accept-Language header
  - Sélecteur manuel

- [ ] **Traductions DISC Standard**
  - [ ] Anglais (EN)
  - [ ] Espagnol (ES)

- [ ] **Emails multi-langues**
  - Templates par langue
  - Sélection automatique

### v3.0 - Plateforme & Licensing (Future)

> Transformation en plateforme SaaS pour coachs

#### Multi-Tenant

- [ ] **Architecture multi-sites WordPress**
- [ ] **Isolation données par coach**
- [ ] **Branding personnalisé**

#### Marketplace

- [ ] **Store de kits de tests**
- [ ] **Vente de kits custom**
- [ ] **Système de rating/reviews**

#### Business Model

- [ ] **Pricing tiers**
  - Gratuit : 1 kit, 100 tests/mois
  - Pro : 97€/mois, 3 kits, illimité
  - Agency : 297€/mois, kits illimités, white-label

## 📊 Métriques de Succès par Phase

### v1.0 (MVP Actuel)
- [ ] 100+ tests complétés
- [ ] Taux completion > 70%
- [ ] Cohérence moyenne > 65%
- [ ] 5+ leads qualifiés vers coaching

### v1.5 (Abstraction)
- [ ] Zéro régression fonctionnelle
- [ ] Code coverage tests > 70%
- [ ] Performance identique ou meilleure

### v2.0 (Multi-Kits)
- [ ] 3 kits actifs (Express, Standard, Pro)
- [ ] 500+ tests tous kits confondus
- [ ] 10%+ conversion Express → Standard
- [ ] 3%+ conversion Standard → Pro

### v2.1 (i18n)
- [ ] Tests EN et ES fonctionnels
- [ ] 20%+ de tests en langues étrangères
- [ ] Expansion géographique validée

### v3.0 (Plateforme)
- [ ] 10+ coachs clients
- [ ] MRR > 1000€
- [ ] 5+ kits custom créés
- [ ] Marketplace actif

## 🚦 Critères de GO/NO-GO par Phase

### Lancer v1.1 (Préparation) ?
- ✅ **GO si** : Pas d'impact sur MVP, juste préparation
- ❌ **NO-GO si** : Ça retarde le lancement MVP

### Lancer v1.5 (Abstraction) ?
- ✅ **GO si** : 
  - MVP validé avec 100+ tests
  - Budget dev 1 semaine disponible
  - Besoin de maintenabilité identifié
- ❌ **NO-GO si** :
  - MVP pas encore validé
  - Pas de besoin concret d'extensibilité

### Lancer v2.0 (Multi-Kits) ?
- ✅ **GO si** : 
  - Demandes fréquentes pour autres versions
  - Opportunité business claire (freemium, premium)
  - Budget dev 2 semaines + budget design
- ❌ **NO-GO si** :
  - Pas de demande marché validée
  - MVP pas rentable
  - Pas de ressources pour maintenir plusieurs kits

### Lancer v2.1 (i18n) ?
- ✅ **GO si** :
  - Opportunité internationale identifiée
  - Contacts/partenaires dans pays cibles
  - Budget traduction + QA
- ❌ **NO-GO si** :
  - Marché français pas saturé
  - Pas de demande internationale

### Lancer v3.0 (Plateforme) ?
- ✅ **GO si** :
  - Demandes concrètes de licensing
  - 5+ coachs prêts à payer
  - Product-market fit validé
  - Équipe ou budget conséquent
- ❌ **NO-GO si** :
  - Pas de demande licensing
  - Focus sur coaching propre plus rentable

## 💡 Décision Architecture pour Claude Code

**En développant MAINTENANT (v1.0)** :
- ✅ Focus sur faire fonctionner le MVP
- ✅ Code simple, pas de sur-ingénierie
- ✅ Mais penser "test" plutôt que "DISC uniquement" dans les noms

**En préparant DISCRÈTEMENT (v1.1)** :
- ✅ Ajouter champs BDD pour versioning
- ✅ Exporter données en JSON à côté du code
- ✅ Commenter zones à modulariser
- ✅ Zéro impact sur fonctionnement actuel

**En refactorant PLUS TARD (v1.5+)** :
- ⏳ Attendre validation MVP avec vrais utilisateurs
- ⏳ Mesurer le besoin réel avant d'investir
- ⏳ Suivre le plan de migration phase par phase



## 🔧 Maintenance Continue

### Monitoring

- [ ] **Alertes erreurs**
  - Email si crash plugin
  - Email si pas de test depuis 24h
  - Email si taux échec email > 10%

- [ ] **Logs review**
  - Audit logs hebdomadaire
  - Détecter patterns suspects
  - Identifier bugs UX

### Mises à jour

- [ ] **WordPress compatibility** tests
- [ ] **PHP 8.0+ compatibility**
- [ ] **Sécurité patches** si vulnérabilités
- [ ] **Chart.js updates**

### Documentation

- [ ] **Guide utilisateur** PDF
- [ ] **Vidéo démo** du test
- [ ] **FAQ** questions fréquentes
- [ ] **Guide admin** WordPress

## 🎯 Métriques de Succès

### KPIs à Tracker

**Acquisition**
- Nombre de tests démarrés / semaine
- Source de trafic (LinkedIn, Direct, Référents)

**Engagement**
- Taux de completion (cible : > 70%)
- Temps moyen (cible : 8-12 min)
- Taux abandon par question

**Conversion**
- Taux soumission formulaire (cible : > 85%)
- Qualité leads (% dirigeants PME/ETI)
- Taux ouverture email (cible : > 40%)

**Qualité**
- Score cohérence moyen (cible : > 70%)
- Distribution profils (équilibrée ?)
- Temps réponse moyen (cible : 15-30s/question)

**Business**
- Leads → Webinar (cible : > 15%)
- Leads → Clients coaching (cible : > 3%)
- ROI campagne LinkedIn

## 📝 Notes & Décisions

### Questions en Suspens

- [ ] **PDF ou pas en v1 ?** → Décision : Email HTML suffit pour MVP
- [ ] **Bloc Gutenberg compilé ?** → Décision : Optionnel, shortcode prioritaire
- [ ] **Multi-langue ?** → Décision : Phase 2+, français uniquement MVP
- [ ] **Limite tests par email ?** → À décider (actuellement : illimité)

### Décisions Validées

- ✅ Capture APRÈS questions (pas avant)
- ✅ 28 questions (validé Philippe)
- ✅ Email chiffré en BDD
- ✅ Rate limiting 3/heure/IP
- ✅ Design moderne gradient violet
- ✅ Mobile-first
- ✅ Pas de PDF en v1
- ✅ Intégration Mautic prioritaire sur EspoCRM

## 🚨 Bugs Connus

Aucun bug connu actuellement - À compléter après tests

## 💡 Idées Futures (Backlog)

- [ ] Comparaison d'équipe (plusieurs personnes entreprise)
- [ ] Rapport manager/collaborateur (compatibilité)
- [ ] Version courte 12 questions (quick test)
- [ ] Mode "équipe" avec dashboard entreprise
- [ ] API REST publique
- [ ] Integration Zapier
- [ ] Version mobile app (React Native)
- [ ] Gamification (badges, scores)
- [ ] Communauté (forum par profil)
- [ ] Formation en ligne par profil

---

**Dernière mise à jour** : 2026-03-08
**Status global** : 🟢 MVP complet — Audit sécurité appliqué (10/10 fixes)
**Prochaine étape** : Tests E2E + déploiement production
