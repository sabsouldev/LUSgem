<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEMBER')]
#[Route('/adherent/blog')]
final class MemberBlogController extends AbstractController
{
    private const STORAGE_RELATIVE_PATH = 'var/data/blog-posts.json';

    #[Route('', name: 'app_member_blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $posts = $this->readPosts();

        // Filtres en lecture seule (aucune action CRUD cote adherent).
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

        // Sans filtre, on limite a 6 articles pour garder une lecture simple cote adherent.
        $hasFilters = $selectedCategory !== '' || $selectedDate !== '';
        $postsToDisplay = $hasFilters ? array_slice($filteredPosts, 0, 100) : array_slice($filteredPosts, 0, 6);

        return $this->render('member/blog/index.html.twig', [
            'posts' => $postsToDisplay,
            'selected_category' => $selectedCategory,
            'selected_date' => $selectedDate,
            'has_filters' => $hasFilters,
            'available_categories' => $this->collectCategories($posts),
        ]);
    }

    #[Route('/{id}', name: 'app_member_blog_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $post = $this->findPostById($id);

        if ($post === null) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        return $this->render('member/blog/show.html.twig', [
            'post' => $post,
        ]);
    }

    private function readPosts(): array
    {
        $storagePath = $this->getStoragePath();

        if (!file_exists($storagePath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($storagePath), true);
        if (!is_array($decoded)) {
            return [];
        }

        $hasChanged = false;
        $normalized = [];
        $allowedMediaTypes = ['pdf', 'image', 'video', 'audio'];

        // Mode lecture seule: on normalise les donnees sans exposer d'actions d'ecriture.
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = isset($item['id']) && is_string($item['id']) && $item['id'] !== '' ? $item['id'] : $this->createId();
            if (!isset($item['id'])) {
                $hasChanged = true;
            }

            $title = trim((string) ($item['title'] ?? 'Article sans titre'));
            if ($title === '') {
                $title = 'Article sans titre';
            }

            $publishDate = (string) ($item['publish_date'] ?? date('Y-m-d'));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $publishDate)) {
                $publishDate = date('Y-m-d');
                $hasChanged = true;
            }

            $categoriesRaw = $item['categories'] ?? [];
            if (!is_array($categoriesRaw)) {
                $categoriesRaw = [];
            }

            $categories = [];
            foreach ($categoriesRaw as $categoryRaw) {
                $category = $this->normalizeCategory((string) $categoryRaw);
                if ($category !== '' && !in_array($category, $categories, true)) {
                    $categories[] = $category;
                }
            }

            $contentsRaw = $item['contents'] ?? [];
            if (!is_array($contentsRaw)) {
                $legacyContent = trim((string) ($item['content'] ?? ''));
                $contentsRaw = $legacyContent !== '' ? [$legacyContent] : [];
                $hasChanged = true;
            }

            $contents = [];
            foreach ($contentsRaw as $contentRaw) {
                $content = trim((string) $contentRaw);
                if ($content !== '') {
                    $contents[] = $content;
                }
            }

            $mediaRaw = $item['media'] ?? [];
            if (!is_array($mediaRaw)) {
                $mediaRaw = [];
                $hasChanged = true;
            }

            $media = [];
            foreach ($mediaRaw as $mediaItem) {
                if (!is_array($mediaItem)) {
                    $hasChanged = true;
                    continue;
                }

                $path = trim((string) ($mediaItem['path'] ?? ''));
                if ($path === '') {
                    $hasChanged = true;
                    continue;
                }

                $type = (string) ($mediaItem['type'] ?? 'pdf');
                // Fallback "pdf" si un type media inattendu est rencontre.
                if (!in_array($type, $allowedMediaTypes, true)) {
                    $type = 'pdf';
                    $hasChanged = true;
                }

                $media[] = [
                    'id' => (string) ($mediaItem['id'] ?? $this->createId()),
                    'name' => trim((string) ($mediaItem['name'] ?? basename($path))),
                    'type' => $type,
                    'path' => $path,
                    'mime_type' => (string) ($mediaItem['mime_type'] ?? ''),
                    'size' => max(0, (int) ($mediaItem['size'] ?? 0)),
                    'uploaded_at' => (string) ($mediaItem['uploaded_at'] ?? ''),
                ];
            }

            if ($categories === []) {
                $categories = ['Institutionnel'];
                $hasChanged = true;
            }

            $normalized[] = [
                'id' => $id,
                'title' => $title,
                'slug' => $this->slugify($title),
                'publish_date' => $publishDate,
                'categories' => $categories,
                'contents' => $contents,
                'media' => $media,
                'excerpt' => $this->makeExcerpt($contents[0] ?? 'Contenu multimedia'),
                'published_at' => (string) ($item['published_at'] ?? date('c')),
                'updated_at' => (string) ($item['updated_at'] ?? date('c')),
            ];
        }

        usort($normalized, static function (array $a, array $b): int {
            // Tri du plus recent au plus ancien.
            $dateCompare = strcmp($b['publish_date'], $a['publish_date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp((string) $b['published_at'], (string) $a['published_at']);
        });

        if ($hasChanged) {
            $this->savePosts($normalized);
        }

        return $normalized;
    }

    private function savePosts(array $posts): void
    {
        $storagePath = $this->getStoragePath();

        // Utilise par la normalisation defensive si le JSON source doit etre corrige.
        $filesystem = new Filesystem();
        $filesystem->mkdir(dirname($storagePath));

        file_put_contents(
            $storagePath,
            (string) json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    private function findPostById(string $id): ?array
    {
        foreach ($this->readPosts() as $post) {
            if ($post['id'] === $id) {
                return $post;
            }
        }

        return null;
    }

    private function collectCategories(array $posts): array
    {
        $categories = [
            'Institutionnel',
            'Vie du GEM',
            'Activites',
            'Autisme',
            'Evenements',
            'Partenariats',
            'Ressources',
            'Informations importantes',
        ];

        foreach ($posts as $post) {
            foreach ($post['categories'] as $category) {
                if (!in_array($category, $categories, true)) {
                    $categories[] = $category;
                }
            }
        }

        sort($categories);

        return $categories;
    }

    private function getStoragePath(): string
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');

        return $projectDir . '/' . self::STORAGE_RELATIVE_PATH;
    }

    private function createId(): string
    {
        return str_replace('.', '', uniqid('blog_', true));
    }

    private function slugify(string $value): string
    {
        $slug = strtolower($value);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? 'article';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'article';
    }

    private function normalizeCategory(string $category): string
    {
        $normalized = trim($category);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        if ($normalized === '') {
            return '';
        }

        return mb_substr($normalized, 0, 40);
    }

    private function makeExcerpt(string $text): string
    {
        $value = trim($text);

        if ($value === '') {
            return 'Contenu multimedia';
        }

        if (mb_strlen($value) <= 180) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, 177)) . '...';
    }
}
