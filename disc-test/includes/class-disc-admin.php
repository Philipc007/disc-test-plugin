<?php
/**
 * Classe DISC_Admin
 * Gère l'interface d'administration WordPress du plugin
 * 
 * @package DISC_Test
 * @since 1.0.0
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class DISC_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Ajoute les pages d'administration
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Test DISC', 'disc-test'),
            __('Test DISC', 'disc-test'),
            'manage_options',
            'disc-test',
            array($this, 'render_results_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'disc-test',
            __('Résultats', 'disc-test'),
            __('Résultats', 'disc-test'),
            'manage_options',
            'disc-test',
            array($this, 'render_results_page')
        );
        
        add_submenu_page(
            'disc-test',
            __('Statistiques', 'disc-test'),
            __('Statistiques', 'disc-test'),
            'manage_options',
            'disc-test-stats',
            array($this, 'render_stats_page')
        );
        
        add_submenu_page(
            'disc-test',
            __('Questions', 'disc-test'),
            __('Questions', 'disc-test'),
            'manage_options',
            'disc-test-questions',
            array($this, 'render_questions_page')
        );
        
        add_submenu_page(
            'disc-test',
            __('Paramètres', 'disc-test'),
            __('Paramètres', 'disc-test'),
            'manage_options',
            'disc-test-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Page des résultats
     */
    public function render_results_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'disc-test'));
        }
        
        // Log l'accès admin
        DISC_Database::log_event('admin_access_results');
        
        $results = DISC_Database::get_all_results(100, 0);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Résultats du Test DISC', 'disc-test'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'disc-test'); ?></th>
                        <th><?php _e('Nom', 'disc-test'); ?></th>
                        <th><?php _e('Email', 'disc-test'); ?></th>
                        <th><?php _e('Entreprise', 'disc-test'); ?></th>
                        <th><?php _e('Profil', 'disc-test'); ?></th>
                        <th><?php _e('Scores', 'disc-test'); ?></th>
                        <th><?php _e('Cohérence', 'disc-test'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="7"><?php _e('Aucun résultat disponible.', 'disc-test'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($result['completed_at']))); ?></td>
                                <td><?php echo esc_html($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                <td><?php echo esc_html($result['email']); ?></td>
                                <td><?php echo esc_html($result['company']); ?></td>
                                <td><strong><?php echo esc_html($result['profile_type']); ?></strong></td>
                                <td>
                                    D:<?php echo $result['score_d']; ?> 
                                    I:<?php echo $result['score_i']; ?> 
                                    S:<?php echo $result['score_s']; ?> 
                                    C:<?php echo $result['score_c']; ?>
                                </td>
                                <td>
                                    <?php 
                                    $consistency = $result['consistency_score'];
                                    $color = $consistency >= 70 ? 'green' : ($consistency >= 50 ? 'orange' : 'red');
                                    ?>
                                    <span style="color: <?php echo $color; ?>;">
                                        <?php echo round($consistency, 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p>
                <a href="<?php echo admin_url('admin.php?page=disc-test&action=export'); ?>" class="button">
                    <?php _e('Exporter en CSV', 'disc-test'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Page des statistiques
     */
    public function render_stats_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'disc-test'));
        }
        
        $stats = DISC_Database::get_statistics();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Statistiques DISC', 'disc-test'); ?></h1>
            
            <div class="disc-stats-dashboard">
                <div class="disc-stat-card">
                    <h3><?php _e('Tests totaux', 'disc-test'); ?></h3>
                    <p class="disc-stat-number"><?php echo number_format_i18n($stats['total_tests']); ?></p>
                </div>
                
                <div class="disc-stat-card">
                    <h3><?php _e('Tests (30 derniers jours)', 'disc-test'); ?></h3>
                    <p class="disc-stat-number"><?php echo number_format_i18n($stats['tests_last_30_days']); ?></p>
                </div>
                
                <div class="disc-stat-card">
                    <h3><?php _e('Cohérence moyenne', 'disc-test'); ?></h3>
                    <p class="disc-stat-number"><?php echo round($stats['average_consistency'], 1); ?>%</p>
                </div>
                
                <div class="disc-stat-card">
                    <h3><?php _e('Temps moyen', 'disc-test'); ?></h3>
                    <p class="disc-stat-number"><?php echo round($stats['average_completion_time'] / 60, 1); ?> min</p>
                </div>
            </div>
            
            <h2><?php _e('Distribution des profils', 'disc-test'); ?></h2>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th><?php _e('Profil', 'disc-test'); ?></th>
                        <th><?php _e('Nombre', 'disc-test'); ?></th>
                        <th><?php _e('Pourcentage', 'disc-test'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['profile_distribution'] as $profile): ?>
                        <tr>
                            <td><strong><?php echo esc_html($profile['profile_type']); ?></strong></td>
                            <td><?php echo number_format_i18n($profile['count']); ?></td>
                            <td><?php echo round(($profile['count'] / $stats['total_tests']) * 100, 1); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Page de gestion des questions
     */
    public function render_questions_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'disc-test'));
        }
        
        $questions = DISC_Database::get_questions();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Questions du Test DISC', 'disc-test'); ?></h1>
            <p><?php _e('Gérez les questions de votre test DISC. Les modifications prendront effet immédiatement.', 'disc-test'); ?></p>
            
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th width="5%"><?php _e('#', 'disc-test'); ?></th>
                        <th width="23%"><?php _e('D - Dominance', 'disc-test'); ?></th>
                        <th width="23%"><?php _e('I - Influence', 'disc-test'); ?></th>
                        <th width="23%"><?php _e('S - Stabilité', 'disc-test'); ?></th>
                        <th width="23%"><?php _e('C - Conformité', 'disc-test'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><?php echo $q['question_order']; ?></td>
                            <td><?php echo esc_html($q['statement_d']); ?></td>
                            <td><?php echo esc_html($q['statement_i']); ?></td>
                            <td><?php echo esc_html($q['statement_s']); ?></td>
                            <td><?php echo esc_html($q['statement_c']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Page des paramètres
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions nécessaires.', 'disc-test'));
        }
        
        // Sauvegarde des paramètres
        if (isset($_POST['disc_save_settings'])) {
            check_admin_referer('disc_settings_save');
            
            update_option('disc_email_subject', sanitize_text_field($_POST['email_subject']));
            update_option('disc_crm_webhook', esc_url_raw($_POST['crm_webhook']));
            
            echo '<div class="notice notice-success"><p>' . __('Paramètres enregistrés.', 'disc-test') . '</p></div>';
        }
        
        $email_subject = get_option('disc_email_subject', __('Votre profil DISC', 'disc-test'));
        $crm_webhook = get_option('disc_crm_webhook', '');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Paramètres du Test DISC', 'disc-test'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('disc_settings_save'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="email_subject"><?php _e('Sujet de l\'email', 'disc-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="email_subject" name="email_subject" value="<?php echo esc_attr($email_subject); ?>" class="regular-text">
                            <p class="description"><?php _e('Le sujet de l\'email envoyé aux participants.', 'disc-test'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="crm_webhook"><?php _e('Webhook CRM', 'disc-test'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="crm_webhook" name="crm_webhook" value="<?php echo esc_attr($crm_webhook); ?>" class="regular-text">
                            <p class="description"><?php _e('URL webhook pour envoyer automatiquement les leads à votre CRM.', 'disc-test'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="disc_save_settings" class="button button-primary" value="<?php _e('Enregistrer les modifications', 'disc-test'); ?>">
                </p>
            </form>
            
            <hr>
            
            <h2><?php _e('Configuration de sécurité', 'disc-test'); ?></h2>
            <p><?php _e('Pour renforcer la sécurité, ajoutez cette ligne à votre fichier wp-config.php :', 'disc-test'); ?></p>
            <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #667eea;">
define('DISC_ENCRYPTION_KEY', '<?php echo bin2hex(random_bytes(16)); ?>');
            </pre>
            <p><?php _e('Cette clé servira à chiffrer les emails dans la base de données.', 'disc-test'); ?></p>
        </div>
        <?php
    }
}