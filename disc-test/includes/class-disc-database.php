<?php
/**
 * Classe DISC_Database
 * Gère toutes les opérations de base de données pour le plugin DISC Test
 * 
 * @package DISC_Test
 * @since 1.0.0
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class DISC_Database {
    
    /**
     * Crée les tables nécessaires lors de l'activation du plugin
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table pour stocker les questions du test
        $table_questions = $wpdb->prefix . 'disc_questions';
        $sql_questions = "CREATE TABLE IF NOT EXISTS $table_questions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question_order int(11) NOT NULL,
            statement_d text NOT NULL COMMENT 'Affirmation pour Dominance',
            statement_i text NOT NULL COMMENT 'Affirmation pour Influence',
            statement_s text NOT NULL COMMENT 'Affirmation pour Stabilité',
            statement_c text NOT NULL COMMENT 'Affirmation pour Conformité',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY question_order (question_order)
        ) $charset_collate;";
        
        // Table pour stocker les résultats des tests
        $table_results = $wpdb->prefix . 'disc_results';
        $sql_results = "CREATE TABLE IF NOT EXISTS $table_results (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_token varchar(64) NOT NULL COMMENT 'Token unique de session',
            email varchar(255) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            company varchar(255) DEFAULT NULL,
            position varchar(255) DEFAULT NULL,
            score_d int(11) NOT NULL DEFAULT 0,
            score_i int(11) NOT NULL DEFAULT 0,
            score_s int(11) NOT NULL DEFAULT 0,
            score_c int(11) NOT NULL DEFAULT 0,
            profile_type varchar(10) NOT NULL COMMENT 'Profil dominant ex: DI, SC, etc.',
            consistency_score decimal(5,2) DEFAULT NULL COMMENT 'Score de cohérence entre 0 et 100',
            average_response_time decimal(8,2) DEFAULT NULL COMMENT 'Temps moyen par question en secondes',
            total_time int(11) DEFAULT NULL COMMENT 'Temps total du test en secondes',
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            consent_given tinyint(1) NOT NULL DEFAULT 0,
            consent_timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY session_token (session_token),
            KEY email (email),
            KEY profile_type (profile_type),
            KEY completed_at (completed_at)
        ) $charset_collate;";
        
        // Table pour stocker les réponses détaillées
        $table_responses = $wpdb->prefix . 'disc_responses';
        $sql_responses = "CREATE TABLE IF NOT EXISTS $table_responses (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            result_id bigint(20) UNSIGNED NOT NULL,
            question_id bigint(20) UNSIGNED NOT NULL,
            most_like varchar(1) NOT NULL COMMENT 'D, I, S ou C',
            least_like varchar(1) NOT NULL COMMENT 'D, I, S ou C',
            response_time decimal(8,2) DEFAULT NULL COMMENT 'Temps de réponse en secondes',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY result_id (result_id),
            KEY question_id (question_id)
        ) $charset_collate;";
        
        // Table pour les logs d'audit de sécurité
        $table_logs = $wpdb->prefix . 'disc_audit_logs';
        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL COMMENT 'test_started, test_completed, admin_access, etc.',
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            session_token varchar(64) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            details text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY event_type (event_type),
            KEY created_at (created_at),
            KEY ip_address (ip_address)
        ) $charset_collate;";
        
        // Exécute les requêtes
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_questions);
        dbDelta($sql_results);
        dbDelta($sql_responses);
        dbDelta($sql_logs);
    }
    
    /**
     * Insère les questions par défaut du test DISC
     * 14 blocs ipsatifs (v1.3) — chaque bloc contient 4 items D/I/S/C
     */
    public static function insert_default_questions() {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_questions';
        
        // Vérifie si des questions existent déjà
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($existing > 0) {
            return;
        }

        // Questions v1.3 — 14 blocs ipsatifs
        $questions = array(
            array('order'=>1,  'd'=>"Je prends facilement les devants quand il faut décider vite.",              'i'=>"J'apporte spontanément de l'énergie dans les échanges.",           's'=>"Je reste présent et fiable dans la durée.",                        'c'=>"Je cherche à comprendre précisément avant d'agir."),
            array('order'=>2,  'd'=>"J'aime relever les situations exigeantes ou complexes.",                    'i'=>"Je crée facilement du lien avec des personnes différentes.",        's'=>"Je prends le temps d'écouter avant de réagir.",                    'c'=>"J'accorde de l'importance à la rigueur dans l'exécution."),
            array('order'=>3,  'd'=>"Je préfère avancer et ajuster ensuite si nécessaire.",                      'i'=>"J'aime entraîner les autres autour d'une idée.",                   's'=>"Je contribue à garder un climat calme et constructif.",            'c'=>"J'aime travailler avec méthode et clarté."),
            array('order'=>4,  'd'=>"Je défends mes positions quand je pense qu'elles sont justes.",             'i'=>"J'exprime facilement mon enthousiasme.",                           's'=>"On peut compter sur moi pour maintenir la continuité.",            'c'=>"Je repère vite ce qui manque ou ce qui n'est pas cohérent."),
            array('order'=>5,  'd'=>"Je suis stimulé par l'atteinte d'objectifs ambitieux.",                    'i'=>"J'aime convaincre et embarquer les autres.",                       's'=>"J'accorde de l'importance à la qualité de la relation.",           'c'=>"Je préfère vérifier les faits avant de conclure."),
            array('order'=>6,  'd'=>"J'accepte facilement de trancher dans l'incertitude.",                     'i'=>"Je me sens à l'aise pour parler avec impact.",                     's'=>"Je garde généralement mon calme même sous pression.",             'c'=>"J'aime structurer les choses pour éviter les erreurs."),
            array('order'=>7,  'd'=>"Je vais naturellement vers l'action plutôt que vers l'attente.",           'i'=>"J'aime encourager et valoriser les autres.",                       's'=>"Je cherche à construire des relations solides et durables.",       'c'=>"Je suis attentif aux détails qui font la qualité finale."),
            array('order'=>8,  'd'=>"J'aime avoir de la latitude pour décider par moi-même.",                   'i'=>"J'entre facilement en contact dans un nouveau groupe.",             's'=>"J'apprécie les collaborations stables et fluides.",                'c'=>"Je préfère un cadre clair quand il faut produire un travail fiable."),
            array('order'=>9,  'd'=>"Je peux être direct quand il faut faire avancer les choses.",               'i'=>"J'aime partager une vision ou une possibilité enthousiasmante.",   's'=>"Je fais preuve de patience dans l'accompagnement des autres.",     'c'=>"Je prends du recul pour analyser avant de m'engager."),
            array('order'=>10, 'd'=>"Je me sens responsable d'impulser un mouvement.",                          'i'=>"J'aime être au contact et faire circuler les idées.",              's'=>"Je contribue à rendre les échanges plus sereins.",                 'c'=>"J'aime quand les attentes et critères sont explicites."),
            array('order'=>11, 'd'=>"Je préfère un cap clair et une décision assumée.",                         'i'=>"J'aime mobiliser les autres autour d'un projet.",                  's'=>"Je suis constant dans mon implication.",                          'c'=>"Je suis exigeant sur la qualité du raisonnement."),
            array('order'=>12, 'd'=>"Je supporte bien la confrontation quand elle est utile.",                   'i'=>"Je rends les échanges plus vivants et engageants.",                's'=>"Je facilite la coopération dans un groupe.",                      'c'=>"Je remarque facilement les zones floues ou imprécises."),
            array('order'=>13, 'd'=>"Je préfère agir sur les problèmes plutôt que les subir.",                  'i'=>"J'aime donner confiance et motiver.",                              's'=>"J'avance avec régularité sans me disperser.",                     'c'=>"Je cherche des standards fiables pour bien faire."),
            array('order'=>14, 'd'=>"Je suis orienté vers le résultat et la progression.",                      'i'=>"J'aime influencer positivement l'ambiance et les relations.",     's'=>"Je privilégie une progression solide plutôt que précipitée.",     'c'=>"Je veille à la cohérence d'ensemble avant de valider."),
        );
        
        foreach ($questions as $q) {
            $wpdb->insert(
                $table,
                array(
                    'question_order' => $q['order'],
                    'statement_d' => $q['d'],
                    'statement_i' => $q['i'],
                    'statement_s' => $q['s'],
                    'statement_c' => $q['c']
                ),
                array('%d', '%s', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Récupère une question par son ID
     */
    public static function get_question($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_questions';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            intval($id)
        ), ARRAY_A);
    }

    /**
     * Met à jour une question existante
     */
    public static function update_question($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_questions';

        return $wpdb->update(
            $table,
            array(
                'statement_d' => sanitize_text_field($data['statement_d']),
                'statement_i' => sanitize_text_field($data['statement_i']),
                'statement_s' => sanitize_text_field($data['statement_s']),
                'statement_c' => sanitize_text_field($data['statement_c']),
            ),
            array('id' => intval($id)),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Récupère toutes les questions dans l'ordre
     */
    public static function get_questions() {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_questions';
        
        return $wpdb->get_results(
            "SELECT * FROM $table ORDER BY question_order ASC",
            ARRAY_A
        );
    }
    
    /**
     * Enregistre les réponses d'un utilisateur
     */
    public static function save_responses($responses) {
        global $wpdb;
        $table   = $wpdb->prefix . 'disc_responses';
        $success = 0;

        foreach ($responses as $response) {
            $inserted = $wpdb->insert(
                $table,
                array(
                    'result_id'     => $response['result_id'],
                    'question_id'   => $response['question_id'],
                    'most_like'     => $response['most_like'],
                    'least_like'    => $response['least_like'],
                    'response_time' => $response['response_time']
                ),
                array('%d', '%d', '%s', '%s', '%f')
            );

            if ($inserted) {
                $success++;
            }
        }

        // Retourne le nombre de réponses effectivement enregistrées
        return $success;
    }
    
    /**
     * Enregistre le résultat final du test
     */
    public static function save_result($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_results';
        
        $inserted = $wpdb->insert(
            $table,
            array(
                'session_token' => $data['session_token'],
                'email' => DISC_Security::encrypt_email(sanitize_email($data['email'])),
                'first_name' => sanitize_text_field($data['first_name']),
                'last_name' => sanitize_text_field($data['last_name']),
                'company' => sanitize_text_field($data['company']),
                'position' => sanitize_text_field($data['position']),
                'score_d' => intval($data['score_d']),
                'score_i' => intval($data['score_i']),
                'score_s' => intval($data['score_s']),
                'score_c' => intval($data['score_c']),
                'profile_type' => sanitize_text_field($data['profile_type']),
                'consistency_score' => floatval($data['consistency_score']),
                'average_response_time' => floatval($data['average_response_time']),
                'total_time' => intval($data['total_time']),
                'ip_address' => DISC_Security::get_client_ip(),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
                'consent_given' => intval($data['consent_given'] ?? 0)
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%f', '%f', '%d', '%s', '%s', '%d')
        );
        
        if ($inserted) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Récupère un résultat par son ID
     */
    public static function get_result_by_id($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_results';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            intval($id)
        ), ARRAY_A);
    }

    /**
     * Récupère un résultat par token de session
     */
    public static function get_result_by_token($session_token) {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_results';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE session_token = %s",
            $session_token
        ), ARRAY_A);
    }
    
    /**
     * Récupère tous les résultats avec pagination, tri et recherche
     *
     * @param int    $limit   Nombre de résultats par page
     * @param int    $offset  Décalage
     * @param string $orderby Colonne de tri (completed_at, last_name, first_name, profile_type)
     * @param string $order   ASC ou DESC
     * @param string $search  Recherche sur prénom, nom, entreprise
     */
    public static function get_all_results($limit = 50, $offset = 0, $orderby = 'completed_at', $order = 'DESC', $search = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_results';

        $allowed_orderby = array('completed_at', 'last_name', 'first_name', 'profile_type');
        $orderby = in_array($orderby, $allowed_orderby, true) ? $orderby : 'completed_at';
        $order   = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table WHERE first_name LIKE %s OR last_name LIKE %s OR company LIKE %s ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                $like, $like, $like, intval($limit), intval($offset)
            ), ARRAY_A);
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
            intval($limit),
            intval($offset)
        ), ARRAY_A);
    }

    /**
     * Compte le nombre total de résultats (optionnellement filtrés par recherche)
     *
     * @param string $search Recherche sur prénom, nom, entreprise
     */
    public static function count_results($search = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_results';

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE first_name LIKE %s OR last_name LIKE %s OR company LIKE %s",
                $like, $like, $like
            ));
        }

        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    /**
     * Supprime un résultat et ses réponses associées.
     * Les logs d'audit sont conservés (traçabilité RGPD).
     *
     * @param int $id ID du résultat
     * @return int|false Nombre de lignes supprimées ou false en cas d'erreur
     */
    public static function delete_result($id) {
        global $wpdb;
        $id = intval($id);

        self::log_event('result_deleted', array(
            'result_id' => $id,
            'admin_id'  => get_current_user_id(),
        ));

        $wpdb->delete(
            $wpdb->prefix . 'disc_responses',
            array('result_id' => $id),
            array('%d')
        );

        return $wpdb->delete(
            $wpdb->prefix . 'disc_results',
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Suppression groupée de résultats
     *
     * @param int[] $ids Tableau d'IDs de résultats
     * @return int Nombre de résultats effectivement supprimés
     */
    public static function bulk_delete_results($ids) {
        if (empty($ids) || !is_array($ids)) {
            return 0;
        }
        $count = 0;
        foreach ($ids as $id) {
            if (self::delete_result(intval($id)) !== false) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Met à jour les coordonnées d'un résultat (prénom, nom, email, entreprise uniquement).
     * Les données psychométriques ne sont jamais modifiées ici.
     *
     * @param int   $id   ID du résultat
     * @param array $data Tableau avec les clés first_name, last_name, email, company
     * @return int|false Nombre de lignes modifiées ou false en cas d'erreur
     */
    public static function update_result($id, $data) {
        global $wpdb;
        $id = intval($id);

        $update  = array();
        $formats = array();

        if (isset($data['first_name'])) {
            $update['first_name'] = sanitize_text_field($data['first_name']);
            $formats[]            = '%s';
        }
        if (isset($data['last_name'])) {
            $update['last_name'] = sanitize_text_field($data['last_name']);
            $formats[]           = '%s';
        }
        if (isset($data['email'])) {
            $clean_email = sanitize_email($data['email']);
            if (!empty($clean_email) && is_email($clean_email)) {
                $update['email'] = DISC_Security::encrypt_email($clean_email);
                $formats[]       = '%s';
            }
        }
        if (isset($data['company'])) {
            $update['company'] = sanitize_text_field($data['company']);
            $formats[]         = '%s';
        }

        if (empty($update)) {
            return false;
        }

        self::log_event('result_edited', array(
            'result_id'      => $id,
            'admin_id'       => get_current_user_id(),
            'fields_updated' => array_keys($update),
        ));

        return $wpdb->update(
            $wpdb->prefix . 'disc_results',
            $update,
            array('id' => $id),
            $formats,
            array('%d')
        );
    }
    
    /**
     * Récupère les statistiques globales
     */
    public static function get_statistics() {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_results';
        
        $stats = array(
            'total_tests' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'tests_last_30_days' => $wpdb->get_var(
                "SELECT COUNT(*) FROM $table WHERE completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            ),
            'profile_distribution' => $wpdb->get_results(
                "SELECT profile_type, COUNT(*) as count 
                FROM $table 
                GROUP BY profile_type 
                ORDER BY count DESC",
                ARRAY_A
            ),
            'average_consistency' => $wpdb->get_var("SELECT AVG(consistency_score) FROM $table"),
            'average_completion_time' => $wpdb->get_var("SELECT AVG(total_time) FROM $table")
        );
        
        return $stats;
    }
    
    /**
     * Enregistre un événement dans les logs d'audit
     */
    public static function log_event($event_type, $details = null, $session_token = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_audit_logs';

        $wpdb->insert(
            $table,
            array(
                'event_type' => sanitize_text_field($event_type),
                'user_id' => get_current_user_id() ?: null,
                'session_token' => $session_token,
                'ip_address' => DISC_Security::get_client_ip(),
                'details' => is_array($details) ? json_encode($details) : $details
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );
    }

    /**
     * Réinitialise la banque de questions avec les 14 blocs v1.3
     * Action explicite admin — ne pas appeler automatiquement
     */
    public static function reset_questions() {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_questions';
        $wpdb->query("TRUNCATE TABLE $table");
        // Force réinsertion en vidant la table d'abord
        self::insert_default_questions();
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
    }

    /**
     * Supprime tous les résultats de test (résultats + réponses + logs)
     * Action explicite admin — ne pas appeler automatiquement
     */
    public static function reset_test_data() {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}disc_results");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}disc_responses");
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}disc_audit_logs");
    }
}