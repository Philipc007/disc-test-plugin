# 🧠 Plugin WordPress - Test DISC Lead Magnet

> Plugin professionnel de test DISC psychométrique pour capturer des leads qualifiés B2B (dirigeants et managers PME/ETI)

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Status](https://img.shields.io/badge/Status-MVP%20Testing-orange.svg)]()

## 📋 Table des Matières

- [Aperçu](#aperçu)
- [Fonctionnalités](#fonctionnalités)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Architecture](#architecture)
- [Développement](#développement)
- [Contribuer](#contribuer)
- [Roadmap](#roadmap)

## 🎯 Aperçu

Ce plugin WordPress permet d'administrer un test DISC complet (28 questions) avec :
- Capture d'emails APRÈS engagement (taux de conversion optimisé)
- Calcul automatique des profils DISC
- Génération de graphiques interactifs
- Email automatique avec résultats personnalisés
- Dashboard administrateur avec analytics
- Détection anti-triche intégrée

**Cas d'usage** : Lead magnet pour coachs, consultants, formateurs ciblant des dirigeants et managers d'entreprises.

## ✨ Fonctionnalités

### 🎨 Frontend
- ✅ **Test DISC complet** : 28 questions professionnelles validées
- ✅ **UX optimisée** : Navigation fluide, barre de progression, animations
- ✅ **Responsive design** : Mobile-first, testé sur tous devices
- ✅ **Graphiques interactifs** : Chart.js avec barres horizontales colorées
- ✅ **12 profils DISC** : Descriptions détaillées avec forces et conseils
- ✅ **Partage social** : Bouton LinkedIn intégré
- ✅ **RGPD compliant** : Consentement explicite, politique confidentialité

### 🔒 Sécurité
- ✅ **Protection CSRF** : Nonces WordPress sur toutes les actions
- ✅ **SQL Injection** : Prepared statements systématiques
- ✅ **XSS Protection** : Validation entrées + échappement sorties
- ✅ **Rate limiting** : 3 tests max/heure/IP (transients)
- ✅ **Encryption** : Emails chiffrés en BDD (AES-256-CBC)
- ✅ **Audit logs** : Traçabilité complète des événements

### 🎯 Anti-Triche
- ✅ **Questions à choix forcé** : "Le plus" ET "le moins" obligatoires
- ✅ **Score de cohérence** : Détection incohérences via paires de questions
- ✅ **Validation temps** : Alerte si trop rapide ou trop lent
- ✅ **Avertissement utilisateur** : Message si score < 60%

### 📊 Administration
- ✅ **Dashboard statistiques** : Tests totaux, 30 jours, distribution profils
- ✅ **Liste résultats** : Tous les participants avec filtres et recherche
- ✅ **Gestion questions** : Visualisation des 28 questions DISC
- ✅ **Export CSV** : Téléchargement données (à finaliser)
- ✅ **Paramètres** : Configuration email, webhook CRM, clé encryption

### 🔌 Intégrations
- ✅ **Hook WordPress** : `do_action('disc_test_completed', ...)` pour CRM
- ⏳ **Webhook** : POST JSON automatique vers Mautic/autre (à finaliser)
- ⏳ **Mautic** : Tags automatiques par profil (roadmap)
- ⏳ **PDF** : Génération rapport téléchargeable (roadmap)

### 🧩 Compatibilité
- ✅ **Shortcode** : `[disc_test]` fonctionne partout
- ✅ **Gutenberg** : Bloc natif avec options configurables
- ✅ **Page builders** : Compatible Elementor, Divi, WPBakery

## 🚀 Installation

### Prérequis
- WordPress 5.8 ou supérieur
- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur

### Méthode 1 : Upload ZIP

1. Téléchargez ou clonez ce repo
2. Zippez le dossier `disc-test/`
3. Dans WordPress admin : **Extensions > Ajouter > Téléverser**
4. Activez le plugin
5. Suivez les étapes de [Configuration](#configuration)

### Méthode 2 : FTP

1. Clonez ce repo
2. Uploadez le dossier `disc-test/` dans `/wp-content/plugins/`
3. Activez via **Extensions** dans l'admin WordPress

### Méthode 3 : Git (développeurs)

```bash
cd /chemin/vers/wordpress/wp-content/plugins/
git clone https://github.com/votre-username/disc-test-plugin.git disc-test
cd disc-test
# Le plugin est prêt, activez-le dans WordPress
```

## ⚙️ Configuration

### 1. Clé de chiffrement (OBLIGATOIRE)

Ajoutez dans votre fichier `wp-config.php` :

```php
define('DISC_ENCRYPTION_KEY', 'votre_cle_aleatoire_32_caracteres_minimum');
```

💡 Une clé aléatoire est générée automatiquement dans **Test DISC > Paramètres**

### 2. SMTP (Recommandé)

Pour une meilleure délivrabilité des emails :

1. Installez **WP Mail SMTP** ou similaire
2. Configurez avec vos identifiants SMTP
3. Testez l'envoi d'un email

### 3. Politique de confidentialité

1. Allez dans **Réglages > Confidentialité**
2. Créez ou sélectionnez votre page de politique
3. Mentionnez la collecte de données du test DISC

### 4. Page de test

Créez une nouvelle page et ajoutez :

**Avec l'éditeur classique :**
```
[disc_test]
```

**Avec Gutenberg :**
1. Cliquez sur **+**
2. Cherchez "Test DISC"
3. Ajoutez le bloc

## 📖 Utilisation

### Pour les administrateurs

#### Voir les résultats
**Test DISC > Résultats** : Liste de tous les participants avec profils et scores

#### Consulter les statistiques
**Test DISC > Statistiques** : Dashboard avec métriques clés et graphiques

#### Gérer les questions
**Test DISC > Questions** : Visualisation des 28 questions DISC

#### Configurer
**Test DISC > Paramètres** : Email, webhook CRM, clé encryption

### Pour les utilisateurs finaux

1. Visitent la landing page du test
2. Cliquent sur "Commencer le test"
3. Répondent aux 28 questions (8-10 minutes)
4. Soumettent leurs coordonnées
5. Reçoivent immédiatement leurs résultats à l'écran
6. Reçoivent un email avec le détail de leur profil

## 🏗️ Architecture

### Structure des fichiers

```
disc-test/
├── disc-test.php                    # Fichier principal du plugin
├── includes/                        # Classes PHP
│   ├── class-disc-database.php     # Gestion BDD
│   ├── class-disc-security.php     # Sécurité
│   ├── class-disc-renderer.php     # Génération HTML
│   ├── class-disc-frontend.php     # Interactions utilisateur
│   ├── class-disc-email.php        # Envoi emails
│   └── class-disc-admin.php        # Interface admin
├── assets/
│   ├── css/
│   │   ├── frontend.css            # Styles utilisateur
│   │   ├── admin.css               # Styles admin
│   │   └── block-editor.css        # Styles Gutenberg
│   └── js/
│       ├── frontend.js             # Logique test
│       └── admin.js                # JS admin
└── build/
    └── block.js                     # Bloc Gutenberg compilé (optionnel)
```

### Base de données

4 tables créées lors de l'activation :

- `wp_disc_questions` : 28 questions DISC
- `wp_disc_results` : Résultats des tests
- `wp_disc_responses` : Réponses détaillées
- `wp_disc_audit_logs` : Logs sécurité et audit

### Technologies

- **Backend** : PHP 7.4+, WordPress API
- **Frontend** : JavaScript ES6, jQuery, Chart.js 3.9
- **Styles** : CSS3, Flexbox, Grid
- **Sécurité** : Nonces, Prepared Statements, AES-256-CBC

## 👨‍💻 Développement

### Setup environnement local

```bash
# Cloner le repo
git clone https://github.com/votre-username/disc-test-plugin.git

# Créer une branche
git checkout -b feature/ma-nouvelle-fonctionnalite

# Développer...

# Commit et push
git add .
git commit -m "feat: description de la fonctionnalité"
git push origin feature/ma-nouvelle-fonctionnalite
```

### Standards de code

- Suivre les [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- Utiliser `$wpdb->prepare()` pour TOUTES les requêtes SQL
- Valider les entrées, échapper les sorties
- Documenter avec PHPDoc
- Tester sur PHP 7.4 et 8.0+

### Tester localement

1. Installez WordPress en local (XAMPP, Local, Docker)
2. Installez le plugin
3. Activez WP_DEBUG dans wp-config.php
4. Testez le parcours complet
5. Vérifiez la console JavaScript (erreurs)
6. Testez sur mobile (responsive)

### Compiler le bloc Gutenberg (optionnel)

```bash
npm install @wordpress/scripts --save-dev
npm run build
```

## 🤝 Contribuer

Les contributions sont les bienvenues ! Voici comment :

1. **Fork** le projet
2. **Créez une branche** (`git checkout -b feature/AmazingFeature`)
3. **Commit** vos changements (`git commit -m 'Add some AmazingFeature'`)
4. **Push** vers la branche (`git push origin feature/AmazingFeature`)
5. **Ouvrez une Pull Request**

### Priorités actuelles

Consultez [TASKS.md](TASKS.md) pour voir les tâches prioritaires.

**Quick wins recherchés** :
- Export CSV fonctionnel
- Webhook CRM automatique
- Tests unitaires PHPUnit
- Traductions (EN, ES)

## 🗺️ Roadmap

### Version 1.1 (Q2 2026)
- [ ] Génération PDF résultats
- [ ] Export CSV automatique
- [ ] Webhook Mautic intégré
- [ ] Bloc Gutenberg compilé

### Version 1.2 (Q3 2026)
- [ ] Traductions EN/ES
- [ ] Comparaison d'équipe
- [ ] Badge LinkedIn personnalisé
- [ ] Analytics avancés (GA4)

### Version 2.0 (Q4 2026)
- [ ] API REST
- [ ] Version SaaS multi-sites
- [ ] Intégrations natives (HubSpot, Salesforce)
- [ ] Rapports avancés avec BI

## 📄 Licence

Ce projet est sous licence GPL v2 ou ultérieure. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 📞 Support

- **Documentation** : [SPECS.md](SPECS.md) pour détails techniques
- **Tâches** : [TASKS.md](TASKS.md) pour roadmap et bugs
- **Issues** : [GitHub Issues](https://github.com/votre-username/disc-test-plugin/issues)
- **Email** : votre-email@example.com

## 🙏 Crédits

- Méthodologie DISC basée sur les travaux de William Moulton Marston
- Charts via [Chart.js](https://www.chartjs.org/)
- Développé pour LIBERMOUV par Philippe (formateur DISC certifié)

---

**Fait avec ❤️ pour les coachs et dirigeants d'entreprises**

⭐ **N'oubliez pas de starrer le repo si ce plugin vous aide !**
