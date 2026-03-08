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
     * 28 questions couvrant les 4 dimensions de manière équilibrée
     */
    public static function insert_default_questions() {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_questions';
        
        // Vérifie si des questions existent déjà
        $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($existing > 0) {
            return;
        }
        
        // Questions conçues selon la méthodologie DISC classique
        $questions = array(
            array('order' => 1, 'd' => "Je prends des décisions rapidement et avec confiance", 'i' => "J'établis facilement des relations avec de nouvelles personnes", 's' => "Je préfère travailler à un rythme stable et prévisible", 'c' => "J'analyse tous les détails avant d'agir"),
            array('order' => 2, 'd' => "J'aime relever des défis et surmonter des obstacles", 'i' => "Je convaincs les autres par mon enthousiasme", 's' => "Je crée une atmosphère harmonieuse dans mon équipe", 'c' => "Je vérifie systématiquement la qualité de mon travail"),
            array('order' => 3, 'd' => "Je me concentre sur l'atteinte des objectifs", 'i' => "Je m'exprime facilement et de manière expressive", 's' => "Je suis patient avec les autres", 'c' => "Je suis précis et méthodique dans mon approche"),
            array('order' => 4, 'd' => "Je n'hésite pas à affronter les conflits", 'i' => "J'inspire et motive mon entourage", 's' => "Je suis loyal envers mon équipe", 'c' => "Je respecte scrupuleusement les règles et procédures"),
            array('order' => 5, 'd' => "Je pousse les autres à atteindre de meilleurs résultats", 'i' => "Je vois le côté positif dans toute situation", 's' => "Je préfère collaborer plutôt que compétitionner", 'c' => "Je m'appuie sur des faits et des données"),
            array('order' => 6, 'd' => "J'aime avoir le contrôle sur les situations", 'i' => "Je partage ouvertement mes émotions", 's' => "J'évite les changements brusques", 'c' => "Je suis consciencieux dans tout ce que je fais"),
            array('order' => 7, 'd' => "Je vais droit au but dans mes communications", 'i' => "J'aime être au centre de l'attention", 's' => "Je suis accommodant et flexible avec les autres", 'c' => "Je pose beaucoup de questions pour bien comprendre"),
            array('order' => 8, 'd' => "Je préfère l'action à la réflexion prolongée", 'i' => "Je génère facilement des idées créatives", 's' => "Je maintiens des routines stables", 'c' => "Je planifie soigneusement avant d'agir"),
            array('order' => 9, 'd' => "J'accepte volontiers les responsabilités", 'i' => "Je persuade les autres de mon point de vue", 's' => "Je suis à l'écoute des besoins des autres", 'c' => "Je maintiens des standards élevés de qualité"),
            array('order' => 10, 'd' => "Je suis compétitif et déterminé à gagner", 'i' => "Je crée facilement des liens sociaux", 's' => "Je suis fiable et constant", 'c' => "Je suis logique et analytique"),
            array('order' => 11, 'd' => "Je n'ai pas peur de prendre des risques calculés", 'i' => "J'influence les autres par mon charisme", 's' => "Je privilégie la stabilité et la sécurité", 'c' => "Je vérifie l'exactitude de toute information"),
            array('order' => 12, 'd' => "Je remets en question le statu quo", 'i' => "Je suis spontané et expressif", 's' => "Je suis patient face aux difficultés", 'c' => "Je suis minutieux et précis"),
            array('order' => 13, 'd' => "Je préfère diriger plutôt que suivre", 'i' => "J'aime travailler en équipe et socialiser", 's' => "Je préfère les environnements calmes et prévisibles", 'c' => "Je suis systématique dans mon organisation"),
            array('order' => 14, 'd' => "Je prends des décisions fermes", 'i' => "Je communique avec enthousiasme", 's' => "Je soutiens loyalement mes collègues", 'c' => "Je documente tout avec précision"),
            array('order' => 15, 'd' => "Je suis orienté vers les résultats immédiats", 'i' => "Je suis optimiste et positif", 's' => "Je préfère les changements graduels", 'c' => "Je suis prudent et réfléchi"),
            array('order' => 16, 'd' => "Je donne des directives claires", 'i' => "Je suis chaleureux et amical", 's' => "Je suis stable sous pression", 'c' => "Je suis objectif et impartial"),
            array('order' => 17, 'd' => "J'aime les situations qui demandent de l'audace", 'i' => "J'exprime mes idées avec passion", 's' => "Je crée un environnement de travail harmonieux", 'c' => "J'analyse les risques avant d'avancer"),
            array('order' => 18, 'd' => "Je suis direct dans mes feedbacks", 'i' => "Je motive les autres par mon énergie", 's' => "Je suis prévisible et constant", 'c' => "Je suis discipliné et rigoureux"),
            array('order' => 19, 'd' => "Je recherche les opportunités de progression", 'i' => "Je crée facilement des relations de confiance", 's' => "Je préfère les tâches familières", 'c' => "Je m'assure que tout est en ordre"),
            array('order' => 20, 'd' => "Je suis assertif dans mes communications", 'i' => "Je suis sociable et extraverti", 's' => "Je suis calme et posé", 'c' => "Je suis perfectionniste"),
            array('order' => 21, 'd' => "J'impose le respect par ma force de caractère", 'i' => "J'attire les autres par ma personnalité", 's' => "Je préfère l'harmonie au conflit", 'c' => "Je suis méticuleux dans les détails"),
            array('order' => 22, 'd' => "Je prends l'initiative sans attendre", 'i' => "Je communique mes émotions ouvertement", 's' => "Je maintiens des relations durables", 'c' => "Je suis consciencieux et diligent"),
            array('order' => 23, 'd' => "J'aime les environnements compétitifs", 'i' => "J'aime recevoir de la reconnaissance", 's' => "Je suis diplomate dans mes interactions", 'c' => "Je respecte les normes établies"),
            array('order' => 24, 'd' => "Je me concentre sur l'efficacité", 'i' => "Je suis créatif et imaginatif", 's' => "Je suis patient et persévérant", 'c' => "Je suis logique et rationnel"),
            array('order' => 25, 'd' => "Je pousse pour obtenir des résultats", 'i' => "J'établis facilement un rapport avec les autres", 's' => "Je privilégie la coopération", 'c' => "Je suis prudent et réservé"),
            array('order' => 26, 'd' => "Je défends mes positions avec fermeté", 'i' => "Je suis démonstratif et expressif", 's' => "Je suis loyal et dévoué", 'c' => "Je suis précis dans mes évaluations"),
            array('order' => 27, 'd' => "J'accepte volontiers les défis difficiles", 'i' => "J'encourage et inspire mon équipe", 's' => "Je suis fiable dans mes engagements", 'c' => "Je suis systématique dans mon approche"),
            array('order' => 28, 'd' => "Je suis déterminé à réussir", 'i' => "Je vois le potentiel dans chaque personne", 's' => "Je préfère la continuité au changement", 'c' => "Je suis exigeant sur la qualité")
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
     * Récupère tous les résultats avec pagination
     */
    public static function get_all_results($limit = 50, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_results';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY completed_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A);
    }
    
    /**
     * Compte le nombre total de résultats
     */
    public static function count_results() {
        global $wpdb;
        $table = $wpdb->prefix . 'disc_results';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table");
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
}