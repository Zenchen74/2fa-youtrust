<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\TwoFactorCodeType;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TwoFactorController extends AbstractController
{
    #[Route('/security/2fa/setup', name: 'app_2fa_setup')]
    public function enable2fa(
        Request $request,
        GoogleAuthenticatorInterface $googleAuthenticator,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Génère une clé secrète uniquement si l'utilisateur n'en a pas encore
        if (!$user->isGoogleAuthenticatorEnabled()) {
            $secret = $request->getSession()->get('2FA_temp_secret');
            if ($secret == null){
                $secret = $googleAuthenticator->generateSecret();
                $request->getSession()->set('2FA_temp_secret', $secret);
            }
        }
        $secret = $request->getSession()->get('2FA_temp_secret');
        $user->setGoogleAuthenticatorSecret($secret);

        // Vérification du code si le formulaire est soumis
        $form = $this->createForm(TwoFactorCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $code = $data['code'];

            // Vérifie si le code est valide
            if ($googleAuthenticator->checkCode($user, $code)) {
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Code valide ! Le 2FA est maintenant activé. Redémarrez votre session pour que les changements soient pris en compte.');
            } else {
                $this->addFlash('error', 'Code invalide. Vérifiez que vous avez bien scanné le QR code et que l\'heure de votre appareil est synchronisée.');
            }

            return $this->redirect($urlGenerator->generate('app_2fa_setup'));
        }

        // Génère l'URL au format otpauth:// pour le QR Code
        $qrCodeUrl = $googleAuthenticator->getQRContent($user);

        return $this->render('security/2fa_setup.html.twig', [
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $user->getGoogleAuthenticatorSecret(),
            'username' => $user->getUserIdentifier(),
            'form' => $form,
        ]);
    }
}
