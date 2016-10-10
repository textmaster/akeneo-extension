<?php

namespace Pim\Bundle\TextmasterBundle\MassAction\Operation;

use Pim\Bundle\EnrichBundle\MassEditAction\Operation\AbstractMassEditOperation;
use Pim\Component\Catalog\Model\LocaleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Batch operation to send attributes to TextMaster translation
 *
 * @author    Jean-Marie Leroux <jean-marie.leroux@akeneo.com>
 * @copyright 2016 TextMaster.com (https://textmaster.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class CreateProjects extends AbstractMassEditOperation
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $briefing;

    /** @var LocaleInterface */
    protected $fromLocale;

    /** @var LocaleInterface[] */
    protected $toLocales;

    /** @var string */
    protected $category;

    /** @var ContainerInterface */
    protected $container;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface   $translator
     * @param string                $jobInstanceCode
     */
    public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator, $jobInstanceCode)
    {
        parent::__construct($jobInstanceCode);
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getBriefing()
    {
        return $this->briefing;
    }

    /**
     * @param string $briefing
     */
    public function setBriefing($briefing)
    {
        $this->briefing = $briefing;
    }

    /**
     * @return LocaleInterface
     */
    public function getFromLocale()
    {
        return $this->fromLocale;
    }

    /**
     * @param LocaleInterface $fromLocale
     */
    public function setFromLocale($fromLocale)
    {
        $this->fromLocale = $fromLocale;
    }

    /**
     * @return LocaleInterface[]
     */
    public function getToLocales()
    {
        return $this->toLocales;
    }

    /**
     * @param LocaleInterface[] $toLocales
     */
    public function setToLocales($toLocales)
    {
        $this->toLocales = $toLocales;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperationAlias()
    {
        return 'textmaster-create-projects';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return 'textmaster_create_projects';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsName()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchJobCode()
    {
        return 'textmaster_start_projects';
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        $this->actions = [
            'name'       => $this->name,
            'briefing'   => !empty($this->briefing) ? $this->briefing : $this->getDefaultBriefing(),
            'fromLocale' => $this->fromLocale->getCode(),
            'category'   => $this->category,
            'username'   => $this->tokenStorage->getToken()->getUsername(),
        ];

        $toLocaleCodes = [];
        foreach ($this->toLocales as $locale) {
            $toLocaleCodes[] = $locale->getCode();
        }
        $this->actions['toLocales'] = $toLocaleCodes;

        return parent::getActions();
    }

    /**
     * @return string
     */
    protected function getDefaultBriefing()
    {
        return $this->translator->trans('textmaster.default_briefing');
    }
}
