import {inject, signal, WritableSignal} from '@angular/core';
import {AbstractControl, FormGroup, NonNullableFormBuilder} from '@angular/forms';
import {Observable} from 'rxjs';
import {apiErrorMessage} from "@app/service/api-error";

/** One saved item being edited in place, with its own save state. */
export interface InlineRow<F extends Record<string, AbstractControl>> {
    readonly id: string;
    readonly form: FormGroup<F>;
    readonly busy: WritableSignal<boolean>;
    readonly saved: WritableSignal<boolean>;
}

/**
 * A list of items that are each edited in their own row, plus a form for adding a new one.
 * Subclasses say how a form is built and which API calls save and delete an item.
 */
export abstract class InlineEditor<M extends {id: string}, F extends Record<string, AbstractControl>> {
    protected readonly fb = inject(NonNullableFormBuilder);
    public readonly rows = signal<InlineRow<F>[]>([]);
    public readonly draft: FormGroup<F> = this.createForm(null);
    public readonly adding = signal(false);
    public readonly error = signal<string | null>(null);

    protected abstract createForm(item: M | null): FormGroup<F>;

    protected abstract saveRequest(id: string | null, value: ReturnType<FormGroup<F>['getRawValue']>): Observable<M>;

    protected abstract deleteRequest(id: string): Observable<void>;

    /** How the delete confirmation names an item. */
    protected abstract describe(row: InlineRow<F>): string;

    protected setItems(items: M[]): void {
        this.rows.set(items.map(item => this.row(item)));
    }

    public save(row: InlineRow<F>): void {
        if (row.form.invalid || row.busy()) {
            row.form.markAllAsTouched();
            return;
        }
        row.busy.set(true);
        row.saved.set(false);
        this.error.set(null);
        this.saveRequest(row.id, row.form.getRawValue()).subscribe({
            next: () => {
                row.busy.set(false);
                row.form.markAsPristine();
                row.saved.set(true);
            },
            error: error => {
                row.busy.set(false);
                this.error.set(apiErrorMessage(error));
            }
        });
    }

    public add(): void {
        if (this.draft.invalid || this.adding()) {
            this.draft.markAllAsTouched();
            return;
        }
        this.adding.set(true);
        this.error.set(null);
        this.saveRequest(null, this.draft.getRawValue()).subscribe({
            next: item => {
                this.adding.set(false);
                this.rows.update(rows => [...rows, this.row(item)]);
                this.draft.reset();
            },
            error: error => {
                this.adding.set(false);
                this.error.set(apiErrorMessage(error));
            }
        });
    }

    public delete(row: InlineRow<F>): void {
        if (!confirm(`Delete ${this.describe(row)}?`)) {
            return;
        }
        row.busy.set(true);
        this.error.set(null);
        this.deleteRequest(row.id).subscribe({
            next: () => this.rows.update(rows => rows.filter(other => other !== row)),
            error: error => {
                row.busy.set(false);
                this.error.set(apiErrorMessage(error));
            }
        });
    }

    private row(item: M): InlineRow<F> {
        const form = this.createForm(item);
        const saved = signal(false);
        // Clear "Saved" as soon as the row is edited again.
        form.valueChanges.subscribe(() => saved.set(false));
        return {id: item.id, form, busy: signal(false), saved};
    }
}
