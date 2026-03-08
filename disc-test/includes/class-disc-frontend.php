<?php
/**
 * Classe DISC_Frontend
 * Gère toutes les interactions frontend du plugin
 * 
 * @package DISC_Test
 * @since 1.0.0
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class DISC_Frontend {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Les hooks sont déjà définis dans la classe principale
    }

    /**
     * Génère les tags CRM à partir des résultats du test
     * Le préfixe est configurable dans les paramètres du plugin
     */
    public static function generate_crm_tags($profile_type, $scores, $consistency_score) {
        $prefix = sanitize_key(get_option('disc_tag_prefix', 'disc'));
        if (empty($prefix)) {
            $prefix = 'disc';
        }

        $tags = array();

        // Tag de base — identifie tous les leads issus du test DISC
        $tags[] = $prefix;

        // Tag profil complet (ex: disc-di, disc-s, disc-disc)
        $tags[] = $prefix . '-' . strtolower($profile_type);

        // Tags par dimension dominante (score >= 60)
        $dimensions = array('D', 'I', 'S', 'C');
        foreach ($dimensions as $dim) {
            if ($scores[$dim] >= 60) {
                $tags[] = $prefix . '-' . strtolower($dim);
            }
        }

        // Tag qualité basé sur le score de cohérence
        if ($consistency_score >= 70) {
            $tags[] = $prefix . '-consistent';
        } elseif ($consistency_score < 50) {
            $tags[] = $prefix . '-suspect';
        }

        return array_unique($tags);
    }
    
    /**
     * Gère la soumission d'une réponse à une question
     * Utilisé aussi pour logger le démarrage du test (event=test_started)
     */
    public static function handle_response_submission() {
        DISC_Security::verify_ajax_nonce();

        $allowed_events  = array('test_started', 'question_answered');
        $event           = sanitize_key($_POST['event'] ?? 'question_answered');
        $session_token   = sanitize_text_field(wp_unslash($_POST['session_token'] ?? ''));

        if (!in_array($event, $allowed_events, true)) {
            wp_send_json_error(array('message' => 'Événement non autorisé.'));
        }

        if ($event === 'test_started') {
            DISC_Database::log_event('test_started', array(
                'session_token' => $session_token
            ), $session_token);
        } else {
            DISC_Database::log_event('question_answered', array(
                'question_id'   => intval($_POST['question_id'] ?? 0),
                'session_token' => $session_token
            ), $session_token);
        }

        wp_send_json_success(array('message' => 'OK'));
    }
    
    /**
     * Gère la soumission du formulaire de contact et calcule les résultats
     */
    public static function handle_contact_submission() {
        // Vérifie le nonce
        DISC_Security::verify_ajax_nonce();
        
        // Vérifie le rate limiting
        if (!DISC_Security::check_rate_limit()) {
            wp_send_json_error(array(
                'message' => __('Vous avez dépassé le nombre maximum de tentatives. Veuillez réessayer dans une heure.', 'disc-test')
            ));
        }
        
        // Récupère et valide les données du formulaire
        $contact_data = array(
            'email' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'first_name' => sanitize_text_field(wp_unslash($_POST['first_name'] ?? '')),
            'last_name' => sanitize_text_field(wp_unslash($_POST['last_name'] ?? '')),
            'company' => sanitize_text_field(wp_unslash($_POST['company'] ?? '')),
            'position' => sanitize_text_field(wp_unslash($_POST['position'] ?? '')),
            'consent' => (!empty($_POST['consent']) && absint(wp_unslash($_POST['consent'])) === 1) ? 1 : 0
        );
        
        // Valide les données
        $errors = DISC_Security::validate_contact_data($contact_data);
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => implode(' ', $errors)
            ));
        }
        
        // Récupère les réponses du test
        $responses = json_decode(wp_unslash($_POST['responses'] ?? '[]'), true);

        if (empty($responses) || !is_array($responses)) {
            wp_send_json_error(array(
                'message' => __('Aucune réponse trouvée. Veuillez recommencer le test.', 'disc-test')
            ));
        }

        // Charge les questions officielles depuis la DB pour valider l'intégrité
        $official_questions = DISC_Database::get_questions();
        $official_ids       = array_map(function($q) { return (int) $q->id; }, $official_questions);
        $expected_count     = count($official_ids);

        if (count($responses) !== $expected_count) {
            wp_send_json_error(array(
                'message' => __('Nombre de réponses incorrect. Veuillez recommencer le test.', 'disc-test')
            ));
        }

        // Valide chaque réponse avant de calculer quoi que ce soit
        $seen_ids        = array();
        $valid_dimensions = array('D', 'I', 'S', 'C');

        foreach ($responses as $response) {
            $question_id = intval($response['question_id'] ?? 0);
            $most_like   = strtoupper(sanitize_text_field($response['most_like'] ?? ''));
            $least_like  = strtoupper(sanitize_text_field($response['least_like'] ?? ''));

            // La question doit exister officiellement
            if (!in_array($question_id, $official_ids, true)) {
                wp_send_json_error(array(
                    'message' => __('Données de réponse invalides. Veuillez recommencer le test.', 'disc-test')
                ));
            }

            // Chaque question ne peut être répondue qu'une seule fois
            if (in_array($question_id, $seen_ids, true)) {
                wp_send_json_error(array(
                    'message' => __('Données de réponse en double. Veuillez recommencer le test.', 'disc-test')
                ));
            }
            $seen_ids[] = $question_id;

            // Les dimensions doivent être valides
            if (!in_array($most_like, $valid_dimensions, true) || !in_array($least_like, $valid_dimensions, true)) {
                wp_send_json_error(array(
                    'message' => __('Données de réponse invalides. Veuillez recommencer le test.', 'disc-test')
                ));
            }

            // most_like et least_like doivent être différents
            if ($most_like === $least_like) {
                wp_send_json_error(array(
                    'message' => __('Données de réponse invalides. Veuillez recommencer le test.', 'disc-test')
                ));
            }
        }

        // Calcule les scores DISC
        $scores         = array('D' => 0, 'I' => 0, 'S' => 0, 'C' => 0);
        $response_times = array();

        foreach ($responses as $response) {
            $most_like  = strtoupper(sanitize_text_field($response['most_like'] ?? ''));
            $least_like = strtoupper(sanitize_text_field($response['least_like'] ?? ''));

            // +2 points pour "le plus", -1 point pour "le moins"
            $scores[$most_like] += 2;
            $scores[$least_like] -= 1;

            $response_times[] = floatval($response['response_time'] ?? 0);
        }
        
        // Normalise les scores entre 0 et 100
        $min_score = min($scores);
        $max_score = max($scores);
        $range = $max_score - $min_score;
        
        if ($range > 0) {
            foreach ($scores as $dim => $score) {
                $scores[$dim] = round((($score - $min_score) / $range) * 100);
            }
        }
        
        // Calcule les métriques de qualité
        $consistency_score = DISC_Security::calculate_consistency_score($responses);
        $average_response_time = !empty($response_times) ? array_sum($response_times) / count($response_times) : 0;
        $total_time = array_sum($response_times);
        
        // Détermine le profil
        $profile_type = DISC_Renderer::determine_profile_type($scores);
        
        // Génère un token de session unique
        $session_token = DISC_Security::generate_session_token();
        
        // Enregistre le résultat
        $result_data = array(
            'session_token' => $session_token,
            'email' => $contact_data['email'],
            'first_name' => $contact_data['first_name'],
            'last_name' => $contact_data['last_name'],
            'company' => $contact_data['company'],
            'position' => $contact_data['position'],
            'score_d' => $scores['D'],
            'score_i' => $scores['I'],
            'score_s' => $scores['S'],
            'score_c' => $scores['C'],
            'profile_type' => $profile_type,
            'consistency_score' => $consistency_score,
            'average_response_time' => $average_response_time,
            'total_time' => $total_time,
            'consent_given' => $contact_data['consent']
        );
        
        $result_id = DISC_Database::save_result($result_data);
        
        if (!$result_id) {
            wp_send_json_error(array(
                'message' => __('Erreur lors de l\'enregistrement des résultats. Veuillez réessayer.', 'disc-test')
            ));
        }
        
        // Enregistre les réponses détaillées
        $detailed_responses = array();
        foreach ($responses as $response) {
            $detailed_responses[] = array(
                'result_id' => $result_id,
                'question_id' => intval($response['question_id']),
                'most_like' => $response['most_like'],
                'least_like' => $response['least_like'],
                'response_time' => floatval($response['response_time'])
            );
        }
        DISC_Database::save_responses($session_token, $detailed_responses);
        
        // Log l'événement
        DISC_Database::log_event('test_completed', array(
            'profile_type' => $profile_type,
            'consistency_score' => $consistency_score
        ), $session_token);
        
        // Envoie l'email avec les résultats
        DISC_Email::send_results_email($contact_data, $scores, $profile_type);
        
        // Intégration CRM — hook WordPress pour extensions tierces
        do_action('disc_test_completed', $contact_data, $scores, $profile_type);

        // Webhook CRM (URL configurée dans les paramètres du plugin)
        $webhook_url = get_option('disc_crm_webhook', '');
        if (!empty($webhook_url)) {
            $payload = array(
                'email'             => $contact_data['email'],
                'first_name'        => $contact_data['first_name'],
                'last_name'         => $contact_data['last_name'],
                'company'           => $contact_data['company'],
                'position'          => $contact_data['position'],
                'profile_type'      => $profile_type,
                'score_d'           => $scores['D'],
                'score_i'           => $scores['I'],
                'score_s'           => $scores['S'],
                'score_c'           => $scores['C'],
                'consistency_score' => $consistency_score,
                'completed_at'      => current_time('c'),
                'tags'              => self::generate_crm_tags($profile_type, $scores, $consistency_score),
                'source'            => 'disc-test-wordpress',
                'test_version'      => DISC_TEST_VERSION,
                'session_token'     => $session_token,
                'consent_given'     => (bool) $contact_data['consent'],
                'locale'            => get_locale(),
            );

            wp_safe_remote_post($webhook_url, array(
                'headers'   => array('Content-Type' => 'application/json'),
                'body'      => json_encode($payload),
                'timeout'   => 5,
                'blocking'  => false,
            ));
        }
        
        // Retourne les résultats
        $profile_description = DISC_Renderer::get_profile_description($profile_type, $scores);
        
        wp_send_json_success(array(
            'scores' => $scores,
            'profile_type' => $profile_type,
            'profile_description' => $profile_description,
            'consistency_score' => $consistency_score,
            'show_consistency_warning' => $consistency_score < 60,
            'session_token' => $session_token
        ));
    }
}