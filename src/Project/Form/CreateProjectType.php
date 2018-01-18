<?php

namespace Pim\Bundle\TextmasterBundle\Project\Form;

use Pim\Bundle\TextmasterBundle\Api\WebApiRepository;
use Pim\Bundle\TextmasterBundle\MassAction\Operation\CreateProjects;
use Pim\Component\Catalog\Repository\LocaleRepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Project form type
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CreateProjectType extends AbstractType
{
    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /** @var WebApiRepository */
    protected $apiRepository;

    /** @var array */
    protected $options;

    /**
     * @param LocaleRepositoryInterface $localeRepository
     * @param WebApiRepository          $apiRepository
     * @param array                     $options
     */
    public function __construct(
        LocaleRepositoryInterface $localeRepository,
        WebApiRepository $apiRepository,
        array $options
    ) {
        $this->localeRepository = $localeRepository;
        $this->apiRepository = $apiRepository;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', [
            'required'    => true,
            'constraints' => new NotBlank(),
        ]);

        $builder->add('api_templates', 'choice', [
            'required'    => true,
            'choices'     => $this->getApiTemplatesChoices(),
            'select2'     => true,
            'multiple'    => true,
            'constraints' => new NotBlank(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            ['data_class' => CreateProjects::class]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'textmaster_create_projects';
    }

    /**
     * @return string[]
     */
    protected function getApiTemplatesChoices()
    {
        $apiTermplates = $this->apiRepository->getApiTemplates();
        $choices = [];
        foreach ($apiTermplates as $id => $data) {
            $choices[$id] = sprintf(
                '%s ["%s" to "%s"]',
                $data['name'],
                $data['language_from'],
                $data['language_to']
            );
        }

        return $choices;
    }
}
