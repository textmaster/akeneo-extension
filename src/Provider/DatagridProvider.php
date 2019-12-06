<?php

namespace Pim\Bundle\TextmasterBundle\Provider;

use Akeneo\Channel\Component\Repository\LocaleRepositoryInterface;
use Pim\Bundle\TextmasterBundle\Api\WebApiRepositoryInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Textmaster\Model\DocumentInterface as ApiDocumentInterface;

/**
 * Class DatagridProvider.
 *
 * @package Pim\Bundle\TextmasterBundle\Provider
 * @author  Jessy JURKOWSKI <jessy.jurkowski@cgi.com>
 */
class DatagridProvider
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var WebApiRepositoryInterface */
    protected $webApiRepository;

    /** @var LocaleRepositoryInterface */
    protected $localeRepository;

    /**
     * DatagridProvider constructor.
     *
     * @param TranslatorInterface       $translator
     * @param WebApiRepositoryInterface $webApiRepository
     * @param LocaleRepositoryInterface $localeRepository
     */
    public function __construct(
        TranslatorInterface $translator,
        WebApiRepositoryInterface $webApiRepository,
        LocaleRepositoryInterface $localeRepository
    ) {
        $this->translator       = $translator;
        $this->webApiRepository = $webApiRepository;
        $this->localeRepository = $localeRepository;
    }

    /**
     * Retrieve choice for datagrid status filter.
     *
     * @return array
     */
    public function getStatusChoices(): array
    {
        $statusKeys = [
            ApiDocumentInterface::STATUS_IN_CREATION,
            ApiDocumentInterface::STATUS_IN_PROGRESS,
            ApiDocumentInterface::STATUS_WAITING_ASSIGNMENT,
            ApiDocumentInterface::STATUS_IN_REVIEW,
            ApiDocumentInterface::STATUS_COMPLETED,
        ];

        $status = [];

        foreach ($statusKeys as $key) {
            $status[$this->translator->trans(sprintf('pim_textmaster.status.%s', $key))] = $key;
        }

        return $status;
    }

    /**
     * Retrieve choice for datagrid langages filter.
     *
     * @return array
     */
    public function getLangageChoices(): array
    {
        $langages    = [];
        $apiLangages = $this->webApiRepository->getLangages();
        $localeCodes     = $this->localeRepository->getActivatedLocaleCodes();

        foreach ($localeCodes as $index => $localeCode) {
            $localeCodes[$index] = str_replace('_', '-', strtolower($localeCode));
        }

        foreach (reset($apiLangages) as $langage) {
            if (in_array($langage['code'], $localeCodes)) {
                $langages[$langage['value']] = $langage['code'];
            }
        }

        return $langages;
    }
}