<?php

namespace App\Form;

use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class TicketFormType extends AbstractType
{
    private $userRepository;
    public function __construct(EntityManagerInterface $em){
        $this->userRepository= $em->getRepository(User::class);
    }
    public function buildForm(FormBuilderInterface $builder, array $options =['edit', 'agent']): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => array(
                    'placeholder' => "Enter Title"
                ),

                'required'=>false,
            ])
            ->add('text', TextareaType::class,[
                'attr'=>array(
                    'placeholder'=> "Enter description of problem"
                ),
                'required'=>false,
            ]);
            if(!$options['edit']){
                $builder->add('priority',ChoiceType::class, [
                    'choices' => [
                        'High' => 'aHigh',
                        'Medium' => 'bMedium',
                        'Low' => 'cLow',
                        
                    ],
                    'required' => true,
                    'expanded' => true,
                    'multiple' => false,
                ]);
            }
            else{
                $agents = array();
                foreach ($options['agents'] as $value) {
                    $agents[$value['username']] = $this->userRepository->find($value['id']);
                }

                $builder->add('belongsTo',ChoiceType::class, [
                    'choices' => $agents,
                    'required' => true,

                ]);

            }
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'edit'=>false,
            'agents'=>array()
        ]);

        $resolver->setAllowedTypes('edit','bool');
        $resolver->setAllowedTypes('agents','array');
    }
}
