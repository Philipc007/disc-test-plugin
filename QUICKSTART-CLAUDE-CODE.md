# 🚀 Quickstart pour Claude Code

> Guide rapide pour que Claude Code reprenne le développement du plugin DISC Test

## 📍 Où en sommes-nous ?

**Status actuel** : Plugin à 95% fonctionnel, en phase de finalisation MVP

**Ce qui fonctionne** :
- ✅ Architecture complète (6 classes PHP + frontend)
- ✅ 28 questions DISC validées
- ✅ Système de sécurité complet
- ✅ Interface frontend responsive
- ✅ Calcul scores et profils
- ✅ Email automatique
- ✅ Dashboard admin

**Ce qui manque** :
- ⏳ Export CSV (code fourni, à intégrer)
- ⏳ Webhook CRM automatique (code fourni, à intégrer)
- ⏳ Admin.js (optionnel, peut rester vide)
- ⏳ Tests end-to-end

## 🎯 Première Mission : Tests & Débogage

### 1. Créer la structure complète

Les fichiers sources sont dans les artifacts de la conversation. Tu dois créer :

```
disc-test/
├── disc-test.php                    ← Artifact "disc-test.php (Fichier Principal)"
├── includes/
│   ├── class-disc-database.php     ← Artifact "includes/class-disc-database.php"
│   ├── class-disc-security.php     ← Extraire de l'artifact principal (cherche section)
│   ├── class-disc-renderer.php     ← Extraire de l'artifact principal
│   ├── class-disc-frontend.php     ← Extraire de l'artifact principal
│   ├── class-disc-email.php        ← Extraire de l'artifact principal
│   └── class-disc-admin.php        ← Extraire de l'artifact principal
├── assets/
│   ├── css/
│   │   ├── frontend.css            ← Artifact "Frontend CSS"
│   │   ├── admin.css               ← Artifact "Admin CSS"
│   │   └── block-editor.css        ← Artifact "Block Editor CSS"
│   └── js/
│       ├── frontend.js             ← Artifact "Frontend JavaScript"
│       └── admin.js                ← Créer vide ou avec console.log('Admin JS loaded')
└── build/
    └── .gitkeep                     ← Dossier vide pour l'instant
```

### 2. Séparer les classes PHP

Le **premier artifact** (Plugin WordPress - Test DISC Lead Magnet) contient TOUTES les classes.

Cherche les sections marquées :
```php
/*
 * =============================================================================
 * FICHIER: includes/class-disc-xxx.php
 * =============================================================================
 */
```

Copie chaque section dans son fichier respectif.

### 3. Tester l'installation

```bash
# Zipper le plugin
zip -r disc-test.zip disc-test/ -x "*.git*" -x "node_modules/*"

# Ou si tu travailles directement dans wp-content/plugins/
# Le plugin est prêt à activer
```

Dans WordPress :
1. Extensions > Ajouter > Téléverser le ZIP
2. Activer
3. Vérifier les tables créées dans phpMyAdmin
4. Vérifier les 28 questions insérées

## 🔧 Deuxième Mission : Finaliser Export CSV

### Fichier à modifier : `includes/class-disc-admin.php`

Ajoute cette méthode dans la classe `DISC_Admin` :

```php
public function handle_export() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'disc-test'));
    }
    
    check_admin_referer('disc_export_csv');
    
    $results = DISC_Database::get_all_results(999999, 0);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="disc-results-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers CSV
    fputcsv($output, array(
        'Date',
        'Prénom',
        'Nom',
        'Email',
        'Entreprise',
        'Poste',
        'Profil',
        'Score D',
        'Score I',
        'Score S',
        'Score C',
        'Cohérence %',
        'Temps (min)'
    ), ';');
    
    // Données
    foreach ($results as $result) {
        fputcsv($output, array(
            date('d/m/Y H:i', strtotime($result['completed_at'])),
            $result['first_name'],
            $result['last_name'],
            $result['email'],
            $result['company'],
            $result['position'],
            $result['profile_type'],
            $result['score_d'],
            $result['score_i'],
            $result['score_s'],
            $result['score_c'],
            round($result['consistency_score'], 1),
            round($result['total_time'] / 60, 1)
        ), ';');
    }
    
    fclose($output);
    exit;
}
```

Puis modifie la méthode `add_admin_menu()` pour ajouter le hook :

```php
add_action('admin_init', array($this, 'handle_export_request'));
```

Et ajoute cette méthode :

```php
public function handle_export_request() {
    if (isset($_GET['page']) && $_GET['page'] === 'disc-test' && 
        isset($_GET['action']) && $_GET['action'] === 'export') {
        $this->handle_export();
    }
}
```

Enfin, modifie le lien dans `render_results_page()` :

```php
<a href="<?php echo wp_nonce_url(admin_url('admin.php?page=disc-test&action=export'), 'disc_export_csv'); ?>" class="button">
    <?php _e('Exporter en CSV', 'disc-test'); ?>
</a>
```

## 🔌 Troisième Mission : Webhook CRM

### Fichier à modifier : `includes/class-disc-frontend.php`

Dans la méthode `handle_contact_submission()`, juste APRÈS l'envoi de l'email (ligne avec `DISC_Email::send_results_email`), ajoute :

```php
// Webhook CRM si configuré
$webhook_url = get_option('disc_crm_webhook');
if (!empty($webhook_url)) {
    $webhook_data = array(
        'email' => $contact_data['email'],
        'first_name' => $contact_data['first_name'],
        'last_name' => $contact_data['last_name'],
        'company' => $contact_data['company'],
        'position' => $contact_data['position'],
        'profile_type' => $profile_type,
        'scores' => $scores,
        'consistency_score' => $consistency_score,
        'timestamp' => current_time('mysql')
    );
    
    wp_remote_post($webhook_url, array(
        'body' => json_encode($webhook_data),
        'headers' => array('Content-Type' => 'application/json'),
        'timeout' => 10,
        'blocking' => false // Non-bloquant pour ne pas ralentir l'UX
    ));
    
    // Log pour debug
    DISC_Database::log_event('webhook_sent', array('url' => $webhook_url), $session_token);
}
```

## ✅ Checklist de Validation

Une fois les modifications faites :

### Tests Fonctionnels
- [ ] Activer le plugin sans erreur
- [ ] Afficher une page avec `[disc_test]`
- [ ] Compléter les 28 questions
- [ ] Soumettre le formulaire
- [ ] Vérifier réception email
- [ ] Vérifier données dans admin > Résultats
- [ ] Tester export CSV (bouton fonctionne)
- [ ] Ouvrir CSV dans Excel (encodage OK)

### Tests Sécurité
- [ ] Tenter d'accéder admin sans droits
- [ ] Tester rate limiting (4ème test bloqué)
- [ ] Vérifier nonces sur AJAX
- [ ] Vérifier emails chiffrés en BDD

### Tests Responsive
- [ ] iPhone (375px)
- [ ] iPad (768px)
- [ ] Desktop (1920px)

## 📊 Métriques de Succès

Une fois déployé, tracker :

1. **Taux de completion** : % qui finissent les 28 questions
   - Cible : > 70%

2. **Taux conversion formulaire** : % qui soumettent après questions
   - Cible : > 85%

3. **Score cohérence moyen** : Qualité des réponses
   - Cible : > 70%

4. **Temps moyen** : Durée du test
   - Cible : 8-12 minutes

## 🐛 Bugs Potentiels à Surveiller

1. **Chart.js ne s'affiche pas**
   - Vérifier console JavaScript
   - Vérifier que CDN Chart.js est accessible

2. **Email non reçu**
   - Vérifier SMTP configuré
   - Vérifier logs audit (email_sent)
   - Tester avec WP Mail SMTP

3. **Erreur 500 lors activation**
   - Vérifier compatibilité PHP (7.4+ requis)
   - Vérifier logs error.log

4. **Export CSV vide**
   - Vérifier qu'il y a des résultats en BDD
   - Vérifier permissions fichiers

## 💡 Astuces de Debug

### Activer le mode debug WordPress

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Les erreurs seront dans `/wp-content/debug.log`

### Vérifier les tables créées

```sql
SHOW TABLES LIKE 'wp_disc_%';
SELECT COUNT(*) FROM wp_disc_questions; -- Doit être 28
```

### Tester l'email manuellement

```php
// Ajouter temporairement dans functions.php
add_action('init', function() {
    if (isset($_GET['test_email'])) {
        wp_mail('ton-email@test.com', 'Test DISC', 'Email de test');
        die('Email envoyé');
    }
});
// Puis visiter : /?test_email
```

## 🚀 Prochaines Étapes Après Validation

1. **Configuration production**
   - Ajouter DISC_ENCRYPTION_KEY dans wp-config.php
   - Configurer SMTP
   - Créer landing page optimisée

2. **Intégration Mautic**
   - Configurer webhook dans paramètres
   - Créer segments par profil
   - Créer séquences email

3. **Campagne LinkedIn**
   - Tester sur 50-100 contacts
   - Analyser taux conversion
   - Optimiser si besoin
   - Déployer sur 2000 contacts

## 📚 Ressources

- **Documentation technique** : Voir [SPECS.md](SPECS.md)
- **Tâches complètes** : Voir [TASKS.md](TASKS.md)
- **Règles du projet** : Voir [.clinerules](.clinerules)
- **Artifacts code** : Dans la conversation Claude originale

## ❓ Questions Fréquentes

**Q: Le bloc Gutenberg est-il obligatoire ?**
R: Non, le shortcode `[disc_test]` suffit. Le bloc est optionnel.

**Q: Peut-on modifier les 28 questions ?**
R: Oui techniquement, mais à valider avec Philippe (expert DISC). Les questions actuelles sont validées professionnellement.

**Q: Comment intégrer avec EspoCRM ?**
R: Utilise le webhook ou le hook WordPress `disc_test_completed` pour envoyer les données.

**Q: Le plugin est-il multilingue ?**
R: Pas encore. Français uniquement en v1. Traductions prévues v1.2.

---

**Bon courage Claude Code ! 🚀**

Si tu bloques, reviens à la conversation originale ou contacte Philippe.
