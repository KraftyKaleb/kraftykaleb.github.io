import {Component, computed, inject, signal} from '@angular/core';
import {RouterLink} from '@angular/router';
import {forkJoin} from 'rxjs';
import {Category} from "@app/model/category";
import {Project} from "@app/model/project";
import {AdminService} from "@app/service/admin.service";
import {apiErrorMessage} from "@app/service/api-error";

@Component({
    selector: 'app-admin-projects',
    standalone: true,
    imports: [RouterLink],
    templateUrl: './projects.component.html'
})
export class ProjectsComponent {
    private readonly admin = inject(AdminService);
    private readonly categories = signal<Category[]>([]);
    protected readonly projects = signal<Project[] | null>(null);
    protected readonly error = signal<string | null>(null);
    protected readonly categoryNames = computed(() => new Map(this.categories().map(category => [category.id, category.name])));

    public constructor() {
        forkJoin([this.admin.getCategories(), this.admin.getProjects()]).subscribe({
            next: ([categories, projects]) => {
                this.categories.set(categories);
                this.projects.set(projects);
            },
            error: error => this.error.set(apiErrorMessage(error))
        });
    }

    protected delete(project: Project): void {
        if (!confirm(`Delete "${project.name}" and its links?`)) {
            return;
        }
        this.error.set(null);
        this.admin.deleteProject(project.id).subscribe({
            next: () => this.projects.update(projects => projects?.filter(other => other.id !== project.id) ?? null),
            error: error => this.error.set(apiErrorMessage(error))
        });
    }
}
