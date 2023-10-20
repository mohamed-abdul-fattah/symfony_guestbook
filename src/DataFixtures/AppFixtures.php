<?php

namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $amsterdam = $this->createConference('Amsterdam', '2019', true);
        $paris = $this->createConference('Paris', '2020', false);

        $manager->persist($amsterdam);
        $manager->persist($paris);

        $comment = $this->createComment($amsterdam, 'Mo', 'm@mauve.de', 'This is a good conference');
        $manager->persist($comment);

        $admin = $this->createAdmin('admin', 'admin');
        $manager->persist($admin);

        $manager->flush();
    }

    private function createConference(
        string $name,
        string $year,
        bool $international
    ): Conference
    {
        $conference = new Conference();
        $conference->setCity($name);
        $conference->setYear($year);
        $conference->setIsInternational($international);

        return $conference;
    }

    private function createComment(
        Conference $conference,
        string $author,
        string $email,
        string $text,
    ): Comment
    {
        $comment = new Comment();
        $comment->setConference($conference);
        $comment->setAuthor($author);
        $comment->setEmail($email);
        $comment->setText($text);

        return $comment;
    }

    private function createAdmin(string $username, string $plainPassword): Admin
    {
        $admin = new Admin();
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setUsername($username);
        $admin->setPassword(
            $this->passwordHasherFactory->getPasswordHasher(Admin::class)->hash($plainPassword)
        );

        return $admin;
    }
}
