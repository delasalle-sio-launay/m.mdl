<?php
// Projet Réservations M2L - version web mobile
// Fonction du contrôleur CtrlConfirmerReservation.php : Consulter les reservations d'un utilisateir
// Ecrit le 03/11/2015 par youn
// Ce contrôleur vérifie l'authentification de l'utilisateur
// si l'authentification est bonne, il affiche le menu utilisateur ou administrateur (vue VueMenu.php)
// si l'authentification est incorrecte, il réaffiche la page de connexion (vue VueConnecter.php)
// on teste si le terminal client est sous Android, pour lui proposer de télécharger l'application Android
// tests des variables de session
if ( ! isset ($_SESSION['nom']) == true)  $nom = '';  else  $nom = $_SESSION['nom'];
if ( ! isset ($_SESSION['mdp']) == true)  $mdp = '';  else  $mdp = $_SESSION['mdp'];
if ( ! isset ($_SESSION['niveauUtilisateur']) == true)  $niveauUtilisateur = '';  else  $niveauUtilisateur = $_SESSION['niveauUtilisateur'];
// si l'utilisateur n'est pas encore identifié, il sera automatiquement redirigé vers le contrôleur d'authentification
// (sauf s'il ne peut pas se connecter et demande de se faire envoyer son mot de passe qu'il a oublié)
if ($nom == '' && $action != 'DemanderMdp') header ('Location: index.php?action=Connecter');
// connexion du serveur web à la base MySQL
include_once ('modele/DAO.class.php');
$dao = new DAO();

if ( ! isset ($_POST ["numReservation"]) == true) {
	// si les données n'ont pas été postées, c'est le premier appel du formulaire : affichage de la vue sans message d'erreur
	$msgFooter = 'Confirmer Réservation';
	$themeFooter = $themeNormal;
	include_once ('vues/VueConfirmerReservation.php');
}
else {
	if ( empty ($_POST ["numReservation"]) == true)  $numReservation = ""; else $numReservation = $_POST["numReservation"];
	
	if ($numReservation == '') {
		// si les données sont incomplètes, réaffichage de la vue avec un message explicatif
		$msgFooter = 'Données incomplètes';
		$themeFooter = $themeProbleme;
		include_once ('vues/VueConfirmerReservation.php');
	}
	if ( ! $dao->existeReservation($numReservation) )
	{
		$msgFooter = "Erreur : numéro de réservation inexistant";
		$themeFooter = $themeProbleme;
		include_once ('vues/VueConfirmerReservation.php');
	}
	else {
		if ( ! $dao->estLeCreateur($nom, $numReservation) )
		{
			$msgFooter = "Erreur : vous n'êtes pas l'auteur de cette réservation";
			$themeFooter = $themeProbleme;
			include_once ('vues/VueConfirmerReservation.php');
		}
		else {
			$dao->creerLesDigicodesManquants();
			if ( $dao->getReservation($numReservation)->getStatus() == 0 )
			{
				$msgFooter = "Erreur : cette réservation est déjà confirmée";
				$themeFooter = $themeProbleme;
				include_once ('vues/VueConfirmerReservation.php');
			}
			else {
				if ( $dao->getReservation($numReservation)->getStart_time() < time() )
				{
					$msgFooter = "Erreur : cette réservation est déjà passée";
					$themeFooter = $themeProbleme;
					include_once ('vues/VueConfirmerReservation.php');
				}
				else {
					$dao->confirmerReservation($numReservation);
	
					// recherche de l'adresse mail
					$adrMail = $dao->getUtilisateur($nom)->getEmail();
					// recherche du digicode
					$digicode = $dao->getReservation($numReservation)->getDigicode();
	
					// envoie un mail de confirmation de l'enregistrement
					$sujet = "Confirmation de réservation";
					$message = "Nous avons bien enregistré la confirmation de la réservation N° " . $numReservation . "\n\n";
					$message .= "Le digicode d'accès à la salle est : " . $digicode . "\n";
					$message .= "Il est valable 1 heure avant la réservation, et pendant 1 heure après la réservation.";
					$ok = Outils::envoyerMail($adrMail, $sujet, $message, $ADR_MAIL_EMETTEUR);
	
					if ( $ok )
					{
						$msgFooter = "Enregistrement effectué ; vous allez recevoir un mail de confirmation";
						$themeFooter = $themeNormal;
						include_once ('vues/VueConfirmerReservation.php');
					}
					else
					{
						$msgFooter = "Enregistrement effectué ; l'envoi du mail de confirmation a rencontré un problème";
						$themeFooter = $themeNormal;
						include_once ('vues/VueConfirmerReservation.php');
					}
				}
			}
		}
	}
}

?>