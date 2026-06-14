/**
 * Learn Way - Gestionnaire centralisé des requêtes AJAX (Fetch API)
 */
const AJAX = {
    /**
     * Récupère le token CSRF depuis la balise meta
     * @returns {string}
     */
    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    },

    /**
     * Effectue une requête GET asynchrone
     * @param {string} url 
     * @returns {Promise<any>}
     */
    async get(url) {
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                const errData = await response.json().catch(() => ({}));
                throw new Error(errData.error || `Erreur HTTP: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            console.error('AJAX GET Error:', error);
            throw error;
        }
    },

    /**
     * Effectue une requête POST asynchrone avec JSON ou FormData
     * @param {string} url 
     * @param {Object|FormData} data 
     * @returns {Promise<any>}
     */
    async post(url, data) {
        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-Token': this.getCsrfToken()
        };

        let body;
        if (data instanceof FormData) {
            body = data;
            // Ne pas définir Content-Type pour FormData, le navigateur le fera avec la limite (boundary) appropriée
        } else {
            headers['Content-Type'] = 'application/json';
            body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: headers,
                body: body
            });
            const result = await response.json().catch(() => ({ success: false, error: 'Réponse serveur non valide.' }));
            if (!response.ok || !result.success) {
                throw new Error(result.error || `Erreur serveur (${response.status})`);
            }
            return result;
        } catch (error) {
            console.error('AJAX POST Error:', error);
            throw error;
        }
    }
};

// Exposer globalement
window.AJAX = AJAX;
