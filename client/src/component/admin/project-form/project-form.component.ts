import {Component, inject, signal} from '@angular/core';
import {NonNullableFormBuilder, ReactiveFormsModule, Validators} from '@angular/forms';
import {ActivatedRoute, Router, RouterLink} from '@angular/router';
import {forkJoin, of} from 'rxjs';
import {Category} from "@app/model/category";
import {Project} from "@app/model/project";
import {Tag} from "@app/model/tag";
import {AdminService} from "@app/service/admin.service";
import {apiErrorMessage} from "@app/service/api-error";
import {LinksComponent} from '../links/links.component';

/** Creates a project, or edits an existing one along with its links. */
@Component({
    selector: 'app-admin-project-form',
    standalone: true,
    imports: [ReactiveFormsModule, RouterLink, LinksComponent],
    templateUrl: './project-form.component.html'
})
export class ProjectFormComponent {
    private readonly admin = inject(AdminService);
    private readonly router = inject(Router);

    protected readonly id: string | null = inject(ActivatedRoute).snapshot.paramMap.get('id');
    protected readonly form = inject(NonNullableFormBuilder).group({
        name: ['', [Validators.required, Validators.maxLength(255)]],
        description: ['', Validators.required],
        categoryId: ['', Validators.required],
        ordinal: [0, Validators.required],
        tagIds: [[] as string[]]
    });
    protected readonly categories = signal<Category[]>([]);
    protected readonly tags = signal<Tag[]>([]);
    protected readonly project = signal<Project | null>(null);
    protected readonly loaded = signal(false);
    protected readonly busy = signal(false);
    protected readonly error = signal<string | null>(null);
    protected readonly saved = signal(false);

    public constructor() {
        forkJoin([
            this.admin.getCategories(),
            this.admin.getTags(),
            this.id === null ? of(null) : this.admin.getProject(this.id)
        ]).subscribe({
            next: ([categories, tags, project]) => {
                this.categories.set(categories);
                this.tags.set(tags);
                if (project) {
                    this.project.set(project);
                    this.form.setValue({
                        name: project.name,
                        description: project.description,
                        categoryId: project.categoryId,
                        ordinal: project.ordinal,
                        tagIds: project.tags.map(tag => tag.id)
                    });
                }
                this.loaded.set(true);
            },
            error: error => this.error.set(apiErrorMessage(error))
        });
    }

    protected hasTag(tag: Tag): boolean {
        return this.form.controls.tagIds.value.includes(tag.id);
    }

    protected toggleTag(tag: Tag, checked: boolean): void {
        const tagIds = this.form.controls.tagIds.value.filter(id => id !== tag.id);
        this.form.controls.tagIds.setValue(checked ? [...tagIds, tag.id] : tagIds);
        this.form.markAsDirty();
    }

    protected submit(): void {
        if (this.form.invalid || this.busy()) {
            this.form.markAllAsTouched();
            return;
        }
        this.busy.set(true);
        this.error.set(null);
        this.saved.set(false);
        this.admin.saveProject(this.id, this.form.getRawValue()).subscribe({
            next: project => {
                this.busy.set(false);
                if (this.id === null) {
                    // Open the saved project so its links can be added.
                    void this.router.navigate(['/admin/projects', project.id]);
                    return;
                }
                this.project.set(project);
                this.form.markAsPristine();
                this.saved.set(true);
            },
            error: error => {
                this.error.set(apiErrorMessage(error));
                this.busy.set(false);
            }
        });
    }
}
