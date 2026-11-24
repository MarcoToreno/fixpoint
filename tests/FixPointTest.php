<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class FixPointTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        // Each test uses a clean SQLite file outside the demo database.
        $file = tempnam(sys_get_temp_dir(), 'fixpoint-');
        self::assertIsString($file);
        $this->database = $file;
        $_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = 'sqlite:///'.str_replace('\\', '/', $file);
    }

    private string $database;

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_SERVER['DATABASE_URL'], $_ENV['DATABASE_URL']);
        if (isset($this->database) && is_file($this->database)) {
            unlink($this->database);
        }
    }

    public function testPublicCatalogAndApiUseTheSameData(): void
    {
        $client = self::createClient();
        $this->loadDemo();

        $client->request('GET', '/?q=АКУМУЛЯТОРА');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.service h2', 'Заміна акумулятора');
        self::assertCount(1, $client->getCrawler()->filter('.service'));
        $client->clickLink('Заміна акумулятора');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Заміна акумулятора');

        $client->request('GET', '/api/categories');
        $categories = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $phone = array_find($categories['data'], static fn (array $item) => $item['name'] === 'Смартфони');
        $client->request('GET', '/api/services?q=АКУМУЛЯТОРА&category='.$phone['id']);
        self::assertResponseIsSuccessful();
        $result = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(1, $result['count']);
        self::assertSame(750, $result['data'][0]['price_uah']);

        $client->request('GET', '/api/services?q=%25');
        self::assertStringContainsString('"count":0', $client->getResponse()->getContent());
        $client->request('GET', '/api/services?category[]=1');
        self::assertResponseStatusCodeSame(400);
        $client->request('GET', '/api/services/9999');
        self::assertResponseStatusCodeSame(404);
        $client->request('GET', '/services/9999');
        self::assertResponseStatusCodeSame(404);
    }

    public function testAdminIsProtectedAndRejectsAnInvalidOrigin(): void
    {
        $client = self::createClient();
        $this->loadDemo();

        $client->request('GET', '/admin/category');
        self::assertResponseRedirects('/login');
        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Увійти')->form();
        $form['_username'] = 'anonymous@example.test';
        $form['_password'] = bin2hex(random_bytes(16));
        $client->submit($form, [], ['HTTP_ORIGIN' => 'https://elsewhere.example.test']);
        $client->followRedirect();
        self::assertSelectorExists('[role="alert"]');

        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $user = (new User())->setEmail('visitor@example.test')->setRoles(['ROLE_USER'])
            ->setPassword(bin2hex(random_bytes(32)));
        $manager->persist($user);
        $manager->flush();
        $client->loginUser($user);
        $client->request('GET', '/admin/repair-service');
        self::assertResponseStatusCodeSame(403);
    }

    public function testFormLoginAndEasyAdminForms(): void
    {
        $client = self::createClient();
        $this->loadDemo();
        $password = bin2hex(random_bytes(16));
        $user = (new User())->setEmail('admin@example.test')->setRoles(['ROLE_ADMIN']);
        $user->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, $password));
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($user);
        $manager->flush();

        $crawler = $client->request('GET', '/login');
        $client->submit($crawler->selectButton('Увійти')->form([
            '_username' => 'admin@example.test',
            '_password' => $password,
        ]), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/admin');
        $client->followRedirect();
        self::assertResponseRedirects('/admin/repair-service');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Послуги');

        $crawler = $client->request('GET', '/admin/category/new');
        $form = $crawler->filter('form[name=Category]')->form();
        $form['Category[name]'] = 'Тестова категорія';
        $form['Category[description]'] = 'Опис тестової категорії';
        $client->submit($form);
        self::assertResponseStatusCodeSame(302);

        $client->followRedirect();
        self::assertSelectorTextContains('table', 'Тестова категорія');

        $crawler = $client->request('GET', '/admin/repair-service/new');
        $form = $crawler->filter('form[name=RepairService]')->form();
        $form['RepairService[title]'] = 'Тестове чищення';
        $form['RepairService[category]'] = '4';
        $form['RepairService[description]'] = 'Додаємо запис через форму EasyAdmin.';
        $form['RepairService[price]'] = '0';
        $form['RepairService[duration]'] = '15';
        $client->submit($form);
        self::assertResponseStatusCodeSame(422);
        $form['RepairService[price]'] = '200';
        $client->submit($form);
        self::assertResponseStatusCodeSame(302);

        $crawler = $client->request('GET', '/admin/repair-service/9/edit');
        $form = $crawler->filter('form[name=RepairService]')->form();
        $form['RepairService[price]'] = '250';
        $client->submit($form);
        self::assertResponseStatusCodeSame(302);
        $client->request('GET', '/api/services/9');
        self::assertStringContainsString('"price_uah":250', $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/admin/repair-service/9');
        self::assertSelectorTextContains('h1', 'Послуга');
        $token = $crawler->filter('#action-confirmation-form input[name=token]')->attr('value');
        $client->request('POST', '/admin/repair-service/9/delete', ['token' => $token]);
        self::assertResponseStatusCodeSame(302);
        $client->request('GET', '/api/services/9');
        self::assertResponseStatusCodeSame(404);

        $crawler = $client->request('GET', '/admin/category/4/edit');
        $form = $crawler->filter('form[name=Category]')->form();
        $form['Category[name]'] = 'Оновлена категорія';
        $client->submit($form);
        self::assertResponseStatusCodeSame(302);
        $crawler = $client->request('GET', '/admin/category/4');
        self::assertSelectorTextContains('body', 'Оновлена категорія');
        $token = $crawler->filter('#action-confirmation-form input[name=token]')->attr('value');
        $client->request('POST', '/admin/category/4/delete', ['token' => $token]);
        self::assertResponseStatusCodeSame(302);
        $client->request('GET', '/api/categories');
        self::assertStringNotContainsString('Оновлена категорія', $client->getResponse()->getContent());

        $crawler = $client->request('GET', '/');
        $client->submit($crawler->selectButton('Вийти')->form(), [], ['HTTP_ORIGIN' => 'http://localhost']);
        self::assertResponseRedirects('/');
        $client->request('GET', '/admin');
        self::assertResponseRedirects('/login');
    }

    private function loadDemo(): void
    {
        $application = new Application(self::$kernel);
        $migration = new CommandTester($application->find('doctrine:migrations:migrate'));
        $migration->execute([], ['interactive' => false]);
        self::assertSame(0, $migration->getStatusCode());
        $seed = new CommandTester($application->find('app:demo:load'));
        $seed->execute([]);
        self::assertSame(0, $seed->getStatusCode());
    }
}
