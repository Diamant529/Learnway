/**
 * Learn Way - Interactivité et composants globaux
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Gestion du menu latéral mobile (Sidebar Toggle)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });
        
        // Fermer la sidebar mobile en cliquant à l'extérieur
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== sidebarToggle) {
                sidebar.classList.remove('open');
            }
        });
    }

    // 2. Fermeture "Light Dismiss" pour TOUS les éléments <dialog> (clic en dehors)
    document.addEventListener('click', (e) => {
        if (e.target.tagName === 'DIALOG' && e.target.hasAttribute('open')) {
            const rect = e.target.getBoundingClientRect();
            const isInDialog = (
                rect.top <= e.clientY &&
                e.clientY <= rect.top + rect.height &&
                rect.left <= e.clientX &&
                e.clientX <= rect.left + rect.width
            );
            if (!isInDialog) {
                e.target.close();
            }
        }
    });

    // 3. Disparition progressive des alertes après 6 secondes
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 600);
        }, 6000);
    });
});

/**
 * Affiche une alerte toast dynamique en haut à droite
 * @param {string} message 
 * @param {string} type 'success' | 'danger' | 'warning'
 */
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '10px';
        container.style.maxWidth = '350px';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} glass-panel alert-auto-dismiss`;
    toast.style.margin = '0';
    toast.style.boxShadow = 'var(--shadow-lg)';
    toast.style.animation = 'slideIn 0.3s ease forwards';
    toast.style.borderLeft = `4px solid var(--color-${type === 'danger' ? 'danger' : (type === 'warning' ? 'warning' : 'success')})`;
    
    toast.innerHTML = `
        <div style="flex-grow:1">${message}</div>
        <button style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    container.appendChild(toast);
    
    // Auto remove
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-15px)';
        setTimeout(() => toast.remove(), 500);
    }, 5000);
}

// Ajouter le style CSS requis pour l'entrée des toasts si non présent
const style = document.createElement('style');
style.innerHTML = `
@keyframes slideIn {
    from { transform: translateX(100%) translateY(0); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
`;
document.head.appendChild(style);
window.showToast = showToast;
