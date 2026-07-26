<footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-newsletter">
                <p class="newsletter-title">Newsletter</p>
                <p class="newsletter-sub">Nouvelles sorties et offres exclusives directement dans votre boîte mail.</p>
                <form class="newsletter-form" id="newsletterForm" novalidate>
                    <input type="email" id="newsletterEmail" name="email" class="newsletter-input" placeholder="Ton adresse email" required>
                    <button type="submit" class="newsletter-btn">Envoyer</button>
                </form>
                <p class="newsletter-message" id="newsletterMessage" role="status" aria-live="polite"></p>
            </div>

            <div class="footer-bottom">
                <p class="footer-brand">APRESBAC</p>
                <nav class="footer-links">
                    <a href="/mentions-legales.php">Mentions légales</a>
                    <a href="/confidentialite.php">Politique de confidentialité</a>
                    <a href="/cgv.php">Conditions générales de vente</a>
                </nav>
                <p class="footer-copy">&copy; <?= date('Y') ?> APRESBAC · Tous droits réservés</p>
            </div>
        </div>
    </footer>
    <script src="/assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('newsletterForm');
        if (!form) return;

        const emailInput = document.getElementById('newsletterEmail');
        const message = document.getElementById('newsletterMessage');
        const button = form.querySelector('.newsletter-btn');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = emailInput.value.trim();
            if (!email) return;

            button.disabled = true;
            const originalLabel = button.textContent;
            button.textContent = 'Envoi...';
            message.textContent = '';
            message.classList.remove('newsletter-message-success', 'newsletter-message-error');

            try {
                const response = await fetch('/newsletter-subscribe.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'email=' + encodeURIComponent(email)
                });
                const data = await response.json();

                message.textContent = data.message;
                message.classList.add(data.success ? 'newsletter-message-success' : 'newsletter-message-error');

                if (data.success) {
                    form.reset();
                }
            } catch (err) {
                message.textContent = 'Une erreur est survenue, réessaie un peu plus tard.';
                message.classList.add('newsletter-message-error');
            } finally {
                button.disabled = false;
                button.textContent = originalLabel;
            }
        });
    });
    </script>
</body>
</html>