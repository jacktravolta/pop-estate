<?php
namespace App\Form;
use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->add('rut', TextType::class, ['label' => 'RUT', 'attr' => ['placeholder' => '76.123.456-7']])
                ->add('razonSocial', TextType::class, ['label' => 'Razón Social'])
                ->add('giro', TextType::class, ['label' => 'Giro', 'required' => false])
                ->add('direccion', TextType::class, ['label' => 'Dirección'])
                ->add('comuna', TextType::class, ['label' => 'Comuna', 'required' => false])
                ->add('ciudad', TextType::class, ['label' => 'Ciudad', 'required' => false])
                ->add('email', EmailType::class, ['label' => 'Email', 'required' => false]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => Company::class]); }
}