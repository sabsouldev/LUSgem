<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('public/home.html.twig');
    }

    #[Route('/mooks', name: 'app_mooks')]
    public function mooks(): Response
    {
        return $this->render('public/mooks.html.twig');
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contact_form', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');

                return $this->redirectToRoute('app_contact');
            }

            $name = trim((string) $request->request->get('name', ''));
            $email = trim((string) $request->request->get('email', ''));
            $subject = trim((string) $request->request->get('subject', ''));
            $messageText = trim((string) $request->request->get('message', ''));

            if ($name === '' || $email === '' || $subject === '' || $messageText === '') {
                $this->addFlash('error', 'Veuillez remplir tous les champs.');

                return $this->redirectToRoute('app_contact');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Adresse email invalide.');

                return $this->redirectToRoute('app_contact');
            }

            $contactMessage = new ContactMessage();
            $contactMessage->setName(mb_substr($name, 0, 100));
            $contactMessage->setEmail(mb_substr($email, 0, 150));
            $contactMessage->setSubject(mb_substr($subject, 0, 200));
            $contactMessage->setMessage(mb_substr($messageText, 0, 4000));

            $em->persist($contactMessage);
            $em->flush();

            $notification = (new TemplatedEmail())
                ->from(new Address('noreply@lusgem.varyacode.fr', 'Les Univers Singuliers'))
                ->to('gem.perigueux@fondationdelisle.fr')
                ->subject('Nouveau message de contact : ' . $subject)
                ->htmlTemplate('emails/contact_notification.html.twig')
                ->context([
                    'contactMessage' => $contactMessage,
                ]);

            $mailer->send($notification);

            $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('public/contact.html.twig');
    }
}
