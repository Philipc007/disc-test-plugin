# Audit technique approfondi du plugin Test DISC Lead Magnet

## Contexte et périmètre de l’audit

Le plugin audité est **Test DISC Lead Magnet** (version **1.0.0**, fichier principal `disc-test.php`). Il expose un parcours lead magnet classique : affichage du test en front, collecte des réponses, calcul des scores/profil, collecte des informations de contact (email, prénom/nom, entreprise, poste), puis actions post-completion (email + intégration CRM via webhook + journalisation + stockage DB).

Côté intégration, ton cas d’usage “plugin WordPress → n8n → Mautic” est cohérent :  
- **Le plugin** émet un **POST JSON** vers une URL webhook (configurable dans l’admin).  
- **n8n** reçoit ce JSON via un Webhook node (qui peut servir d’endpoint), transforme / mappe les champs, puis appelle l’API de ton CRM (ex : Mautic). La doc n8n précise bien que le Webhook node est fait pour recevoir des données et déclencher un workflow, et qu’il supporte nativement plusieurs méthodes d’authentification (header auth, basic auth, JWT) + options comme IP whitelist. citeturn4view3

L’audit ci-dessous est **centré sur le code** (sécurité, robustesse, conformité aux bonnes pratiques WordPress) et sur la cohérence “guides / UI / comportement réel”.

## Validation du JSON CRM et implications d’intégration vers n8n et Mautic

### Conformité du payload généré par le plugin

Ton exemple de JSON :

```json
{
  "email": "john.doe@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "company": "Acme Corp",
  "position": "Directeur",
  "profile_type": "DI",
  "score_d": 88,
  "score_i": 100,
  "score_s": 0,
  "score_c": 12,
  "consistency_score": 75.5,
  "completed_at": "2026-03-08T17:58:00+01:00",
  "tags": ["disc","disc-di","disc-d","disc-i","disc-consistent"]
}
```

Le plugin construit effectivement un payload de ce type dans `includes/class-disc-frontend.php` lors de `handle_contact_submission()` : champs identiques, `completed_at` en ISO-8601 via `current_time('c')`, `tags` via `generate_crm_tags()`, et `Content-Type: application/json`. Cette structure est très “n8n-friendly”.

### Point important : mapping Mautic

**Mautic** (API Contacts) expose des champs “contact” et indique explicitement que `tags` est un **tableau de tags associés au contact** (format JSON). citeturn3view0  
En revanche, Mautic utilise généralement des alias de champs du type `firstname`, `lastname` (sans underscore), dépendant de la configuration des champs (core/custom). Conclusion :  
- garder ton payload stable en `first_name`/`last_name` est OK (c’est une API “source” propre)  
- mais **il faudra mapper** vers les alias attendus par ton Mautic dans n8n (ou via une future “cible” d’intégration directe).

### Sécurisation du webhook n8n

Si un jour ton webhook n8n est exposé publiquement (hébergement), il est important d’activer une auth (Header Auth par exemple) ou une IP whitelist. n8n documente ces options de sécurité au niveau du Webhook node. citeturn4view3

## Contrôles de sécurité déjà présents et alignement bonnes pratiques WordPress

Globalement, le plugin suit une bonne partie des standards attendus :

- **Sanitization/validation** : usage de `sanitize_email()`, `sanitize_text_field()`, `sanitize_key()`, `esc_url_raw()`, `intval()`, `floatval()` sur les entrées critiques. Ces fonctions sont celles recommandées côté WordPress pour nettoyer/normaliser les données, et `sanitize_text_field()` est bien le “baseline” pour du texte simple. citeturn0search0turn2search1turn2search2  
- **Nonces** : génération via `wp_create_nonce()` et vérification via `wp_verify_nonce()` côté AJAX ; côté admin, usage de `check_admin_referer()` et `wp_nonce_url()`. WordPress rappelle que les nonces protègent surtout contre certains abus (notamment CSRF) et doivent être combinés à d’autres contrôles quand nécessaire. citeturn0search19turn0search1  
- **Réponses AJAX** : usage des helpers `wp_send_json_success()` / `wp_send_json_error()`, qui renvoient du JSON et terminent correctement l’exécution. citeturn0search2turn0search32  
- **Admin access control** : beaucoup de pages admin et actions sensibles sont protégé(e)s par `current_user_can('manage_options')` + nonce, ce qui correspond aux bonnes pratiques d’access control dans un plugin. citeturn0search12  
- **DB** : usage de `$wpdb->prepare()` quand il y a des paramètres (`get_result_by_token`, `get_all_results`, etc.), ce qui est attendu pour éviter l’injection SQL. citeturn2search3  

Mais : “bonne base” ne veut pas dire “sans point critique” — et l’audit détaillé fait ressortir un vrai défaut bloquant sur le consentement.

## Analyse détaillée par fichier

### `disc-test.php` (bootstrap, hooks, assets)

Constats solides :  
- constantes plugin (`DISC_TEST_VERSION`, etc.), `ABSPATH` guard, activation hook (création tables + insertion questions), enqueues front + admin, localisation JS avec nonce (`wp_localize_script`).  
- AJAX actions `wp_ajax_` + `wp_ajax_nopriv_` sur 2 endpoints (réponses + contact), ce qui est cohérent pour un lead magnet public.

Points précis à corriger / améliorer :  
- **Dépendance Chart.js via CDN** : `wp_enqueue_script('chartjs', 'https://cdnjs.cloudflare.com/...')`. C’est pratique, mais tu introduis un tiers (disponibilité, supply chain, CSP, confidentialité). Sur un lead magnet “profilage”, je recommanderais de packager localement (build) ou au minimum de documenter ce choix.
- **Bloc Gutenberg** : `register_gutenberg_block()` enregistre `build/block.js`, mais dans ton zip, `build/` ne contient pas ce fichier (juste un `.gitkeep`). Résultat : bloc potentiellement non fonctionnel / 404 côté admin. À trancher : soit tu fournis le build, soit tu retires l’`editor_script` pour éviter une feature “fantôme”.

### `includes/class-disc-security.php` (nonce, rate limit, scoring cohérence, chiffrement)

Points forts :  
- `verify_ajax_nonce()` centralise la vérification et renvoie un JSON error en cas d’échec.  
- **Rate limiting** porte sur l’IP via transient (3 tests/heure) — c’est un bon premier filet.  
- `generate_session_token()` utilise `random_bytes(32)` (excellent, CSPRNG).  
- `validate_contact_data()` fait un check minimaliste mais utile (email valide, prénom/nom min length, consent requirement).

Points à surveiller (non bloquants) :  
- `get_client_ip()` lit `HTTP_X_FORWARDED_FOR` : c’est classique mais facilement spoofable si ton infra ne “clean” pas l’en-tête (important si tu te reposes fortement dessus pour du rate-limit).  
- Le module de chiffrement (`encrypt_email/decrypt_email`) est implémenté, mais **pas utilisé** (voir plus bas). Côté WordPress, c’est un vrai sujet : ce qui est affiché à l’admin doit refléter la réalité.

Référence utile : WordPress insiste sur la logique “sanitize/validate/escape” appliquée partout où entrées et sorties existent. citeturn0search0turn2search10  

### `includes/class-disc-frontend.php` (AJAX handlers, calcul, stockage, webhook)

Points forts :  
- `handle_contact_submission()` fait : nonce → rate limit → sanitize inputs → validation → parse JSON → calcul scores → détermination profil → stockage DB → email → webhook CRM. Le pipeline est clair et maintenable.  
- Validation des dimensions `D/I/S/C` sur chaque réponse (bonne pratique).
- `do_action('disc_test_completed', ...)` est une excellente extension point (tiers).

Problème critique (RGPD/consentement) :  
- Dans `handle_contact_submission()`, le consentement est construit ainsi :

```php
'consent' => isset($_POST['consent']) ? 1 : 0
```

Comme ton JS envoie **toujours** le champ `consent` (1 ou 0), `isset($_POST['consent'])` est presque toujours vrai → `consent` devient **toujours 1** côté serveur, même si la valeur envoyée vaut `0`.  
Du coup, `validate_contact_data()` ne peut pas bloquer (puisqu’il voit toujours consent=1). C’est un défaut bloquant : la conformité “consentement obligatoire” est aujourd’hui **principalement front-end**, ce qui est insuffisant.

Correction attendue (exemple robuste) :

```php
$consent = ! empty($_POST['consent'])
    && absint(wp_unslash($_POST['consent'])) === 1
    ? 1
    : 0;

$contact_data = array(
  // ...
  'consent' => $consent,
);
```

Et il faut aussi **propager** ce consentement au stockage DB (voir `save_result()` + schema DB).

Cohérence événements / logs (bug de logique) :  
- Ton JS envoie `event: 'test_started'` via l’action AJAX `disc_submit_response`, mais le handler PHP `handle_response_submission()` ignore `event` et logue toujours `question_answered`. Concrètement, tu vas produire un log “question_answered” avec `question_id` absent/0 — pollution de la table d’audit et métriques incorrectes.  
Tu dois soit :  
- supprimer ce call (si inutile),  
- soit traiter un allowlist d’events (`test_started`, `question_answered`…),  
- soit créer une action AJAX dédiée `disc_log_event` (plus propre).

Webhook sortant :  
- Tu utilises `wp_remote_post($webhook_url, ...)`. WordPress précise que si l’URL est user-controlled, il faut préférer `wp_safe_remote_post()` et/ou restreindre les URLs possibles. citeturn0search21turn2search0  
Dans ton plugin, l’URL est contrôlée par un admin (donc risque SSRF “classique” faible), mais c’est quand même une zone qui mérite des garde-fous si tu veux un produit “pro” (allowlist de domaine, check host, etc.).  
- `blocking => false` est cohérent pour ne pas ralentir l’utilisateur, mais ça veut dire : pas de gestion d’erreur fiable (ni retry, ni logging du résultat). Si l’intégration CRM est stratégique, je recommanderais au moins une trace “webhook_sent” / “webhook_error” (ou une queue) avec `blocking => true` en mode “soft” (timeout court) quand tu as besoin de feedback.

### `includes/class-disc-database.php` (tables, insert, queries)

Points forts :  
- Schema DB propre : tables `disc_questions`, `disc_results`, `disc_responses`, `disc_audit_logs`.  
- `dbDelta()` utilisé pour les CREATE TABLE (good).  
- `$wpdb->insert` avec formats, et `$wpdb->prepare` sur les requêtes paramétrées. citeturn2search3  

Points de divergence / cohérence :  
- La table `disc_results` a `consent_given` en `DEFAULT 1`. Tant que tu corriges le bug de consentement côté serveur, je recommande de mettre `DEFAULT 0` (ou au minimum stocker la vraie valeur) pour éviter que le schéma “déclare” un consentement quand il n’existe pas.  
- L’email est stocké **en clair** (`save_result()` fait `sanitize_email($data['email'])`), alors que tu as du code de chiffrement dans `DISC_Security` et une UI admin qui suggère d’ajouter `DISC_ENCRYPTION_KEY`. Il faut aligner : choisir “on chiffre réellement” ou “on n’en parle pas”.

Audit logs :  
- Bonne idée pour debug et stats, mais attention à la volumétrie. Vu que l’AJAX “start” est public, sans rate-limit spécifique, il devient possible de spammer `disc_audit_logs`. Même sans vulnérabilité directe, c’est un risque de gonflement DB.

### `includes/class-disc-renderer.php` (HTML front, UX, privacy policy)

Points forts :  
- Les statements sont affichés avec `esc_html()` (bonne barrière XSS).  
- Tu utilises `get_privacy_policy_url()` pour proposer un lien vers la politique de confidentialité : c’est une bonne pratique WordPress.

Point à durcir :  
- Le texte de consentement inclut un `<a>` dans une string traduite et est output via `printf()`. Pour les strings contenant du HTML, un pattern plus sûr est de passer la sortie par `wp_kses_post()` (et d’ajouter `rel="noopener noreferrer"` pour un lien `target="_blank"`).

### `includes/class-disc-email.php` (email HTML + QuickChart)

Points forts :  
- Utilisation de `wp_mail()` (standard WordPress).  
- Plusieurs champs sont échappés (`esc_html`) dans le template.

Points stratégiques :  
- Le sujet est codé en dur alors que l’admin enregistre une option `disc_email_subject` (dans la page settings). Il faut les relier pour éviter une option “inutile” (voir section priorités).  
- QuickChart : ton email embarque une image via URL `https://quickchart.io/chart?...&c=...` contenant une config Chart.js (donc indirectement les scores). La documentation QuickChart confirme que le endpoint `/chart` accepte des paramètres (dont `c/chart`) et que le service est conçu pour générer des images “on-the-fly” adaptées à l’email. citeturn4view2turn4view1  
C’est fonctionnel, mais c’est aussi un **partage de données** vers un tiers : à documenter et/ou rendre optionnel si tu veux être carré RGPD.

### `includes/class-disc-admin.php` (admin UI, export, édition questions, settings)

Points forts :  
- Contrôle d’accès (`manage_options`) + nonces (`check_admin_referer`) sur les actions sensibles (export CSV, édition questions, renvoi email). C’est conforme aux attentes. citeturn0search12turn0search19  
- Échappements nombreux (`esc_html`, `esc_attr`, `esc_url`) sur les sorties.

Point précis à corriger :  
- Dans la page résultats, le `confirm()` JS concatène l’email dans une string échappée partiellement : le texte est passé dans `esc_js()`, mais l’email concaténé ne l’est pas. Le risque est modéré (email déjà sanitizé), mais c’est un “code smell” et ça casse l’hygiène XSS/JS-escaping. Il faut échapper l’email aussi.

## Points de risque prioritaires et recommandations précises

### Consentement RGPD forcé à 1 côté serveur

**Sévérité : critique** (juridique + conformité + trust).  
Changer immédiatement la logique `isset()` → check de valeur, et stocker `consent_given` selon la valeur réelle. Alignement avec la page settings, et idéalement stocker un timestamp de consentement “vrai”.

### Injection HTML côté JS via `showError()`

**Sévérité : haute** (XSS potentielle).  
Dans `assets/js/frontend.js`, `showError()` fait :

```js
$container.html('<p class="error">' + message + '</p>')
```

Remplacer par du texte pur (ou un DOM node) :

```js
$container.empty().append(
  $('<p/>', { class: 'error', text: message })
).fadeIn(300);
```

Même si aujourd’hui tes messages serveur ne contiennent pas d’HTML, ce changement évite qu’un futur message (ou une intégration) n’ouvre une XSS.

### Webhook sortant : garde-fous SSRF + sécurité n8n

**Sévérité : moyenne** (risque surtout si l’URL devient influenceable ou si un admin est compromis).  
WordPress recommande `wp_safe_remote_post()` quand l’URL est user-controlled et rappelle qu’`esc_url_raw()` n’est pas une protection SSRF suffisante à elle seule. citeturn0search21turn2search0  
Actions concrètes possibles (à choisir selon ton scénario local vs prod) :  
- schéma autorisé (http/https)  
- allowlist de domaines (ex : ton domaine n8n) ou au moins rejet des IP privées si tu es en prod  
- secret partagé (header) vérifié dans n8n (Header Auth, ou IF node). n8n documente très clairement les méthodes d’auth supportées et l’IP whitelist. citeturn4view3  

### Cohérence “UI settings” vs implémentation réelle

**Sévérité : moyenne** (qualité produit + risque de “fausse promesse”).  
Deux incohérences observées :  
- `disc_email_subject` est sauvegardé, mais le sujet d’email est codé en dur dans `send_results_email()`.  
- L’admin settings propose `DISC_ENCRYPTION_KEY` “pour chiffrer les emails en base”, mais la DB stocke l’email en clair, et `encrypt_email()` n’est pas utilisé.

Soit tu implémentes “vraiment” ces features, soit tu simplifies l’UI.

### Journalisation public sans contrôle fin

**Sévérité : moyenne** (volumétrie DB, bruit).  
L’action `disc_submit_response` est publique (`nopriv`) + nonce (mais nonce ≠ auth forte). WordPress rappelle que les nonces sont un mécanisme anti-misuse/CSRF, pas une authentification. citeturn0search19turn0search1  
Comme ton JS l’utilise uniquement pour un “test_started” mal géré, le plus propre est :  
- soit supprimer cette action côté front,  
- soit la rate-limiter + allowlist d’événements + payload minimal.

## Roadmap de corrections avec priorités projet

Dans l’ordre, comme un chef de projet “sécurité + produit” :

1. **Correctif consentement** (serveur + stockage) : c’est la seule correction réellement bloquante.
2. **Correctif XSS défensif côté JS** (`showError`) + échappement complet dans le confirm admin.
3. **Nettoyage cohérence logs** (AJAX `disc_submit_response` : supprimer ou normaliser).
4. **Alignement UI settings** : brancher `disc_email_subject`, décider chiffrement email (et si oui : migration / compat export / déchiffrement affichage).
5. **Hardening webhook** : secret/header auth, logging basique des erreurs webhook, et décision “blocking vs non-blocking” selon ton besoin business.
6. **Qualité build** : soit livrer `build/block.js` (si bloc Gutenberg voulu), soit retirer la feature.

Si tu veux, colle-moi maintenant le “guide d’intégration CRM” (ou juste le passage où il décrit la sécurité et l’exemple JSON), et je vérifierai aussi la cohérence doc↔code, point par point, pour éviter de demander à Claude des modifications inutiles ou mal ciblées.