<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/blog')]
final class AdminBlogController extends AbstractController
{
    // Stockage mixte: contenu des articles en JSON, medias en fichiers uploades.
    private const STORAGE_RELATIVE_PATH = 'var/data/blog-posts.json';
    private const UPLOAD_RELATIVE_PATH = 'public/uploads/blog-media';
    private const UPLOAD_PUBLIC_PREFIX = '/uploads/blog-media/';
    private const MAX_MEDIA_FILE_SIZE = 50 * 1024 * 1024;

    private const ALLOWED_EXTENSIONS = [
        'pdf' => 'pdf',
        'jpg' => 'image',
        'jpeg' => 'image',
        'png' => 'image',
        'webp' => 'image',
        'gif' => 'image',
        'svg' => 'image',
        'mp4' => 'video',
        'webm' => 'video',
        'mov' => 'video',
        'ogv' => 'video',
        'mp3' => 'audio',
        'wav' => 'audio',
        'ogg' => 'audio',
        'm4a' => 'audio',
        'aac' => 'audio',
    ];

    #[Route('', name: 'app_admin_blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $posts = $this->readPosts();

        // Filtres optionnels pour retrouver rapidement un article en admin.
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

        return $this->render('admin/blog/index.html.twig', [
            'posts' => $filteredPosts,
            'selected_category' => $selectedCategory,
            'selected_date' => $selectedDate,
            'has_filters' => $selectedCategory !== '' || $selectedDate !== '',
            'available_categories' => $this->collectCategories($posts),
        ]);
    }

    #[Route('/nouveau', name: 'app_admin_blog_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $post = [
            'id' => '',
            'title' => '',
            'publish_date' => date('Y-m-d'),
            'categories' => [],
            'contents' => [''],
            'media' => [],
        ];

        return $this->handleForm($request, $post, true);
    }

    #[Route('/{id}', name: 'app_admin_blog_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        $post = $this->findPostById($id);

        if ($post === null) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        return $this->render('admin/blog/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_blog_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $id): Response
    {
        $post = $this->findPostById($id);

        if ($post === null) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        return $this->handleForm($request, $post, false);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_blog_delete', methods: ['POST'])]
    public function delete(Request $request, string $id): Response
    {
        if (!$this->isCsrfTokenValid('admin_blog_delete_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_admin_blog_index');
        }

        $posts = $this->readPosts();
        $beforeCount = count($posts);
        $deletedPost = null;

        $posts = array_values(array_filter($posts, function (array $post) use ($id, &$deletedPost): bool {
            if ($post['id'] === $id) {
                $deletedPost = $post;

                return false;
            }

            return true;
        }));

        if (count($posts) === $beforeCount || $deletedPost === null) {
            $this->addFlash('warning', 'Article deja supprime ou introuvable.');

            return $this->redirectToRoute('app_admin_blog_index');
        }

        // Suppression physique des medias pour eviter les fichiers orphelins.
        foreach ($deletedPost['media'] as $mediaItem) {
            $this->deleteMediaFile($mediaItem);
        }

        $this->savePosts($posts);
        $this->addFlash('success', 'Article supprime.');

        return $this->redirectToRoute('app_admin_blog_index');
    }

    private function handleForm(Request $request, array $post, bool $isNew): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
          
            // Token distinct creation/edition pour isoler les intentions de formulaire.
            $tokenId = 'admin_blog_save_' . ($isNew ? 'new' : $post['id']);
            if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
                $errors[] = 'Jeton CSRF invalide.';
            }

            $input = $this->extractFormInput($request);
            $errors = array_merge($errors, $this->validateInput($input));

            $remainingMedia = $post['media'];
            $removeIds = [];
            if (!$isNew) {
                // Edition: on garde les medias non coches et on retire les coches.
                $removeMedia = $request->request->all('remove_media');
                if (is_array($removeMedia) && $removeMedia !== []) {
                    $removeIds = array_values(array_filter(array_map('strval', $removeMedia)));
                    $remainingMedia = [];

                    foreach ($post['media'] as $mediaItem) {
                        if (in_array($mediaItem['id'], $removeIds, true)) {
                            continue;
                        }

                        $remainingMedia[] = $mediaItem;
                    }
                }
            }

            $uploadedMediaResult = $this->handleMediaUploads($request);
            $errors = array_merge($errors, $uploadedMediaResult['errors']);
            $media = array_merge($remainingMedia, $uploadedMediaResult['media']);

            if ($input['contents'] === [] && $media === []) {
                $errors[] = 'Ajouter au moins un bloc de contenu ou un fichier media.';
            }

            if ($errors === []) {
                $posts = $this->readPosts();

                if ($isNew) {
                    $newPost = [
                        'id' => $this->createId(),
                        'title' => $input['title'],
                        'slug' => $this->slugify($input['title']),
                        'publish_date' => $input['publish_date'],
                        'categories' => $input['categories'],
                        'contents' => $input['contents'],
                        'media' => $media,
                        'excerpt' => $this->makeExcerpt($input['contents'][0] ?? 'Contenu multimedia'),
                        'external_links' => $input['external_links'],
                        'published_at' => date('c'),
                        'updated_at' => date('c'),
                    ];

                    array_unshift($posts, $newPost);
                    $this->savePosts($posts);
                    $this->addFlash('success', 'Article cree.');

                    return $this->redirectToRoute('app_admin_blog_index');
                }

                foreach ($posts as $index => $currentPost) {
                    if ($currentPost['id'] !== $post['id']) {
                        continue;
                    }

                    $posts[$index]['title'] = $input['title'];
                    $posts[$index]['slug'] = $this->slugify($input['title']);
                    $posts[$index]['publish_date'] = $input['publish_date'];
                    $posts[$index]['categories'] = $input['categories'];
                    $posts[$index]['contents'] = $input['contents'];
                    $posts[$index]['media'] = $media;
                    $posts[$index]['excerpt'] = $this->makeExcerpt($input['contents'][0] ?? 'Contenu multimedia');
                    $posts[$index]['external_links'] = $input['external_links'];
                    $posts[$index]['updated_at'] = date('c');
                    break;
                }

                $this->savePosts($posts);

                if ($removeIds !== []) {
                    foreach ($post['media'] as $mediaItem) {
                        if (in_array($mediaItem['id'], $removeIds, true)) {
                            $this->deleteMediaFile($mediaItem);
                        }
                    }
                }

                $this->addFlash('success', 'Article mis a jour.');

                return $this->redirectToRoute('app_admin_blog_show', ['id' => $post['id']]);
            }

            // En cas d'erreur formulaire, nettoyage des nouveaux fichiers deja uploades.
            foreach ($uploadedMediaResult['media'] as $uploadedMediaItem) {
                $this->deleteMediaFile($uploadedMediaItem);
            }

            $post['title'] = $input['title'];
            $post['publish_date'] = $input['publish_date'];
            $post['categories'] = $input['categories'];
            $post['contents'] = $input['contents'];
            $post['media'] = $remainingMedia;
        }

        return $this->render('admin/blog/form.html.twig', [
            'post' => $post,
            'is_new' => $isNew,
            'errors' => $errors,
            'available_categories' => $this->collectCategories($this->readPosts()),
            'token_id' => 'admin_blog_save_' . ($isNew ? 'new' : $post['id']),
        ]);
    }

   private function extractFormInput(Request $request): array
    {
        $title = trim((string) $request->request->get('title', ''));
        $publishDate = trim((string) $request->request->get('publish_date', date('Y-m-d')));

        $selectedCategories = $request->request->all('categories');
        if (!is_array($selectedCategories)) {
            $selectedCategories = [];
        }

        $customCategories = preg_split('/,/', (string) $request->request->get('categories_custom', '')) ?: [];

        $categories = [];
        foreach (array_merge($selectedCategories, $customCategories) as $category) {
            $normalized = $this->normalizeCategory((string) $category);
            if ($normalized !== '' && !in_array($normalized, $categories, true)) {
                $categories[] = $normalized;
            }
        }

        $contentBlocksRaw = $request->request->all('contents');
        if (!is_array($contentBlocksRaw)) {
            $contentBlocksRaw = [];
        }

        $contents = [];
        foreach ($contentBlocksRaw as $rawBlock) {
            $value = trim((string) $rawBlock);
            if ($value !== '') {
                $contents[] = $value;
            }
        }

        $linkTitles = $request->request->all('external_link_titles');
        $linkUrls = $request->request->all('external_link_urls');
        if (!is_array($linkTitles)) $linkTitles = [];
        if (!is_array($linkUrls)) $linkUrls = [];

        $externalLinks = [];
        foreach ($linkUrls as $i => $url) {
            $url = trim((string) $url);
            $linkTitle = trim((string) ($linkTitles[$i] ?? ''));
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                $externalLinks[] = [
                    'title' => $linkTitle !== '' ? mb_substr($linkTitle, 0, 200) : $url,
                    'url' => $url,
                    'embed_type' => $this->detectEmbedType($url),
                    'embed_url' => $this->getEmbedUrl($url),
                ];
            }
        }

        return [
            'title' => $title,
            'publish_date' => $publishDate,
            'categories' => $categories,
            'contents' => $contents,
            'external_links' => $externalLinks,
        ];
    }

    private function validateInput(array $input): array
    {
        $errors = [];

        if ($input['title'] === '') {
            $errors[] = 'Le titre est obligatoire.';
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['publish_date'])) {
            $errors[] = 'La date doit etre au format YYYY-MM-DD.';
        }

        if ($input['categories'] === []) {
            $errors[] = 'Selectionner au moins une categorie.';
        }

        return $errors;
    }

    private function handleMediaUploads(Request $request): array
    {
        $media = [];
        $errors = [];

        $uploadedItems = $request->files->all('media_files');
        if (!is_array($uploadedItems)) {
            $uploadedItems = [];
        }

        $filesystem = new Filesystem();
        $filesystem->mkdir($this->getUploadDirectory());

        foreach ($uploadedItems as $uploadedItem) {
            if (!$uploadedItem instanceof UploadedFile || !$uploadedItem->isValid()) {
                continue;
            }

            $extension = strtolower((string) $uploadedItem->getClientOriginalExtension());
            // Filtrage strict par extension autorisee.
            if ($extension === '' || !array_key_exists($extension, self::ALLOWED_EXTENSIONS)) {
                $errors[] = sprintf(
                    'Type de fichier non autorise: %s',
                    $uploadedItem->getClientOriginalName()
                );
                continue;
            }

            // Limite de taille pour proteger le serveur et garder des temps de chargement stables.
            if ($uploadedItem->getSize() > self::MAX_MEDIA_FILE_SIZE) {
                $errors[] = sprintf(
                    'Fichier trop volumineux (max 50 Mo): %s',
                    $uploadedItem->getClientOriginalName()
                );
                continue;
            }

            $originalName = pathinfo((string) $uploadedItem->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = $this->slugify($originalName);
            $filename = date('YmdHis') . '-' . str_replace('.', '', uniqid('', true)) . '-' . $safeName . '.' . $extension;

            $mimeType = (string) $uploadedItem->getMimeType();
$fileSize = (int) $uploadedItem->getSize();

$uploadedItem->move($this->getUploadDirectory(), $filename);

$media[] = [
    'id' => $this->createId(),
    'name' => $originalName !== '' ? $originalName : $filename,
    'type' => self::ALLOWED_EXTENSIONS[$extension],
    'path' => self::UPLOAD_PUBLIC_PREFIX . $filename,
    'mime_type' => $mimeType,
    'size' => $fileSize,
    'uploaded_at' => date('c'),
];
        }

        return [
            'media' => $media,
            'errors' => $errors,
        ];
    }

    private function deleteMediaFile(array $mediaItem): void
    {
        $path = (string) ($mediaItem['path'] ?? '');
        // Garde-fou: on ne supprime que dans le dossier media du projet.
        if (!str_starts_with($path, self::UPLOAD_PUBLIC_PREFIX)) {
            return;
        }

        $filename = basename($path);
        if ($filename === '') {
            return;
        }

        $absolutePath = $this->getUploadDirectory() . '/' . $filename;
        if (file_exists($absolutePath)) {
            @unlink($absolutePath);
        }
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

        // Normalisation defensive pour rester compatible avec d'anciens formats JSON.
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
                if (!in_array($mediaType, ['pdf', 'image', 'video', 'audio'], true)) {
                    $mediaType = self::ALLOWED_EXTENSIONS[$extension] ?? 'pdf';
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

        if ($hasChanged) {
            $this->savePosts($normalized);
        }

        return $normalized;
    }

    private function savePosts(array $posts): void
    {
        $storagePath = $this->getStoragePath();

        // JSON unique pour faciliter sauvegarde/restauration des contenus blog.
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

    private function getUploadDirectory(): string
    {
        $projectDir = (string) $this->getParameter('kernel.project_dir');

        return $projectDir . '/' . self::UPLOAD_RELATIVE_PATH;
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
       private function detectEmbedType(string $url): string
    {
        if (preg_match('/youtube\.com\/watch|youtu\.be\/|youtube\.com\/embed/', $url)) {
            return 'youtube';
        }
        if (str_contains($url, 'spotify.com')) {
            return 'spotify';
        }
        if (str_contains($url, 'soundcloud.com')) {
            return 'soundcloud';
        }
        if (str_contains($url, 'deezer.com')) {
            return 'deezer';
        }
        if (str_contains($url, 'podcasts.apple.com') || str_contains($url, 'podcasts.google.com')) {
            return 'podcast';
        }

        return 'link';
    }

     private function getEmbedUrl(string $url): string
    {
        // YouTube: youtu.be/ID ou youtube.com/watch?v=ID
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }
        if (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        // Spotify
        if (str_contains($url, 'open.spotify.com')) {
            return str_replace('open.spotify.com/', 'open.spotify.com/embed/', $url);
        }

        return $url;
    }
}
