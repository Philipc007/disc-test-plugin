<?php
/**
 * Classe DISC_Email
 * Gère l'envoi des emails pour le plugin
 * 
 * @package DISC_Test
 * @since 1.0.0
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class DISC_Email {
    
    /**
     * Envoie l'email avec les résultats du test
     */
    public static function send_results_email($contact_data, $scores, $profile_type) {
        $to             = $contact_data['email'];
        $subject_tpl    = get_option('disc_email_subject', __('Votre profil DISC : {profil}', 'disc-test'));
        $subject        = str_replace('{profil}', $profile_type, $subject_tpl);
        
        $profile_description = DISC_Renderer::get_profile_description($profile_type, $scores);

        // Génère l'URL du graphique via QuickChart.io (image statique, compatible email)
        $chart_config = json_encode(array(
            'type' => 'horizontalBar',
            'data' => array(
                'labels'   => array('Dominance (D)', 'Influence (I)', 'Stabilité (S)', 'Conformité (C)'),
                'datasets' => array(array(
                    'data'            => array($scores['D'], $scores['I'], $scores['S'], $scores['C']),
                    'backgroundColor' => array('#dc2626', '#eab308', '#22c55e', '#3b82f6'),
                ))
            ),
            'options' => array(
                'legend'  => array('display' => false),
                'scales'  => array(
                    'xAxes' => array(array('ticks' => array('min' => 0, 'max' => 100))),
                    'yAxes' => array(array('gridLines' => array('display' => false)))
                ),
                'plugins' => array('datalabels' => array(
                    'anchor' => 'end', 'align' => 'right',
                    'formatter' => "function(v){return v+'/100';}"
                ))
            )
        ));
        $chart_url = 'https://quickchart.io/chart?w=500&h=200&c=' . urlencode($chart_config);

        // Construit le message HTML
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .profile-badge { background: white; padding: 20px; margin: 20px 0; text-align: center; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .profile-type { font-size: 48px; font-weight: bold; color: #667eea; }
                .scores { display: table; width: 100%; margin: 20px 0; }
                .score-row { display: table-row; }
                .score-label, .score-value { display: table-cell; padding: 10px; }
                .score-label { font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Vos résultats DISC</h1>
                    <p>Bonjour <?php echo esc_html($contact_data['first_name']); ?>,</p>
                </div>
                <div class="content">
                    <div class="profile-badge">
                        <div class="profile-type"><?php echo esc_html($profile_type); ?></div>
                        <h2><?php echo esc_html($profile_description['title']); ?></h2>
                        <p><?php echo esc_html($profile_description['subtitle']); ?></p>
                    </div>
                    
                    <h3>Vos tendances DISC</h3>
                    <div class="scores">
                        <div class="score-row">
                            <div class="score-label">Dominance (D):</div>
                            <div class="score-value"><?php echo $scores['D']; ?>%</div>
                        </div>
                        <div class="score-row">
                            <div class="score-label">Influence (I):</div>
                            <div class="score-value"><?php echo $scores['I']; ?>%</div>
                        </div>
                        <div class="score-row">
                            <div class="score-label">Stabilité (S):</div>
                            <div class="score-value"><?php echo $scores['S']; ?>%</div>
                        </div>
                        <div class="score-row">
                            <div class="score-label">Conformité (C):</div>
                            <div class="score-value"><?php echo $scores['C']; ?>%</div>
                        </div>
                    </div>

                    <h3>Votre graphique DISC</h3>
                    <img src="<?php echo esc_url($chart_url); ?>" alt="Graphique DISC" width="500" style="max-width:100%;display:block;margin:0 auto 10px;">
                    <p style="text-align:center;color:#666;font-size:12px;margin-bottom:20px;"><em>Les valeurs représentent la répartition de vos tendances comportementales. Elles totalisent 100%.</em></p>

                    <h3>Votre profil</h3>
                    <p><?php echo esc_html($profile_description['description']); ?></p>
                    
                    <h3>Vos forces</h3>
                    <ul>
                        <?php foreach ($profile_description['strengths'] as $strength): ?>
                            <li><?php echo esc_html($strength); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <h3>Axes de développement</h3>
                    <p><?php echo esc_html($profile_description['development']); ?></p>
                </div>
                <?php
                // Pied de page légal configurable (activé/désactivé dans les paramètres du plugin)
                if (get_option('disc_email_footer_enabled', 1)) :
                    $footer_default = "Conformément au RGPD, vous disposez d'un droit d'accès, de rectification et d'effacement de vos données personnelles.\n"
                                    . "Pour exercer ces droits ou vous désinscrire, contactez-nous : {email_admin}\n\n"
                                    . "Cet email vous a été envoyé suite à votre participation au test DISC sur {site_name}.";
                    $footer_text    = get_option('disc_email_footer_content', $footer_default);
                    $footer_text    = str_replace(
                        array('{email_admin}', '{site_name}', '{first_name}', '{profil}'),
                        array(
                            get_option('admin_email'),
                            get_bloginfo('name'),
                            esc_html($contact_data['first_name']),
                            esc_html($profile_type)
                        ),
                        $footer_text
                    );
                ?>
                <div class="footer" style="border-top:1px solid #e0e0e0;margin-top:20px;padding-top:16px;">
                    <p style="font-size:11px;color:#888;line-height:1.6;white-space:pre-line;"><?php echo esc_html($footer_text); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        $message = ob_get_clean();
        
        // Headers pour email HTML
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        // Envoie l'email
        $sent = wp_mail($to, $subject, $message, $headers);
        
        // Log l'envoi
        DISC_Database::log_event('email_sent', array(
            'to' => $to,
            'success' => $sent
        ));
        
        return $sent;
    }
}