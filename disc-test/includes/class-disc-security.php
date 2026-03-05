<?php
/**
 * Classe DISC_Security
 * Gère tous les aspects de sécurité du plugin DISC Test
 * 
 * @package DISC_Test
 * @since 1.0.0
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class DISC_Security {
    
    /**
     * Vérifie le nonce pour les requêtes AJAX
     */
    public static function verify_ajax_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'disc_test_nonce')) {
            wp_send_json_error(array(
                'message' => __('Erreur de sécurité. Veuillez recharger la page.', 'disc-test')
            ));
            exit;
        }
    }
    
    /**
     * Obtient l'adresse IP du client de manière sécurisée
     */
    public static function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        // Valide que c'est bien une IP
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
    
    /**
     * Vérifie le rate limiting basé sur l'IP
     * Limite à 3 tests par heure par IP
     */
    public static function check_rate_limit() {
        $ip = self::get_client_ip();
        $transient_key = 'disc_rate_limit_' . md5($ip);
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            // Première tentative dans cette fenêtre d'une heure
            set_transient($transient_key, 1, HOUR_IN_SECONDS);
            return true;
        }
        
        if ($attempts >= 3) {
            return false;
        }
        
        // Incrémente le compteur
        set_transient($transient_key, $attempts + 1, HOUR_IN_SECONDS);
        return true;
    }
    
    /**
     * Génère un token de session unique et sécurisé
     */
    public static function generate_session_token() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Valide les données du formulaire de contact
     */
    public static function validate_contact_data($data) {
        $errors = array();
        
        // Email requis et valide
        if (empty($data['email']) || !is_email($data['email'])) {
            $errors[] = __('Adresse email invalide.', 'disc-test');
        }
        
        // Prénom requis
        if (empty($data['first_name']) || strlen($data['first_name']) < 2) {
            $errors[] = __('Le prénom est requis (minimum 2 caractères).', 'disc-test');
        }
        
        // Nom requis
        if (empty($data['last_name']) || strlen($data['last_name']) < 2) {
            $errors[] = __('Le nom est requis (minimum 2 caractères).', 'disc-test');
        }
        
        // Consentement RGPD requis
        if (empty($data['consent'])) {
            $errors[] = __('Vous devez accepter la politique de confidentialité.', 'disc-test');
        }
        
        return $errors;
    }
    
    /**
     * Calcule le score de cohérence des réponses
     * Retourne un score entre 0 et 100
     */
    public static function calculate_consistency_score($responses) {
        // Paires de questions qui mesurent des traits similaires
        // Ces paires doivent avoir des réponses cohérentes
        $consistency_pairs = array(
            array(1, 13),  // Leadership/Direction
            array(2, 17),  // Défis/Audace
            array(3, 24),  // Focus objectifs/Efficacité
            array(5, 10),  // Positivité
            array(6, 15),  // Contrôle/Résultats immédiats
            array(8, 22),  // Action/Initiative
            array(11, 23), // Risques/Compétition
            array(14, 26), // Communication ferme
            array(16, 20), // Chaleur sociale
            array(19, 25)  // Relations/Rapport
        );
        
        $total_pairs = count($consistency_pairs);
        $consistent_pairs = 0;
        
        foreach ($consistency_pairs as $pair) {
            $q1 = $responses[$pair[0] - 1] ?? null;
            $q2 = $responses[$pair[1] - 1] ?? null;
            
            if ($q1 && $q2) {
                // Compare si les mêmes dimensions sont choisies
                if ($q1['most_like'] === $q2['most_like'] || 
                    $q1['least_like'] === $q2['least_like']) {
                    $consistent_pairs++;
                }
            }
        }
        
        return round(($consistent_pairs / $total_pairs) * 100, 2);
    }
    
    /**
     * Vérifie la validité des temps de réponse
     * Trop rapide ou trop lent peut indiquer un problème
     */
    public static function validate_response_times($response_times) {
        $warnings = array();
        $too_fast = 0;
        $too_slow = 0;
        
        foreach ($response_times as $time) {
            if ($time < 2) {
                $too_fast++;
            }
            if ($time > 180) { // Plus de 3 minutes par question
                $too_slow++;
            }
        }
        
        if ($too_fast > count($response_times) * 0.3) {
            $warnings[] = 'many_fast_responses';
        }
        
        if ($too_slow > 5) {
            $warnings[] = 'many_slow_responses';
        }
        
        return $warnings;
    }
    
    /**
     * Nettoie et crypte l'email avant stockage
     * Utilise une clé définie dans wp-config.php
     */
    public static function encrypt_email($email) {
        if (!defined('DISC_ENCRYPTION_KEY')) {
            // Si pas de clé définie, retourne l'email en clair avec avertissement
            error_log('DISC Test: DISC_ENCRYPTION_KEY non définie dans wp-config.php');
            return $email;
        }
        
        $method = 'AES-256-CBC';
        $key = substr(hash('sha256', DISC_ENCRYPTION_KEY), 0, 32);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        
        $encrypted = openssl_encrypt($email, $method, $key, 0, $iv);
        
        // Retourne l'email crypté avec l'IV encodé en base64
        return base64_encode($encrypted . '::' . $iv);
    }
    
    /**
     * Décrypte un email
     */
    public static function decrypt_email($encrypted_email) {
        if (!defined('DISC_ENCRYPTION_KEY')) {
            return $encrypted_email;
        }
        
        $method = 'AES-256-CBC';
        $key = substr(hash('sha256', DISC_ENCRYPTION_KEY), 0, 32);
        
        list($encrypted_data, $iv) = explode('::', base64_decode($encrypted_email), 2);
        
        return openssl_decrypt($encrypted_data, $method, $key, 0, $iv);
    }
}