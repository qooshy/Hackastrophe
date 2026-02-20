<?php
/**
 * Page de déconnexion
 */

// Destruction de la session
session_destroy();

// Redirection vers l'accueil
setFlashMessage('info', 'Vous avez été déconnecté avec succès.');
redirect('/');