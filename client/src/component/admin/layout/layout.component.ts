import {Component, inject} from '@angular/core';
import {Router, RouterLink, RouterLinkActive, RouterOutlet} from '@angular/router';
import {AuthService} from "@app/service/auth.service";

/** The admin area's navigation around whichever admin page is open. */
@Component({
    selector: 'app-admin-layout',
    standalone: true,
    imports: [RouterLink, RouterLinkActive, RouterOutlet],
    templateUrl: './layout.component.html'
})
export class LayoutComponent {
    private readonly router = inject(Router);
    protected readonly auth = inject(AuthService);

    protected logout(): void {
        this.auth.logout();
        void this.router.navigate(['/admin/login']);
    }
}
