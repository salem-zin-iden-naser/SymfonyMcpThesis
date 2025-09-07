<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Comment;

#[ORM\Entity]
#[ORM\Table(name: 'like_comment', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_user_comment_like', columns: ['user_id', 'comment_id'])
])]
class LikeComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Comment::class, inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Comment $comment = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): void { $this->user = $user; }
    public function getComment(): ?Comment { return $this->comment; }
    public function setComment(?Comment $comment): void { $this->comment = $comment; }
}
