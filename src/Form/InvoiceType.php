<?php
namespace App\Form;
use App\Entity\Invoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('folio', TextType::class, ['label'=>'Folio'])
            ->add('emisor', TextType::class, ['label'=>'Emisor'])
            ->add('receptor', TextType::class, ['label'=>'Receptor','required'=>false])
            ->add('periodo', TextType::class, ['label'=>'Periodo','required'=>false])
            ->add('total', NumberType::class, ['label'=>'Total'])
            ->add('estado', ChoiceType::class, [
                'label'=>'Estado',
                'choices'=>['Pendiente'=>'PENDIENTE','Pagada'=>'PAGADA','Anulada'=>'ANULADA']
            ])
        ;
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class'=>Invoice::class]);
    }
}
