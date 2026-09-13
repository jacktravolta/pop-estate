<?php
namespace App\Form;
use App\Entity\Settlement;
use App\Form\SettlementItemType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Property;
class SettlementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add("property", EntityType::class, ["class"=>Property::class,"choice_label"=>"direccion","placeholder"=>"Selecciona propiedad","label"=>false])
        ->add("fechaInicio", DateType::class, ["widget"=>"single_text","label"=>false])
        ->add("fechaTermino", DateType::class, ["widget"=>"single_text","label"=>false])
        ->add("items", CollectionType::class, ["entry_type"=>SettlementItemType::class,"allow_add"=>true,"allow_delete"=>true,"by_reference"=>false,"prototype"=>true,"label"=>false]);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(["data_class"=>Settlement::class]); }
}

