<?php
namespace App\Form;
use App\Entity\Company;
use App\Entity\Owner;
use App\Entity\Property;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options): void {
        $builder->add('company', EntityType::class, ['class' => Company::class, 'choice_label' => 'razonSocial', 'label' => 'Empresa'])
                ->add('owner', EntityType::class, ['class' => Owner::class, 'choice_label' => 'nombre', 'label' => 'Propietario'])
                ->add('direccion', TextType::class, ['label' => 'Dirección'])
                ->add('rol', TextType::class, ['label' => 'Rol Avalúo', 'required' => false])
                ->add('comuna', TextType::class, ['label' => 'Comuna', 'required' => false])
                ->add('ciudad', TextType::class, ['label' => 'Ciudad', 'required' => false]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => Property::class]); }
}