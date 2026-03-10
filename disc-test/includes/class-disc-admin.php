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
        add_action('admin_init', array($this, 'handle_export'));
    }
    
    /**
     * Gère l'export CSV des résultats
     */
    public function handle_export() {
        if (!isset($_GET['page'], $_GET['action']) ||
            $_GET['page'] !== 'disc-test' ||
            $_GET['action'] !== 'export') {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Permissions insuffisantes.', 'disc-test'));
        }

        check_admin_referer('disc_export_csv');

        $results = DISC_Database::get_all_results(9999, 0);

        $filename = 'disc-resultats-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM UTF-8 pour Excel
        fputs($output, "\xEF\xBB\xBF");

        // En-têtes colonnes
        fputcsv($output, array(
            'Date', 'Prénom', 'Nom', 'Email', 'Entreprise', 'Poste',
            'Profil', 'Score D', 'Score I', 'Score S', 'Score C',
            'Cohérence (%)', 'Temps moyen/question (s)', 'Temps total (s)'
        ), ';');

        foreach ($results as $r) {
            fputcsv($output, array(
                date('d/m/Y H:i', strtotime($r['completed_at'])),
                $r['first_name'],
                $r['last_name'],
                DISC_Security::decrypt_email($r['email']),
                $r['company'],
                $r['position'],
                $r['profile_type'],
                $r['score_d'],
                $r['score_i'],
                $r['score_s'],
                $r['score_c'],
                round($r['consistency_score'], 1),
                round($r['average_response_time'], 1),
                $r['total_time']
            ), ';');
        }

        fclose($output);
        exit;
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

        // Traitement du renvoi d'email
        if (isset($_GET['action']) && $_GET['action'] === 'resend_email' && isset($_GET['result_id'])) {
            check_admin_referer('disc_resend_email_' . intval($_GET['result_id']));

            $result = DISC_Database::get_result_by_id(intval($_GET['result_id']));

            if ($result) {
                $contact_data = array(
                    'email'      => DISC_Security::decrypt_email($result['email']),
                    'first_name' => $result['first_name'],
                    'last_name'  => $result['last_name'],
                    'company'    => $result['company'],
                    'position'   => $result['position'],
                );
                $scores = array(
                    'D' => $result['score_d'],
                    'I' => $result['score_i'],
                    'S' => $result['score_s'],
                    'C' => $result['score_c'],
                );

                $sent = DISC_Email::send_results_email($contact_data, $scores, $result['profile_type']);
                DISC_Database::log_event('email_resent', array('result_id' => $result['id'], 'success' => $sent));

                $display_email = esc_html(DISC_Security::decrypt_email($result['email']));
                $notice_class  = $sent ? 'notice-success' : 'notice-error';
                $notice_msg    = $sent
                    ? sprintf(__('Email renvoyé avec succès à %s.', 'disc-test'), $display_email)
                    : sprintf(__('Échec du renvoi à %s. Vérifiez votre configuration SMTP.', 'disc-test'), $display_email);

                echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>' . $notice_msg . '</p></div>';
            }
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
                        <th><?php _e('Action', 'disc-test'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($results)): ?>
                        <tr>
                            <td colspan="8"><?php _e('Aucun résultat disponible.', 'disc-test'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($results as $result): ?>
                            <?php
                            $resend_url = wp_nonce_url(
                                admin_url('admin.php?page=disc-test&action=resend_email&result_id=' . $result['id']),
                                'disc_resend_email_' . $result['id']
                            );
                            $consistency = floatval($result['consistency_score']);
                            $color = $consistency >= 70 ? 'green' : ($consistency >= 50 ? 'orange' : 'red');
                            ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($result['completed_at']))); ?></td>
                                <td><?php echo esc_html($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                <td><?php echo esc_html(DISC_Security::decrypt_email($result['email'])); ?></td>
                                <td><?php echo esc_html($result['company']); ?></td>
                                <td><strong><?php echo esc_html($result['profile_type']); ?></strong></td>
                                <td>
                                    D:<?php echo intval($result['score_d']); ?>
                                    I:<?php echo intval($result['score_i']); ?>
                                    S:<?php echo intval($result['score_s']); ?>
                                    C:<?php echo intval($result['score_c']); ?>
                                </td>
                                <td>
                                    <span style="color: <?php echo esc_attr($color); ?>;">
                                        <?php echo round($consistency, 1); ?>%
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url($resend_url); ?>"
                                       class="button button-small"
                                       onclick="return confirm('<?php echo esc_js(__('Renvoyer l\'email de résultats à ', 'disc-test') . DISC_Security::decrypt_email($result['email']) . ' ?'); ?>')">
                                        <?php _e('Renvoyer email', 'disc-test'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=disc-test&action=export'), 'disc_export_csv')); ?>" class="button">
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
                            <td><?php echo $stats['total_tests'] > 0 ? round(($profile['count'] / $stats['total_tests']) * 100, 1) : 0; ?>%</td>
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

        $action      = isset($_GET['action']) ? $_GET['action'] : 'list';
        $question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;

        // Traitement de la sauvegarde
        if ($action === 'save' && $question_id && isset($_POST['disc_edit_question_nonce'])) {
            check_admin_referer('disc_edit_question_' . $question_id, 'disc_edit_question_nonce');

            DISC_Database::update_question($question_id, array(
                'statement_d' => wp_unslash($_POST['statement_d'] ?? ''),
                'statement_i' => wp_unslash($_POST['statement_i'] ?? ''),
                'statement_s' => wp_unslash($_POST['statement_s'] ?? ''),
                'statement_c' => wp_unslash($_POST['statement_c'] ?? ''),
            ));

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Question mise à jour.', 'disc-test') . '</p></div>';
            $action = 'list';
        }

        // Formulaire d'édition
        if ($action === 'edit' && $question_id) {
            $q = DISC_Database::get_question($question_id);
            if (!$q) {
                echo '<div class="notice notice-error"><p>' . __('Question introuvable.', 'disc-test') . '</p></div>';
            } else {
                $save_url = admin_url('admin.php?page=disc-test-questions&action=save&question_id=' . $question_id);
                ?>
                <div class="wrap">
                    <h1>
                        <?php printf(__('Modifier la question %d', 'disc-test'), $q['question_order']); ?>
                        <a href="<?php echo admin_url('admin.php?page=disc-test-questions'); ?>" class="page-title-action">
                            <?php _e('← Retour à la liste', 'disc-test'); ?>
                        </a>
                    </h1>

                    <form method="post" action="<?php echo esc_url($save_url); ?>">
                        <?php wp_nonce_field('disc_edit_question_' . $question_id, 'disc_edit_question_nonce'); ?>

                        <table class="form-table">
                            <?php
                            $dimensions = array(
                                'D' => array('label' => __('D — Dominance', 'disc-test'),  'color' => '#dc2626', 'field' => 'statement_d'),
                                'I' => array('label' => __('I — Influence', 'disc-test'),   'color' => '#eab308', 'field' => 'statement_i'),
                                'S' => array('label' => __('S — Stabilité', 'disc-test'),   'color' => '#22c55e', 'field' => 'statement_s'),
                                'C' => array('label' => __('C — Conformité', 'disc-test'),  'color' => '#3b82f6', 'field' => 'statement_c'),
                            );
                            foreach ($dimensions as $dim => $meta):
                            ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo $meta['field']; ?>" style="color: <?php echo $meta['color']; ?>; font-weight: bold;">
                                        <?php echo $meta['label']; ?>
                                    </label>
                                </th>
                                <td>
                                    <textarea
                                        id="<?php echo $meta['field']; ?>"
                                        name="<?php echo $meta['field']; ?>"
                                        rows="3"
                                        class="large-text"
                                    ><?php echo esc_textarea($q[$meta['field']]); ?></textarea>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>

                        <p class="submit">
                            <input type="submit" class="button button-primary" value="<?php _e('Enregistrer', 'disc-test'); ?>">
                            <a href="<?php echo admin_url('admin.php?page=disc-test-questions'); ?>" class="button">
                                <?php _e('Annuler', 'disc-test'); ?>
                            </a>
                        </p>
                    </form>
                </div>
                <?php
                return;
            }
        }

        // Liste des questions
        $questions = DISC_Database::get_questions();
        ?>
        <div class="wrap">
            <h1><?php _e('Questions du Test DISC', 'disc-test'); ?></h1>
            <p><?php _e('Cliquez sur "Modifier" pour éditer une question. Les modifications sont appliquées immédiatement.', 'disc-test'); ?></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="4%"><?php _e('#', 'disc-test'); ?></th>
                        <th width="22%" style="color:#dc2626;"><?php _e('D — Dominance', 'disc-test'); ?></th>
                        <th width="22%" style="color:#eab308;"><?php _e('I — Influence', 'disc-test'); ?></th>
                        <th width="22%" style="color:#22c55e;"><?php _e('S — Stabilité', 'disc-test'); ?></th>
                        <th width="22%" style="color:#3b82f6;"><?php _e('C — Conformité', 'disc-test'); ?></th>
                        <th width="8%"><?php _e('Action', 'disc-test'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $q): ?>
                        <tr>
                            <td><strong><?php echo $q['question_order']; ?></strong></td>
                            <td><?php echo esc_html($q['statement_d']); ?></td>
                            <td><?php echo esc_html($q['statement_i']); ?></td>
                            <td><?php echo esc_html($q['statement_s']); ?></td>
                            <td><?php echo esc_html($q['statement_c']); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=disc-test-questions&action=edit&question_id=' . $q['id'])); ?>" class="button button-small">
                                    <?php _e('Modifier', 'disc-test'); ?>
                                </a>
                            </td>
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

            update_option('disc_email_subject', sanitize_text_field(wp_unslash($_POST['email_subject'] ?? '')));
            update_option('disc_crm_webhook', esc_url_raw(wp_unslash($_POST['crm_webhook'] ?? '')));
            update_option('disc_tag_prefix', sanitize_key(wp_unslash($_POST['tag_prefix'] ?? 'disc')));
            update_option('disc_email_footer_enabled', isset($_POST['email_footer_enabled']) ? 1 : 0);
            update_option('disc_email_footer_content', wp_kses_post(wp_unslash($_POST['email_footer_content'] ?? '')));
            update_option('disc_cta_enabled', isset($_POST['cta_enabled']) ? 1 : 0);
            update_option('disc_cta_title', sanitize_text_field(wp_unslash($_POST['cta_title'] ?? '')));
            update_option('disc_cta_body', sanitize_textarea_field(wp_unslash($_POST['cta_body'] ?? '')));
            update_option('disc_cta_button_text', sanitize_text_field(wp_unslash($_POST['cta_button_text'] ?? '')));
            update_option('disc_cta_button_url', esc_url_raw(wp_unslash($_POST['cta_button_url'] ?? '')));

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Paramètres enregistrés.', 'disc-test') . '</p></div>';
        }

        // Action : réinitialiser la banque de questions
        if (isset($_POST['disc_reset_questions'])) {
            check_admin_referer('disc_reset_questions');
            $count = DISC_Database::reset_questions();
            echo '<div class="notice notice-success is-dismissible"><p>' .
                 sprintf(__('Banque de questions réinitialisée : %d blocs chargés.', 'disc-test'), $count) .
                 '</p></div>';
        }

        // Action : supprimer tous les résultats
        if (isset($_POST['disc_reset_test_data'])) {
            check_admin_referer('disc_reset_test_data');
            DISC_Database::reset_test_data();
            echo '<div class="notice notice-warning is-dismissible"><p>' .
                 __('Tous les résultats, réponses et logs ont été supprimés.', 'disc-test') .
                 '</p></div>';
        }

        $email_subject         = get_option('disc_email_subject', __('Votre profil DISC : {profil}', 'disc-test'));
        $crm_webhook           = get_option('disc_crm_webhook', '');
        $tag_prefix            = get_option('disc_tag_prefix', 'disc');
        $email_footer_enabled  = get_option('disc_email_footer_enabled', 1);
        $email_footer_default  = "Conformément au RGPD, vous disposez d'un droit d'accès, de rectification et d'effacement de vos données personnelles.\n"
                               . "Pour exercer ces droits ou vous désinscrire, contactez-nous : {email_admin}\n\n"
                               . "Cet email vous a été envoyé suite à votre participation au test DISC sur {site_name}.";
        $email_footer_content  = get_option('disc_email_footer_content', $email_footer_default);
        $cta_enabled           = get_option('disc_cta_enabled', 0);
        $cta_title             = get_option('disc_cta_title', '');
        $cta_body              = get_option('disc_cta_body', '');
        $cta_button_text       = get_option('disc_cta_button_text', '');
        $cta_button_url        = get_option('disc_cta_button_url', '');
        
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
                            <p class="description"><?php _e('Le sujet de l\'email envoyé aux participants. Utilisez <code>{profil}</code> pour insérer le profil DISC (ex: DI, S…).', 'disc-test'); ?></p>
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

                    <tr>
                        <th scope="row">
                            <label for="tag_prefix"><?php _e('Préfixe des tags CRM', 'disc-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="tag_prefix" name="tag_prefix" value="<?php echo esc_attr($tag_prefix); ?>" class="small-text" pattern="[a-z0-9\-]+" placeholder="disc">
                            <p class="description">
                                <?php _e('Préfixe utilisé pour tous les tags envoyés au CRM. Uniquement lettres minuscules, chiffres et tirets.', 'disc-test'); ?><br>
                                <?php
                                $ex = esc_html($tag_prefix ?: 'disc');
                                printf(
                                    __('Exemples avec le préfixe actuel : <code>%1$s</code>, <code>%1$s-di</code>, <code>%1$s-d</code>, <code>%1$s-consistent</code>', 'disc-test'),
                                    $ex
                                );
                                ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Pied de page email', 'disc-test'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="email_footer_enabled" id="email_footer_enabled" value="1" <?php checked(1, $email_footer_enabled); ?>>
                                <?php _e('Activer le pied de page légal dans les emails', 'disc-test'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr id="email_footer_row" <?php echo $email_footer_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="email_footer_content"><?php _e('Contenu du pied de page', 'disc-test'); ?></label>
                        </th>
                        <td>
                            <textarea id="email_footer_content" name="email_footer_content" rows="6" class="large-text"><?php echo esc_textarea($email_footer_content); ?></textarea>
                            <p class="description">
                                <?php _e('Variables disponibles :', 'disc-test'); ?>
                                <code>{email_admin}</code> — <?php _e('adresse email de l\'administrateur', 'disc-test'); ?>,
                                <code>{site_name}</code> — <?php _e('nom du site', 'disc-test'); ?>,
                                <code>{first_name}</code> — <?php _e('prénom du participant', 'disc-test'); ?>,
                                <code>{profil}</code> — <?php _e('profil DISC (ex : DI)', 'disc-test'); ?>.<br>
                                <?php _e('Le texte est affiché tel quel dans le pied de page de chaque email de résultats.', 'disc-test'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2 style="margin-top:28px;"><?php _e('Bloc marketing (après résultats)', 'disc-test'); ?></h2>
                <p><?php _e('Bloc optionnel affiché après le profil DISC, sur la page de résultats et dans l\'email. Idéal pour proposer un accompagnement ou un appel de découverte.', 'disc-test'); ?></p>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Activer le bloc', 'disc-test'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cta_enabled" id="cta_enabled" value="1" <?php checked(1, $cta_enabled); ?>>
                                <?php _e('Afficher ce bloc après les résultats', 'disc-test'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr id="cta_fields_row" <?php echo $cta_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="cta_title"><?php _e('Titre', 'disc-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cta_title" name="cta_title" value="<?php echo esc_attr($cta_title); ?>" class="large-text" placeholder="<?php esc_attr_e('Ex : Allez plus loin avec un accompagnement personnalisé', 'disc-test'); ?>">
                            <p class="description"><?php _e('Titre principal du bloc. Affiché en évidence, sans balisage supplémentaire.', 'disc-test'); ?></p>
                        </td>
                    </tr>

                    <tr id="cta_body_row" <?php echo $cta_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="cta_body"><?php _e('Corps du message', 'disc-test'); ?></label>
                        </th>
                        <td>
                            <textarea id="cta_body" name="cta_body" rows="6" class="large-text" placeholder="<?php esc_attr_e('Votre texte ici...', 'disc-test'); ?>"><?php echo esc_textarea($cta_body); ?></textarea>
                            <p class="description">
                                <?php _e('Mini-markdown supporté :', 'disc-test'); ?>
                                <code># Sous-titre</code> — <?php _e('sous-titre (h4)', 'disc-test'); ?>,
                                <code>**gras**</code> — <?php _e('texte en gras', 'disc-test'); ?>,
                                <code>- élément</code> — <?php _e('élément de liste', 'disc-test'); ?>,
                                <code>[texte](url)</code> — <?php _e('lien', 'disc-test'); ?>.
                                <?php _e('Ligne vide = nouveau paragraphe.', 'disc-test'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr id="cta_button_row" <?php echo $cta_enabled ? '' : 'style="display:none;"'; ?>>
                        <th scope="row">
                            <label for="cta_button_text"><?php _e('Bouton d\'action', 'disc-test'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cta_button_text" name="cta_button_text" value="<?php echo esc_attr($cta_button_text); ?>" class="regular-text" placeholder="<?php esc_attr_e('Ex : Réserver mon appel découverte', 'disc-test'); ?>">
                            <input type="url" id="cta_button_url" name="cta_button_url" value="<?php echo esc_attr($cta_button_url); ?>" class="large-text" placeholder="https://" style="margin-top:6px;">
                            <p class="description"><?php _e('Laisser vides pour ne pas afficher de bouton.', 'disc-test'); ?></p>
                        </td>
                    </tr>
                </table>
                <script>
                document.getElementById('email_footer_enabled').addEventListener('change', function() {
                    document.getElementById('email_footer_row').style.display = this.checked ? '' : 'none';
                });
                document.getElementById('cta_enabled').addEventListener('change', function() {
                    var show = this.checked ? '' : 'none';
                    document.getElementById('cta_fields_row').style.display = show;
                    document.getElementById('cta_body_row').style.display   = show;
                    document.getElementById('cta_button_row').style.display = show;
                });
                </script>

                <p class="submit">
                    <input type="submit" name="disc_save_settings" class="button button-primary" value="<?php _e('Enregistrer les modifications', 'disc-test'); ?>">
                </p>
            </form>
            
            <hr>

            <h2><?php _e('Guide d\'intégration CRM', 'disc-test'); ?></h2>
            <p><?php _e('Après chaque test complété, deux canaux se déclenchent simultanément :', 'disc-test'); ?></p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0;">

                <div style="background:#f0f4ff;border-left:4px solid #667eea;padding:16px;border-radius:0 6px 6px 0;">
                    <h3 style="margin-top:0;">📌 Canal 1 — Hook WordPress</h3>
                    <p><strong>Pour :</strong> Bit Integrations (Pro), code custom</p>
                    <p>Le hook <code>disc_test_completed</code> se déclenche automatiquement. Aucune URL à configurer.</p>
                    <p><strong>Sur site pro :</strong> Bit Integrations → New Integration → Mautic<br>
                    <a href="https://bit-integrations.com/wp-docs/actions/mautic-integrations/" target="_blank">Documentation Bit Integrations →</a></p>
                </div>

                <div style="background:#f0fff4;border-left:4px solid #22c55e;padding:16px;border-radius:0 6px 6px 0;">
                    <h3 style="margin-top:0;">🔗 Canal 2 — HTTP Webhook</h3>
                    <p><strong>Pour :</strong> n8n, Make, Zapier, webhook.site</p>
                    <p>Renseignez l'URL ci-dessus. Un POST JSON est envoyé avec contact, scores et tags.</p>
                    <p><strong>En local :</strong> <code>docker run -p 5678:5678 docker.n8n.io/n8nio/n8n</code><br>
                    Puis créer un nœud Webhook dans n8n → copier l'URL → la coller ici.</p>
                </div>

            </div>

            <details style="margin:16px 0;">
                <summary style="cursor:pointer;font-weight:600;padding:8px 0;"><?php _e('📋 Voir le payload JSON envoyé au webhook', 'disc-test'); ?></summary>
                <pre style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:6px;overflow-x:auto;margin-top:10px;font-size:12px;"><?php echo esc_html(json_encode(array(
                    'email'             => 'john.doe@example.com',
                    'first_name'        => 'John',
                    'last_name'         => 'Doe',
                    'company'           => 'Acme Corp',
                    'position'          => 'Directeur',
                    'profile_type'      => 'DI',
                    'score_d'           => 88,
                    'score_i'           => 100,
                    'score_s'           => 0,
                    'score_c'           => 12,
                    'consistency_score' => 75.5,
                    'completed_at'      => '2026-03-08T17:58:00+01:00',
                    'tags'              => array(
                        $tag_prefix,
                        $tag_prefix . '-di',
                        $tag_prefix . '-d',
                        $tag_prefix . '-i',
                        $tag_prefix . '-consistent',
                    ),
                ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                <p style="color:#666;font-size:12px;"><?php _e('💡 Pour voir un vrai payload, utilisez webhook.site : collez une URL de webhook.site dans le champ ci-dessus et faites un test DISC.', 'disc-test'); ?></p>
            </details>

            <hr>

            <h2><?php _e('Configuration de sécurité', 'disc-test'); ?></h2>
            <p><?php _e('Pour renforcer la sécurité, ajoutez cette ligne à votre fichier wp-config.php :', 'disc-test'); ?></p>
            <pre style="background: #f5f5f5; padding: 15px; border-left: 4px solid #667eea;">
define('DISC_ENCRYPTION_KEY', '<?php echo bin2hex(random_bytes(16)); ?>');
            </pre>
            <p><?php _e('Cette clé servira à chiffrer les emails dans la base de données.', 'disc-test'); ?></p>

            <hr>
            <h2><?php _e('Maintenance', 'disc-test'); ?></h2>
            <p><?php _e('Ces actions sont irréversibles. Elles ne s\'exécutent que sur confirmation explicite.', 'disc-test'); ?></p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0;">

                <div style="background:#fff8e1;border-left:4px solid #f59e0b;padding:16px;border-radius:0 6px 6px 0;">
                    <h3 style="margin-top:0;"><?php _e('Réinitialiser la banque de questions', 'disc-test'); ?></h3>
                    <p><?php _e('Vide et recharge les 14 blocs de questions de la version courante. À utiliser après une mise à jour de la banque d\'items.', 'disc-test'); ?></p>
                    <p><strong><?php _e('N\'affecte pas les résultats existants.', 'disc-test'); ?></strong></p>
                    <form method="post" onsubmit="return confirm('<?php echo esc_js(__('Confirmer la réinitialisation de la banque de questions ?', 'disc-test')); ?>');">
                        <?php wp_nonce_field('disc_reset_questions'); ?>
                        <button type="submit" name="disc_reset_questions" class="button button-secondary">
                            <?php _e('Réinitialiser les questions', 'disc-test'); ?>
                        </button>
                    </form>
                </div>

                <div style="background:#fff0f0;border-left:4px solid #dc2626;padding:16px;border-radius:0 6px 6px 0;">
                    <h3 style="margin-top:0;"><?php _e('Supprimer tous les résultats', 'disc-test'); ?></h3>
                    <p><?php _e('Supprime définitivement tous les résultats de test, toutes les réponses détaillées et tous les logs d\'audit.', 'disc-test'); ?></p>
                    <p><strong><?php _e('⚠️ Opération irréversible — ne pas utiliser en production.', 'disc-test'); ?></strong></p>
                    <form method="post" onsubmit="return confirm('<?php echo esc_js(__('ATTENTION : Cette action est irréversible. Supprimer tous les résultats ?', 'disc-test')); ?>');">
                        <?php wp_nonce_field('disc_reset_test_data'); ?>
                        <button type="submit" name="disc_reset_test_data" class="button" style="background:#dc2626;color:white;border-color:#b91c1c;">
                            <?php _e('Supprimer tous les résultats', 'disc-test'); ?>
                        </button>
                    </form>
                </div>

            </div>
        </div>
        <?php
    }
}