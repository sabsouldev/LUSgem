<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

final class BlogPostStorage
{
    private const STORAGE_RELATIVE_PATH = 'var/data/blog-posts.json';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readPosts(bool $allowWrite = false): array
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
        $allowedExtensions = [
            'pdf' => 'pdf',
            'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image',
            'webp' => 'image', 'gif' => 'image', 'svg' => 'image',
            'mp4' => 'video', 'webm' => 'video', 'mov' => 'video', 'ogv' => 'video',
            'mp3' => 'audio', 'wav' => 'audio', 'ogg' => 'audio', 'm4a' => 'audio', 'aac' => 'audio',
        ];

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

                $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
                $mediaType = (string) ($mediaItem['type'] ?? '');
                if (!in_array($mediaType, $allowedMediaTypes, true)) {
                    $mediaType = $allowedExtensions[$extension] ?? 'pdf';
                    $hasChanged = true;
                }

                $media[] = [
                    'id' => (string) ($mediaItem['id'] ?? $this->createId()),
                    'name' => trim((string) ($mediaItem['name'] ?? basename($path))),
                    'type' => $mediaType,
                    'path' => $path,
                    'mime_type' => (string) ($mediaItem['mime_type'] ?? ''),
                    'size' => max(0, (int) ($mediaItem['size'] ?? 0)),
                    'uploaded_at' => (string) ($mediaItem['uploaded_at'] ?? date('c')),
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
                'external_links' => is_array($item['external_links'] ?? null) ? $item['external_links'] : [],
                'excerpt' => $this->makeExcerpt($contents[0] ?? 'Contenu multimedia'),
                'published_at' => (string) ($item['published_at'] ?? date('c')),
                'updated_at' => (string) ($item['updated_at'] ?? date('c')),
            ];
        }

        usort($normalized, static function (array $a, array $b): int {
            $dateCompare = strcmp($b['publish_date'], $a['publish_date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp((string) $b['published_at'], (string) $a['published_at']);
        });

        if ($hasChanged && $allowWrite) {
            $this->savePosts($normalized);
        }

        return $normalized;
    }

    /**
     * @param list<array<string, mixed>> $posts
     */
    public function savePosts(array $posts): void
    {
        $storagePath = $this->getStoragePath();

        $filesystem = new Filesystem();
        $filesystem->mkdir(dirname($storagePath));

        file_put_contents(
            $storagePath,
            (string) json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPostById(string $id): ?array
    {
        foreach ($this->readPosts() as $post) {
            if ($post['id'] === $id) {
                return $post;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $posts
     * @return list<string>
     */
    public function collectCategories(array $posts): array
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

    public function createId(): string
    {
        return str_replace('.', '', uniqid('blog_', true));
    }

    public function slugify(string $value): string
    {
        $slug = strtolower($value);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? 'article';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'article';
    }

    public function normalizeCategory(string $category): string
    {
        $normalized = trim($category);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';

        if ($normalized === '') {
            return '';
        }

        return mb_substr($normalized, 0, 40);
    }

    public function makeExcerpt(string $text): string
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

    private function getStoragePath(): string
    {
        return $this->projectDir . '/' . self::STORAGE_RELATIVE_PATH;
    }
}
