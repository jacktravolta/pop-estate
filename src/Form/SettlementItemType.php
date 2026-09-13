<?php
namespace App\Form;
use App\Entity\SettlementItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
class SettlementItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add("tipo", ChoiceType::class, ["choices"=>["CARGO"=>"CARGO","DESCUENTO"=>"DESCUENTO"],"label"=>false])
        ->add("descripcion", TextType::class, ["label"=>false])
        ->add("monto", NumberType::class, ["label"=>false]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(["data_class"=>SettlementItem::class]); }
}

