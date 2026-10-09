import {Component, inject, input, OnInit} from '@angular/core';
import {FormControl, FormGroup, ReactiveFormsModule, Validators} from '@angular/forms';
import {Observable} from 'rxjs';
import {Link} from "@app/model/link";
import {AdminService} from "@app/service/admin.service";
import {InlineEditor, InlineRow} from '../inline-editor';

type LinkForm = {
    title: FormControl<string>;
    url: FormControl<string>;
    icon: FormControl<string>;
};

/** Edits the links of one saved project. */
@Component({
    selector: 'app-admin-links',
    standalone: true,
    imports: [ReactiveFormsModule],
    templateUrl: './links.component.html'
})
export class LinksComponent extends InlineEditor<Link, LinkForm> implements OnInit {
    private readonly admin = inject(AdminService);
    public readonly projectId = input.required<string>();
    public readonly links = input.required<Link[]>();

    public ngOnInit(): void {
        this.setItems(this.links());
    }

    protected createForm(link: Link | null): FormGroup<LinkForm> {
        return this.fb.group({
            title: [link?.title ?? '', [Validators.required, Validators.maxLength(255)]],
            url: [link?.url ?? '', [Validators.required, Validators.maxLength(2048)]],
            icon: [link?.icon ?? 'pi pi-external-link', Validators.maxLength(64)]
        });
    }

    protected saveRequest(id: string | null, value: {title: string, url: string, icon: string}): Observable<Link> {
        return this.admin.saveLink(id, this.projectId(), value);
    }

    protected deleteRequest(id: string): Observable<void> {
        return this.admin.deleteLink(id);
    }

    protected describe(row: InlineRow<LinkForm>): string {
        return `the link "${row.form.controls.title.value}"`;
    }
}
