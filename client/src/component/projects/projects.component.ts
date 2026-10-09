import {Component, computed, inject} from '@angular/core';
import {toSignal} from "@angular/core/rxjs-interop";
import {catchError, of} from "rxjs";
import {ProjectComponent} from "./project/project.component";
import {Project, ProjectCategory} from "@app/model/project";
import {ProjectService} from "@app/service/project.service";

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
    private readonly projects = toSignal(
        inject(ProjectService).getProjects().pipe(catchError(error => {
            console.error('Could not load projects', error);
            return of([]);
        })),
        {initialValue: []}
    );

    protected readonly professionalProjects = this.inCategory('professional');
    protected readonly sideProjects = this.inCategory('side');
    protected readonly otherProjects = this.inCategory('other');

    private inCategory(category: ProjectCategory) {
        return computed<readonly Project[]>(() => this.projects().filter(project => project.category === category));
    }
}
