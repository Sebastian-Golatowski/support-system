<?php

namespace App\Controller;

use App\Entity\Log;
use App\Entity\Ticket;
use App\Entity\User;
use App\Form\TicketFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/', name: 'main_')]
class MainController extends AbstractController
{
    private $userRepository;
    private $ticketRepository;
    private $logRepository;
    private $mailer;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer,)
    {
        $this->ticketRepository = $em->getRepository(Ticket::class);
        $this->userRepository = $em->getRepository(User::class);
        $this->logRepository = $em->getRepository(Log::class);
        $this->mailer = $mailer;
    }

    private  function createLog($what, $ticket, $user){
            
        $log = new Log;
        $dateTime = new \DateTime();

        $log -> setId(Uuid::uuid4());
        $log -> setWhat($what);
        $log -> setDate($dateTime);
        $log -> setTicket($ticket);
        $log -> setByWho($user);

        $this->logRepository->save($log, true);

        return null;
    }

    private function sendMail($user, $subject, $ticketId){

        $email = (new Email())
        ->from($_ENV['EMAIL'])
        ->to($user->getEmail())
        ->subject($subject)
        ->html($this->renderView('email/email.html.twig', [
            'user' => $user,
            'subject' => $subject,
            'ticketId' => $ticketId,
            'kernel' => $_ENV['HOST']
        ]));
        // ->attachFromPath($this->getParameter('kernel.project_dir').'/public/attachments/snake.png', 'photo.png');

        $this->mailer->send($email);

        return null;
    }

    #[Route('/', name: 'dashboard')]
    public function dashboard(): Response
    {
        $opened = sizeof($this->ticketRepository->findBy(['status'=>false]));
        $closed = sizeof($this->ticketRepository->findBy(['status'=>true]));

        return $this->render('main/index.html.twig',[
            'status'=> array($opened, $closed)
        ]);
    }


    #[Route('/tickets/make', name: 'makeTickets')]
    public function make(Request $req, SessionInterface $session): Response
    {
        $ticket = new Ticket();

        $form = $this->createForm(TicketFormType::class, $ticket);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {

            $user = $this->getUser();

            $ticket->setId(Uuid::uuid4());
            $ticket->setCreatedBy($this->userRepository->find($user));
            
            $this->ticketRepository->save($ticket, true);

            $session->set('ticket_created', 'ticket created');

            $this->createLog('Ticket created By', $ticket, $user);

            return $this->redirectToRoute('main_tickets');
        }

        return $this->render('main/make.html.twig', [
            'form' => $form->createView(),
            
        ]);
    }

    #[Route('/ticket/{id}', name: 'ticketInfo')]
    public function ticketInfo($id, Request $req,SessionInterface $session): Response
    {   
        $ticket = $this->ticketRepository->find(['id'=>$id]);
        $names = $this->userRepository->namesOfAgents();

        if($this->isGranted('ROLE_ADMIN')){
            $form = $this->createForm(TicketFormType::class, $ticket, ['edit'=>true, 'agents'=>$names]);
        }
        else{
            $form = $this->createForm(TicketFormType::class, $ticket, ['edit'=>false, 'agents'=>$names]);
        }
        
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->ticketRepository->save($ticket,true);

            $user = $ticket->getBelongsTo();

            $this->createLog('Ticket Asigned To', $ticket, $user);

            $this->sendMail($user, 'you have new ticket', $id);

            $session->set('ticket_assigned', 'ticket assigned to'.$user->getUsername());

            return $this->redirectToRoute('main_tickets');

        }

        return $this->render('main/info.html.twig', [
            'form' => $form->createView(),
            'ticket'=> $ticket
        ]);

    }


    #[Route('/tickets', name: 'tickets')]
    public function tickets(SessionInterface $session): Response
    {

        if($this->isGranted('ROLE_ADMIN')){
            $tickets = $this->ticketRepository->findBy(['status'=>false, 'belongsTo'=>null],['priority'=>'ASC']);
        }
        elseif($this->isGranted('ROLE_AGENT')){
            $tickets = $this->ticketRepository->findBy(['belongsTo'=>$this->getUser(),'status'=>false],['priority'=>'ASC']);
        }
        else{ // is user
            $tickets = $this->ticketRepository->findBy(['createdBy'=>$this->getUser(),'status'=>false]);
        }


        $flashBag='';

        if($value = $session->get('ticket_created')){
            $flashBag = new FlashBag;
            $flashBag->add('success', $value);
            $session->remove('ticket_created');
        }
        elseif($value = $session->get('ticket_assigned')){
            $flashBag = new FlashBag;
            $flashBag->add('success', $value);
            $session->remove('ticket_assigned');
        }
        
        return $this->render('main/tickets.html.twig',[
            'flashBag'=>$flashBag,
            'tickets'=>$tickets
        ]);
    }

    #[Route('/close/{id}', name: 'close')]
    public function closeTicket($id): Response
    {
        $ticket = $this->ticketRepository->find($id);
        $ticket->setStatus(true);

        $this->ticketRepository->save($ticket, true);

        $this->sendMail($ticket->getCreatedBy(), 'your ticket has been closed', $id);

        $this->createLog('Ticket closed by',$ticket, $this->getUser());
    
        return $this->redirectToRoute('main_tickets');
    }

    #[Route('/logs', name: 'logs')]
    public function logs(): Response
    {
        $logs = $this->logRepository->findBy([],['date'=>'DESC']);
        return $this->render('main/logs.html.twig',[
            'logs'=>$logs
        ]);
    }

//     #[Route('/test', name: 'test')]
//     public function test(MailerInterface $mailer): Response
//     {
//         $email = (new Email())
//         ->from($_ENV['EMAIL'])
//         ->to('recipient@example.com')
//         ->subject('Heqqllo')
//         ->html($this->renderView('email/email.html.twig', [
//             'name' => '',
//             ''
//         ]));
//         // ->attachFromPath($this->getParameter('kernel.project_dir').'/public/attachments/podkaszarka.pdf', 'photo.png');

//         $mailer->send($email);

//     }

}
