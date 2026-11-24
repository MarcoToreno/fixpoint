<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:admin:create', description: 'Створює користувача з роллю ROLE_ADMIN.')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly EntityManagerInterface $manager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!$input->isInteractive()) {
            $io->error('Запустіть команду в інтерактивному терміналі.');
            return Command::INVALID;
        }

        $email = mb_strtolower(trim((string) $io->ask('Email адміністратора')), 'UTF-8');
        if (strlen($email) > 180 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $io->error('Введіть коректний email.');
            return Command::INVALID;
        }
        if ($this->users->findOneBy(['email' => $email]) !== null) {
            $io->error('Користувач із таким email уже існує.');
            return Command::FAILURE;
        }

        $password = $this->secret($io, 'Пароль (від 12 символів)');
        if ($password === null || mb_strlen($password) < 12 || strlen($password) > 72) {
            $io->error('Пароль має містити щонайменше 12 символів і до 72 байтів.');
            return Command::INVALID;
        }
        if ($this->secret($io, 'Повторіть пароль') !== $password) {
            $io->error('Паролі не збігаються.');
            return Command::INVALID;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->manager->persist($user);
        $this->manager->flush();

        $io->success('Адміністратора створено.');
        return Command::SUCCESS;
    }

    private function secret(SymfonyStyle $io, string $label): ?string
    {
        $question = new Question($label);
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $question->setTrimmable(false);
        $answer = $io->askQuestion($question);

        return $answer === null ? null : rtrim($answer, "\r\n");
    }
}
