import {Component, inject, signal} from '@angular/core';
import {NonNullableFormBuilder, ReactiveFormsModule, Validators} from '@angular/forms';
import {Router} from '@angular/router';
import {AuthService} from "@app/service/auth.service";
import {apiErrorMessage} from "@app/service/api-error";

@Component({
    selector: 'app-admin-login',
    standalone: true,
    imports: [ReactiveFormsModule],
    templateUrl: './login.component.html'
})
export class LoginComponent {
    private readonly auth = inject(AuthService);
    private readonly router = inject(Router);

    protected readonly form = inject(NonNullableFormBuilder).group({
        username: ['', Validators.required],
        password: ['', Validators.required]
    });
    protected readonly busy = signal(false);
    protected readonly error = signal<string | null>(null);

    public constructor() {
        if (this.auth.token()) {
            void this.router.navigate(['/admin']);
        }
    }

    protected submit(): void {
        if (this.form.invalid || this.busy()) {
            this.form.markAllAsTouched();
            return;
        }
        const {username, password} = this.form.getRawValue();
        this.busy.set(true);
        this.error.set(null);
        this.auth.login(username, password).subscribe({
            next: () => void this.router.navigate(['/admin']),
            error: error => {
                this.error.set(apiErrorMessage(error));
                this.busy.set(false);
            }
        });
    }
}
