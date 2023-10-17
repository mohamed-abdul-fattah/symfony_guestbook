<?php

namespace App\EntityListener;

use App\Entity\Conference;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsEntityListener(event: Events::prePersist, entity: Conference::class)]
class ConferenceEntityListener
{
    public function __construct(private readonly SluggerInterface $slugger)
    {}

    public function prePersist(Conference $conference): void
    {
        $conference->computeSlug($this->slugger);
    }
}
