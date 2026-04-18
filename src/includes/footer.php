<?php
// includes/footer.php
// Footer commun pour toutes les pages
?>
        </div> <!-- Fermeture de .content-wrapper -->
    </div> <!-- Fermeture de .main-content-super -->
    
    <script>
        // Fonction pour basculer le sidebar sur mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Fermer le sidebar quand on clique en dehors sur mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
                if (menuToggle && !menuToggle.contains(event.target) && !sidebar.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
        
        // Confirmation avant suppression
        function confirmDelete(message) {
            return confirm(message || 'Êtes-vous sûr de vouloir supprimer cet élément ?');
        }
        
        // Afficher un message de succès temporaire
        function showSuccessMessage(message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-success';
            alert.innerHTML = message;
            alert.style.position = 'fixed';
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '9999';
            alert.style.animation = 'slideIn 0.3s ease';
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
        
        // Afficher un message d'erreur temporaire
        function showErrorMessage(message) {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger';
            alert.innerHTML = message;
            alert.style.position = 'fixed';
            alert.style.top = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = '9999';
            alert.style.animation = 'slideIn 0.3s ease';
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
        
        // Animation pour les messages
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Activer les tooltips sur les boutons (optionnel)
        document.addEventListener('DOMContentLoaded', function() {
            // Ajouter une classe pour les boutons avec tooltip
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.setAttribute('title', btn.textContent.trim());
            });
        });
        
        // Gestionnaire pour les liens de suppression
        document.addEventListener('DOMContentLoaded', function() {
            const deleteLinks = document.querySelectorAll('.btn-danger');
            deleteLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Confirmer la suppression définitive ?')) {
                        e.preventDefault();
                    }
                });
            });
        });
        
        // Mise à jour automatique de l'horloge (optionnel)
        function updateClock() {
            const clockElement = document.querySelector('.top-bar-right span:first-child');
            if (clockElement) {
                const now = new Date();
                const dateStr = now.toLocaleDateString('fr-FR');
                const timeStr = now.toLocaleTimeString('fr-FR');
                clockElement.innerHTML = `<i class="fas fa-clock"></i> ${dateStr} ${timeStr}`;
            }
        }
        
        // Mettre à jour l'horloge toutes les minutes
        if (document.querySelector('.top-bar-right span:first-child')) {
            setInterval(updateClock, 60000);
        }
        
        // Scroll to top button (optionnel)
        const scrollBtn = document.createElement('button');
        scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        scrollBtn.style.position = 'fixed';
        scrollBtn.style.bottom = '20px';
        scrollBtn.style.right = '20px';
        scrollBtn.style.width = '45px';
        scrollBtn.style.height = '45px';
        scrollBtn.style.borderRadius = '50%';
        scrollBtn.style.background = '#2a5298';
        scrollBtn.style.color = 'white';
        scrollBtn.style.border = 'none';
        scrollBtn.style.cursor = 'pointer';
        scrollBtn.style.display = 'none';
        scrollBtn.style.zIndex = '999';
        scrollBtn.style.transition = 'all 0.3s';
        
        scrollBtn.onclick = () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };
        
        document.body.appendChild(scrollBtn);
        
        window.onscroll = () => {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        };
        
        // Survol du bouton scroll
        scrollBtn.onmouseover = () => {
            scrollBtn.style.transform = 'translateY(-3px)';
            scrollBtn.style.boxShadow = '0 5px 15px rgba(0,0,0,0.3)';
        };
        
        scrollBtn.onmouseout = () => {
            scrollBtn.style.transform = 'translateY(0)';
            scrollBtn.style.boxShadow = 'none';
        };
    </script>
    
    <!-- Footer HTML -->
    <footer style="
        background: white;
        padding: 20px 30px;
        text-align: center;
        border-top: 1px solid #e0e0e0;
        margin-top: 30px;
        font-size: 13px;
        color: #666;
    ">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?> - Tous droits réservés</p>
        <p style="margin-top: 5px; font-size: 11px;">
            Version 1.0 | Développé avec <i class="fas fa-heart" style="color: #ff4757;"></i> pour Super Admin
        </p>
    </footer>
</body>
</html>