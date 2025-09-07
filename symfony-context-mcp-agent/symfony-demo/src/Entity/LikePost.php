<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Post;

#[ORM\Entity]
#[ORM\Table(name: 'like_post', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_user_post_like', columns: ['user_id', 'post_id'])
])]
class LikePost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'likes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): void { $this->user = $user; }
    public function getPost(): ?Post { return $this->post; }
    public function setPost(?Post $post): void { $this->post = $post; }
}
