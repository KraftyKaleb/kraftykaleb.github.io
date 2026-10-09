import {Component, inject} from '@angular/core';
import {FormControl, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {Observable} from 'rxjs';
import {Tag} from "@app/model/tag";
import {AdminService, TagInput} from "@app/service/admin.service";
import {apiErrorMessage} from "@app/service/api-error";
import {InlineEditor, InlineRow} from '../inline-editor';

type TagForm = {
    name: FormControl<string>;
    ordinal: FormControl<number>;
};

@Component({
    selector: 'app-admin-tags',
    standalone: true,
    imports: [ReactiveFormsModule],
    templateUrl: './tags.component.html'
})
export class TagsComponent extends InlineEditor<Tag, TagForm> {
    private readonly admin = inject(AdminService);

    public constructor() {
        super();
        this.admin.getTags().subscribe({
            next: tags => this.setItems(tags),
            error: error => this.error.set(apiErrorMessage(error))
        });
    }

    protected createForm(tag: Tag | null): FormGroup<TagForm> {
        return this.fb.group({
            name: [tag?.name ?? '', [Validators.required, Validators.maxLength(64)]],
            ordinal: [tag?.ordinal ?? 0, Validators.required]
        });
    }

    protected saveRequest(id: string | null, value: TagInput): Observable<Tag> {
        return this.admin.saveTag(id, value);
    }

    protected deleteRequest(id: string): Observable<void> {
        return this.admin.deleteTag(id);
    }

    protected describe(row: InlineRow<TagForm>): string {
        return `the tag "${row.form.controls.name.value}" and remove it from every project that has it`;
    }
}
