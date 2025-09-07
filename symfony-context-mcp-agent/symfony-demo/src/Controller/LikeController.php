<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\LikeComment;
use App\Entity\LikePost;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class LikeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route(path: '/{_locale}/post/{id}/like', name: 'like_post', methods: ['POST'])]
    public function likePost(Request $request, Post $post): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->assertValidCsrf($request->request->get('_token'), 'like_post'.$post->getId());

        // prevent duplicates
        foreach ($post->getLikes() as $like) {
            if ($like->getUser() === $this->getUser()) {
                return $this->json(['liked' => true, 'likes_count' => \count($post->getLikes())]);
            }
        }

        $like = new LikePost();
        $like->setUser($this->getUser());
        $post->addLike($like);

        $this->em->persist($like);
        $this->em->flush();

        return $this->json(['liked' => true, 'likes_count' => \count($post->getLikes())]);
    }

    #[Route(path: '/{_locale}/post/{id}/unlike', name: 'unlike_post', methods: ['POST'])]
    public function unlikePost(Request $request, Post $post): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->assertValidCsrf($request->request->get('_token'), 'unlike_post'.$post->getId());

        foreach ($post->getLikes() as $like) {
            if ($like->getUser() === $this->getUser()) {
                $post->removeLike($like);
                $this->em->remove($like);
                $this->em->flush();

                return $this->json(['liked' => false, 'likes_count' => \count($post->getLikes())]);
            }
        }

        return $this->json(['liked' => false, 'likes_count' => \count($post->getLikes())], 404);
    }

    #[Route(path: '/{_locale}/comment/{id}/like', name: 'like_comment', methods: ['POST'])]
    public function likeComment(Request $request, Comment $comment): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->assertValidCsrf($request->request->get('_token'), 'like_comment'.$comment->getId());

        // prevent duplicates
        foreach ($comment->getLikes() as $like) {
            if ($like->getUser() === $this->getUser()) {
                return $this->json(['liked' => true, 'likes_count' => \count($comment->getLikes())]);
            }
        }

        $like = new LikeComment();
        $like->setUser($this->getUser());
        $comment->addLike($like);

        $this->em->persist($like);
        $this->em->flush();

        return $this->json(['liked' => true, 'likes_count' => \count($comment->getLikes())]);
    }

    #[Route(path: '/{_locale}/comment/{id}/unlike', name: 'unlike_comment', methods: ['POST'])]
    public function unlikeComment(Request $request, Comment $comment): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $this->assertValidCsrf($request->request->get('_token'), 'unlike_comment'.$comment->getId());

        foreach ($comment->getLikes() as $like) {
            if ($like->getUser() === $this->getUser()) {
                $comment->removeLike($like);
                $this->em->remove($like);
                $this->em->flush();

                return $this->json(['liked' => false, 'likes_count' => \count($comment->getLikes())]);
            }
        }

        return $this->json(['liked' => false, 'likes_count' => \count($comment->getLikes())], 404);
    }

    private function assertValidCsrf(?string $token, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, $token ?? '')) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}
