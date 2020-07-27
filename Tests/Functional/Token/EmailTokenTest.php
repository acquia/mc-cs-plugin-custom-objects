<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\EmailBundle\Model\EmailModel;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItem;

class EmailTokenTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    private $filterFactory;


    public function testSomething(): void
    {
        $product = $this->createCustomObjectWithAllFields($this->container, 'Product');
        $productA = $this->createCustomItem(
            $this->container,
            $product,
            'Product A',
            [
                'country-list-test-field' => 'Czech Republic',
                'date-test-field' => '2020-07-22',
                'datetime-test-field' => '2020-07-22 14:55',
                'email-test-field' => 'product@a.email',
                'hidden-test-field' => 'top secret',
                'number-test-field' => 123,
                'phone-number-test-field' => '+420555666777',
                'select-test-field' => 'option_b',
                'multiselect-test-field' => ['option_a', 'option_b'],
                'text-test-field' => 'Text A',
                'textarea-test-field' => 'Text ABC',
                'url-test-field' => 'https://mautic.org',
            ]
        );
        $productB = $this->createCustomItem(
            $this->container,
            $product,
            'Product B',
            [
                'country-list-test-field' => 'Slovak Republic',
                'date-test-field' => '2020-07-23',
                'datetime-test-field' => '2020-07-23 14:55',
                'email-test-field' => 'product@b.email',
                'hidden-test-field' => 'hidden secret',
                'number-test-field' => 456,
                'phone-number-test-field' => '+420555666888',
                'select-test-field' => 'option_a',
                'multiselect-test-field' => ['option_b'],
                'text-test-field' => 'Text B',
                'textarea-test-field' => 'Text BCD',
                'url-test-field' => 'https://mautic.org',
            ]
        );

        $contact = new Lead();
        $contact->setEmail('john@doe.com');
        $contact->setFirstname('John');
        $contact->setLastname('Doe');

        $segment = new LeadList();
        $segment->setName('CO tokens test segment');
        $segment->setAlias('co-tokens-test-segment');

        $segmentXrefContact = new ListLead();
        $segmentXrefContact->setList($segment);
        $segmentXrefContact->setLead($contact);
        $segmentXrefContact->setDateAdded(new \DateTime('now'));

        $email = new Email();
        $email->setEmailType('list');
        $email->addList($segment);
        $email->setName('CO token test email');
        $email->setSubject('CO token test email');
        $email->setCustomHtml('
            Dear George,

            check these values, please:
            List: {custom-object=products:country-list-test-field | limit=10 | format=default | default=No Value found}
            Date: {custom-object=products:date-test-field | limit=10 | format=default | default=No Value found}
            Datetime: {custom-object=products:datetime-test-field | limit=10 | format=default | default=No Value found}
            Email: {custom-object=products:email-test-field | limit=10 | format=default | default=No Value found}
            Hidden: {custom-object=products:hidden-test-field | limit=10 | format=default | default=No Value found}
            Number: {custom-object=products:number-test-field | limit=10 | format=default | default=No Value found}
            Phone: {custom-object=products:phone-number-test-field | limit=10 | format=default | default=No Value found}
            Select: {custom-object=products:select-test-field | limit=10 | format=default | default=No Value found}
            Multiselect: {custom-object=products:multiselect-test-field | limit=10 | format=default | default=No Value found}
            Text: {custom-object=products:text-test-field | limit=10 | format=default | default=No Value found}
            Textarea: {custom-object=products:textarea-test-field | limit=10 | format=default | default=No Value found}
            Url: {custom-object=products:url-test-field | limit=10 | format=default | default=No Value found}
        ');

        $this->em->persist($contact);
        $this->em->persist($segment);
        $this->em->persist($segmentXrefContact);
        $this->em->persist($email);
        $this->em->persist(new CustomItemXrefContact($this->em->find(CustomItem::class, $productA->getId()), $contact));
        $this->em->persist(new CustomItemXrefContact($this->em->find(CustomItem::class, $productB->getId()), $contact));
        $this->em->flush();

        // $this->container->set(
        //     'mailer',
        //     new class() extends \Swift_Mailer
        //     {
        //         public function __construct()
        //         {
        //         }

        //         public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
        //         {
        //             dump($message);
        //         }
        //     }
        // );

        /** @var EmailModel $emailModel */
        $emailModel = $this->container->get('mautic.email.model.email');
        $emailModel->sendEmail(
            $email,
            [
                [
                    'id' => $contact->getId(),
                    'email' => $contact->getEmail(),
                    'firstname' => $contact->getFirstname(),
                    'lastname' => $contact->getLastname(),
                ]
            ]
        );

        Assert::assertTrue(true);
    }

}
