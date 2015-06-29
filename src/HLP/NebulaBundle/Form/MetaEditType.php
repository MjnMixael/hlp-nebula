<?php
namespace HLP\NebulaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MetaEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $id = $builder->getData()->getId();
        $builder->remove('metaId')
            ->add('defaultBranch', 'entity', array(
                'class'         => 'HLPNebulaBundle:Branch',
                'expanded'      => false,
                'multiple'      => false,
                'mapped'        => false,
                'query_builder' => function(\HLP\NebulaBundle\Entity\BranchRepository $repo) use($id) {
                    return $repo->getBranchQueryBuilder($id);
                }))
        ;
    }

    public function getName()
    {
        return 'hlp_nebulabundle_meta_edit';
    }

    public function getParent()
    {
        return new MetaType();
    }
}
