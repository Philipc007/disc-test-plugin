# TASKS - Plugin DISC Test WordPress

## 🚀 Phase 1 : MVP Fonctionnel (PRIORITÉ IMMÉDIATE)

### Fichiers Manquants à Créer

- [ ] **assets/js/admin.js** - JavaScript administration
  - Graphiques statistiques avec Chart.js
  - Interactions dashboard
  - Peut rester vide pour MVP si pas de JS admin nécessaire

### Fonctionnalités à Finaliser

- [ ] **Export CSV** (30 min)
  - Ajouter méthode `handle_export()` dans `DISC_Admin`
  - Générer CSV avec tous les champs
  - Hook au bouton "Exporter en CSV"
  - Format : Date, Prénom, Nom, Email, Entreprise, Poste, Profil, Scores, Cohérence

- [ ] **Webhook CRM** (20 min)
  - Implémenter dans `DISC_Frontend::handle_contact_submission()`
  - Récupérer URL depuis `get_option('disc_crm_webhook')`
  - POST JSON avec `wp_remote_post()`
  - Gérer erreurs silencieusement (ne pas bloquer le test)

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

**Dernière mise à jour** : {{ DATE }}  
**Status global** : 🟡 En finalisation MVP  
**Prochaine deadline** : Tests MVP dans 3 jours
