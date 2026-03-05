# Script PowerShell simplifie - Creation arborescence plugin DISC Test
# Usage: .\setup-simple.ps1

Write-Host "Creation de l'arborescence du plugin DISC Test..." -ForegroundColor Green
Write-Host ""

# Creer la structure de base
Write-Host "Creation des dossiers..." -ForegroundColor Yellow

New-Item -ItemType Directory -Force -Path "disc-test\includes" | Out-Null
New-Item -ItemType Directory -Force -Path "disc-test\assets\css" | Out-Null
New-Item -ItemType Directory -Force -Path "disc-test\assets\js" | Out-Null
New-Item -ItemType Directory -Force -Path "disc-test\build" | Out-Null
New-Item -ItemType Directory -Force -Path "disc-test\languages" | Out-Null

Write-Host "OK - Dossiers crees" -ForegroundColor Green
Write-Host ""

# Creer le fichier principal
Write-Host "Creation des fichiers PHP..." -ForegroundColor Yellow

@'
<?php
/**
 * Plugin Name: Test DISC Lead Magnet
 * Plugin URI: https://libermouv.com
 * Description: Plugin complet pour administrer un test DISC professionnel comme lead magnet
 * Version: 1.0.0
 * Author: LIBERMOUV
 * License: GPL v2 or later
 * Text Domain: disc-test
 */

// A COMPLETER : Copiez le contenu de l'artifact "disc-test.php (Fichier Principal)"

if (!defined('ABSPATH')) {
    exit;
}
'@ | Out-File -FilePath "disc-test\disc-test.php" -Encoding UTF8

# Creer les fichiers de classes PHP
$classes = @(
    "class-disc-database.php",
    "class-disc-security.php",
    "class-disc-renderer.php",
    "class-disc-frontend.php",
    "class-disc-email.php",
    "class-disc-admin.php"
)

foreach ($class in $classes) {
    @"
<?php
/**
 * Fichier: includes/$class
 * A COMPLETER : Copiez le contenu depuis les artifacts Claude
 */

if (!defined('ABSPATH')) {
    exit;
}

// TODO: Copier le contenu de la classe depuis l'artifact correspondant
"@ | Out-File -FilePath "disc-test\includes\$class" -Encoding UTF8
}

Write-Host "OK - Fichiers PHP crees" -ForegroundColor Green
Write-Host ""

# Creer les fichiers CSS
Write-Host "Creation des fichiers CSS..." -ForegroundColor Yellow

@'
/**
 * Frontend CSS pour le Test DISC
 * A COMPLETER : Copiez le contenu de l'artifact "DISC Test - Frontend CSS"
 */

/* TODO: Copier le CSS depuis l'artifact */
'@ | Out-File -FilePath "disc-test\assets\css\frontend.css" -Encoding UTF8

@'
/**
 * Admin CSS pour le Test DISC
 * A COMPLETER : Copiez le contenu de l'artifact "DISC Test - Admin CSS"
 */

/* TODO: Copier le CSS depuis l'artifact */
'@ | Out-File -FilePath "disc-test\assets\css\admin.css" -Encoding UTF8

@'
/**
 * Block Editor CSS
 * A COMPLETER : Copiez le contenu de l'artifact "DISC Test - Bloc Gutenberg CSS"
 */

/* TODO: Copier le CSS depuis l'artifact */
'@ | Out-File -FilePath "disc-test\assets\css\block-editor.css" -Encoding UTF8

Write-Host "OK - Fichiers CSS crees" -ForegroundColor Green
Write-Host ""

# Creer les fichiers JavaScript
Write-Host "Creation des fichiers JavaScript..." -ForegroundColor Yellow

@'
/**
 * Frontend JavaScript pour le Test DISC
 * A COMPLETER : Copiez le contenu de l'artifact "DISC Test - Frontend JavaScript"
 */

// TODO: Copier le JavaScript depuis l'artifact
'@ | Out-File -FilePath "disc-test\assets\js\frontend.js" -Encoding UTF8

@'
/**
 * Admin JavaScript pour le Test DISC
 * Ce fichier peut rester vide pour le MVP
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        console.log('DISC Test Admin JS loaded');
    });
    
})(jQuery);
'@ | Out-File -FilePath "disc-test\assets\js\admin.js" -Encoding UTF8

Write-Host "OK - Fichiers JavaScript crees" -ForegroundColor Green
Write-Host ""

# Creer .gitkeep
New-Item -ItemType File -Force -Path "disc-test\build\.gitkeep" | Out-Null

# Creer les fichiers SPECS
Write-Host "Creation des fichiers SPECS..." -ForegroundColor Yellow

@'
# Node modules
node_modules/
npm-debug.log*

# Build files
*.zip
dist/

# IDE
.vscode/
.idea/
*.sublime-*

# OS
.DS_Store
Thumbs.db

# Logs
*.log
debug.log

# WordPress
wp-config-local.php

# Temporary files
*.tmp
*.bak
*.swp
*~
'@ | Out-File -FilePath ".gitignore" -Encoding UTF8

@'
# A COMPLETER : Copiez le contenu de l'artifact ".clinerules - Regles du projet"
'@ | Out-File -FilePath ".clinerules" -Encoding UTF8

@'
# A COMPLETER : Copiez le contenu de l'artifact "SPECS.md - Specifications Techniques"
'@ | Out-File -FilePath "SPECS.md" -Encoding UTF8

@'
# A COMPLETER : Copiez le contenu de l'artifact "TASKS.md - Liste des Taches"
'@ | Out-File -FilePath "TASKS.md" -Encoding UTF8

@'
# A COMPLETER : Copiez le contenu de l'artifact "README.md - Documentation GitHub"
'@ | Out-File -FilePath "README.md" -Encoding UTF8

@'
# A COMPLETER : Copiez le contenu de l'artifact "QUICKSTART-CLAUDE-CODE.md"
'@ | Out-File -FilePath "QUICKSTART-CLAUDE-CODE.md" -Encoding UTF8

Write-Host "OK - Fichiers SPECS crees" -ForegroundColor Green
Write-Host ""

# Creer le TODO
@'
# Checklist de Completion des Fichiers

## Fichiers SPECS (Priorite 1)
- [ ] .clinerules
- [ ] SPECS.md
- [ ] TASKS.md
- [ ] README.md
- [ ] QUICKSTART-CLAUDE-CODE.md

## Fichiers PHP du Plugin (Priorite 2)
- [ ] disc-test/disc-test.php
- [ ] disc-test/includes/class-disc-database.php
- [ ] disc-test/includes/class-disc-security.php
- [ ] disc-test/includes/class-disc-renderer.php
- [ ] disc-test/includes/class-disc-frontend.php
- [ ] disc-test/includes/class-disc-email.php
- [ ] disc-test/includes/class-disc-admin.php

## Fichiers CSS (Priorite 3)
- [ ] disc-test/assets/css/frontend.css
- [ ] disc-test/assets/css/admin.css
- [ ] disc-test/assets/css/block-editor.css

## Fichiers JavaScript (Priorite 4)
- [ ] disc-test/assets/js/frontend.js
- [x] disc-test/assets/js/admin.js (deja fonctionnel)

## Instructions
1. Ouvrez chaque fichier marque "A COMPLETER"
2. Allez dans la conversation Claude
3. Trouvez l'artifact correspondant
4. Copiez-collez le contenu
5. Cochez la case dans cette liste
'@ | Out-File -FilePath "TODO-COMPLETION.md" -Encoding UTF8

Write-Host "OK - TODO cree" -ForegroundColor Green
Write-Host ""

# Initialiser Git
Write-Host "Initialisation Git..." -ForegroundColor Yellow
git init
git add .gitignore
git commit -m "chore: add gitignore"
Write-Host "OK - Git initialise" -ForegroundColor Green
Write-Host ""

# Afficher le resume
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Arborescence creee avec succes !" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Structure creee :" -ForegroundColor Yellow
Write-Host "  - .gitignore (complet)"
Write-Host "  - .clinerules (a completer)"
Write-Host "  - SPECS.md (a completer)"
Write-Host "  - TASKS.md (a completer)"
Write-Host "  - README.md (a completer)"
Write-Host "  - QUICKSTART-CLAUDE-CODE.md (a completer)"
Write-Host "  - TODO-COMPLETION.md (checklist)"
Write-Host "  - disc-test/"
Write-Host "      - disc-test.php (a completer)"
Write-Host "      - includes/ (6 classes a completer)"
Write-Host "      - assets/css/ (3 fichiers a completer)"
Write-Host "      - assets/js/ (1 a completer, 1 OK)"
Write-Host "      - build/"
Write-Host ""
Write-Host "Prochaines etapes :" -ForegroundColor Yellow
Write-Host "  1. Ouvrez TODO-COMPLETION.md"
Write-Host "  2. Completez les fichiers depuis les artifacts Claude"
Write-Host "  3. Testez le plugin"
Write-Host "  4. git add . && git commit -m 'feat: complete plugin files'"
Write-Host ""
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "Termine !" -ForegroundColor Green