<?php
namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * CompanyType - Form empresa que factura
 * Campos según challenge: RUT único, razón social obligatoria, dirección obligatoria
 */
class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rut', TextType::class, [
                'label' => 'RUT *',
                'attr' => ['placeholder' => '76.123.456-7', 'id' => 'rut-input-company']
            ])
            ->add('razonSocial', TextType::class, [
                'label' => 'Razón Social *',
                'attr' => ['placeholder' => 'Pop Estate SpA']
            ])
            ->add('giro', TextType::class, [
                'label' => 'Giro',
                'required' => false,
                'attr' => ['placeholder' => 'Gestión inmobiliaria']
            ])
            ->add('direccion', TextType::class, [
                'label' => 'Dirección *',
                'attr' => ['placeholder' => 'Av. Apoquindo 3600, Las Condes']
            ])
            ->add('comuna', TextType::class, [
                'label' => 'Comuna',
                'required' => false,
                'attr' => ['placeholder' => 'Las Condes']
            ])
            ->add('ciudad', TextType::class, [
                'label' => 'Ciudad',
                'required' => false,
                'attr' => ['placeholder' => 'Santiago']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email facturación',
                'required' => false,
                'attr' => ['placeholder' => 'facturacion@popestate.cl']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Company::class]);
    }
}
