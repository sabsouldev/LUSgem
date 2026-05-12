<?php

namespace App\Controller;

use App\Service\BlogPostStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEMBER')]
#[Route('/adherent/blog')]
final class MemberBlogController extends AbstractController
{
    public function __construct(
        private readonly BlogPostStorage $blogStorage,
    ) {
    }

    #[Route('', name: 'app_member_blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $posts = $this->blogStorage->readPosts();

        $selectedCategory = trim((string) $request->query->get('category', ''));
        $selectedDate = trim((string) $request->query->get('date', ''));

        $filteredPosts = array_values(array_filter($posts, function (array $post) use ($selectedCategory, $selectedDate): bool {
            if ($selectedCategory !== '') {
                $categories = array_map('mb_strtolower', $post['categories']);
                if (!in_array(mb_strtolower($selectedCategory), $categories, true)) {
                    return false;
                }
            }

            if ($selectedDate !== '' && $post['publish_date'] !== $selectedDate) {
                return false;
            }

            return true;
        }));

        $hasFilters = $selectedCategory !== '' || $selectedDate !== '';
        $postsToDisplay = $hasFilters ? array_slice($filteredPosts, 0, 100) : array_slice($filteredPosts, 0, 6);

        return $this->render('member/blog/index.html.twig', [
            'posts' => $postsToDisplay,
            'selected_category' => $selectedCategory,
            'selected_date' => $selectedDate,
            'has_filters' => $hasFilters,
            'available_categories' => $this->blogStorage->collectCategories($posts),
        ]);
    }

    #[Route('/{id}', name: 'app_member_blog_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $post = $this->blogStorage->findPostById($id);

        if ($post === null) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        return $this->render('member/blog/show.html.twig', [
            'post' => $post,
        ]);
    }
}
