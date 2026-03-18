import {Component, signal, Signal} from '@angular/core';
import {ProjectComponent} from "./project/project.component";
import {Project} from "@app/model/project";
import {Link} from "@app/model/link";

@Component({
    selector: 'app-projects',
    standalone: true,
    templateUrl: './projects.component.html',
    imports: [
        ProjectComponent
    ],
    styleUrl: './projects.component.css'
})
export class ProjectsComponent {
    private readonly commonStack: string[] = [
        'TypeScript',
        'CSS',
        'PHP',
        'Angular',
        'Symfony',
        'MariaDB'
    ]

    // TODO: Move these definitions to a blog-like backend.
    protected professionalProjects: Signal<readonly Project[]> = signal<readonly Project[]>([
        new Project(
            'UND Platform',
            'A monorepo-type project that consolidated several small apps, primarily forms utilizing abstraction to reduce redundancy and improve maintainability.',
            [
                'UND',
                ...this.commonStack
            ],
            [new Link('Demo', 'https://apps.und.edu/uit/platform/client/public/', 'pi pi-external-link')]
        ),
        new Project(
            'UND Work Well',
            'A set of challenges "events" for employees to complete to stay healthy in the workplace.',
            [
                'UND',
                ...this.commonStack
            ],
            [new Link('Demo', 'https://uitapps.und.edu/wel/work_well/client/public/', 'pi pi-external-link')]
        ),
        new Project(
            'UND Front-End Commons',
            'A library for common front',
            [
                'UND',
                ...this.commonStack
            ],
            null
        ),
        new Project(
            'UNDerground',
            'A library for common front',
            [
                'UND',
                ...this.commonStack
            ],
            null
        ),
    ]);
    protected readonly sideProjects = signal<readonly Project[]>([]);
    protected readonly otherProjects = signal<readonly Project[]>([]);
}
