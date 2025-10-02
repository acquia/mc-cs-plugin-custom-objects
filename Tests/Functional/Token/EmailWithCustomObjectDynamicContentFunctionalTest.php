<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Token;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\CustomObjectsBundle\Entity\CustomFieldValueInterface;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;
use MauticPlugin\CustomObjectsBundle\Entity\CustomObject;
use MauticPlugin\CustomObjectsBundle\Model\CustomFieldValueModel;
use MauticPlugin\CustomObjectsBundle\Model\CustomItemModel;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class EmailWithCustomObjectDynamicContentFunctionalTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    /**
     * @var CustomItemModel
     */
    private $customItemModel;

    /**
     * @var CustomFieldValueModel
     */
    private $customFieldValueModel;

    /**
     * @var CustomObject
     */
    private $customObject;

    /**
     * @var array<CustomItem>
     */
    private $customItems;

    /**
     * @var array<CustomFieldValueInterface>
     */
    private $customFieldValues;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customItemModel       = self::$container->get('mautic.custom.model.item');
        $this->customFieldValueModel = self::$container->get('mautic.custom.model.field.value');

        $this->customObject           = $this->createCustomObjectWithAllFields(self::$container, 'Car');
        $this->customItems['nexon']   = new CustomItem($this->customObject);

        $this->customItems['nexon']->setName('Nexon');
        $this->customFieldValueModel->createValuesForItem($this->customItems['nexon']);

        $this->customFieldValues['nexon-text']       = $this->customItems['nexon']->findCustomFieldValueForFieldAlias('text-test-field');
        $this->customFieldValues['nexon-text']->setValue('Tata');

        $this->customFieldValues['nexon-datetime']       = $this->customItems['nexon']->findCustomFieldValueForFieldAlias('datetime-test-field');
        $this->customFieldValues['nexon-datetime']->setValue('2024-07-01 13:29:43');

        $this->customFieldValues['nexon-multiselect']       = $this->customItems['nexon']->findCustomFieldValueForFieldAlias('multiselect-test-field');
        $this->customFieldValues['nexon-multiselect']->setValue('option_a');

        $this->customFieldValues['nexon-select']       = $this->customItems['nexon']->findCustomFieldValueForFieldAlias('select-test-field');

        $this->customFieldValues['nexon-url']       = $this->customItems['nexon']->findCustomFieldValueForFieldAlias('url-test-field');
        $this->customFieldValues['nexon-url']->setValue('https://test.mautic.fr');

        $this->customFieldValues['nexon-number']       = $this->customItems['nexon']->findCustomFieldValueForFieldAlias('number-test-field');
        $this->customFieldValues['nexon-number']->setValue(10);

        $this->customItems['nexon'] = $this->customItemModel->save($this->customItems['nexon']);

        $this->customItems['fortuner']   = new CustomItem($this->customObject);

        $this->customItems['fortuner']->setName('Fortuner');
        $this->customFieldValueModel->createValuesForItem($this->customItems['fortuner']);

        $this->customFieldValues['fortuner-text']       = $this->customItems['fortuner']->findCustomFieldValueForFieldAlias('text-test-field');
        $this->customFieldValues['fortuner-text']->setValue('Toyota');

        $this->customItems['fortuner'] = $this->customItemModel->save($this->customItems['fortuner']);

        $this->customItems['city']   = new CustomItem($this->customObject);

        $this->customItems['city']->setName('City');
        $this->customFieldValueModel->createValuesForItem($this->customItems['city']);

        $this->customFieldValues['city-text']       = $this->customItems['city']->findCustomFieldValueForFieldAlias('text-test-field');
        $this->customFieldValues['city-text']->setValue('Honda');

        $this->customItems['city'] = $this->customItemModel->save($this->customItems['city']);
    }

    public function testDynamicContentEmail(): void
    {
        foreach ([
            [
                'nexonlikeurl@acquia.com',
                $this->buildDynamicContentArray([
                    ['nexon-url', 'tic.f', 'like'],
                ]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonselect@acquia.com',
                $this->buildDynamicContentArray([
                    ['nexon-select', null, 'empty', 'select'],
                ]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonmultiselect@acquia.com',
                $this->buildDynamicContentArray([
                    ['nexon-datetime', null, '!empty', 'datetime'],
                    ['nexon-number', 12, 'lt', 'number'],
                ]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonmultiselect@acquia.com',
                $this->buildDynamicContentArray([
                    ['nexon-text', 'Tata', '='],
                    ['nexon-datetime', '2024-07-01 00:00', 'gte', 'datetime'],
                    ['nexon-multiselect', 'option_a', 'in', 'multiselect'],
                ]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexondatetime@acquia.com',
                $this->buildDynamicContentArray([
                    ['nexon-text', 'Tata', '='],
                    ['nexon-datetime', '2024-07-01', 'gte'],
                ]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonequal@acquia.com',
                $this->buildDynamicContentArray([
                    ['nexon-text', 'Tata', '='],
                ]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonnotequal@acquia.com',
                $this->buildDynamicContentArray([['nexon-text', 'Toyota', '!=']]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonempty@acquia.com',
                $this->buildDynamicContentArray([['nexon-text', '', 'empty']]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonnotempty@acquia.com',
                $this->buildDynamicContentArray([['nexon-text', '', '!empty']]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonlike@acquia.com',
                $this->buildDynamicContentArray([['nexon-text', 'at', 'like']]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonnotlike@acquia.com',
                $this->buildDynamicContentArray([['nexon-text', 'Toyota', '!like']]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonstartsWith@acquia.com',
                $this->buildDynamicContentArray([['nexon-text', 'Ta', 'startsWith']]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonendsWith@acquia.com',
                $this->buildDynamicContentArray([['nexon-text', 'ta', 'endsWith']]),
                'Custom Object Dynamic Content',
            ],
            [
                'nexonendsWith@acquia.com',
                $this->buildDynamicContentArray([['nexon-text', 'at', 'contains']]),
                'Custom Object Dynamic Content',
            ],
        ] as $item) {
            $this->emailWithCustomObjectDynamicContent($item[0], $item[1], $item[2]);
        }
    }

    /**
     * @param array<mixed> $inputs
     *
     * @return array<mixed>
     */
    private function buildDynamicContentArray(array $inputs): array
    {
        return [
            [
                'tokenName' => 'Dynamic Content 1',
                'content'   => 'Default Dynamic Content',
                'filters'   => [
                    [
                        'content' => null,
                        'filters' => [
                        ],
                    ],
                ],
            ],
            [
                'tokenName' => 'Dynamic Content 2',
                'content'   => 'Default Dynamic Content',
                'filters'   => [
                    [
                        'content' => 'Custom Object Dynamic Content',
                        'filters' => array_merge(
                            array_map(function ($input) {
                                return [
                                    'glue'     => 'and',
                                    'field'    => 'cmf_'.$this->customFieldValues[$input[0]]->getCustomField()->getId(),
                                    'object'   => 'custom_object',
                                    'type'     => $input[3] ?? 'text',
                                    'filter'   => $input[1],
                                    'display'  => $this->customObject->getName().':'.$this->customFieldValues[$input[0]]->getCustomField()->getLabel(),
                                    'operator' => $input[2],
                                ];
                            }, $inputs),
                            [
                                [
                                    'glue'     => 'and',
                                    'field'    => 'email',
                                    'object'   => 'lead',
                                    'type'     => 'email',
                                    'filter'   => null,
                                    'operator' => '!empty',
                                ],
                            ]
                        ),
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $dynamicContent
     */
    private function emailWithCustomObjectDynamicContent(string $emailAddress, array $dynamicContent, string $assertText): void
    {
        $lead  = $this->createLead($emailAddress);
        $email = $this->createEmail();
        $email->setDynamicContent($dynamicContent);
        $this->em->persist($email);
        $this->em->flush();

        $this->customItemModel->linkEntity($this->customItems['nexon'], 'contact', (int) $lead->getId());
        $this->customItemModel->linkEntity($this->customItems['fortuner'], 'contact', (int) $lead->getId());
        $this->customItemModel->linkEntity($this->customItems['city'], 'contact', (int) $lead->getId());

        $this->sendAndAssetText($email, $lead, $assertText);
    }

    private function createEmail(bool $publicPreview = true): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject('Email subject');
        $email->setTemplate('Blank');
        $email->setPublicPreview($publicPreview);
        $email->setCustomHtml('<html><body>{dynamiccontent="Dynamic Content 2"}</body></html>');
        $this->em->persist($email);

        return $email;
    }

    private function createLead(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $this->em->persist($lead);

        return $lead;
    }

    private function sendAndAssetText(Email $email, Lead $lead, string $matchText): void
    {
        /** @var EmailModel $emailModel */
        $emailModel = self::$container->get('mautic.email.model.email');
        $emailModel->sendEmail(
            $email,
            [
                [
                    'id'        => $lead->getId(),
                    'email'     => $lead->getEmail(),
                    'firstname' => $lead->getFirstname(),
                    'lastname'  => $lead->getLastname(),
                ],
            ]
        );

        /** @var StatRepository $emailStatRepository */
        $emailStatRepository = $this->em->getRepository(Stat::class);

        /** @var Stat|null $emailStat */
        $emailStat = $emailStatRepository->findOneBy(
            [
                'email' => $email->getId(),
                'lead'  => $lead->getId(),
            ]
        );

        Assert::assertNotNull($emailStat);

        $crawler = $this->client->request(Request::METHOD_GET, "/email/view/{$emailStat->getTrackingHash()}");

        $body = $crawler->filter('body');

        // Remove the tracking tags that are causing troubles with different Mautic configurations.
        $body->filter('a,img,div')->each(function (Crawler $crawler) {
            foreach ($crawler as $node) {
                $node->parentNode->removeChild($node);
            }
        });

        Assert::assertStringContainsString(
            $matchText,
            $body->html()
        );
    }
}
