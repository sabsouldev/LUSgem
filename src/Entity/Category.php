<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $color = null;

    /**
     * @var Collection<int, Article>
     */
    #[ORM\ManyToMany(targetEntity: Article::class, inversedBy: 'categories')]
    private Collection $article;

    /**
     * @var Collection<int, Presse>
     */
    #[ORM\ManyToMany(targetEntity: Presse::class, inversedBy: 'categories')]
    private Collection $presse;

    /**
     * @var Collection<int, Podcast>
     */
    #[ORM\ManyToMany(targetEntity: Podcast::class, inversedBy: 'categories')]
    private Collection $podcast;

    public function __construct()
    {
        $this->article = new ArrayCollection();
        $this->presse = new ArrayCollection();
        $this->podcast = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticle(): Collection
    {
        return $this->article;
    }

    public function addArticle(Article $article): static
    {
        if (!$this->article->contains($article)) {
            $this->article->add($article);
        }

        return $this;
    }

    public function removeArticle(Article $article): static
    {
        $this->article->removeElement($article);

        return $this;
    }

    /**
     * @return Collection<int, Presse>
     */
    public function getPresse(): Collection
    {
        return $this->presse;
    }

    public function addPresse(Presse $presse): static
    {
        if (!$this->presse->contains($presse)) {
            $this->presse->add($presse);
        }

        return $this;
    }

    public function removePresse(Presse $presse): static
    {
        $this->presse->removeElement($presse);

        return $this;
    }

    /**
     * @return Collection<int, Podcast>
     */
    public function getPodcast(): Collection
    {
        return $this->podcast;
    }

    public function addPodcast(Podcast $podcast): static
    {
        if (!$this->podcast->contains($podcast)) {
            $this->podcast->add($podcast);
        }

        return $this;
    }

    public function removePodcast(Podcast $podcast): static
    {
        $this->podcast->removeElement($podcast);

        return $this;
    }
}
