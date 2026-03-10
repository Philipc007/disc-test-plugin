/**
 * Frontend JavaScript pour le Test DISC
 * Gère toute l'interactivité du test
 */

(function($) {
    'use strict';
    
    // Variables globales pour le test
    let sessionToken = null;
    let currentQuestionIndex = 0;
    let responses = [];
    let questionStartTime = null;
    let testStartTime = null;
    
    /**
     * Initialisation au chargement du DOM
     */
    $(document).ready(function() {
        initDISCTest();
    });
    
    /**
     * Initialise le test DISC
     */
    function initDISCTest() {
        const $container = $('.disc-test-container');
        
        if (!$container.length) {
            return;
        }
        
        // Génère un token de session unique
        sessionToken = generateSessionToken();
        
        // Bouton de démarrage
        $('.disc-btn-start').on('click', function() {
            startTest();
        });
        
        // Boutons de navigation
        $('.disc-btn-next').on('click', function() {
            handleNextQuestion();
        });
        
        $('.disc-btn-prev').on('click', function() {
            handlePreviousQuestion();
        });
        
        // Gestion des choix radio
        $('input[type="radio"]').on('change', function() {
            handleRadioChange($(this));
        });
        
        // Soumission du formulaire de contact
        $('#disc-contact-form').on('submit', function(e) {
            e.preventDefault();
            submitContactForm();
        });
        
        // Validation email en temps réel
        $('#disc-email').on('blur', function() {
            validateEmail($(this).val());
        });
        
        // Partage LinkedIn
        $('.disc-btn-share-linkedin').on('click', function() {
            shareOnLinkedIn();
        });
    }
    
    /**
     * Démarre le test
     */
    function startTest() {
        testStartTime = Date.now();
        questionStartTime = Date.now();
        
        showScreen('questions');
        
        // Log l'événement de démarrage
        $.post(discTest.ajaxUrl, {
            action: 'disc_submit_response',
            nonce: discTest.nonce,
            session_token: sessionToken,
            event: 'test_started'
        });
    }
    
    /**
     * Gère le changement de sélection radio
     */
    function handleRadioChange($radio) {
        const $question = $radio.closest('.disc-question');
        const questionId = $question.data('question-id');
        const name = $radio.attr('name');
        
        // Empêche de sélectionner la même dimension pour "le plus" et "le moins"
        if (name.includes('most_like')) {
            const dimension = $radio.val();
            $question.find('input[name^="least_like"][value="' + dimension + '"]').prop('disabled', true);
            $question.find('input[name^="least_like"]').not('[value="' + dimension + '"]').prop('disabled', false);
        } else {
            const dimension = $radio.val();
            $question.find('input[name^="most_like"][value="' + dimension + '"]').prop('disabled', true);
            $question.find('input[name^="most_like"]').not('[value="' + dimension + '"]').prop('disabled', false);
        }
        
        // Cache l'erreur si elle était affichée
        $question.find('.disc-question-error').hide();
    }
    
    /**
     * Gère le passage à la question suivante
     */
    function handleNextQuestion() {
        const $currentQuestion = $('.disc-question').eq(currentQuestionIndex);
        const questionId = $currentQuestion.data('question-id');
        
        // Valide que les deux choix sont faits
        const mostLike = $currentQuestion.find('input[name^="most_like"]:checked').val();
        const leastLike = $currentQuestion.find('input[name^="least_like"]:checked').val();
        
        if (!mostLike || !leastLike) {
            $currentQuestion.find('.disc-question-error').show();
            return;
        }
        
        if (mostLike === leastLike) {
            $currentQuestion.find('.disc-question-error').show();
            return;
        }
        
        // Calcule le temps de réponse
        const responseTime = (Date.now() - questionStartTime) / 1000;
        
        // Enregistre la réponse
        responses[currentQuestionIndex] = {
            question_id: questionId,
            most_like: mostLike,
            least_like: leastLike,
            response_time: responseTime
        };
        
        const totalQuestions = $('.disc-question').length;
        
        // Si c'est la dernière question
        if (currentQuestionIndex === totalQuestions - 1) {
            showScreen('contact');
        } else {
            // Passe à la question suivante
            currentQuestionIndex++;
            showQuestion(currentQuestionIndex);
            updateProgressBar();
            questionStartTime = Date.now();
        }
    }
    
    /**
     * Gère le retour à la question précédente
     */
    function handlePreviousQuestion() {
        if (currentQuestionIndex > 0) {
            currentQuestionIndex--;
            showQuestion(currentQuestionIndex);
            updateProgressBar();
            questionStartTime = Date.now();
        }
    }
    
    /**
     * Affiche une question spécifique
     */
    function showQuestion(index) {
        $('.disc-question').hide();
        $('.disc-question').eq(index).fadeIn(300);
        
        // Scroll vers le haut
        $('.disc-test-container').get(0).scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }
    
    /**
     * Met à jour la barre de progression
     */
    function updateProgressBar() {
        const totalQuestions = $('.disc-question').length;
        const progress = ((currentQuestionIndex + 1) / totalQuestions) * 100;
        
        $('.disc-progress-fill').css('width', progress + '%');
        $('.disc-current-question').text(currentQuestionIndex + 1);
    }
    
    /**
     * Affiche un écran spécifique
     */
    function showScreen(screenName) {
        $('.disc-screen').removeClass('active').hide();
        $('.disc-screen-' + screenName).addClass('active').fadeIn(300);
        
        // Scroll vers le haut
        $('.disc-test-container').get(0).scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }
    
    /**
     * Valide une adresse email
     */
    function validateEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const $input = $('#disc-email');
        
        if (!regex.test(email)) {
            $input.addClass('error');
            return false;
        }
        
        $input.removeClass('error');
        return true;
    }
    
    /**
     * Soumet le formulaire de contact et calcule les résultats
     */
    function submitContactForm() {
        const $form = $('#disc-contact-form');
        const $submitBtn = $form.find('.disc-btn-submit');
        const $errorDiv = $form.find('.disc-form-error');
        
        // Récupère les données du formulaire
        const formData = {
            action: 'disc_submit_contact',
            nonce: discTest.nonce,
            session_token: sessionToken,
            email: $('#disc-email').val(),
            first_name: $('#disc-first-name').val(),
            last_name: $('#disc-last-name').val(),
            company: $('#disc-company').val(),
            position: $('#disc-position').val(),
            consent: $('#disc-consent').is(':checked') ? 1 : 0,
            responses: JSON.stringify(responses)
        };
        
        // Valide le formulaire
        if (!formData.first_name || formData.first_name.length < 2) {
            showError($errorDiv, discTest.strings.required);
            return;
        }
        
        if (!formData.last_name || formData.last_name.length < 2) {
            showError($errorDiv, discTest.strings.required);
            return;
        }
        
        if (!validateEmail(formData.email)) {
            showError($errorDiv, discTest.strings.emailInvalid);
            return;
        }
        
        if (!formData.consent) {
            showError($errorDiv, 'Vous devez accepter la politique de confidentialité.');
            return;
        }
        
        // Affiche le loader
        showLoader();
        $submitBtn.prop('disabled', true).text(discTest.strings.submitting);
        
        // Envoie la requête AJAX
        $.ajax({
            url: discTest.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    displayResults(response.data);
                } else {
                    showError($errorDiv, response.data.message || discTest.strings.error);
                    $submitBtn.prop('disabled', false).text('Recevoir mes résultats');
                }
            },
            error: function() {
                hideLoader();
                showError($errorDiv, discTest.strings.error);
                $submitBtn.prop('disabled', false).text('Recevoir mes résultats');
            }
        });
    }
    
    /**
     * Affiche les résultats du test
     */
    function displayResults(data) {
        // Affiche l'écran des résultats
        showScreen('results');
        
        // Affiche le type de profil
        $('.disc-profile-type').text(data.profile_type);
        
        // Crée le graphique
        createChart(data.scores);
        
        // Affiche la description du profil — structure 6 blocs v1.3
        const description = data.profile_description;

        const $content = $('<div>', { class: 'disc-profile-content' });

        // BLOC A — Titre (déjà affiché via disc-profile-type, on affiche le titre complet ici)
        $content.append($('<h3>', { class: 'disc-profile-title' }).text(description.title));

        // BLOC B — Synthèse
        $content.append($('<p>', { class: 'disc-synthesis' }).text(description.synthesis));

        // Phrase de contextualisation (1.2)
        if (description.contextualization) {
            $content.append($('<p>', { class: 'disc-contextualization' }).text(description.contextualization));
        }

        // Niveau de contraste (1.3) — phrase explicative
        if (description.contrast_level) {
            var contrastText = description.contrast_level.explanation
                ? 'Profil ' + description.contrast_level.label + ' — ' + description.contrast_level.explanation
                : 'Profil ' + description.contrast_level.label + ' (contraste : ' + description.contrast + ' pts)';
            $content.append($('<p>', { class: 'disc-contrast-level' }).text(contrastText));
        }

        // BLOC D — Forces
        if (description.strengths && description.strengths.length) {
            $content.append($('<h4>').text('Vos forces probables'));
            const $list = $('<ul>');
            description.strengths.forEach(function(s) { $list.append($('<li>').text(s)); });
            $content.append($list);
        }

        // BLOC E — Points de vigilance
        if (description.vigilance && description.vigilance.length) {
            $content.append($('<h4>').text('Points de vigilance'));
            const $list = $('<ul>');
            description.vigilance.forEach(function(v) { $list.append($('<li>').text(v)); });
            $content.append($list);
        }

        // BLOC F — Conseils pratiques
        if (description.advice && description.advice.length) {
            $content.append($('<h4>').text('Conseils pratiques'));
            const $list = $('<ul>');
            description.advice.forEach(function(a) { $list.append($('<li>').text(a)); });
            $content.append($list);
        }

        $('.disc-profile-description').empty().append($content);
        
        // Affiche l'avertissement de cohérence si nécessaire
        if (data.show_consistency_warning) {
            $('.disc-consistency-notice').show();
        }
    }
    
    /**
     * Crée le graphique des scores avec Chart.js
     */
    function createChart(scores) {
        const ctx = document.getElementById('disc-chart');
        
        if (!ctx) {
            return;
        }
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    'Dominance',
                    'Influence',
                    'Stabilité',
                    'Conformité'
                ],
                datasets: [{
                    label: 'Tendance',
                    data: [
                        scores.D,
                        scores.I,
                        scores.S,
                        scores.C
                    ],
                    backgroundColor: [
                        'rgba(220, 38, 38, 0.8)',   // Rouge pour D
                        'rgba(234, 179, 8, 0.8)',   // Jaune pour I
                        'rgba(34, 197, 94, 0.8)',   // Vert pour S
                        'rgba(59, 130, 246, 0.8)'   // Bleu pour C
                    ],
                    borderColor: [
                        'rgba(220, 38, 38, 1)',
                        'rgba(234, 179, 8, 1)',
                        'rgba(34, 197, 94, 1)',
                        'rgba(59, 130, 246, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                indexAxis: 'y', // Barres horizontales
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Score : ' + context.parsed.x + '/100';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value;
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    /**
     * Partage sur LinkedIn
     * LinkedIn ne permet plus de pré-remplir le texte via URL.
     * On affiche donc le texte à copier, puis on ouvre LinkedIn.
     */
    function shareOnLinkedIn() {
        const profileType = $('.disc-profile-type').text();
        const shareText = 'Je viens de découvrir mon profil DISC : ' + profileType + ' 🎯\n'
            + 'Un outil puissant pour mieux se connaître en tant que leader.\n'
            + 'Découvrez le vôtre → ' + window.location.href;

        // Copie dans le presse-papier si disponible
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shareText).then(function() {
                showLinkedInModal(shareText);
            }).catch(function() {
                showLinkedInModal(shareText);
            });
        } else {
            showLinkedInModal(shareText);
        }
    }

    /**
     * Affiche la modale de partage LinkedIn
     */
    function showLinkedInModal(shareText) {
        // Supprime une modale existante
        $('#disc-linkedin-modal').remove();

        const modal = $('<div id="disc-linkedin-modal" style="'
            + 'position:fixed;top:0;left:0;width:100%;height:100%;'
            + 'background:rgba(0,0,0,0.6);z-index:99999;'
            + 'display:flex;align-items:center;justify-content:center;">'
            + '<div style="background:#fff;border-radius:12px;padding:30px;max-width:480px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);">'
            + '<h3 style="margin:0 0 12px;font-size:18px;">Partager sur LinkedIn</h3>'
            + '<p style="margin:0 0 12px;color:#555;font-size:14px;">LinkedIn ne permet plus de pré-remplir le texte. Copiez ce message, puis collez-le dans votre post :</p>'
            + '<textarea id="disc-share-text" style="width:100%;height:100px;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:13px;resize:none;">' + shareText + '</textarea>'
            + '<p id="disc-copy-confirm" style="color:#22c55e;font-size:13px;margin:6px 0 0;display:none;">✓ Texte copié dans le presse-papier !</p>'
            + '<div style="display:flex;gap:10px;margin-top:16px;">'
            + '<button id="disc-copy-btn" style="flex:1;padding:10px;background:#667eea;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">Copier le texte</button>'
            + '<button id="disc-linkedin-btn" style="flex:1;padding:10px;background:#0077b5;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;">Ouvrir LinkedIn</button>'
            + '<button id="disc-modal-close" style="padding:10px 14px;background:#f1f1f1;border:none;border-radius:6px;cursor:pointer;font-size:14px;">✕</button>'
            + '</div></div></div>');

        $('body').append(modal);

        $('#disc-copy-btn').on('click', function() {
            const text = $('#disc-share-text').val();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    $('#disc-copy-confirm').show();
                });
            } else {
                $('#disc-share-text').select();
                document.execCommand('copy');
                $('#disc-copy-confirm').show();
            }
        });

        $('#disc-linkedin-btn').on('click', function() {
            const url = encodeURIComponent(window.location.href);
            window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + url, '_blank', 'width=600,height=600');
        });

        $('#disc-modal-close, #disc-linkedin-modal').on('click', function(e) {
            if (e.target === this) {
                $('#disc-linkedin-modal').remove();
            }
        });

        $('#disc-linkedin-modal > div').on('click', function(e) {
            e.stopPropagation();
        });
    }
    
    /**
     * Affiche le loader
     */
    function showLoader() {
        $('.disc-loading-overlay').fadeIn(200);
    }
    
    /**
     * Cache le loader
     */
    function hideLoader() {
        $('.disc-loading-overlay').fadeOut(200);
    }
    
    /**
     * Affiche un message d'erreur
     */
    function showError($container, message) {
        $container.empty().append(
            $('<p>', { class: 'error', text: message })
        ).fadeIn(300);

        setTimeout(function() {
            $container.fadeOut(300);
        }, 5000);
    }
    
    /**
     * Génère un token de session unique — 64 caractères hex
     * Format compatible avec le regex PHP /^[0-9a-f]{64}$/ pour la corrélation des logs
     */
    function generateSessionToken() {
        if (window.crypto && window.crypto.getRandomValues) {
            var array = new Uint8Array(32);
            window.crypto.getRandomValues(array);
            return Array.from(array, function(b) { return b.toString(16).padStart(2, '0'); }).join('');
        }
        // Fallback navigateur sans crypto API (très rare)
        var token = '';
        for (var i = 0; i < 64; i++) {
            token += Math.floor(Math.random() * 16).toString(16);
        }
        return token;
    }
    
})(jQuery);