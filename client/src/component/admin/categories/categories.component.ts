import {Component, inject, signal} from '@angular/core';
import {FormControl, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {forkJoin, Observable} from 'rxjs';
import {Category} from "@app/model/category";
import {AdminService, CategoryInput} from "@app/service/admin.service";
import {apiErrorMessage} from "@app/service/api-error";
import {InlineEditor, InlineRow} from '../inline-editor';

type CategoryForm = {
    name: FormControl<string>;
    description: FormControl<string>;
    ordinal: FormControl<number>;
};

@Component({
    selector: 'app-admin-categories',
    standalone: true,
    imports: [ReactiveFormsModule],
    templateUrl: './categories.component.html'
})
export class CategoriesComponent extends InlineEditor<Category, CategoryForm> {
    private readonly admin = inject(AdminService);
    /** How many projects each category has. A category can only be deleted once it has none. */
    protected readonly projectCounts = signal(new Map<string, number>());

    public constructor() {
        super();
        forkJoin([this.admin.getCategories(), this.admin.getProjects()]).subscribe({
            next: ([categories, projects]) => {
                const counts = new Map<string, number>();
                projects.forEach(project => counts.set(project.categoryId, (counts.get(project.categoryId) ?? 0) + 1));
                this.projectCounts.set(counts);
                this.setItems(categories);
            },
            error: error => this.error.set(apiErrorMessage(error))
        });
    }

    protected createForm(category: Category | null): FormGroup<CategoryForm> {
        return this.fb.group({
            name: [category?.name ?? '', [Validators.required, Validators.maxLength(255)]],
            description: [category?.description ?? ''],
            ordinal: [category?.ordinal ?? 0, Validators.required]
        });
    }

    protected saveRequest(id: string | null, value: CategoryInput): Observable<Category> {
        return this.admin.saveCategory(id, value);
    }

    protected deleteRequest(id: string): Observable<void> {
        return this.admin.deleteCategory(id);
    }

    protected describe(row: InlineRow<CategoryForm>): string {
        return `the category "${row.form.controls.name.value}"`;
    }
}
