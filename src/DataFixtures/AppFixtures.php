<?php

namespace App\DataFixtures;

use App\Entity\Log;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin-> setId(Uuid::uuid4());
        $admin-> setUsername('Admin');
        $admin-> setRoles(["ROLE_ADMIN"]);
        $admin->setPassword('$2y$13$I2rbjID8yyVRCEXmGFjJHOV8K3K024dFTUIHBcBgNh3QoAG4lCGyi'); //zaq1@WSX
        $admin->setEmail($admin->getUsername().'@support.com');
        $manager->persist($admin);

        $agent = new User();
        $agent-> setId(Uuid::uuid4());
        $agent-> setUsername('Agent');
        $agent-> setRoles(["ROLE_AGENT"]);
        $agent->setPassword('$2y$13$I2rbjID8yyVRCEXmGFjJHOV8K3K024dFTUIHBcBgNh3QoAG4lCGyi'); //zaq1@WSX
        $agent->setEmail($agent->getUsername().'@support.com');
        $manager->persist($agent);

        $user = new User();
        $user-> setId(Uuid::uuid4());
        $user-> setUsername('User');
        $user-> setRoles(["ROLE_USER"]);
        $user->setPassword('$2y$13$I2rbjID8yyVRCEXmGFjJHOV8K3K024dFTUIHBcBgNh3QoAG4lCGyi'); //zaq1@WSX
        $user->setEmail($user->getUsername().'@support.com');
        $manager->persist($user);

        $this->addReference('user',$user);

        $ticket = new Ticket();
        $ticket->setId(Uuid::uuid4());
        $ticket->setTitle('Ticket made by user');
        $ticket->setText('Ticket made by user, fadjskal');
        $ticket->setPriority('bMedium');
        $ticket->setStatus(false);
        $ticket->setCreatedBy($this->getReference('user'));
        $ticket->setBelongsTo(null);
        $manager->persist($ticket);

        $this->addReference('ticket', $ticket);

        $log = new Log();
        $log->setId(Uuid::uuid4());
        $log->setWhat('Ticket created by');
        $log->setDate(new \DateTime());
        $log->setTicket($this->getReference('ticket'));
        $log->setByWho($this->getReference('user'));
        $manager->persist($log);

        $manager->flush();
    }
}
