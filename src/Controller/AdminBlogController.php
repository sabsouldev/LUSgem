<?php

namespace App\Controller;

use App\Service\BlogPostStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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

    public function __construct(
        private readonly BlogPostStorage $blogStorage,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('', name: 'app_admin_blog_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $posts = $this->blogStorage->readPosts(allowWrite: true);

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
            'available_categories' => $this->blogStorage->collectCategories($posts),
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
        $post = $this->blogStorage->findPostById($id);

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
        $post = $this->blogStorage->findPostById($id);

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

        $posts = $this->blogStorage->readPosts(allowWrite: true);
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

        foreach ($deletedPost['media'] as $mediaItem) {
            $this->deleteMediaFile($mediaItem);
        }

        $this->blogStorage->savePosts($posts);
        $this->addFlash('success', 'Article supprime.');

        return $this->redirectToRoute('app_admin_blog_index');
    }

    private function handleForm(Request $request, array $post, bool $isNew): Response
    {
        $errors = [];

        if ($request->isMethod('POST')) {
            $tokenId = 'admin_blog_save_' . ($isNew ? 'new' : $post['id']);
            if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
                $errors[] = 'Jeton CSRF invalide.';
            }

            $input = $this->extractFormInput($request);
            $errors = array_merge($errors, $this->validateInput($input));

            $remainingMedia = $post['media'];
            $removeIds = [];
            if (!$isNew) {
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
                $posts = $this->blogStorage->readPosts(allowWrite: true);

                if ($isNew) {
                    $newPost = [
                        'id' => $this->blogStorage->createId(),
                        'title' => $input['title'],
                        'slug' => $this->blogStorage->slugify($input['title']),
                        'publish_date' => $input['publish_date'],
                        'categories' => $input['categories'],
                        'contents' => $input['contents'],
                        'media' => $media,
                        'excerpt' => $this->blogStorage->makeExcerpt($input['contents'][0] ?? 'Contenu multimedia'),
                        'external_links' => $input['external_links'],
                        'published_at' => date('c'),
                        'updated_at' => date('c'),
                    ];

                    array_unshift($posts, $newPost);
                    $this->blogStorage->savePosts($posts);
                    $this->addFlash('success', 'Article cree.');

                    return $this->redirectToRoute('app_admin_blog_index');
                }

                foreach ($posts as $index => $currentPost) {
                    if ($currentPost['id'] !== $post['id']) {
                        continue;
                    }

                    $posts[$index]['title'] = $input['title'];
                    $posts[$index]['slug'] = $this->blogStorage->slugify($input['title']);
                    $posts[$index]['publish_date'] = $input['publish_date'];
                    $posts[$index]['categories'] = $input['categories'];
                    $posts[$index]['contents'] = $input['contents'];
                    $posts[$index]['media'] = $media;
                    $posts[$index]['excerpt'] = $this->blogStorage->makeExcerpt($input['contents'][0] ?? 'Contenu multimedia');
                    $posts[$index]['external_links'] = $input['external_links'];
                    $posts[$index]['updated_at'] = date('c');
                    break;
                }

                $this->blogStorage->savePosts($posts);

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
            'available_categories' => $this->blogStorage->collectCategories($this->blogStorage->readPosts(allowWrite: true)),
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
            $normalized = $this->blogStorage->normalizeCategory((string) $category);
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
        if (!is_array($linkTitles)) {
            $linkTitles = [];
        }
        if (!is_array($linkUrls)) {
            $linkUrls = [];
        }

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
            if ($extension === '' || !array_key_exists($extension, self::ALLOWED_EXTENSIONS)) {
                $errors[] = sprintf(
                    'Type de fichier non autorise: %s',
                    $uploadedItem->getClientOriginalName()
                );
                continue;
            }

            if ($uploadedItem->getSize() > self::MAX_MEDIA_FILE_SIZE) {
                $errors[] = sprintf(
                    'Fichier trop volumineux (max 50 Mo): %s',
                    $uploadedItem->getClientOriginalName()
                );
                continue;
            }

            $originalName = pathinfo((string) $uploadedItem->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = $this->blogStorage->slugify($originalName);
            $filename = date('YmdHis') . '-' . str_replace('.', '', uniqid('', true)) . '-' . $safeName . '.' . $extension;

            $mimeType = (string) $uploadedItem->getMimeType();
            $fileSize = (int) $uploadedItem->getSize();

            $uploadedItem->move($this->getUploadDirectory(), $filename);

            $media[] = [
                'id' => $this->blogStorage->createId(),
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

    private function getUploadDirectory(): string
    {
        return $this->projectDir . '/' . self::UPLOAD_RELATIVE_PATH;
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
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }
        if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }
        if (preg_match('/youtube\.com\/shorts\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        if (str_contains($url, 'open.spotify.com')) {
            return str_replace('open.spotify.com/', 'open.spotify.com/embed/', $url);
        }

        return $url;
    }
}
