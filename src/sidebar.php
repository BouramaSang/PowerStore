<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="apple-touch-icon" sizes="180x180" href="inapp-1.0.0/assets/images/favicon_io/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="inapp-1.0.0/assets/images/favicon_io/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="inapp-1.0.0/assets/images/favicon_io/favicon-16x16.png">
  <link rel="manifest" href="inapp-1.0.0/assets/images/favicon_io/site.webmanifest">
    <!-- Bootstrap CSS --> 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tabler Icons -->
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
  <!-- Custom CSS -->
 <link rel="stylesheet" href="inapp-1.0.0/assets/css/style.css">
 <style>
  /* Style de base pour le logo complet */
  .logo-full {
      display: flex;
      align-items: center;
      width: 100%;
  }
  
  /* Conteneur du texte/logo */
  .logo-text {
      margin-left: 0;
      width: 100%;
      display: block;
  }
  
  /* Image dans le logo complet */
  .logo-text img {
      width: 100%;
      height: auto;
      display: block;
  }
  
  /* Logo réduit - caché par défaut */
  .logo-collapsed {
      display: none;
      height: 32px;
      width: auto;
      margin: 0 auto;
  }
  
  /* Quand la sidebar est réduite */
  .sidebar.collapsed .logo-full {
      display: none !important;
  }
  
  .sidebar.collapsed .logo-collapsed {
      display: block !important;
      margin: 0 auto;
  }
  
  /* Centrer le contenu dans la sidebar réduite */
  .sidebar.collapsed .logo-area a {
      justify-content: center !important;
      width: 100%;
  }
  
  /* Ajustement du padding pour centrer parfaitement */
  .sidebar.collapsed .logo-area {
      padding: 1rem 0;
      display: flex;
      justify-content: center;
  }

  /* Style optionnel pour la zone logo */
  .logo-area {
      padding: 1rem;
  }
  
  .logo-area a {
      text-decoration: none;
  }

  /* Style pour les liens actifs - ORANGE TRANSPARENT */
  .sidebar .nav-link.active {
      background-color: rgba(255, 165, 0, 0.15) !important; /* Orange transparent à 15% */
      color: #FF8C00 !important; /* Orange foncé pour le texte */
      border-left: 3px solid #FF8C00; /* Bordure orange à gauche */
      border-radius: 0 4px 4px 0; /* Arrondi uniquement à droite */
      font-weight: 500;
  }

  /* Style de base des liens */
  .sidebar .nav-link {
      transition: all 0.3s ease;
      color: #495057;
      border-left: 3px solid transparent; /* Pour garder l'alignement */
      margin: 2px 0;
  }

  /* Effet hover sur les liens - ORANGE TRÈS LÉGER */
  .sidebar .nav-link:hover {
      background-color: rgba(255, 165, 0, 0.08) !important;
      color: #FF8C00 !important;
      border-left: 3px solid rgba(255, 140, 0, 0.5);
  }

  /* Icônes en orange pour le lien actif */
  .sidebar .nav-link.active i {
      color: #FF8C00 !important;
  }

  /* Icônes en orange au hover */
  .sidebar .nav-link:hover i {
      color: #FF8C00 !important;
  }

  /* Option : Différentes opacités d'orange */
  /* Pour un orange plus foncé : rgba(255, 165, 0, 0.25) */
  /* Pour un orange plus clair : rgba(255, 165, 0, 0.1) */
  /* Pour un orange très transparent : rgba(255, 165, 0, 0.05) */
 </style>
</head>
<body>
    <!-- SIDEBAR -->
  <aside id="sidebar" class="sidebar">
    <div class="logo-area">
     <a href="index.php" class="d-inline-flex align-items-center w-100">
        <!-- Logo complet (avec classe logo-full) -->
        <div class="logo-full w-100">
            <span class="logo-text">
                <img src="/inapp-1.0.0/src/assets/images/logo-icon2.svg" alt="Texte logo">
            </span>
        </div>
        
        <!-- Logo réduit (visible uniquement quand collapsed) -->
        <img src="/inapp-1.0.0/src/assets/images/logo-icon3.svg" alt="Logo réduit" class="logo-collapsed">
     </a>
    </div>
    
    <ul class="nav flex-column">
      <li class="px-4 py-2"><small class="nav-text">Principal</small></li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/Frontend/dashboard.php"><i class="ti ti-home"></i><span
            class="nav-text">Tableau de bord</span></a></li>
        <li class="px-4 py-2"><small class="nav-text">Enregistrements</small></li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/Frontend/category/index_category.php"><i class="ti ti-category"></i><span
            class="nav-text">Catégories</span></a></li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/Frontend/produits/index_produit.php"><i class="ti ti-package"></i><span class="nav-text">Produits</span></a></li>
     <li><a class="nav-link" href="/inapp-1.0.0/src/Frontend/commandes/index_commande.php"><i class="ti ti-truck"></i><span class="nav-text">Commandes</span></a></li>   
    <li><a class="nav-link" href="/inapp-1.0.0/src/Frontend/factures/index_facture.php"><i class="ti ti-file-text"></i><span class="nav-text">Factures</span></a>
      </li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/docs.php"><i class="ti ti-file-text"></i><span class="nav-text">Docs</span></a></li>

      <li class="px-4 pt-4 pb-2"><small class="nav-text">Compte</small></li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/signin.php"><i class="ti ti-logout"></i><span class="nav-text">Se connecter</span></a>
      </li>
      <li><a class="nav-link" href="/inapp-1.0.0/src/signup.php"><i class="ti ti-user-plus"></i><span class="nav-text">S'inscrire</span></a></li>
    </ul>
  </aside>
  
 
  
  <!-- Bootstrap JS cdn -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- ApexCharts -->
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  
  <!-- Script complet avec gestion des liens actifs et toggle sidebar -->
  <script>
    (function() {
      'use strict';
      
      // ========== GESTION DES LIENS ACTIFS ==========
      function setActiveLink() {
        // Récupère le chemin complet de la page actuelle
        const currentPath = window.location.pathname;
        
        // Récupère tous les liens de navigation
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        
        // Variable pour stocker le meilleur match
        let bestMatch = null;
        let bestMatchLength = 0;
        
        // Parcourt tous les liens pour trouver le plus spécifique qui correspond
        navLinks.forEach(link => {
          const href = link.getAttribute('href');
          if (!href) return;
          
          // Vérifie si le chemin actuel se termine par le href
          // ou si le href est inclus dans le chemin actuel
          if (currentPath.endsWith(href) || currentPath.includes(href)) {
            const matchLength = href.length;
            if (matchLength > bestMatchLength) {
              bestMatch = link;
              bestMatchLength = matchLength;
            }
          }
        });
        
        // Si aucun match trouvé, essaie de trouver par le nom du fichier uniquement
        if (!bestMatch) {
          const currentPage = currentPath.split('/').pop() || 'index.php';
          
          navLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (!href) return;
            
            const linkPage = href.split('/').pop();
            if (linkPage === currentPage) {
              bestMatch = link;
            }
          });
        }
        
        // Supprime la classe active de tous les liens
        navLinks.forEach(link => {
          link.classList.remove('active');
        });
        
        // Ajoute la classe active au lien correspondant
        if (bestMatch) {
          bestMatch.classList.add('active');
        } else {
          // Si aucun match, active Dashboard par défaut
          const dashboardLink = document.querySelector('.sidebar .nav-link[href*="index.php"]');
          if (dashboardLink) {
            dashboardLink.classList.add('active');
          }
        }
      }
      
      // ========== GESTION DU CLIC SUR LES LIENS ==========
      function handleLinkClick(event) {
        const link = event.currentTarget;
        
        // Supprime la classe active de tous les liens
        const allLinks = document.querySelectorAll('.sidebar .nav-link');
        allLinks.forEach(l => l.classList.remove('active'));
        
        // Ajoute la classe active au lien cliqué
        link.classList.add('active');
      }
      
      // ========== GESTION DU TOGGLE SIDEBAR ==========
      function initSidebarToggle() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        
        if (toggleBtn && sidebar) {
          toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
          });
        }
      }
      
      // ========== GESTION DU MENU MOBILE (optionnel) ==========
      function initMobileMenu() {
        const mobileBtn = document.getElementById('mobileBtn');
        const overlay = document.getElementById('overlay');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileBtn) {
          mobileBtn.addEventListener('click', () => {
            if (sidebar) sidebar.classList.add('mobile-show');
            if (overlay) overlay.classList.add('show');
          });
        }
        
        if (overlay) {
          overlay.addEventListener('click', () => {
            if (sidebar) sidebar.classList.remove('mobile-show');
            if (overlay) overlay.classList.remove('show');
          });
        }
      }
      
      // ========== INITIALISATION ==========
      function init() {
        // Définit le lien actif au chargement
        setActiveLink();
        
        // Initialise le toggle sidebar
        initSidebarToggle();
        
        // Initialise le menu mobile
        initMobileMenu();
        
        // Ajoute les écouteurs de clic sur tous les liens
        const navLinks = document.querySelectorAll('.sidebar .nav-link');
        navLinks.forEach(link => {
          link.addEventListener('click', handleLinkClick);
        });
        
        // Gère aussi le clic sur le logo (optionnel)
        const logoLink = document.querySelector('.logo-area a');
        if (logoLink) {
          logoLink.addEventListener('click', function() {
            const dashboardLink = document.querySelector('.sidebar .nav-link[href*="index.php"]');
            if (dashboardLink) {
              const allLinks = document.querySelectorAll('.sidebar .nav-link');
              allLinks.forEach(l => l.classList.remove('active'));
              dashboardLink.classList.add('active');
            }
          });
        }
      }
      
      // Lance l'initialisation quand le DOM est prêt
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
      } else {
        init();
      }
      
    })();
  </script>
  
  <!-- Bootstrap JS fichier main -->
  <script src="/inapp-1.0.0/src/assets/js/main.js" type="module"></script>
</body>
</html>