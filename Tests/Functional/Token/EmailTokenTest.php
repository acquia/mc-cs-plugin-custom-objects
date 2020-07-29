<?php

declare(strict_types=1);

namespace MauticPlugin\CustomObjectsBundle\Tests\Functional\Token;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use MauticPlugin\CustomObjectsBundle\Tests\Functional\DataFixtures\Traits\CustomObjectsTrait;
use MauticPlugin\CustomObjectsBundle\Entity\CustomItemXrefContact;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\HttpFoundation\Request;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;

class EmailTokenTest extends MauticMysqlTestCase
{
    use CustomObjectsTrait;

    public function testEmailTokens(): void
    {
        $product  = $this->createCustomObjectWithAllFields($this->container, 'Product');
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

        $productC = $this->createCustomItem(
            $this->container,
            $product,
            'Product C',
            []
        );

        $contact = new Lead();
        $contact->setEmail('george@doe.com');
        $contact->setFirstname('George');
        $contact->setLastname('Doe');

        $email = new Email();
        $email->setEmailType('list');
        $email->setName('CO token test email');
        $email->setSubject('CO token test email');
        $email->setCustomHtml('
            Dear George,

            check these values, please:

            ## Default formatting
            Name: {custom-object=products:name | limit=10 | format=default | default=No Value found}
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

            ## Various formatting and limit
            Name: {custom-object=products:name | limit=10 | format=or-list | default=No Value found}
            List: {custom-object=products:country-list-test-field | limit=10 | format=and-list | default=No Value found}
            Date: {custom-object=products:date-test-field | limit=10 | format=bullet-list | default=No Value found}
            Datetime: {custom-object=products:datetime-test-field | limit=10 | format=ordered-list | default=No Value found}
            Email: {custom-object=products:email-test-field | limit=1 | format=default | default=No Value found}
            Hidden: {custom-object=products:hidden-test-field | limit=10 | format=bullet-list | default=No Value found}
            Number: {custom-object=products:number-test-field | limit=10 | format=and-list | default=No Value found}
            Phone: {custom-object=products:phone-number-test-field | limit=10 | format=default | default=No Value found}
            Select: {custom-object=products:select-test-field | limit=10 | format=ordered-list | default=No Value found}
            Multiselect: {custom-object=products:multiselect-test-field | limit=10 | format=ordered-list | default=No Value found}
            Text: {custom-object=products:text-test-field | limit=10 | format=bullet-list | default=No Value found}
            Textarea: {custom-object=products:textarea-test-field | limit=10 | format=or-list | default=No Value found}
            Url: {custom-object=products:url-test-field | limit=10 | format=ordered-list | default=No Value found}
        ');

        $this->em->persist($contact);
        $this->em->persist($email);
        $this->em->persist(new CustomItemXrefContact($productA, $contact));
        $this->em->persist(new CustomItemXrefContact($productB, $contact));
        $this->em->persist(new CustomItemXrefContact($productC, $contact));
        $this->em->flush();

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

        /** @var StatRepository $emailStatRepository */
        $emailStatRepository = $this->em->getRepository(Stat::class);

        /** @var Stat|null $emailStat */
        $emailStat = $emailStatRepository->findOneBy(
            [
                'email' => $email->getId(),
                'lead' => $contact->getId(),
            ]
        );

        Assert::assertNotNull($emailStat);

        $crawler = $this->client->request(Request::METHOD_GET, "/email/view/{$emailStat->getTrackingHash()}");
        Assert::assertSame(
            "
            Dear George,

            check these values, please:

            ## Default formatting
            Name: Product A, Product B, Product C
            List: Czech Republic, Slovak Republic
            Date: 2020-07-22, 2020-07-23
            Datetime: 2020-07-22 14:55:00, 2020-07-23 14:55:00
            Email: product@a.email, product@b.email
            Hidden: top secret, hidden secret
            Number: 123, 456, 0
            Phone: +420555666777, +420555666888
            Select: Option B, Option A
            Multiselect: \"Option A\",\"Option B\", \"Option B\"
            Text: Text A, Text B
            Textarea: Text ABC, Text BCD
            Url: https://mautic.org

            ## Various formatting and limit
            Name: Product A, Product B or Product C
            List: Czech Republic and Slovak Republic
            Date: <ul>
<li>2020-07-22</li>
<li>2020-07-23</li>
</ul>
            Datetime: <ol>
<li>2020-07-22 14:55:00</li>
<li>2020-07-23 14:55:00</li>
</ol>
            Email: product@a.email
            Hidden: <ul>
<li>top secret</li>
<li>hidden secret</li>
</ul>
            Number: 123, 456 and 0
            Phone: +420555666777, +420555666888
            Select: <ol>
<li>Option B</li>
<li>Option A</li>
</ol>
            Multiselect: <ol>
<li>\"Option A\",\"Option B\"</li>
<li>\"Option B\"</li>
</ol>
            Text: <ul>
<li>Text A</li>
<li>Text B</li>
</ul>
            Textarea: Text ABC or Text BCD
            Url: <ol><li>https://mautic.org</li></ol>
        <img height=\"1\" width=\"1\" src=\"data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=\" alt=\"\"><div style=\"width: 100%; text-align: center; font-size: 10px; margin-top: 15px;\">
<a href=\"https://mautic-cloud.test/email/unsubscribe/{$emailStat->getTrackingHash()}\">Unsubscribe</a> to no longer receive emails from us.</div>",
            $crawler->filter('body')->html()
        );
    }
}
