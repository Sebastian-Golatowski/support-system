<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

#[Route('/users', name: 'users_')]
class UserController extends AbstractController
{
    private $userRepository;

    public function __construct(EntityManagerInterface $em)
    {
        
        $this->userRepository = $em->getRepository(User::class);
    }

    private function flashbagAndRender($user, $form, $info, $value){
        $flashBag = new FlashBag;
        $flashBag->add('danger',$info.$value);

        return $this->render('user/change.html.twig',[
            'user'=>$user,
            'form'=>$form,
            'flashBag'=>$flashBag
            ]);
    }

    #[Route('/', name: 'users')]
    public function options(SessionInterface $session): Response
    {
        $flashBag='';

        if($value = $session->get('user_created')){
            $flashBag = new FlashBag;
            $flashBag->add('success', $value);
            $session->remove('user_created');
        }
    
        return $this->render('user/users.html.twig',[
            'flashBag'=>$flashBag
        ]);
    }

    #[Route('/manage/user/{id}', name: 'infoUser')]
    public function infoUser(Request $req, UserPasswordHasherInterface $passwordHasher, SessionInterface $session, $id): Response
    {
        $flashBag='';
        
        $user = $this->userRepository->find($id);
        $oldUsername = $user->getUsername();
        $oldEmail = $user->getEmail();

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($req);

        if($form->isSubmitted()){

            $newUsername = $req->get('changeName');

            if ($oldUsername != $newUsername) {
                
                $len = strlen($newUsername);
                if($len > 20 or $len < 3){
                    return $this->flashbagAndRender($user, $form,
                        'You know the drill and so do I, 3 <= username <= 20, username given: ', $newUsername);
                }

                $user->setUsername($newUsername);
            }

            $newPassword = $req->get('changePassword');
            if($newPassword != null){
                $len = strlen($newPassword);
                if($len >= 8 and $len <= 30){
                    if(preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/', $newPassword)){

                        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                    }
                    else{
                        return $this->flashbagAndRender($user, $form,
                            'One lowercase letter, one uppercase letter, one number, and one special character, password given: ', $newPassword);
                    }
                }
                else{
                    return $this->flashbagAndRender($user, $form,
                        'You know the drill and so do I, 8 <= password <= 30, password given: ', $newPassword);
                }
            }
            
            $newEmail= $req->get('changeEmail');
            if($oldEmail != $newEmail){

                if(strlen($newEmail) == 0){
                    // dd(array($oldEmail, $newEmail));
                    return $this->flashbagAndRender($user, $form,
                        'It (user) nedds an email, you know.', null);
                }

                if(!filter_var($newEmail, FILTER_VALIDATE_EMAIL)){

                    return $this->flashbagAndRender($user, $form,
                        'This ( '.$newEmail.' ) doesn`t look like email', null);
                }
            }

            $this->userRepository->save($user, true);

            $session->set('user_changed', 'user changed');

            return $this->redirectToRoute('users_infoUser',['id'=>$id]);
        }
        
        if($value = $session->get('user_changed')){
            $flashBag = new FlashBag;
            $flashBag->add('success', $value);
            $session->remove('user_changed');
        }

        return $this->render('user/change.html.twig',[
            'form'=>$form,
            'user'=>$user,
            'flashBag'=>$flashBag
        ]);
    }

    #[Route('/manage', name: 'manage')]
    public function manage(SessionInterface $session): Response
    {
        $flashBag = '';
        if($value = $session->get('user_deleted')){
            $flashBag = new FlashBag;
            $flashBag->add('success', $value);
            $session->remove('user_deleted');
        }
        
        $users = $this->userRepository->findAll();
        return $this->render('user/manage.html.twig',[
            'users'=>$users,
            'flashBag'=>$flashBag
        ]);
    }

    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        return $this->render('user/login.html.twig');
    }
    
    #[Route('/create', name: 'create')]
    public function crete(Request $req, UserPasswordHasherInterface $passwordHasher, SessionInterface $session): Response
    {
        $user = new User();

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {

            $formData = $form->getData();
            $username = $formData->getUsername();
            
            if(sizeof($this->userRepository->findBy(['username'=>$username])) != 0 ){
            
                $flashBag = new FlashBag;
                $flashBag->add('danger', 'username is occupied');

                return $this->render('user/create.html.twig', [
                    'form' => $form->createView(),
                    'flashBag'=>$flashBag
                ]);
            }
            $user->setId(Uuid::uuid4());
            $user->setUsername($username);
            $user->setRoles(array($formData->getRoles()[0]));
            $user->setPassword($passwordHasher->hashPassword($user, $formData->getPassword()));

            $this->userRepository->save($user, true);
            
            $session->set('user_created', 'user created');

            return $this->redirectToRoute('users_users');

        }


        return $this->render('user/create.html.twig', [
            'form' => $form->createView(),
            
        ]);
    }

    #[Route('/delete/{id}', name:'delete')]
    public function delete($id, SessionInterface $session){

        $user = $this->userRepository->find($id);
        $this->userRepository->remove($user, true);

        $session->set('user_deleted', 'user deleted');

        return $this->redirectToRoute('users_manage');
    }
}
