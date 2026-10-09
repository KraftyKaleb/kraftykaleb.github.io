<?php
declare(strict_types=1);

namespace App\Command;

use App\Model\Entity\Category;
use App\Model\Entity\Link;
use App\Model\Entity\Project;
use App\Model\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Loads the projects and categories the client used to hardcode, so the site keeps its content
 * once it reads from the API. Does nothing when any project already exists.
 */
#[AsCommand(name: 'app:content:seed', description: 'Loads the initial categories, projects, links and tags into an empty database.')]
final readonly class SeedContentCommand {
    private const array COMMON_STACK = ['TypeScript', 'CSS', 'PHP', 'Angular', 'Symfony', 'MariaDB'];

    private const array CATEGORIES = [
        'professional' => [
            'name' => 'Professional Projects',
            'description' => 'My highest quality work, comprised of the most valuable use of my time. Often, these projects are sponsored by full-time employers.',
        ],
        'side' => [
            'name' => 'Side Projects',
            'description' => 'These projects are one-offs, but still require a professional tone.',
        ],
        'other' => [
            'name' => 'Other Projects',
            'description' => 'These are projects that don\'t easily fall into another category. Generally, you can recognize these as times where I got annoyed and thought "Fine, I\'ll do it myself 🫰". These are the lowest quality of my work, and tend to be thrown together for niche situations when I need quick automation.',
        ],
    ];

    private const array PROJECTS = [
        [
            'name' => 'UND Platform',
            'description' => 'A monorepo-type project that consolidated several small apps, primarily forms utilizing abstraction to reduce redundancy and improve maintainability.',
            'category' => 'professional',
            'tags' => ['UND', ...self::COMMON_STACK],
            'links' => [['Demo', 'https://apps.und.edu/uit/platform/client/public/', 'pi pi-external-link']],
        ],
        [
            'name' => 'UND Work Well',
            'description' => 'A set of challenges "events" for employees to complete to stay healthy in the workplace.',
            'category' => 'professional',
            'tags' => ['UND', ...self::COMMON_STACK],
            'links' => [['Demo', 'https://uitapps.und.edu/wel/work_well/client/public/', 'pi pi-external-link']],
        ],
        [
            'name' => 'UND Front-End Commons',
            'description' => 'A library for common front',
            'category' => 'professional',
            'tags' => ['UND', ...self::COMMON_STACK],
            'links' => [],
        ],
        [
            'name' => 'UNDerground',
            'description' => 'A library for common front',
            'category' => 'professional',
            'tags' => ['UND', ...self::COMMON_STACK],
            'links' => [],
        ],
    ];

    public function __construct(private EntityManagerInterface $entityManager) {
    }

    public function __invoke(SymfonyStyle $io): int {
        if ($this->entityManager->getRepository(Project::class)->count() > 0) {
            $io->note('Projects already exist, nothing was loaded.');

            return Command::SUCCESS;
        }

        /** @var array<string, Tag> $tags */
        $tags = [];
        foreach ($this->entityManager->getRepository(Tag::class)->findAll() as $tag) {
            $tags[$tag->name] = $tag;
        }

        /** @var array<string, Category> $categories */
        $categories = [];
        foreach (self::CATEGORIES as $key => $data) {
            $category = $this->entityManager->getRepository(Category::class)->findOneBy(['name' => $data['name']]);
            if ($category === null) {
                $category = new Category();
                $category->name = $data['name'];
                $category->description = $data['description'];
                $category->ordinal = count($categories);
                $this->entityManager->persist($category);
            }
            $categories[$key] = $category;
        }

        foreach (self::PROJECTS as $ordinal => $data) {
            $project = new Project();
            $project->name = $data['name'];
            $project->description = $data['description'];
            $project->category = $categories[$data['category']];
            $project->ordinal = $ordinal;

            foreach ($data['tags'] as $name) {
                if (!isset($tags[$name])) {
                    $tag = new Tag();
                    $tag->name = $name;
                    $tag->ordinal = count($tags);
                    $this->entityManager->persist($tag);
                    $tags[$name] = $tag;
                }
                $project->tags->add($tags[$name]);
            }

            foreach ($data['links'] as [$title, $url, $icon]) {
                $link = new Link();
                $link->title = $title;
                $link->url = $url;
                $link->icon = $icon;
                $link->project = $project;
                $project->links->add($link);
                $this->entityManager->persist($link);
            }

            $this->entityManager->persist($project);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Loaded %d projects.', count(self::PROJECTS)));

        return Command::SUCCESS;
    }
}
