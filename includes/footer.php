</main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Hackastrophe</h3>
                    <p>La plateforme de Bug Bounty et CTF pour pentesters passionnés.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Liens Rapides</h4>
                    <ul>
                        <li><a href="<?php echo url('/'); ?>">Challenges</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="<?php echo url('/account'); ?>">Mon Compte</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo url('/login'); ?>">Connexion</a></li>
                            <li><a href="<?php echo url('/register'); ?>">Inscription</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Catégories</h4>
                    <ul>
                        <?php 
                        $categories = array_slice(CHALLENGE_CATEGORIES, 0, 4);
                        foreach ($categories as $key => $name): 
                        ?>
                            <li><a href="<?php echo url('/?category=' . $key); ?>"><?php echo h($name); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>À Propos</h4>
                    <p>Projet développé dans le cadre du module PHP.</p>
                    <p>Bordeaux YNOV Campus - 2026</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2026 Hackastrophe. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo asset('js/main.js'); ?>"></script>
</body>
</html>