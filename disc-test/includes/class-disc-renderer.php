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
                                    esc_url(get_privacy_policy_url())
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

                    <?php echo self::render_cta_block('frontend'); ?>

                    <div class="disc-consistency-notice" style="display:none;">
                        <p class="disc-warning">
                            <?php _e('⚠️ Attention : Nous avons détecté quelques incohérences dans vos réponses. Pour des résultats plus fiables, nous vous recommandons de refaire le test en prenant le temps de réfléchir à chaque question.', 'disc-test'); ?>
                        </p>
                    </div>
                    
                    <div class="disc-next-steps">
                        <h4><?php _e('Et maintenant ?', 'disc-test'); ?></h4>
                        <p><?php _e('Vous allez recevoir par email un rapport détaillé avec des conseils personnalisés pour développer votre leadership selon votre profil.', 'disc-test'); ?></p>
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
     * Détermine le type de profil à partir des scores normalisés (0–100 indépendants)
     *
     * Seuils (configurables via constantes dans disc-test.php ou wp-config.php) :
     *   DISC_PROFILE_BALANCED_CONTRAST (14) : contrast max pour profil équilibré
     *   DISC_PROFILE_SIMPLE_GAP        (10) : écart min rank1-rank2 pour profil simple
     *   DISC_PROFILE_NUANCED_RANGE     (8)  : plage max des 3 premiers pour profil nuancé
     *
     * L'ordre des lettres reflète l'ordre réel des scores (pas l'ordre canonique D-I-S-C).
     */
    public static function determine_profile_type($scores) {
        $balanced_contrast = defined('DISC_PROFILE_BALANCED_CONTRAST') ? DISC_PROFILE_BALANCED_CONTRAST : 14;
        $simple_gap        = defined('DISC_PROFILE_SIMPLE_GAP')        ? DISC_PROFILE_SIMPLE_GAP        : 10;
        $nuanced_range     = defined('DISC_PROFILE_NUANCED_RANGE')     ? DISC_PROFILE_NUANCED_RANGE     : 8;

        // Trie par score décroissant
        arsort($scores);
        $dims = array_keys($scores);
        $vals = array_values($scores);

        $contrast  = $vals[0] - $vals[3]; // max - min
        $ecart_1_2 = $vals[0] - $vals[1];
        $range_top3 = $vals[0] - $vals[2]; // écart entre rank-1 et rank-3

        // 1. Profil équilibré : contraste global faible
        if ($contrast <= $balanced_contrast) {
            return 'DISC';
        }

        // 2. Profil simple : la dimension dominante se détache nettement
        if ($ecart_1_2 >= $simple_gap) {
            return $dims[0];
        }

        // 3. Profil nuancé : les 3 premiers tiennent dans une plage de 8 pts
        if ($range_top3 <= $nuanced_range) {
            return $dims[0] . $dims[1] . $dims[2];
        }

        // 4. Profil combiné : les 2 premiers sont proches
        return $dims[0] . $dims[1];
    }

    /**
     * Applique le formatage inline (gras, liens) sur une ligne de texte brut
     * Doit être appelé AVANT esc_html pour les parties non formatées
     */
    private static function inline_format($text) {
        $placeholders = array();

        // Extraire les liens [texte](url)
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^\)]+)\)/', function($m) use (&$placeholders) {
            $key = '%%L' . count($placeholders) . '%%';
            $placeholders[$key] = '<a href="' . esc_url($m[2]) . '" target="_blank" rel="noopener noreferrer">' . esc_html($m[1]) . '</a>';
            return $key;
        }, $text);

        // Extraire les **gras**
        $text = preg_replace_callback('/\*\*(.+?)\*\*/', function($m) use (&$placeholders) {
            $key = '%%B' . count($placeholders) . '%%';
            $placeholders[$key] = '<strong>' . esc_html($m[1]) . '</strong>';
            return $key;
        }, $text);

        // Échapper le reste
        $text = esc_html($text);

        // Réinsérer les éléments formatés (placeholders sans chars HTML spéciaux)
        foreach ($placeholders as $key => $html) {
            $text = str_replace($key, $html, $text);
        }

        return $text;
    }

    /**
     * Convertit du texte mini-markdown en HTML sécurisé
     * Syntaxe supportée : # Titre → <h4>, **gras**, - liste, [texte](url), ligne vide = <p>
     *
     * @param string $text    Texte source
     * @param string $context 'frontend' (classes CSS) ou 'email' (styles inline)
     * @return string
     */
    public static function mini_markdown($text, $context = 'frontend') {
        if (empty($text)) {
            return '';
        }

        $lines       = explode("\n", str_replace("\r\n", "\n", $text));
        $html        = '';
        $list_items  = array();
        $para_buffer = '';

        $flush_para = function() use (&$html, &$para_buffer, $context) {
            if ($para_buffer !== '') {
                $style = ($context === 'email') ? ' style="margin:8px 0;color:#333;"' : '';
                $html .= '<p' . $style . '>' . $para_buffer . '</p>';
                $para_buffer = '';
            }
        };
        $flush_list = function() use (&$html, &$list_items, $context) {
            if (!empty($list_items)) {
                $style = ($context === 'email') ? ' style="margin:8px 0 8px 20px;padding:0;"' : '';
                $html .= '<ul' . $style . '>' . implode('', $list_items) . '</ul>';
                $list_items = array();
            }
        };

        foreach ($lines as $line) {
            $line = rtrim($line);

            // Ligne vide → ferme le bloc courant
            if ($line === '') {
                $flush_list();
                $flush_para();
                continue;
            }

            // Titre : # ...
            if (preg_match('/^# (.+)$/', $line, $m)) {
                $flush_list();
                $flush_para();
                $style = ($context === 'email') ? ' style="font-size:16px;font-weight:600;color:#444;margin:12px 0 4px;"' : '';
                $html .= '<h4' . $style . '>' . esc_html($m[1]) . '</h4>';
                continue;
            }

            // Élément de liste : - ...
            if (preg_match('/^- (.+)$/', $line, $m)) {
                $flush_para();
                $item_style = ($context === 'email') ? ' style="margin:3px 0;"' : '';
                $list_items[] = '<li' . $item_style . '>' . self::inline_format($m[1]) . '</li>';
                continue;
            }

            // Texte courant → accumuler dans le paragraphe
            $flush_list();
            $para_buffer .= ($para_buffer !== '' ? ' ' : '') . self::inline_format($line);
        }

        // Vider les tampons restants
        $flush_list();
        $flush_para();

        return $html;
    }

    /**
     * Génère le HTML du bloc marketing configurable
     * Affiché après les résultats DISC, avant les prochaines étapes
     *
     * @param string $context 'frontend' (classes CSS) ou 'email' (styles inline)
     * @return string HTML ou chaîne vide si bloc désactivé ou vide
     */
    public static function render_cta_block($context = 'frontend') {
        if (!get_option('disc_cta_enabled', 0)) {
            return '';
        }

        $title    = get_option('disc_cta_title', '');
        $body     = get_option('disc_cta_body', '');
        $btn_text = get_option('disc_cta_button_text', '');
        $btn_url  = get_option('disc_cta_button_url', '');

        if (empty($title) && empty($body)) {
            return '';
        }

        if ($context === 'email') {
            $html  = '<div style="background:#f4f0ff;border-left:4px solid #667eea;padding:20px 24px;margin:24px 0;border-radius:0 8px 8px 0;">';
            if ($title) {
                $html .= '<h3 style="margin:0 0 10px;font-size:18px;color:#667eea;">' . esc_html($title) . '</h3>';
            }
            if ($body) {
                $html .= self::mini_markdown($body, 'email');
            }
            if ($btn_text && $btn_url) {
                $html .= '<p style="margin:16px 0 0;text-align:center;"><a href="' . esc_url($btn_url) . '" target="_blank" rel="noopener noreferrer" style="display:inline-block;background:#667eea;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;">' . esc_html($btn_text) . '</a></p>';
            }
            $html .= '</div>';
        } else {
            $html  = '<div class="disc-cta-block">';
            if ($title) {
                $html .= '<h3 class="disc-cta-title">' . esc_html($title) . '</h3>';
            }
            if ($body) {
                $html .= '<div class="disc-cta-body">' . self::mini_markdown($body, 'frontend') . '</div>';
            }
            if ($btn_text && $btn_url) {
                $html .= '<p class="disc-cta-button-wrap"><a href="' . esc_url($btn_url) . '" target="_blank" rel="noopener noreferrer" class="disc-btn disc-btn-primary disc-cta-btn">' . esc_html($btn_text) . '</a></p>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Retourne le nom complet d'une dimension DISC
     */
    private static function get_dimension_name($letter) {
        $names = array('D' => 'Dominance', 'I' => 'Influence', 'S' => 'Stabilité', 'C' => 'Conformité');
        return $names[$letter] ?? $letter;
    }

    /**
     * Retourne le niveau de contraste du profil
     * Basé sur contrast = max(scores) - min(scores)
     */
    public static function get_contrast_level($contrast) {
        if ($contrast <= 14) return array(
            'label'       => 'équilibré',
            'key'         => 'balanced',
            'explanation' => 'Vos 4 dimensions sont proches : vous adaptez naturellement votre style selon le contexte.',
        );
        if ($contrast <= 29) return array(
            'label'       => 'modérément contrasté',
            'key'         => 'moderate',
            'explanation' => 'Une ou deux dimensions se détachent légèrement : votre profil conjugue adaptabilité et axes de prédilection.',
        );
        if ($contrast <= 44) return array(
            'label'       => 'contrasté',
            'key'         => 'high',
            'explanation' => 'Vos dimensions dominantes se distinguent clairement : votre style comportemental est bien affirmé.',
        );
        return array(
            'label'       => 'très contrasté',
            'key'         => 'very_high',
            'explanation' => 'Une dimension prédomine très fortement : votre comportement naturel est très marqué dans cette direction.',
        );
    }

    /**
     * Génère le titre du profil (BLOC A de la restitution)
     * Chaque lettre est accompagnée de son nom complet (Dominance, Influence, Stabilité, Conformité)
     */
    public static function get_profile_title($profile_type) {
        $len = strlen($profile_type);
        if ($profile_type === 'DISC') {
            return __('Votre profil DISC apparaît équilibré', 'disc-test');
        }
        if ($len === 1) {
            return sprintf(
                __('Votre profil DISC dominant : %s (%s)', 'disc-test'),
                $profile_type, self::get_dimension_name($profile_type)
            );
        }
        if ($len === 2) {
            return sprintf(
                __('Votre profil DISC : %s (%s) — %s (%s)', 'disc-test'),
                $profile_type[0], self::get_dimension_name($profile_type[0]),
                $profile_type[1], self::get_dimension_name($profile_type[1])
            );
        }
        // 3 ou 4 lettres
        $parts = array();
        for ($i = 0; $i < $len; $i++) {
            $parts[] = $profile_type[$i] . ' (' . self::get_dimension_name($profile_type[$i]) . ')';
        }
        return sprintf(__('Votre profil DISC nuancé : %s', 'disc-test'), implode(' — ', $parts));
    }

    /**
     * Retourne la description complète d'un profil DISC
     * Structure : title (A), synthesis (B), strengths (D), vigilance (E), advice (F)
     * Le bloc C (scores graphique) est géré par la couche d'affichage.
     *
     * @param string $profile_type  Profil détecté (ex : I, DI, IDS, DISC)
     * @param array  $scores        Scores normalisés 0–100 par dimension
     * @return array
     */
    public static function get_profile_description($profile_type, $scores) {
        $contrast       = max($scores) - min($scores);
        $contrast_info  = self::get_contrast_level($contrast);
        $profile_title  = self::get_profile_title($profile_type);

        $descriptions = array(

            'D' => array(
                'synthesis'  => __("Votre profil met en avant une forte orientation vers l'action et les résultats. Vous prenez les décisions avec assurance et aimez tenir la barre, même dans l'incertitude. Votre dynamisme est un moteur pour votre entourage.", 'disc-test'),
                'strengths'  => array(
                    __('Capacité à décider vite et à assumer les choix', 'disc-test'),
                    __('Énergie pour impulser et faire avancer les projets', 'disc-test'),
                    __('Résistance à la pression et goût du défi', 'disc-test'),
                    __('Leadership naturel dans les situations exigeantes', 'disc-test'),
                ),
                'vigilance'  => array(
                    __("Tendance à aller vite, parfois au détriment de l'écoute", 'disc-test'),
                    __('Risque de sous-estimer les besoins relationnels de l\'équipe', 'disc-test'),
                    __('Impatience face aux processus lents ou aux hésitations', 'disc-test'),
                ),
                'advice'     => array(
                    __("Prenez le temps d'associer votre équipe aux décisions importantes.", 'disc-test'),
                    __('Développez votre écoute active pour capter les signaux faibles.', 'disc-test'),
                    __('Célébrez les étapes intermédiaires, pas seulement les résultats finaux.', 'disc-test'),
                ),
            ),

            'I' => array(
                'synthesis'  => __("Votre profil met en avant une forte capacité à créer du lien, à entraîner les autres et à faire circuler l'énergie dans un groupe. Vous êtes à l'aise pour convaincre, inspirer et donner confiance. Votre enthousiasme est souvent contagieux.", 'disc-test'),
                'strengths'  => array(
                    __('Aisance relationnelle et capacité à fédérer', 'disc-test'),
                    __('Communication naturelle et impact à l\'oral', 'disc-test'),
                    __('Capacité à motiver et à insuffler de l\'optimisme', 'disc-test'),
                    __('Créativité dans les échanges et ouverture aux idées nouvelles', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Tendance à sur-s\'engager ou à disperser son énergie', 'disc-test'),
                    __('Risque de manquer de suivi sur les détails et les livrables', 'disc-test'),
                    __('Difficulté à dire non ou à gérer des conversations difficiles', 'disc-test'),
                ),
                'advice'     => array(
                    __('Mettez en place des systèmes simples pour suivre vos engagements.', 'disc-test'),
                    __('Travaillez votre assertivité : dire ce qui ne va pas est aussi une forme de respect.', 'disc-test'),
                    __('Associez-vous à des profils plus structurants pour équilibrer votre style.', 'disc-test'),
                ),
            ),

            'S' => array(
                'synthesis'  => __("Votre profil met en avant une grande fiabilité, une capacité d'écoute et un sens profond de la coopération. Vous apportez de la stabilité à votre environnement et vous engagez sur la durée. Votre présence rassure et fédère.", 'disc-test'),
                'strengths'  => array(
                    __('Constance et fiabilité dans les engagements pris', 'disc-test'),
                    __('Écoute authentique et capacité à créer un climat de confiance', 'disc-test'),
                    __('Patience et persévérance dans les projets longs', 'disc-test'),
                    __('Facilitation naturelle de la coopération dans un groupe', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Résistance au changement et inconfort face aux ruptures', 'disc-test'),
                    __('Tendance à accepter des situations insatisfaisantes pour préserver l\'harmonie', 'disc-test'),
                    __("Difficulté à s'affirmer ou à défendre son point de vue", 'disc-test'),
                ),
                'advice'     => array(
                    __('Pratiquez l\'assertivité : vos besoins et opinions ont de la valeur.', 'disc-test'),
                    __('Acceptez que le changement puisse être une ressource, pas seulement une contrainte.', 'disc-test'),
                    __('Fixez des limites claires pour protéger votre énergie et votre espace.', 'disc-test'),
                ),
            ),

            'C' => array(
                'synthesis'  => __("Votre profil met en avant une grande rigueur analytique et un sens élevé de la qualité. Vous aimez comprendre en profondeur, structurer l'information et travailler avec des critères explicites. Votre exigence est un gage de fiabilité.", 'disc-test'),
                'strengths'  => array(
                    __('Pensée analytique et capacité à repérer les incohérences', 'disc-test'),
                    __('Standards élevés de qualité et d\'exactitude', 'disc-test'),
                    __('Rigueur dans la planification et l\'organisation', 'disc-test'),
                    __('Recul objectif face aux situations complexes', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Tendance à la sur-analyse pouvant freiner la prise de décision', 'disc-test'),
                    __('Exigence parfois difficile à vivre pour l\'entourage', 'disc-test'),
                    __('Inconfort face à l\'ambiguïté ou aux zones grises', 'disc-test'),
                ),
                'advice'     => array(
                    __('Acceptez que l\'imperfection fait partie du processus — avancer vaut parfois mieux qu\'attendre.', 'disc-test'),
                    __('Dosez votre exigence en fonction de l\'enjeu réel de chaque situation.', 'disc-test'),
                    __('Exprimez vos analyses de façon accessible pour ne pas être perçu comme trop critique.', 'disc-test'),
                ),
            ),

            'DI' => array(
                'synthesis'  => __("Votre profil combine une forte orientation résultats et une réelle capacité à embarquer les autres. Vous avancez avec assurance tout en sachant mobiliser et convaincre. Ce duo fait de vous un leader à la fois décisif et inspirant.", 'disc-test'),
                'strengths'  => array(
                    __('Leadership dynamique, capable d\'initier et d\'entraîner', 'disc-test'),
                    __('Aisance à prendre des décisions et à les faire accepter', 'disc-test'),
                    __('Énergie communicative dans les moments-clés', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque d\'impulsivité — agir avant d\'avoir pleinement écouté', 'disc-test'),
                    __('Tendance à négliger les profils plus lents ou plus prudents', 'disc-test'),
                ),
                'advice'     => array(
                    __('Veillez à intégrer les points de vue plus posés avant de trancher.', 'disc-test'),
                    __('Faites de la place aux profils S et C dans votre équipe — ils vous complètent.', 'disc-test'),
                ),
            ),

            'ID' => array(
                'synthesis'  => __("Votre profil combine un fort sens du contact humain et une capacité à impulser l'action. Vous inspirez les autres et savez aussi faire avancer les choses quand c'est nécessaire. Vous associez l'enthousiasme à la détermination.", 'disc-test'),
                'strengths'  => array(
                    __('Capacité à rallier et à mettre en mouvement', 'disc-test'),
                    __('Aisance relationnelle doublée d\'un sens du résultat', 'disc-test'),
                    __('Influence positive sur l\'ambiance et la dynamique de groupe', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Tendance à manquer de rigueur dans le suivi opérationnel', 'disc-test'),
                    __('Risque de vouloir faire trop vite sans impliquer suffisamment', 'disc-test'),
                ),
                'advice'     => array(
                    __('Structurez vos idées avant de les partager pour plus d\'impact.', 'disc-test'),
                    __('Entourez-vous de profils C ou S pour équilibrer votre style.', 'disc-test'),
                ),
            ),

            'DS' => array(
                'synthesis'  => __("Votre profil associe la détermination à la constance. Vous avancez vers vos objectifs avec ténacité, sans relâche, en maintenant un cap clair. Vous êtes fiable dans l'effort comme dans la décision.", 'disc-test'),
                'strengths'  => array(
                    __('Persévérance et capacité à tenir un effort dans la durée', 'disc-test'),
                    __('Leadership stable, ancré dans les faits et les résultats', 'disc-test'),
                    __('Fiabilité dans les engagements pris', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Tendance à la rigidité face aux changements de cap nécessaires', 'disc-test'),
                    __('Risque de sous-estimer l\'importance du collectif et de la communication', 'disc-test'),
                ),
                'advice'     => array(
                    __('Cultivez votre agilité : parfois changer de route est un signe de force.', 'disc-test'),
                    __('Investissez dans la qualité des relations, pas seulement dans les résultats.', 'disc-test'),
                ),
            ),

            'SD' => array(
                'synthesis'  => __("Votre profil associe une grande stabilité à une capacité d'action quand la situation l'exige. Vous préférez avancer avec méthode, mais vous savez décider quand c'est nécessaire. Vous inspirez confiance par votre calme et votre détermination.", 'disc-test'),
                'strengths'  => array(
                    __('Capacité à tenir la durée sans perdre de vue les objectifs', 'disc-test'),
                    __('Style de leadership rassurant et orienté résultats', 'disc-test'),
                    __('Fiabilité et solidité dans les moments de tension', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque de rester trop longtemps dans une situation qui nécessite une rupture', 'disc-test'),
                    __('Tendance à sous-communiquer sur les orientations prises', 'disc-test'),
                ),
                'advice'     => array(
                    __('N\'attendez pas que la situation se dégrade pour prendre position.', 'disc-test'),
                    __('Partagez vos réflexions plus tôt pour favoriser l\'alignement.', 'disc-test'),
                ),
            ),

            'DC' => array(
                'synthesis'  => __("Votre profil combine une forte orientation résultats avec un souci de rigueur et de qualité. Vous prenez des décisions stratégiques basées sur des faits solides. Vous êtes à la fois exigeant et efficace.", 'disc-test'),
                'strengths'  => array(
                    __('Prise de décision fondée sur l\'analyse et les faits', 'disc-test'),
                    __('Standards élevés d\'excellence dans l\'action', 'disc-test'),
                    __('Capacité à structurer une vision et à la déployer avec rigueur', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque de paraître froid ou distant dans les relations', 'disc-test'),
                    __('Tendance au perfectionnisme pouvant freiner l\'exécution', 'disc-test'),
                ),
                'advice'     => array(
                    __('Développez votre intelligence émotionnelle pour mieux connecter avec votre équipe.', 'disc-test'),
                    __('Acceptez le "suffisamment bon" quand l\'enjeu ne justifie pas la perfection.', 'disc-test'),
                ),
            ),

            'CD' => array(
                'synthesis'  => __("Votre profil associe une grande rigueur analytique à une vraie capacité d'action. Vous aimez comprendre en profondeur avant d'agir, et quand vous décidez, vous assumez pleinement. Votre crédibilité repose sur la solidité de vos analyses.", 'disc-test'),
                'strengths'  => array(
                    __('Rigueur analytique couplée à une capacité de décision', 'disc-test'),
                    __('Crédibilité par la qualité du raisonnement et des résultats', 'disc-test'),
                    __('Autonomie et sens des responsabilités', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque d\'aller seul, sans suffisamment impliquer les autres', 'disc-test'),
                    __('Tendance à l\'exigence difficile à vivre pour l\'entourage', 'disc-test'),
                ),
                'advice'     => array(
                    __('Partagez votre raisonnement pour embarquer, pas seulement pour informer.', 'disc-test'),
                    __('Valorisez les contributions des autres même quand elles sont imparfaites.', 'disc-test'),
                ),
            ),

            'IS' => array(
                'synthesis'  => __("Votre profil combine un fort sens du contact humain avec une capacité d'écoute et de soutien. Vous créez des environnements où les gens se sentent valorisés et soutenus. Vous associez l'enthousiasme à la patience.", 'disc-test'),
                'strengths'  => array(
                    __('Relations authentiques et durables', 'disc-test'),
                    __('Capacité à créer un environnement positif et collaboratif', 'disc-test'),
                    __('Écoute active et empathie naturelle', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Tendance à éviter les confrontations nécessaires', 'disc-test'),
                    __('Difficulté à donner des feedbacks négatifs malgré les besoins réels', 'disc-test'),
                ),
                'advice'     => array(
                    __('Pratiquez les conversations difficiles — elles sont souvent nécessaires pour progresser.', 'disc-test'),
                    __('Différenciez l\'harmonie superficielle de la coopération réelle.', 'disc-test'),
                ),
            ),

            'SI' => array(
                'synthesis'  => __("Votre profil associe une grande stabilité relationnelle à une réelle capacité d'influence. Vous êtes à l'aise pour construire des relations durables et pour mobiliser les autres autour d'un projet commun. Vous agissez avec douceur mais avec conviction.", 'disc-test'),
                'strengths'  => array(
                    __('Construction de relations solides et de confiance mutuelle', 'disc-test'),
                    __('Capacité à rassembler et à maintenir la cohésion', 'disc-test'),
                    __('Influence par la régularité et la bienveillance', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque de rester trop consensuel face à des décisions difficiles', 'disc-test'),
                    __('Tendance à sur-adapter son style aux attentes des autres', 'disc-test'),
                ),
                'advice'     => array(
                    __('Affirmez votre point de vue même quand il dérange — c\'est aussi servir les autres.', 'disc-test'),
                    __('Fixez un cap clair plutôt que de vous adapter en permanence.', 'disc-test'),
                ),
            ),

            'IC' => array(
                'synthesis'  => __("Votre profil combine le charisme et l'attention aux détails. Vous êtes convaincant parce que vous maîtrisez votre sujet. Vous associez l'enthousiasme à la rigueur pour produire des communications percutantes et solides.", 'disc-test'),
                'strengths'  => array(
                    __('Présentations convaincantes et bien documentées', 'disc-test'),
                    __('Créativité ancrée dans la recherche et les faits', 'disc-test'),
                    __('Communication claire, structurée et engageante', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Tendance à la sur-préparation qui peut freiner l\'action', 'disc-test'),
                    __('Risque de perfectionnisme dans la forme au détriment du fond', 'disc-test'),
                ),
                'advice'     => array(
                    __('Acceptez de livrer avant d\'avoir tout peaufiné.', 'disc-test'),
                    __('Distinguez les situations où la qualité prime et celles où la rapidité suffit.', 'disc-test'),
                ),
            ),

            'CI' => array(
                'synthesis'  => __("Votre profil associe une grande rigueur analytique à une réelle capacité de communication. Vous aimez comprendre en profondeur et vous savez aussi partager vos analyses de façon engageante. Votre crédibilité est renforcée par votre clarté.", 'disc-test'),
                'strengths'  => array(
                    __('Capacité à rendre des sujets complexes accessibles', 'disc-test'),
                    __('Rigueur intellectuelle associée à une aisance relationnelle', 'disc-test'),
                    __('Influence par la qualité du raisonnement partagé', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque d\'intellectualiser les situations relationnelles', 'disc-test'),
                    __('Tendance à la perfection dans la communication qui ralentit la décision', 'disc-test'),
                ),
                'advice'     => array(
                    __('Simplifiez votre message — la clarté vaut mieux que l\'exhaustivité.', 'disc-test'),
                    __('Acceptez que tout le monde ne partage pas votre niveau d\'exigence.', 'disc-test'),
                ),
            ),

            'SC' => array(
                'synthesis'  => __("Votre profil combine la fiabilité et la rigueur. Vous créez des systèmes durables et vous engagez sur la qualité à long terme. Votre régularité et votre précision sont des atouts précieux dans les environnements qui exigent de la consistance.", 'disc-test'),
                'strengths'  => array(
                    __('Processus solides, fiables et bien documentés', 'disc-test'),
                    __('Engagement dans la durée avec des standards élevés', 'disc-test'),
                    __('Approche méthodique qui réduit les risques d\'erreur', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Résistance au changement et inconfort face à l\'imprévu', 'disc-test'),
                    __('Tendance à la paralysie par l\'analyse dans les situations nouvelles', 'disc-test'),
                ),
                'advice'     => array(
                    __('Acceptez l\'expérimentation comme mode d\'apprentissage.', 'disc-test'),
                    __('Développez votre confiance dans les situations non balisées.', 'disc-test'),
                ),
            ),

            'CS' => array(
                'synthesis'  => __("Votre profil associe une grande rigueur analytique à un fort sens de la stabilité. Vous travaillez avec méthode, dans un cadre clair, et vous vous engagez sur la durée. Votre sérieux et votre fiabilité inspirent confiance.", 'disc-test'),
                'strengths'  => array(
                    __('Rigueur analytique associée à une grande régularité', 'disc-test'),
                    __('Fiabilité dans les engagements et les livrables', 'disc-test'),
                    __('Capacité à maintenir des standards élevés sur le long terme', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque de rester dans sa zone de confort au détriment de l\'adaptation', 'disc-test'),
                    __('Tendance à sous-communiquer ses besoins ou ses difficultés', 'disc-test'),
                ),
                'advice'     => array(
                    __('Exposez-vous régulièrement à des contextes nouveaux pour développer votre adaptabilité.', 'disc-test'),
                    __('Partagez vos analyses et vos doutes — les autres en ont besoin.', 'disc-test'),
                ),
            ),

            'DIS' => array(
                'synthesis'  => __("Votre profil nuancé associe trois dimensions fortes : action, influence et stabilité. Vous êtes à la fois moteur, fédérateur et ancre pour votre entourage. Cette polyvalence vous rend très adaptable mais peut parfois manquer de tranchant.", 'disc-test'),
                'strengths'  => array(
                    __('Grande adaptabilité selon les situations', 'disc-test'),
                    __('Capacité à initier, mobiliser et tenir dans la durée', 'disc-test'),
                    __('Leadership situationnel efficace', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque de dilution du focus entre plusieurs styles', 'disc-test'),
                    __('Difficulté à prendre des décisions tranchées ou impopulaires', 'disc-test'),
                ),
                'advice'     => array(
                    __('Identifiez votre registre le plus naturel et assumez-le davantage.', 'disc-test'),
                    __('La polyvalence est une force — à condition de ne pas chercher à plaire à tout le monde.', 'disc-test'),
                ),
            ),

            'DIC' => array(
                'synthesis'  => __("Votre profil nuancé associe détermination, influence et rigueur. Vous avancez vite, vous embarquez les autres et vous vous assurez de la qualité du résultat. Cette combinaison est rare et puissante.", 'disc-test'),
                'strengths'  => array(
                    __('Capacité à décider, convaincre et délivrer avec qualité', 'disc-test'),
                    __('Leadership crédible par l\'action et la rigueur', 'disc-test'),
                    __('Autonomie forte et sens des responsabilités', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque d\'exigence excessive sur soi et sur les autres', 'disc-test'),
                    __('Tendance à manquer de patience envers les profils plus lents', 'disc-test'),
                ),
                'advice'     => array(
                    __('Faites de la place aux profils S dans votre équipe pour équilibrer votre rythme.', 'disc-test'),
                    __('Acceptez que la perfection immédiate n\'est pas toujours possible.', 'disc-test'),
                ),
            ),

            'DSC' => array(
                'synthesis'  => __("Votre profil nuancé combine détermination, constance et rigueur. Vous avancez avec ténacité vers vos objectifs, en maintenant des standards élevés sur la durée. Ce profil est souvent associé à une grande crédibilité professionnelle.", 'disc-test'),
                'strengths'  => array(
                    __('Persévérance dans l\'atteinte d\'objectifs ambitieux', 'disc-test'),
                    __('Rigueur et fiabilité sur la durée', 'disc-test'),
                    __('Capacité à tenir un cap malgré les obstacles', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Manque de souplesse relationnelle qui peut isoler', 'disc-test'),
                    __('Tendance à prioriser le résultat sur le bien-être de l\'équipe', 'disc-test'),
                ),
                'advice'     => array(
                    __('Investissez dans la qualité des relations — elles sont un levier de performance.', 'disc-test'),
                    __('Accueillez les signaux d\'alarme avant qu\'ils deviennent des crises.', 'disc-test'),
                ),
            ),

            'ISC' => array(
                'synthesis'  => __("Votre profil nuancé associe influence, stabilité et rigueur. Vous combinez le soin des relations avec la fiabilité et l'exigence de qualité. Vous êtes souvent perçu comme quelqu'un de sérieux, accessible et digne de confiance.", 'disc-test'),
                'strengths'  => array(
                    __('Relations durables fondées sur la confiance et la qualité', 'disc-test'),
                    __('Capacité à animer et à structurer des environnements collectifs', 'disc-test'),
                    __('Régularité et sérieux dans le travail accompli', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Tendance à éviter les confrontations même quand elles sont nécessaires', 'disc-test'),
                    __('Risque de se sentir épuisé à vouloir tout faire bien pour tout le monde', 'disc-test'),
                ),
                'advice'     => array(
                    __('Apprenez à prioriser — vous ne pouvez pas tout tenir à 100%.', 'disc-test'),
                    __('Osez les désaccords constructifs — ils enrichissent les relations.', 'disc-test'),
                ),
            ),

            'DISC' => array(
                'synthesis'  => __("Votre profil apparaît équilibré entre les quatre dimensions DISC. Vous adaptez facilement votre style selon les contextes et les personnes. Cette flexibilité est une vraie force, à condition de rester ancré dans vos propres valeurs et besoins.", 'disc-test'),
                'strengths'  => array(
                    __('Adaptabilité exceptionnelle face aux situations variées', 'disc-test'),
                    __('Compréhension naturelle des différents styles de fonctionnement', 'disc-test'),
                    __('Capacité à jouer des rôles différents selon les besoins du groupe', 'disc-test'),
                ),
                'vigilance'  => array(
                    __('Risque de sur-adaptation et de perte d\'identité', 'disc-test'),
                    __('Tendance à ne pas affirmer de cap clair, par souci d\'équilibre', 'disc-test'),
                ),
                'advice'     => array(
                    __('Définissez votre propre style de référence pour ne pas toujours vous adapter aux autres.', 'disc-test'),
                    __('Restez fidèle à vos valeurs profondes — c\'est votre ancrage.', 'disc-test'),
                ),
            ),

        );

        // Fallback : si le profil n'est pas dans la liste (ex: combinaisons rares),
        // on construit une description générique à partir de la première dimension
        $desc = $descriptions[$profile_type] ?? null;
        if (!$desc) {
            $first_dim = $profile_type[0];
            $base      = $descriptions[$first_dim] ?? $descriptions['D'];
            $desc = array(
                'synthesis' => $base['synthesis'],
                'strengths' => $base['strengths'],
                'vigilance' => $base['vigilance'],
                'advice'    => $base['advice'],
            );
        }

        // Phrase de contextualisation dynamique (1.2) — explique la combinaison des dimensions
        $dim_context = array(
            'D' => "l'action directe et la prise de décision",
            'I' => "la communication et l'influence relationnelle",
            'S' => "la stabilité et la coopération d'équipe",
            'C' => "la rigueur et le souci du détail",
        );
        if ($profile_type === 'DISC') {
            $contextualization = "Ce profil équilibré traduit une grande polyvalence comportementale : vous mobilisez l'ensemble des registres DISC selon les situations.";
        } else {
            $dims       = str_split($profile_type);
            $dim_labels = array_map(function($d) use ($dim_context) {
                return $dim_context[$d] ?? $d;
            }, $dims);
            if (count($dim_labels) === 1) {
                $contextualization = 'Ce profil traduit une orientation marquée vers ' . $dim_labels[0] . '.';
            } else {
                $last              = array_pop($dim_labels);
                $contextualization = 'Ce profil traduit une combinaison de ' . implode(', ', $dim_labels) . ' et ' . $last . '.';
            }
        }

        return array(
            'title'             => $profile_title,
            'synthesis'         => $desc['synthesis'],
            'contextualization' => $contextualization,
            'strengths'         => $desc['strengths'],
            'vigilance'         => $desc['vigilance'],
            'advice'            => $desc['advice'],
            'contrast'          => $contrast,
            'contrast_level'    => $contrast_info,
            // Compatibilité avec l'ancien format pour l'email
            'description'       => $desc['synthesis'],
            'development'       => $desc['advice'][0] ?? '',
            'subtitle'          => $contrast_info['label'],
        );
    }
}