<?php
/**
 * Classe DISC_Renderer
 * Génère le HTML du test (approche hybride shortcode/Gutenberg)
 * 
 * @package DISC_Test
 * @since 1.0.0
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

class DISC_Renderer {
    
    /**
     * Fonction centrale de rendu utilisée par le shortcode ET le bloc Gutenberg
     * C'est ici que toute la logique de présentation se trouve
     */
    public static function render_test($atts = array()) {
        // Normalise les attributs avec des valeurs par défaut
        $atts = shortcode_atts(array(
            'showTitle' => true,
            'buttonText' => __('Commencer le test', 'disc-test'),
            'redirectUrl' => ''
        ), $atts);
        
        // Convertit les valeurs booléennes (Gutenberg envoie des strings)
        $atts['showTitle'] = filter_var($atts['showTitle'], FILTER_VALIDATE_BOOLEAN);
        
        // Génère un ID unique pour cette instance
        $instance_id = 'disc-test-' . uniqid();
        
        // Récupère les questions
        $questions = DISC_Database::get_questions();
        
        if (empty($questions)) {
            return '<div class="disc-error">' . 
                   __('Aucune question trouvée. Veuillez contacter l\'administrateur.', 'disc-test') . 
                   '</div>';
        }
        
        // Commence la mise en tampon de sortie
        ob_start();
        ?>
        
        <div id="<?php echo esc_attr($instance_id); ?>" class="disc-test-container" data-redirect="<?php echo esc_url($atts['redirectUrl']); ?>">
            
            <?php if ($atts['showTitle']): ?>
            <div class="disc-test-header">
                <h2><?php _e('Découvrez votre profil de leadership DISC', 'disc-test'); ?></h2>
                <p class="disc-test-intro">
                    <?php _e('Répondez à ces questions pour mieux comprendre votre style naturel de communication et de leadership. Ce test prend environ 8 à 10 minutes.', 'disc-test'); ?>
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Écran de démarrage -->
            <div class="disc-screen disc-screen-start active">
                <div class="disc-welcome-content">
                    <div class="disc-icon">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <h3><?php _e('Test DISC pour Dirigeants', 'disc-test'); ?></h3>
                    <div class="disc-benefits">
                        <ul>
                            <li><?php _e('✓ Comprenez votre style de leadership naturel', 'disc-test'); ?></li>
                            <li><?php _e('✓ Identifiez vos forces et zones de développement', 'disc-test'); ?></li>
                            <li><?php _e('✓ Améliorez votre communication avec votre équipe', 'disc-test'); ?></li>
                            <li><?php _e('✓ Recevez un rapport détaillé personnalisé', 'disc-test'); ?></li>
                        </ul>
                    </div>
                    <div class="disc-instructions">
                        <h4><?php _e('Comment répondre ?', 'disc-test'); ?></h4>
                        <p><?php _e('Pour chaque question, vous verrez 4 affirmations. Choisissez celle qui vous décrit LE MIEUX et celle qui vous décrit LE MOINS. Répondez instinctivement, sans trop réfléchir.', 'disc-test'); ?></p>
                    </div>
                    <button class="disc-btn disc-btn-primary disc-btn-start">
                        <?php echo esc_html($atts['buttonText']); ?>
                    </button>
                </div>
            </div>
            
            <!-- Écran des questions -->
            <div class="disc-screen disc-screen-questions">
                <div class="disc-progress-bar">
                    <div class="disc-progress-fill" style="width: 0%"></div>
                    <span class="disc-progress-text">
                        <span class="disc-current-question">1</span> / <?php echo count($questions); ?>
                    </span>
                </div>
                
                <div class="disc-questions-wrapper">
                    <?php foreach ($questions as $index => $question): ?>
                    <div class="disc-question" data-question-id="<?php echo esc_attr($question['id']); ?>" data-question-number="<?php echo $index + 1; ?>" style="<?php echo $index === 0 ? '' : 'display:none;'; ?>">
                        <h3 class="disc-question-title">
                            <?php printf(__('Question %d sur %d', 'disc-test'), $index + 1, count($questions)); ?>
                        </h3>
                        <p class="disc-question-instruction">
                            <?php _e('Choisissez l\'affirmation qui vous décrit le MIEUX et celle qui vous décrit le MOINS :', 'disc-test'); ?>
                        </p>
                        
                        <div class="disc-statements">
                            <?php 
                            $statements = array(
                                'D' => $question['statement_d'],
                                'I' => $question['statement_i'],
                                'S' => $question['statement_s'],
                                'C' => $question['statement_c']
                            );
                            foreach ($statements as $dimension => $statement): 
                            ?>
                            <div class="disc-statement" data-dimension="<?php echo esc_attr($dimension); ?>">
                                <div class="disc-statement-text">
                                    <?php echo esc_html($statement); ?>
                                </div>
                                <div class="disc-statement-choices">
                                    <label class="disc-choice disc-choice-most">
                                        <input type="radio" name="most_like_<?php echo $question['id']; ?>" value="<?php echo esc_attr($dimension); ?>">
                                        <span><?php _e('Le plus', 'disc-test'); ?></span>
                                    </label>
                                    <label class="disc-choice disc-choice-least">
                                        <input type="radio" name="least_like_<?php echo $question['id']; ?>" value="<?php echo esc_attr($dimension); ?>">
                                        <span><?php _e('Le moins', 'disc-test'); ?></span>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="disc-question-error" style="display:none;">
                            <?php _e('Veuillez sélectionner une réponse "Le plus" et une réponse "Le moins" différentes.', 'disc-test'); ?>
                        </div>
                        
                        <div class="disc-question-nav">
                            <?php if ($index > 0): ?>
                            <button class="disc-btn disc-btn-secondary disc-btn-prev">
                                <?php _e('← Précédent', 'disc-test'); ?>
                            </button>
                            <?php endif; ?>
                            
                            <button class="disc-btn disc-btn-primary disc-btn-next">
                                <?php echo ($index < count($questions) - 1) ? __('Suivant →', 'disc-test') : __('Terminer', 'disc-test'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Écran de capture des coordonnées -->
            <div class="disc-screen disc-screen-contact">
                <div class="disc-contact-content">
                    <div class="disc-icon-success">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <h3><?php _e('Votre analyse est prête !', 'disc-test'); ?></h3>
                    <p class="disc-contact-intro">
                        <?php _e('Pour recevoir votre profil DISC détaillé et personnalisé, merci de renseigner vos coordonnées. Vous recevrez également un rapport complet par email.', 'disc-test'); ?>
                    </p>
                    
                    <form class="disc-contact-form" id="disc-contact-form">
                        <div class="disc-form-row">
                            <div class="disc-form-field">
                                <label for="disc-first-name"><?php _e('Prénom', 'disc-test'); ?> *</label>
                                <input type="text" id="disc-first-name" name="first_name" required>
                            </div>
                            <div class="disc-form-field">
                                <label for="disc-last-name"><?php _e('Nom', 'disc-test'); ?> *</label>
                                <input type="text" id="disc-last-name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="disc-form-field">
                            <label for="disc-email"><?php _e('Email professionnel', 'disc-test'); ?> *</label>
                            <input type="email" id="disc-email" name="email" required>
                        </div>
                        
                        <div class="disc-form-row">
                            <div class="disc-form-field">
                                <label for="disc-company"><?php _e('Entreprise', 'disc-test'); ?></label>
                                <input type="text" id="disc-company" name="company">
                            </div>
                            <div class="disc-form-field">
                                <label for="disc-position"><?php _e('Poste', 'disc-test'); ?></label>
                                <input type="text" id="disc-position" name="position">
                            </div>
                        </div>
                        
                        <div class="disc-form-field disc-form-checkbox">
                            <label>
                                <input type="checkbox" name="consent" id="disc-consent" required>
                                <span><?php printf(
                                    __('J\'accepte la <a href="%s" target="_blank">politique de confidentialité</a> et consens au traitement de mes données personnelles. *', 'disc-test'),
                                    get_privacy_policy_url()
                                ); ?></span>
                            </label>
                        </div>
                        
                        <div class="disc-form-error" style="display:none;"></div>
                        
                        <button type="submit" class="disc-btn disc-btn-primary disc-btn-submit">
                            <?php _e('Recevoir mes résultats', 'disc-test'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Écran des résultats -->
            <div class="disc-screen disc-screen-results">
                <div class="disc-results-content">
                    <h3 class="disc-results-title"><?php _e('Votre profil de leadership', 'disc-test'); ?></h3>
                    <div class="disc-profile-badge">
                        <span class="disc-profile-type"></span>
                    </div>
                    
                    <div class="disc-chart-container">
                        <canvas id="disc-chart"></canvas>
                    </div>
                    
                    <div class="disc-profile-description">
                        <!-- Sera rempli dynamiquement par JavaScript -->
                    </div>
                    
                    <div class="disc-consistency-notice" style="display:none;">
                        <p class="disc-warning">
                            <?php _e('⚠️ Attention : Nous avons détecté quelques incohérences dans vos réponses. Pour des résultats plus fiables, nous vous recommandons de refaire le test en prenant le temps de réfléchir à chaque question.', 'disc-test'); ?>
                        </p>
                    </div>
                    
                    <div class="disc-next-steps">
                        <h4><?php _e('Et maintenant ?', 'disc-test'); ?></h4>
                        <p><?php _e('Vous allez recevoir par email un rapport PDF détaillé avec des conseils personnalisés pour développer votre leadership selon votre profil.', 'disc-test'); ?></p>
                        <p><?php _e('En attendant, partagez ce test avec vos collègues pour mieux comprendre vos dynamiques d\'équipe !', 'disc-test'); ?></p>
                    </div>
                    
                    <div class="disc-social-share">
                        <button class="disc-btn disc-btn-secondary disc-btn-share-linkedin">
                            <?php _e('Partager sur LinkedIn', 'disc-test'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Overlay de chargement -->
            <div class="disc-loading-overlay" style="display:none;">
                <div class="disc-spinner"></div>
                <p><?php _e('Analyse de votre profil en cours...', 'disc-test'); ?></p>
            </div>
            
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Génère la description détaillée d'un profil DISC
     */
    public static function get_profile_description($profile_type, $scores) {
        $descriptions = array(
            'D' => array(
                'title' => __('Dominance Élevée', 'disc-test'),
                'subtitle' => __('Le Leader Décisif', 'disc-test'),
                'description' => __('Vous êtes un leader naturel, orienté résultats et action. Vous prenez des décisions rapidement et n\'hésitez pas à prendre des risques calculés. Votre force réside dans votre capacité à voir la vue d\'ensemble et à mobiliser les ressources pour atteindre vos objectifs.', 'disc-test'),
                'strengths' => array(
                    __('Prise de décision rapide et efficace', 'disc-test'),
                    __('Orientation forte vers les résultats', 'disc-test'),
                    __('Capacité à gérer la pression', 'disc-test'),
                    __('Initiative et proactivité naturelles', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut paraître trop direct ou autoritaire', 'disc-test'),
                    __('Risque d\'impatience avec les processus lents', 'disc-test'),
                    __('Peut négliger les aspects relationnels', 'disc-test')
                ),
                'development' => __('Développez votre écoute active et prenez le temps de considérer les perspectives des autres. Apprenez à déléguer et à faire confiance à votre équipe.', 'disc-test')
            ),
            'I' => array(
                'title' => __('Influence Élevée', 'disc-test'),
                'subtitle' => __('Le Leader Inspirant', 'disc-test'),
                'description' => __('Vous êtes un communicateur charismatique qui inspire et motive naturellement les autres. Votre enthousiasme est contagieux et vous excellez dans la création de relations solides. Vous voyez le potentiel dans chaque personne et situation.', 'disc-test'),
                'strengths' => array(
                    __('Excellent communicateur et persuasif', 'disc-test'),
                    __('Capacité à motiver et inspirer les équipes', 'disc-test'),
                    __('Créativité et innovation', 'disc-test'),
                    __('Optimisme et énergie positive', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut manquer de suivi sur les détails', 'disc-test'),
                    __('Risque de sur-engagement', 'disc-test'),
                    __('Peut éviter les conflits nécessaires', 'disc-test')
                ),
                'development' => __('Renforcez votre discipline dans le suivi des projets. Développez des systèmes pour ne pas perdre de vue les détails importants.', 'disc-test')
            ),
            'S' => array(
                'title' => __('Stabilité Élevée', 'disc-test'),
                'subtitle' => __('Le Leader Collaboratif', 'disc-test'),
                'description' => __('Vous êtes un leader patient et fiable qui crée un environnement de travail harmonieux. Votre approche calme et votre loyauté inspirent la confiance. Vous excellez dans le maintien de relations durables et la création de cohésion d\'équipe.', 'disc-test'),
                'strengths' => array(
                    __('Excellente capacité d\'écoute', 'disc-test'),
                    __('Patience et persévérance', 'disc-test'),
                    __('Création d\'un environnement stable', 'disc-test'),
                    __('Loyauté et fiabilité', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut résister au changement', 'disc-test'),
                    __('Difficulté à dire non', 'disc-test'),
                    __('Peut éviter les conflits nécessaires', 'disc-test')
                ),
                'development' => __('Acceptez que le changement est parfois nécessaire pour la croissance. Pratiquez l\'assertivité et fixez des limites claires.', 'disc-test')
            ),
            'C' => array(
                'title' => __('Conformité Élevée', 'disc-test'),
                'subtitle' => __('Le Leader Analytique', 'disc-test'),
                'description' => __('Vous êtes un leader méthodique qui prend des décisions basées sur des faits et des analyses approfondies. Votre attention aux détails et vos standards élevés garantissent une qualité exceptionnelle. Vous excellez dans la planification et la gestion des risques.', 'disc-test'),
                'strengths' => array(
                    __('Analyse approfondie et pensée critique', 'disc-test'),
                    __('Standards élevés de qualité', 'disc-test'),
                    __('Organisation et planification rigoureuses', 'disc-test'),
                    __('Objectivité et logique', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut être perçu comme trop critique', 'disc-test'),
                    __('Risque de paralysie par l\'analyse', 'disc-test'),
                    __('Peut avoir du mal à déléguer', 'disc-test')
                ),
                'development' => __('Apprenez à accepter l\'imperfection et à faire confiance à votre intuition. Équilibrez l\'analyse avec l\'action.', 'disc-test')
            ),
            'DI' => array(
                'title' => __('Dominance + Influence', 'disc-test'),
                'subtitle' => __('Le Leader Visionnaire', 'disc-test'),
                'description' => __('Vous combinez l\'orientation résultats avec un charisme naturel. Vous êtes un leader dynamique qui inspire l\'action tout en maintenant le focus sur les objectifs.', 'disc-test'),
                'strengths' => array(
                    __('Leadership charismatique et décisif', 'disc-test'),
                    __('Capacité à mobiliser et motiver', 'disc-test'),
                    __('Vision stratégique claire', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut être trop impulsif', 'disc-test'),
                    __('Risque de négliger les détails', 'disc-test')
                ),
                'development' => __('Pratiquez l\'écoute active et donnez de l\'espace aux autres. Équilibrez votre vision avec l\'attention aux détails.', 'disc-test')
            ),
            'DS' => array(
                'title' => __('Dominance + Stabilité', 'disc-test'),
                'subtitle' => __('Le Leader Déterminé', 'disc-test'),
                'description' => __('Vous combinez la détermination avec la patience. Vous poursuivez vos objectifs de manière méthodique et persistante.', 'disc-test'),
                'strengths' => array(
                    __('Persévérance et ténacité', 'disc-test'),
                    __('Leadership calme mais ferme', 'disc-test'),
                    __('Fiabilité dans l\'atteinte des objectifs', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut être têtu', 'disc-test'),
                    __('Résistance au changement', 'disc-test')
                ),
                'development' => __('Restez ouvert aux nouvelles approches. Développez votre agilité dans les situations changeantes.', 'disc-test')
            ),
            'DC' => array(
                'title' => __('Dominance + Conformité', 'disc-test'),
                'subtitle' => __('Le Leader Stratégique', 'disc-test'),
                'description' => __('Vous combinez l\'orientation résultats avec une approche analytique rigoureuse. Vous prenez des décisions basées sur des données.', 'disc-test'),
                'strengths' => array(
                    __('Décisions stratégiques basées sur les faits', 'disc-test'),
                    __('Standards élevés d\'excellence', 'disc-test'),
                    __('Planification détaillée', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut être perçu comme distant', 'disc-test'),
                    __('Risque de perfectionnisme excessif', 'disc-test')
                ),
                'development' => __('Développez votre intelligence émotionnelle. Célébrez les progrès, pas seulement les résultats parfaits.', 'disc-test')
            ),
            'IS' => array(
                'title' => __('Influence + Stabilité', 'disc-test'),
                'subtitle' => __('Le Leader Bienveillant', 'disc-test'),
                'description' => __('Vous créez des environnements où les gens se sentent valorisés et soutenus. Vous combinez l\'enthousiasme avec la patience.', 'disc-test'),
                'strengths' => array(
                    __('Relations authentiques et durables', 'disc-test'),
                    __('Environnement positif et collaboratif', 'disc-test'),
                    __('Excellente écoute empathique', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut éviter les confrontations nécessaires', 'disc-test'),
                    __('Difficulté à donner du feedback négatif', 'disc-test')
                ),
                'development' => __('Pratiquez les conversations difficiles. Le feedback constructif est une forme de respect.', 'disc-test')
            ),
            'IC' => array(
                'title' => __('Influence + Conformité', 'disc-test'),
                'subtitle' => __('Le Leader Persuasif', 'disc-test'),
                'description' => __('Vous combinez le charisme avec l\'attention aux détails. Vous excellez à convaincre par des arguments documentés.', 'disc-test'),
                'strengths' => array(
                    __('Présentations convaincantes et structurées', 'disc-test'),
                    __('Créativité basée sur la recherche', 'disc-test'),
                    __('Communication claire et documentée', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut être perfectionniste dans la communication', 'disc-test'),
                    __('Risque de sur-préparation', 'disc-test')
                ),
                'development' => __('Acceptez que l\'imperfection fait partie du processus créatif. Développez votre résilience.', 'disc-test')
            ),
            'SC' => array(
                'title' => __('Stabilité + Conformité', 'disc-test'),
                'subtitle' => __('Le Leader Méthodique', 'disc-test'),
                'description' => __('Vous créez des systèmes fiables et durables. Vous combinez la loyauté avec des standards élevés.', 'disc-test'),
                'strengths' => array(
                    __('Processus solides et bien documentés', 'disc-test'),
                    __('Fiabilité et cohérence exceptionnelles', 'disc-test'),
                    __('Patience dans l\'amélioration continue', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut résister au changement', 'disc-test'),
                    __('Risque de paralysie par l\'analyse', 'disc-test')
                ),
                'development' => __('Embrassez le changement comme opportunité. Développez votre confiance dans la prise de décisions.', 'disc-test')
            ),
            'DIS' => array(
                'title' => __('Dominance + Influence + Stabilité', 'disc-test'),
                'subtitle' => __('Le Leader Équilibré', 'disc-test'),
                'description' => __('Vous démontrez un équilibre remarquable entre l\'action, la persuasion et la collaboration. Votre adaptabilité est votre force.', 'disc-test'),
                'strengths' => array(
                    __('Grande adaptabilité de style', 'disc-test'),
                    __('Leadership situationnel efficace', 'disc-test'),
                    __('Polyvalence dans les approches', 'disc-test')
                ),
                'challenges' => array(
                    __('Peut manquer de spécialisation', 'disc-test'),
                    __('Risque de dilution du focus', 'disc-test')
                ),
                'development' => __('Identifiez votre "super-pouvoir" principal et développez-le. La polyvalence nécessite aussi de la profondeur.', 'disc-test')
            ),
            'DISC' => array(
                'title' => __('Profil Complet DISC', 'disc-test'),
                'subtitle' => __('Le Leader Universel', 'disc-test'),
                'description' => __('Vous démontrez toutes les dimensions du DISC de manière équilibrée. Vous vous adaptez à pratiquement toute situation.', 'disc-test'),
                'strengths' => array(
                    __('Adaptabilité exceptionnelle', 'disc-test'),
                    __('Compréhension approfondie des différents styles', 'disc-test'),
                    __('Leadership transformationnel', 'disc-test')
                ),
                'challenges' => array(
                    __('Risque de sur-adaptation', 'disc-test'),
                    __('Peut perdre son authenticité', 'disc-test')
                ),
                'development' => __('Restez fidèle à vos valeurs profondes tout en utilisant votre flexibilité.', 'disc-test')
            )
        );
        
        return $descriptions[$profile_type] ?? $descriptions['D'];
    }
    
    /**
     * Détermine le profil dominant basé sur les scores
     */
    public static function determine_profile_type($scores) {
        // Trie les scores par valeur décroissante
        arsort($scores);
        $dimensions = array_keys($scores);
        
        // Profil dominant = les 2 dimensions les plus élevées si elles dépassent 60
        $primary = $dimensions[0];
        $secondary = $dimensions[1];
        
        $profile = '';
        
        if ($scores[$primary] >= 60) {
            $profile .= $primary;
            
            if ($scores[$secondary] >= 60) {
                $profile .= $secondary;
            }
        } else {
            // Profil équilibré
            $profile = 'DISC';
        }
        
        return $profile;
    }
}