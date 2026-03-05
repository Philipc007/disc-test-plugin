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
     * Gère la soumission d'une réponse à une question
     */
    public static function handle_response_submission() {
        // Vérifie le nonce
        DISC_Security::verify_ajax_nonce();
        
        // Log l'événement
        DISC_Database::log_event('question_answered', array(
            'question_id' => intval($_POST['question_id'] ?? 0),
            'session_token' => sanitize_text_field($_POST['session_token'] ?? '')
        ));
        
        wp_send_json_success(array(
            'message' => __('Réponse enregistrée', 'disc-test')
        ));
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
            'email' => sanitize_email($_POST['email'] ?? ''),
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
            'company' => sanitize_text_field($_POST['company'] ?? ''),
            'position' => sanitize_text_field($_POST['position'] ?? ''),
            'consent' => isset($_POST['consent']) ? 1 : 0
        );
        
        // Valide les données
        $errors = DISC_Security::validate_contact_data($contact_data);
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => implode(' ', $errors)
            ));
        }
        
        // Récupère les réponses du test
        $responses = json_decode(stripslashes($_POST['responses'] ?? '[]'), true);
        
        if (empty($responses)) {
            wp_send_json_error(array(
                'message' => __('Aucune réponse trouvée. Veuillez recommencer le test.', 'disc-test')
            ));
        }
        
        // Calcule les scores DISC
        $scores = array('D' => 0, 'I' => 0, 'S' => 0, 'C' => 0);
        $response_times = array();
        
        foreach ($responses as $response) {
            // +2 points pour "le plus", -1 point pour "le moins"
            $scores[$response['most_like']] += 2;
            $scores[$response['least_like']] -= 1;
            
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
            'total_time' => $total_time
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
        
        // Intégration CRM (si configurée)
        do_action('disc_test_completed', $contact_data, $scores, $profile_type);
        
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