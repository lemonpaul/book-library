<?php

namespace App\Form;

use App\Entity\Book;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class EditBookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class)
            ->add('author', TextType::class)
            ->add('date', DateType::class, array('format' => 'yyyy-MM-dd'))
            ->add('download', CheckboxType::class, array('required' => false))
            ->add('delete_cover', SubmitType::class, array('label' => 'Delete Cover'))
            ->add('delete_file', SubmitType::class, array('label' => 'Delete File'))
            ->add('save', SubmitType::class, array('label' => 'Save Book'));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Book::class
        ));
    }
}
