<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\RepairService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:demo:load', description: 'Додає демонстраційний каталог у порожню базу.')]
final class LoadDemoCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $manager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($this->manager->getRepository(Category::class)->count() > 0) {
            $io->note('Каталог уже має категорії. Наявні записи збережено.');
            return Command::SUCCESS;
        }

        $data = [
            ['Ноутбуки', 'Обслуговування корпусу, охолодження та внутрішніх компонентів.', [
                ['Чищення системи охолодження', 'Розбираємо ноутбук, прибираємо пил із радіатора та вентиляторів, замінюємо термопасту й перевіряємо температуру під навантаженням.', 850, 90, true],
                ['Заміна накопичувача SSD', 'Перевіряємо сумісність, встановлюємо накопичувач і тестуємо його. Вартість деталі розраховується окремо.', 650, 60, true],
                ['Відновлення роз’єму живлення', 'Діагностуємо гніздо, перевіряємо плату та погоджуємо вартість запчастин перед ремонтом.', 1100, 150, false],
            ]],
            ['Смартфони', 'Батареї, модулі заряджання та діагностика телефонів.', [
                ['Заміна акумулятора', 'Перевіряємо стан батареї, замінюємо її та тестуємо заряджання. Ціна запчастини залежить від моделі.', 750, 75, true],
                ['Чищення порту заряджання', 'Видаляємо забруднення з роз’єму під збільшенням та перевіряємо підключення кабелю.', 300, 25, true],
                ['Діагностика після потрапляння вологи', 'Оглядаємо пристрій і визначаємо пошкоджені вузли. Подальший ремонт погоджується окремо.', 500, 60, true],
            ]],
            ['Периферія', 'Клавіатури, миші та інше обладнання для робочого місця.', [
                ['Заміна перемикача миші', 'Перепаюємо несправний мікроперемикач та перевіряємо натискання кнопок.', 450, 45, true],
                ['Обслуговування механічної клавіатури', 'Чистимо корпус та клавіші, перевіряємо перемикачі й стабілізатори.', 700, 90, true],
            ]],
        ];

        foreach ($data as [$name, $description, $items]) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription($description);
            $this->manager->persist($category);

            foreach ($items as [$title, $details, $price, $duration, $available]) {
                $service = new RepairService();
                $service->setCategory($category);
                $service->setTitle($title);
                $service->setDescription($details);
                $service->setPrice($price);
                $service->setDuration($duration);
                $service->setAvailable($available);
                $this->manager->persist($service);
            }
        }

        $this->manager->flush();
        $io->success('Додано 3 категорії та 8 послуг.');
        return Command::SUCCESS;
    }
}
