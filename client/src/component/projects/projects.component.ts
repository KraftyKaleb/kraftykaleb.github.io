import {Component, computed, inject} from '@angular/core';
import {toSignal} from "@angular/core/rxjs-interop";
import {catchError, Observable, of} from "rxjs";
import {ProjectComponent} from "./project/project.component";
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
    private readonly projectService = inject(ProjectService);
    private readonly categories = toSignal(this.orEmpty(this.projectService.getCategories()), {initialValue: []});
    private readonly projects = toSignal(this.orEmpty(this.projectService.getProjects()), {initialValue: []});

    /** Each category with its projects, in display order. */
    protected readonly sections = computed(() => this.categories().map(category => ({
        category,
        projects: this.projects().filter(project => project.categoryId === category.id)
    })));

    private orEmpty<T>(items: Observable<T[]>): Observable<T[]> {
        return items.pipe(catchError(error => {
            console.error('Could not load projects', error);
            return of([]);
        }));
    }
}
